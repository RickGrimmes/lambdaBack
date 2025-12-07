<?php

namespace App\Http\Controllers;

use App\Models\Excercise;
use App\Models\Room;
use App\Models\User;
use App\Services\FirebaseNotificationService;
use App\Services\WebPushService;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Validator;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
                    //$mediaPaths["EXC_Media{$i}"] = asset('storage/' . $path);
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

            // CAMBIO HECHO PARA FCM, enviar notificación a trainees de la sala
            $notificationsSent = 0;
            $notificationErrors = [];

            try {
                // Obtener usuarios de la sala que tienen token FCM
                $trainees = User::join('Users_Rooms', 'Users.USR_ID', '=', 'Users_Rooms.URO_USR_ID')
                            ->where('Users_Rooms.URO_ROO_ID', $room->ROO_ID)
                            ->where('Users.USR_UserRole', 'trainee')
                            ->whereNotNull('Users.USR_FCM')
                            ->select('Users.*')
                            ->get();

                if ($trainees->count() > 0) {
                    $firebaseService = new FirebaseNotificationService();
                    
                    foreach ($trainees as $trainee) {
                        try {
                            $result = $firebaseService->sendToDevice(
                                $trainee->USR_FCM,
                                'Nuevo Ejercicio Disponible',
                                'La sala: ' . $room->ROO_Name . ' ha recibido un nuevo ejercicio. ¡Revisa la app para más detalles!',
                                [
                                    'type' => 'new_exercise',
                                    'exercise_id' => (string)$excercise->EXC_ID,
                                    'room_id' => (string)$room->ROO_ID,
                                    'room_name' => $room->ROO_Name
                                ]
                            );

                            if (isset($result['success']) && $result['success']) {
                                $notificationsSent++;
                            } else {
                                $notificationErrors[] = [
                                    'user' => $trainee->USR_Name,
                                    'error' => $result['error'] ?? 'Error desconocido'
                                ];
                            }
                            
                        } catch (\Exception $e) {
                            $notificationErrors[] = [
                                'user' => $trainee->USR_Name,
                                'error' => $e->getMessage()
                            ];
                        }
                    }
                }
                
            } catch (\Exception $e) {
                $notificationErrors[] = ['general' => $e->getMessage()];
            }

            return response()->json([
                'success' => true,
                'message' => 'Ejercicio creado exitosamente',
                'data' => $excercise,
                'fcm_notifications' => [
                    'sent' => $notificationsSent,
                    'errors' => count($notificationErrors),
                    'trainees_found' => isset($trainees) ? $trainees->count() : 0,
                    'error_details' => $notificationErrors
                ]
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

            if ($user->USR_UserRole !== 'trainer') {
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

            if ($user->USR_UserRole !== 'trainer') {
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
}
