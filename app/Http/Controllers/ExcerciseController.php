<?php

namespace App\Http\Controllers;

use App\Models\Excercise;
use App\Models\Room;
use App\Services\WebPushService;
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
            'EXC_Media1' => 'nullable|image|mimes:jpeg,png,jpg,webp,mp4,mov|max:20480',
            'EXC_Media2' => 'nullable|image|mimes:jpeg,png,jpg,webp,mp4,mov|max:20480', 
            'EXC_Media3' => 'nullable|image|mimes:jpeg,png,jpg,webp,mp4,mov|max:20480',
            'EXC_Media4' => 'nullable|image|mimes:jpeg,png,jpg,webp,mp4,mov|max:20480',
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

            // 🔔 Enviar notificaciones a los trainees de la sala
            $this->notifyRoomTrainees($room, $excercise);

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

            // Contar imágenes y URLs directamente del ejercicio
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

    public function editExcercise(Request $request, $excercise)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            if ($user->USR_Type !== 'trainee') {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para editar ejercicios'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'EXC_Type' => 'nullable|in:Calentamiento,Calistenia,Musculatura,Elasticidad,Resistencia,Médico',
                'EXC_Instructions' => 'nullable|string',
                'EXC_DifficultyLevel' => 'nullable|in:PRINCIPIANTE,INTERMEDIO,AVANZADO',
                'EXC_Media1' => 'nullable|image|mimes:jpeg,png,jpg,webp,mp4,mov|max:20480',
                'EXC_Media2' => 'nullable|image|mimes:jpeg,png,jpg,webp,mp4,mov|max:20480',
                'EXC_Media3' => 'nullable|image|mimes:jpeg,png,jpg,webp,mp4,mov|max:20480',
                'EXC_Media4' => 'nullable|image|mimes:jpeg,png,jpg,webp,mp4,mov|max:20480',
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

            $excerciseModel = Excercise::with('room')->find($excercise);

            if (!$excerciseModel) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ejercicio no encontrado'
                ], 404);
            }

            if ($excerciseModel->room->ROO_USR_ID !== $user->USR_ID) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para editar este ejercicio'
                ], 403);
            }

            $updateData = [];

            if ($request->has('EXC_Type')) {
                $updateData['EXC_Type'] = $request->EXC_Type;
            }
            if ($request->has('EXC_Instructions')) {
                $updateData['EXC_Instructions'] = $request->EXC_Instructions;
            }
            if ($request->has('EXC_DifficultyLevel')) {
                $updateData['EXC_DifficultyLevel'] = $request->EXC_DifficultyLevel;
            }

            $mediaPaths = [];
            $uploadedFilesCount = 0;
            $exerciseFolder = null;

            for ($i = 1; $i <= 4; $i++) {
                $fieldName = "EXC_Media{$i}";
                
                if ($request->hasFile($fieldName)) {
                    if (!$exerciseFolder) {
                        $exerciseFolder = "exercises/exercise_{$excerciseModel->EXC_ID}";
                    }
                    
                    $file = $request->file($fieldName);
                    $fileName = time() . "_media{$i}_" . $file->getClientOriginalName();
                    
                    $path = $file->storeAs($exerciseFolder, $fileName, 'public');
                    $updateData["EXC_Media{$i}"] = "/storage/" . $path;
                    $uploadedFilesCount++;
                }
            }

            if ($request->has('EXC_URL1')) {
                $updateData['EXC_URL1'] = $request->EXC_URL1;
            }
            if ($request->has('EXC_URL2')) {
                $updateData['EXC_URL2'] = $request->EXC_URL2;
            }

            if (!empty($updateData)) {
                $excerciseModel->update($updateData);
                $excerciseModel->refresh();
            }

            return response()->json([
                'success' => true,
                'message' => 'Ejercicio actualizado exitosamente',
                'data' => $excerciseModel,
                'uploaded_files' => $uploadedFilesCount,
                'updated_fields' => array_keys($updateData)
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar ejercicio',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function deleteExcercise($excercise)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            if ($user->USR_Type !== 'trainee') {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para eliminar ejercicios'
                ], 403);
            }

            $excerciseModel = Excercise::with('room')->find($excercise);

            if (!$excerciseModel) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ejercicio no encontrado'
                ], 404);
            }

            if ($excerciseModel->room->ROO_USR_ID !== $user->USR_ID) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para eliminar este ejercicio'
                ], 403);
            }

            $roomId = $excerciseModel->EXC_ROO_ID;
            $exerciseTitle = $excerciseModel->EXC_Title;

            $excerciseModel->delete();

            $remainingExercises = Excercise::where('EXC_ROO_ID', $roomId)->count();
            
            if ($remainingExercises === 0) {
                Room::find($roomId)->delete();
                $roomDeleted = true;
            } else {
                $roomDeleted = false;
            }

            return response()->json([
                'success' => true,
                'message' => 'Ejercicio eliminado exitosamente',
                'deleted_exercise' => $exerciseTitle,
                'room_deleted' => $roomDeleted,
                'remaining_exercises_in_room' => $remainingExercises
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar ejercicio',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Enviar notificaciones a los trainees de una sala
     */
    private function notifyRoomTrainees($room, $excercise)
    {
        try {
            \Log::info("Enviando notificaciones para ejercicio {$excercise->EXC_ID} en sala {$room->ROO_ID}");
            
            // Obtener todos los trainees de la sala
            $usersRooms = \App\Models\UsersRoom::where('USR_ROO_ID', $room->ROO_ID)
                                                ->with('user')
                                                ->get();

            \Log::info("Trainees en la sala: " . $usersRooms->count());

            if ($usersRooms->isEmpty()) {
                \Log::info("No hay trainees en la sala");
                return;
            }

            $webPushService = new WebPushService();
            $traineeIds = $usersRooms->pluck('user.USR_ID')->filter()->toArray();

            \Log::info("IDs de trainees: " . json_encode($traineeIds));

            foreach ($traineeIds as $traineeId) {
                \Log::info("Enviando notificación a trainee ID: {$traineeId}");
                
                $result = $webPushService->sendToUser(
                    $traineeId,
                    '🏋️ Nuevo ejercicio asignado',
                    "Tu entrenador ha añadido: {$excercise->EXC_Title}",
                    [
                        'type' => 'new_exercise',
                        'exercise_id' => $excercise->EXC_ID,
                        'room_id' => $room->ROO_ID,
                        'room_name' => $room->ROO_Name
                    ]
                );
                
                \Log::info("Resultado envío notificación: " . json_encode($result));
            }

        } catch (\Exception $e) {
            \Log::error('Error enviando notificaciones: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
        }
    }
}
