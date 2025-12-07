<?php

namespace App\Services;

use Kreait\Firebase\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class FirebaseNotificationService
{
    private $messaging;

    public function __construct()
    {
        $this->messaging = app('firebase.messaging');
    }

    public function sendToDevice(string $token, string $title, string $body, array $data = [])
    {
        $message = CloudMessage::withTarget('token', $token)
            ->withNotification(Notification::create($title, $body))
            ->withData($data);

        try {
            $this->messaging->send($message);
            return ['success' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function sendToMultipleDevices(array $tokens, string $title, string $body, array $data = [])
    {
        $message = CloudMessage::new()
            ->withNotification(Notification::create($title, $body))
            ->withData($data);

        try {
            $result = $this->messaging->sendMulticast($message, $tokens);
            return ['success' => true, 'result' => $result];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
