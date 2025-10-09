<?php

namespace App\Http\Controllers;

use App\Models\UsersRoom;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class UsersRoomController extends Controller
{
    public function joinRoom(Request $request)
    {
        try {
            $user =JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            if ($user->USR_UserRole !== 'trainee') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo los trainees pueden unirse a rooms'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'URO_ROO_ID' => 'required|integer|exists:Rooms,ROO_ID'
            ], [
                'URO_ROO_ID.required' => 'El ID del room es obligatorio',
                'URO_ROO_ID.exists' => 'El room no existe'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $existingRelation = UsersRoom::where('URO_USR_ID', $user->USR_ID)
                                        ->where('URO_ROO_ID', $request->URO_ROO_ID)
                                        ->first();

            if ($existingRelation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya estás unido a este room'
                ], 400);
            }

            UsersRoom::create([
                'URO_USR_ID' => $user->USR_ID,
                'URO_ROO_ID' => $request->URO_ROO_ID
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Te has unido al room correctamente'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al unirse al room',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function leaveRoom(Request $request, $room)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            $existingRelation = UsersRoom::where('URO_USR_ID', $user->USR_ID)
                                        ->where('URO_ROO_ID', $room)
                                        ->first();

            if (!$existingRelation) {
                return response()->json([
                    'success' => false,
                    'message' => 'No estás unido a este room'
                ], 400);
            }

            $existingRelation->delete();

            return response()->json([
                'success' => true,
                'message' => 'Te has salido del room correctamente'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al salir del room',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public  function getMyJoinedRooms()
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            if ($user->USR_UserRole !== 'trainee') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo los trainees tienen rooms unidas'
                ], 403);
            }

            $joinedRooms = UsersRoom::with('room')
                            ->where('URO_USR_ID', $user->USR_ID)
                            ->get()
                            ->map(function($relation) {
                                return $relation->room;
                            });

            return response()->json([
                'success' => true,
                'message' => 'Rooms unidas obtenidas correctamente',
                'rooms' => $joinedRooms
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener rooms unidas',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
