<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class RoomController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function getMyRooms(Request $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            $rooms = Room::where('ROO_USR_ID', $user->USR_ID)->with('user')->get();

            return response()->json([
                'success' => true,
                'rooms' => $rooms
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

    /**
     * Generate unique room code (7 characters alphanumeric)
     */
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

    /**
     * Display the specified resource.
     */
    public function getRoom(Room $room)
    {
         try {
            $user = JWTAuth::parseToken()->authenticate();
            
            if ($room->ROO_USR_ID !== $user->USR_ID) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para ver este room'
                ], 403);
            }

            $room->load('user', 'exercises');
            
            return response()->json([
                'success' => true,
                'room' => $room
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener room',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Room $room)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Room $room)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Room $room)
    {
        //
    }
}
