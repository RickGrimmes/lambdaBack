<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class NotificationController extends Controller
{
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
                ->with(['room:ROO_ID,ROO_Name'])
                ->orderBy('created_at', 'desc')
                ->get();

            $unreadCount = Notification::forUser($user->USR_ID)->unread()->count();

            return response()->json([
                'success' => true,
                'message' => 'Notificaciones obtenidas exitosamente',
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

    // Marcar notificación como leída
    public function markAsRead(Request $request, $notificationId)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            $notification = Notification::where('NOT_ID', $notificationId)
                ->where('NOT_USR_ID', $user->USR_ID)
                ->first();

            if (!$notification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notificación no encontrada'
                ], 404);
            }

            $notification->update(['NOT_Status' => 'read']);

            return response()->json([
                'success' => true,
                'message' => 'Notificación marcada como leída',
                'data' => $notification
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al marcar notificación',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}