<?php

namespace App\Http\Controllers;

use App\Models\Excercise;
use App\Models\Routine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class RoutineController extends Controller
{
    public function createRoutine($excercise)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            $excerciseModel = Excercise::find($excercise);

            if (!$excerciseModel) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ejercicio no encontrado'
                ], 404);
            }

            $existingRoutine = Routine::where('ROU_USR_ID', $user->USR_ID)
                                    ->where('ROU_EXC_ID', $excercise)
                                    ->first();

            if ($existingRoutine) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya tienes una rutina con este ejercicio'
                ], 409);
            }

            $routine = Routine::create([
                'ROU_USR_ID' => $user->USR_ID,
                'ROU_EXC_ID' => $excercise,
                'ROU_Status' => 'In Progress',
                'ROU_Fav' => false
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Rutina creada con éxito',
                'data' => $routine,
                'exercise' => $excerciseModel
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la rutina',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function AddFavorite($excercise)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            $excerciseExists = Excercise::find($excercise);
            
            if (!$excerciseExists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ejercicio no encontrado'
                ], 404);
            }

            $routine = Routine::where('ROU_USR_ID', $user->USR_ID)
                            ->where('ROU_EXC_ID', $excercise)
                            ->first();

            if (!$routine) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes una rutina con este ejercicio'
                ], 404);
            }

            $updateData = ['ROU_Fav' => true];

            if (empty($routine->ROU_Status)) {
                $updateData['ROU_Status'] = 'In Progress';
            }

            $routine->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Rutina marcada como favorita',
                'data' => $routine->fresh()
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al marcar como favorito',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getMyRoutines()
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            $routines = Routine::where('ROU_USR_ID', $user->USR_ID)
                            ->with(['excercise' => function($query) {
                                $query->select('EXC_ID', 'EXC_Title', 'EXC_Type', 'EXC_Instructions', 'EXC_DifficultyLevel', 'EXC_Media1', 'EXC_Media2', 'EXC_Media3', 'EXC_Media4', 'EXC_URL1', 'EXC_URL2');
                            }])
                            ->orderBy('created_at', 'desc')
                            ->get();

            if ($routines->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No tienes rutinas creadas',
                    'data' => []
                ], 200);
            }

            return response()->json([
                'success' => true,
                'message' => 'Rutinas obtenidas correctamente',
                'data' => $routines,
                'total_routines' => $routines->count()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las rutinas',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
