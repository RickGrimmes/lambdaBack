<?php

namespace App\Services;

use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;
use App\Models\PushSubscription;
use App\Models\Notification;

class WebPushService
{
    private $webPush;

    public function __construct()
    {
        $auth = [
            'VAPID' => [
                'subject' => config('services.vapid.subject'),
                'publicKey' => config('services.vapid.public_key'),
                'privateKey' => config('services.vapid.private_key'),
            ]
        ];

        $this->webPush = new WebPush($auth);
    }

    /**
     * Enviar notificación a un usuario específico
     */
    public function sendToUser($userId, $title, $body, $data = [])
    {
        try {
            \Log::info("Intentando enviar notificación a usuario: {$userId}");
            
            // Obtener todas las suscripciones del usuario (puede tener múltiples dispositivos)
            $subscriptions = PushSubscription::where('user_id', $userId)->get();

            \Log::info("Suscripciones encontradas: " . $subscriptions->count());

            if ($subscriptions->isEmpty()) {
                \Log::warning("Usuario {$userId} no tiene suscripciones");
                
                // Guardar notificación en BD de todas formas
                Notification::create([
                    'user_id' => $userId,
                    'type' => $data['type'] ?? 'general',
                    'title' => $title,
                    'body' => $body,
                    'data' => json_encode($data),
                    'read' => false
                ]);
                
                return ['success' => false, 'message' => 'Usuario no tiene suscripciones'];
            }

            // MODO DESARROLLO: Solo guardar en BD sin enviar push real
            // (Porque Windows tiene problemas con OpenSSL y VAPID)
            if (config('app.env') === 'local') {
                \Log::info("Modo local: Guardando notificación sin enviar push");
                
                $notification = Notification::create([
                    'user_id' => $userId,
                    'type' => $data['type'] ?? 'general',
                    'title' => $title,
                    'body' => $body,
                    'data' => json_encode($data),
                    'read' => false
                ]);

                \Log::info("Notificación guardada en BD con ID: {$notification->id}");

                return [
                    'success' => true,
                    'sent' => $subscriptions->count(),
                    'failed' => 0,
                    'message' => 'Notificación guardada (modo desarrollo - push real deshabilitado)'
                ];
            }

            // MODO PRODUCCIÓN: Enviar push real
            $payload = json_encode([
                'title' => $title,
                'body' => $body,
                'icon' => '/icon.png',
                'badge' => '/badge.png',
                'data' => $data
            ]);

            $sentCount = 0;
            $failedCount = 0;

            foreach ($subscriptions as $sub) {
                $subscription = Subscription::create([
                    'endpoint' => $sub->endpoint,
                    'publicKey' => $sub->public_key,
                    'authToken' => $sub->auth_token,
                    'contentEncoding' => $sub->content_encoding
                ]);

                $report = $this->webPush->sendOneNotification($subscription, $payload);

                if ($report->isSuccess()) {
                    $sentCount++;
                    \Log::info("Notificación enviada exitosamente a suscripción");
                } else {
                    $failedCount++;
                    \Log::error("Error enviando notificación: " . $report->getReason());
                    // Si la suscripción expiró, eliminarla
                    if ($report->isSubscriptionExpired()) {
                        $sub->delete();
                        \Log::info("Suscripción expirada eliminada");
                    }
                }
            }

            // Guardar notificación en BD para historial
            $notification = Notification::create([
                'user_id' => $userId,
                'type' => $data['type'] ?? 'general',
                'title' => $title,
                'body' => $body,
                'data' => json_encode($data),
                'read' => false
            ]);

            \Log::info("Notificación guardada en BD con ID: {$notification->id}");

            return [
                'success' => true,
                'sent' => $sentCount,
                'failed' => $failedCount
            ];

        } catch (\Exception $e) {
            \Log::error('Error enviando WebPush: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Enviar notificación a múltiples usuarios
     */
    public function sendToMultipleUsers($userIds, $title, $body, $data = [])
    {
        $results = [];
        foreach ($userIds as $userId) {
            $results[$userId] = $this->sendToUser($userId, $title, $body, $data);
        }
        return $results;
    }

    /**
     * Suscribir un dispositivo
     */
    public function subscribe($userId, $endpoint, $publicKey, $authToken, $contentEncoding = 'aes128gcm')
    {
        try {
            // Verificar si ya existe
            $existing = PushSubscription::where('user_id', $userId)
                ->where('endpoint', $endpoint)
                ->first();

            if ($existing) {
                return ['success' => true, 'message' => 'Ya está suscrito'];
            }

            PushSubscription::create([
                'user_id' => $userId,
                'endpoint' => $endpoint,
                'public_key' => $publicKey,
                'auth_token' => $authToken,
                'content_encoding' => $contentEncoding
            ]);

            return ['success' => true, 'message' => 'Suscrito exitosamente'];

        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Desuscribir un dispositivo
     */
    public function unsubscribe($userId, $endpoint)
    {
        try {
            PushSubscription::where('user_id', $userId)
                ->where('endpoint', $endpoint)
                ->delete();

            return ['success' => true, 'message' => 'Desuscrito exitosamente'];

        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
