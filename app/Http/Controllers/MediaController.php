<?php

namespace App\Http\Controllers;

use App\Models\ExcerciseMedia;
use App\Models\Excercise;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class MediaController extends Controller
{
    public function uploadMedia(Request $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            $validator = Validator::make($request->all(), [
                'MED_EXC_ID' => 'required|integer|exists:Excercises,EXC_ID',
                'MED_Media1' => 'nullable|image|mimes:jpeg,png,jpg,webp,mp4,mov|max:20480', 
                'MED_Media2' => 'nullable|image|mimes:jpeg,png,jpg,webp,mp4,mov|max:20480',
                'MED_Media3' => 'nullable|image|mimes:jpeg,png,jpg,webp,mp4,mov|max:20480',
                'MED_Media4' => 'nullable|image|mimes:jpeg,png,jpg,webp,mp4,mov|max:20480',
                'MED_URL1' => 'nullable|url|max:255',
                'MED_URL2' => 'nullable|url|max:255'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $exercise = Excercise::with('room')->find($request->MED_EXC_ID);
            
            if (!$exercise) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ejercicio no encontrado'
                ], 404);
            }
            if ($exercise->room->ROO_USR_ID !== $user->USR_ID) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para subir media a este ejercicio'
                ], 403);
            }
            
            $exerciseFolder = "exercises/exercise_{$exercise->EXC_ID}";

            $mediaPaths = [];
            
            for ($i = 1; $i <= 4; $i++) {
                $fieldName = "MED_Media{$i}";
                
                if ($request->hasFile($fieldName)) {
                    $file = $request->file($fieldName);
                    $fileName = time() . "_media{$i}_" . $file->getClientOriginalName();
                    
                    $path = $file->storeAs($exerciseFolder, $fileName, 'public');
                    $mediaPaths[$fieldName] = "/storage/" . $path;
                }
            }

            $media = ExcerciseMedia::create([
                'MED_EXC_ID' => $request->MED_EXC_ID,
                'MED_Media1' => $mediaPaths['MED_Media1'] ?? null,
                'MED_Media2' => $mediaPaths['MED_Media2'] ?? null,
                'MED_Media3' => $mediaPaths['MED_Media3'] ?? null,
                'MED_Media4' => $mediaPaths['MED_Media4'] ?? null,
                'MED_URL1' => $request->MED_URL1,
                'MED_URL2' => $request->MED_URL2
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Media subida correctamente',
                'data' => $media,
                'uploaded_files' => count($mediaPaths),
                'storage_path' => $exerciseFolder
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al subir media',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getMediaByExcercise($excercise)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            $exercise = Excercise::with('room')->find($excercise);
            
            if (!$exercise) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ejercicio no encontrado'
                ], 404);
            }

            if ($exercise->room->ROO_USR_ID !== $user->USR_ID) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para ver la media de este ejercicio'
                ], 403);
            }

            $media = ExcerciseMedia::where('MED_EXC_ID', $excercise)->get();

            return response()->json([
                'success' => true,
                'message' => 'Media obtenida correctamente',
                'total_media' => $media->count(),
                'data' => $media
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener media',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateMedia(Request $request, $media)
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
                'MED_Media1' => 'nullable|image|mimes:jpeg,png,jpg,webp,mp4,mov|max:20480', 
                'MED_Media2' => 'nullable|image|mimes:jpeg,png,jpg,webp,mp4,mov|max:20480',
                'MED_Media3' => 'nullable|image|mimes:jpeg,png,jpg,webp,mp4,mov|max:20480',
                'MED_Media4' => 'nullable|image|mimes:jpeg,png,jpg,webp,mp4,mov|max:20480',
                'MED_URL1' => 'nullable|string|max:255',
                'MED_URL2' => 'nullable|string|max:255',
                'remove_media1' => 'nullable|boolean',
                'remove_media2' => 'nullable|boolean',
                'remove_media3' => 'nullable|boolean',
                'remove_media4' => 'nullable|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $mediaRecord = ExcerciseMedia::with('exercise.room')->find($media);
        
            if (!$mediaRecord) {
                return response()->json([
                    'success' => false,
                    'message' => 'Media no encontrada'
                ], 404);
            }
            if ($mediaRecord->exercise->room->ROO_USR_ID !== $user->USR_ID) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso'
                ], 403);
            }

            $exerciseFolder = "exercises/exercise_{$mediaRecord->MED_EXC_ID}";

            for ($i = 1; $i <= 4; $i++) {
                $fieldName = "MED_Media{$i}";
                $removeField = "remove_media{$i}";
                
                // CASO 1: Eliminar imagen específica
                if ($request->boolean($removeField)) {
                    $oldPath = $mediaRecord->$fieldName;
                    if ($oldPath) {
                        $physicalPath = str_replace('/storage/', 'public/', $oldPath);
                        Storage::delete($physicalPath);
                    }
                    $mediaRecord->$fieldName = null;
                }
                // CASO 2: Reemplazar con nueva imagen
                elseif ($request->hasFile($fieldName)) {
                    $oldPath = $mediaRecord->$fieldName;
                    if ($oldPath) {
                        $physicalPath = str_replace('/storage/', 'public/', $oldPath);
                        Storage::delete($physicalPath);
                    }
                    
                    $file = $request->file($fieldName);
                    $fileName = time() . "_media{$i}_" . $file->getClientOriginalName();
                    $path = $file->storeAs($exerciseFolder, $fileName, 'public');
                    $mediaRecord->$fieldName = "/storage/" . $path;
                }
            }

            if ($request->has('MED_URL1')) {
                $url1 = trim($request->MED_URL1);
                $mediaRecord->MED_URL1 = empty($url1) ? null : $url1;
            }
            if ($request->has('MED_URL2')) {
                $url2 = trim($request->MED_URL2);
                $mediaRecord->MED_URL2 = empty($url2) ? null : $url2;
            }

            $mediaRecord->save();

            return response()->json([
                'success' => true,
                'message' => 'URLs actualizadas',
                'data' => $mediaRecord
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
