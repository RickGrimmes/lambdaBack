<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\WebPushService;
use App\Models\Notification;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    private $webPushService;

    public function __construct()
    {
        $this->webPushService = new WebPushService();
    }

    /**
     * Suscribir dispositivo a notificaciones push
     */
    public function subscribe(Request $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'endpoint' => 'required|string',
                'keys.p256dh' => 'required|string',
                'keys.auth' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de suscripción inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $result = $this->webPushService->subscribe(
                $user->USR_ID,
                $request->endpoint,
                $request->input('keys.p256dh'),
                $request->input('keys.auth')
            );

            return response()->json($result, $result['success'] ? 200 : 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al suscribir',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Desuscribir dispositivo
     */
    public function unsubscribe(Request $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'endpoint' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Endpoint requerido',
                    'errors' => $validator->errors()
                ], 422);
            }

            $result = $this->webPushService->unsubscribe($user->USR_ID, $request->endpoint);

            return response()->json($result, $result['success'] ? 200 : 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al desuscribir',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener mis notificaciones
     */
    public function getMyNotifications(Request $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            $notifications = Notification::forUser($user->USR_ID)
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            $unreadCount = Notification::forUser($user->USR_ID)
                ->unread()
                ->count();

            return response()->json([
                'success' => true,
                'data' => $notifications,
                'unread_count' => $unreadCount
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener notificaciones',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener contador de notificaciones no leídas
     */
    public function getUnreadCount(Request $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            $count = Notification::forUser($user->USR_ID)
                ->unread()
                ->count();

            return response()->json([
                'success' => true,
                'count' => $count
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener contador',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Marcar notificación como leída
     */
    public function markAsRead($notificationId)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            $notification = Notification::where('id', $notificationId)
                ->where('user_id', $user->USR_ID)
                ->first();

            if (!$notification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notificación no encontrada'
                ], 404);
            }

            $notification->markAsRead();

            return response()->json([
                'success' => true,
                'message' => 'Notificación marcada como leída'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al marcar notificación',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Marcar todas como leídas
     */
    public function markAllAsRead()
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            Notification::where('user_id', $user->USR_ID)
                ->where('read', false)
                ->update([
                    'read' => true,
                    'read_at' => now()
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Todas las notificaciones marcadas como leídas'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al marcar notificaciones',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar notificación
     */
    public function deleteNotification($notificationId)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            $notification = Notification::where('id', $notificationId)
                ->where('user_id', $user->USR_ID)
                ->first();

            if (!$notification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notificación no encontrada'
                ], 404);
            }

            $notification->delete();

            return response()->json([
                'success' => true,
                'message' => 'Notificación eliminada'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar notificación',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener clave pública VAPID (para el frontend)
     */
    public function getVapidPublicKey()
    {
        return response()->json([
            'success' => true,
            'public_key' => config('services.vapid.public_key')
        ], 200);
    }

    /**
     * Enviar notificación de prueba
     */
    public function testNotification(Request $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            $result = $this->webPushService->sendToUser(
                $user->USR_ID,
                '🧪 Notificación de prueba',
                'Esta es una notificación de prueba del sistema Lambda',
                [
                    'type' => 'test',
                    'timestamp' => time()
                ]
            );

            return response()->json([
                'success' => $result['success'],
                'message' => 'Notificación enviada',
                'result' => $result
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al enviar notificación de prueba',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

