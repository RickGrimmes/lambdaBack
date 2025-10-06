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
            'EXE_Title' => 'required|string|max:255',
            'EXE_Type' => 'nullable|in:Calentamiento,Calistenia,Musculatura,Elasticidad,Resistencia,Médico',
            'EXE_Instructions' => 'nullable|string',
            'EXE_DifficultyLevel' => 'nullable|in:PRINCIPIANTE,INTERMEDIO,AVANZADO',
            'EXE_ROO_ID' => 'required|integer|exists:rooms,id'
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

            $room = Room::find($request->input('EXE_ROO_ID'));
            
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
                'EXE_Title' => $request->EXE_Title,
                'EXE_Type' => $request->EXE_Type,
                'EXE_Instructions' => $request->EXE_Instructions,
                'EXE_DifficultyLevel' => $request->EXE_DifficultyLevel,
                'EXE_ROO_ID' => $request->EXE_ROO_ID
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

            $excercises = Excercise::where('EXE_ROO_ID', $room)->get();

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

    
    public function getExcercise()
    {
        
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Excercise $excercise)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Excercise $excercise)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Excercise $excercise)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Excercise $excercise)
    {
        //
    }
}
