<?php

namespace App\Http\Controllers;

use App\Models\Excercise;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class RoomController extends Controller
{
    public function getMyRoomsData()
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            
            $rooms = Room::where('ROO_USR_ID', $user->USR_ID)
                        ->withCount(['excercises', 'userrooms'])
                        ->get();

            $totalRooms = $rooms->count();
            $totalExercises = $rooms->sum('excercises_count');
            $totalTrainees = $rooms->sum('userrooms_count');

            return response()->json([
                'success' => true,
                'data' => [
                    'total_rooms' => $totalRooms,
                    'total_exercises' => $totalExercises,
                    'total_trainees' => $totalTrainees
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas de rooms',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getMyRooms(Request $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            
            $rooms = Room::where('ROO_USR_ID', $user->USR_ID)
                        ->withCount(['excercises', 'userrooms']) 
                        ->get();

            $roomsWithCounts = $rooms->map(function($room) {
                return [
                    'ROO_ID' => $room->ROO_ID,
                    'ROO_Name' => $room->ROO_Name,
                    'ROO_Code' => $room->ROO_Code,
                    'ROO_USR_ID' => $room->ROO_USR_ID,
                    'created_at' => $room->created_at,
                    'updated_at' => $room->updated_at,
                    'total_exercises' => $room->excercises_count,
                    'trainees_count' => $room->userrooms_count
                ];
            });

            return response()->json([
                'success' => true,
                'rooms' => $roomsWithCounts,
                'total_rooms' => $rooms->count()
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener rooms',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function createRoom(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ROO_Name' => 'required|string|min:2|max:100',
        ], [
            'ROO_Name.required' => 'El nombre del room es obligatorio',
            'ROO_Name.min' => 'El nombre debe tener mínimo 2 caracteres',
            'ROO_Name.max' => 'El nombre no puede tener más de 100 caracteres',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = JWTAuth::parseToken()->authenticate();
            $roomCode = $this->generateUniqueRoomCode();

            $room = Room::create([
                'ROO_Name' => $request->ROO_Name,
                'ROO_Code' => $roomCode,
                'ROO_USR_ID' => $user->USR_ID
            ]);

            $room->load('user');

            return response()->json([
                'success' => true,
                'message' => 'Room creado exitosamente',
                'room' => [
                    'ROO_ID' => $room->ROO_ID,
                    'ROO_Name' => $room->ROO_Name,
                    'ROO_Code' => $room->ROO_Code,
                    'ROO_USR_ID' => $room->ROO_USR_ID,
                    'created_at' => $room->created_at,
                    'user' => $room->user
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear room',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function generateUniqueRoomCode()
    {
        do {
            $code = strtoupper(Str::random(7));
            
            $code = preg_replace('/[^A-Z0-9]/', '', $code);
            if (strlen($code) < 7) {
                $code = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 7));
            }
            
        } while (Room::where('ROO_Code', $code)->exists());

        return $code;
    }

    public function getRoomExcercises(Room $room)
    {
         try {
            $user = JWTAuth::parseToken()->authenticate();
            
            if ($room->ROO_USR_ID !== $user->USR_ID) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para ver este room'
                ], 403);
            }

            $room->load('userrooms.user');
            $excercises = Excercise::where('EXC_ROO_ID', $room->ROO_ID)->get();

            return response()->json([
                'success' => true,
                'room' => [$room],
                'exercises' => $excercises,
                'total_exercises' => $excercises->count()
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener room data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function editRoom(Room $room)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            
            if ($room->ROO_USR_ID !== $user->USR_ID) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para editar'
                ], 403);
            }

            $validator = Validator::make(request()->all(), [
                'ROO_Name' => 'required|string|min:2|max:100',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $room->ROO_Name = request()->ROO_Name;
            $room->save();

            return response()->json([
                'success' => true,
                'message' => 'Room editado exitosamente'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al editar room',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function searchRoom(Request $request)
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
                'ROO_Code' => 'required|string|size:7',
            ], [
                'ROO_Code.required' => 'El código del room es obligatorio',
                'ROO_Code.size' => 'El código del room debe tener exactamente 7 caracteres',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $room = Room::where('ROO_Code', $request->ROO_Code)->first();

            if (!$room) {
                return response()->json([
                    'success' => false,
                    'message' => 'Room no encontrado con ese código'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'room' => $room
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al buscar room',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function deleteRoom($room)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            $room = Room::find($room);

            if (!$room) {
                return response()->json([
                    'success' => false,
                    'message' => 'Room no encontrado'
                ], 404);
            }

            if ($room->ROO_USR_ID !== $user->USR_ID) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para eliminar este room'
                ], 403);
            }

            Excercise::where('EXC_ROO_ID', $room->ROO_ID)->delete();

            $room->delete();

            return response()->json([
                'success' => true,
                'message' => 'Room y sus ejercicios eliminados exitosamente'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar room',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
