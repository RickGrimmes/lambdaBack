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
            'EXC_ROO_ID' => 'required|integer',
            'EXC_Media1' => 'nullable|file|mimes:jpeg,png,jpg,webp,mp4,mov|max:20480',
            'EXC_Media2' => 'nullable|file|mimes:jpeg,png,jpg,webp,mp4,mov|max:20480', 
            'EXC_Media3' => 'nullable|file|mimes:jpeg,png,jpg,webp,mp4,mov|max:20480',
            'EXC_Media4' => 'nullable|file|mimes:jpeg,png,jpg,webp,mp4,mov|max:20480',
            'EXC_URL1' => 'nullable|url|max:255',
            'EXC_URL2' => 'nullable|url|max:255'
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

            // Manejo de archivos multimedia
            $mediaPaths = [];
            $uploadedFilesCount = 0;
            $exerciseFolder = null;

            for ($i = 1; $i <= 4; $i++) {
                $fieldName = "EXC_Media{$i}";
                
                if ($request->hasFile($fieldName)) {
                    if (!$exerciseFolder) {
                        $exerciseFolder = "exercises/exercise_{$excercise->EXC_ID}";
                    }
                    
                    $file = $request->file($fieldName);
                    $fileName = time() . "_media{$i}_" . $file->getClientOriginalName();
                    
                    $path = $file->storeAs($exerciseFolder, $fileName, 'public');
                    $mediaPaths["EXC_Media{$i}"] = "/storage/" . $path;
                    $uploadedFilesCount++;
                }
            }

            // Actualizar con las rutas de los archivos y URLs
            $updateData = [];
        
            for ($i = 1; $i <= 4; $i++) {
                if (isset($mediaPaths["EXC_Media{$i}"])) {
                    $updateData["EXC_Media{$i}"] = $mediaPaths["EXC_Media{$i}"];
                }
            }

            if ($request->EXC_URL1) {
                $updateData['EXC_URL1'] = $request->EXC_URL1;
            }
            if ($request->EXC_URL2) {
                $updateData['EXC_URL2'] = $request->EXC_URL2;
            }

            if (!empty($updateData)) {
                $excercise->update($updateData);
                $excercise->refresh();
            }

            return response()->json([
                'success' => true,
                'message' => 'Ejercicio creado exitosamente',
                'data' => $excercise,
                'uploaded_files' => $uploadedFilesCount
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

            $excercises = Excercise::where('EXC_ROO_ID', $room->ROO_ID)->get();

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

            $excercise = Excercise::with('room')->find($excercise);
            
            if (!$excercise) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ejercicio no encontrado'
                ], 404);
            }

            // Contar imágenes y URLs
            $totalImages = 0;
            $totalUrls = 0;
            
            if ($excercise->EXC_Media1) $totalImages++;
            if ($excercise->EXC_Media2) $totalImages++;
            if ($excercise->EXC_Media3) $totalImages++;
            if ($excercise->EXC_Media4) $totalImages++;
            if ($excercise->EXC_URL1) $totalUrls++;
            if ($excercise->EXC_URL2) $totalUrls++;

            return response()->json([
                'success' => true,
                'message' => 'Ejercicio obtenido exitosamente',
                'data' => $excercise,
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
}
