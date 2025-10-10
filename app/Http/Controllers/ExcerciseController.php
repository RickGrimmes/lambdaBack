<?php

namespace App\Http\Controllers;

use App\Models\Excercise;
use App\Models\Room;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Validator;

class ExcerciseController extends Controller
{
    public function createExcercise(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'EXC_Title' => 'required|string|max:255',
            'EXC_Type' => 'nullable|in:Calentamiento,Calistenia,Musculatura,Elasticidad,Resistencia,Médico',
            'EXC_Instructions' => 'nullable|string',
            'EXC_DifficultyLevel' => 'nullable|in:PRINCIPIANTE,INTERMEDIO,AVANZADO',
            'EXC_ROO_ID' => 'required|integer|exists:rooms,id'
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

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            $room = Room::find($request->input('EXC_ROO_ID'));
            
            if (!$room) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sala no encontrada'
                ], 404);
            }

            if ($room->ROO_USR_ID !== $user->USR_ID) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para agregar ejercicios a esta sala'
                ], 403);
            }

            $excercise = Excercise::create([
                'EXC_Title' => $request->EXC_Title,
                'EXC_Type' => $request->EXC_Type,
                'EXC_Instructions' => $request->EXC_Instructions,
                'EXC_DifficultyLevel' => $request->EXC_DifficultyLevel,
                'EXC_ROO_ID' => $request->EXC_ROO_ID
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Ejercicio creado exitosamente',
                'data' => $excercise
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear ejercicio',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getExcercisesByRoom($room)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            $room = Room::find($room);
            
            if (!$room) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sala no encontrada'
                ], 404);
            }

            $excercises = Excercise::where('EXC_ROO_ID', $room)->get();

            return response()->json([
                'success' => true,
                'message' => 'Ejercicios obtenidos exitosamente',
                'total_exercises' => $excercises->count(),
                'data' => $excercises
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener excercises',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    
    public function getExcercise($excercise)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            $excercise = Excercise::with('room', 'media')->find($excercise);
            
            if (!$excercise) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ejercicio no encontrado'
                ], 404);
            }

            if ($excercise->room->ROO_USR_ID !== $user->USR_ID) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para ver este ejercicio'
                ], 403);
            }

            $totalImages = 0;
            $totalUrls = 0;
            
            foreach ($excercise->media as $media) {
                if ($media->MED_Media1) $totalImages++;
                if ($media->MED_Media2) $totalImages++;
                if ($media->MED_Media3) $totalImages++;
                if ($media->MED_Media4) $totalImages++;
                if ($media->MED_URL1) $totalUrls++;
                if ($media->MED_URL2) $totalUrls++;
            }

            return response()->json([
                'success' => true,
                'message' => 'Ejercicio obtenido exitosamente',
                'data' => $excercise,
                'media' => $excercise->media,
                'total_images' => $totalImages,
                'total_urls' => $totalUrls
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener ejercicio',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Excercise $excercise)
    {
        //
    }

}
