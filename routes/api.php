<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RoutineController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\ExcerciseController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\UsersRoomController;

// USER
Route::post('login', [UserController::class, 'login']);
Route::post('register', [UserController::class, 'register']);

Route::middleware('auth:api')->group(function () {
    // ADMIN
    Route::prefix('admin')->group(function () {
        Route::get('getAllUsers/{filter}', [UserController::class, 'getAllUsers']);
    });

    // USER
    Route::post('logout', [UserController::class, 'logout']);
    Route::put('editUser', [UserController::class, 'editUser']);
    Route::post('refreshToken', [UserController::class, 'refreshToken']);
    Route::get('getUser', [UserController::class, 'getUser']);

    // ROOM (SON LOS QUE YO CREO COMO TRAINER)
    Route::get('getMyRooms', [RoomController::class, 'getMyRooms']); // Solo para el trainer
    Route::post('createRoom', [RoomController::class, 'createRoom']);
    Route::get('getRoom/{room}', [RoomController::class, 'getRoom']); // Obtener 1 solo room
    Route::get('getRoomData/{room}', [RoomController::class, 'getRoomData']); // Obtener media de un room
    Route::put('editRoom/{room}', [RoomController::class, 'editRoom']);
    Route::post('searchRoom', [RoomController::class, 'searchRoom']); //para que el usuario trainee busque una room por su code

    // EXCERCISE
    Route::post('createExcercise', [ExcerciseController::class, 'createExcercise']);
    Route::get('getExcercisesByRoom/{room}', [ExcerciseController::class, 'getExcercisesByRoom']);
    Route::get('getExcercise/{excercise}', [ExcerciseController::class, 'getExcercise']); // ejercicio con media también

    // EXCERCISE MEDIA
    Route::post('uploadMedia', [MediaController::class, 'uploadMedia']);
    Route::get('getMediaByExcercise/{excercise}', [MediaController::class, 'getMediaByExcercise']);
    Route::put('updateMedia/{media}', [MediaController::class, 'updateMedia']); // para editar imágenes o url de video, permite también que si dejas nulo eso cuenta como borrar

    // USERS_ROOM (unión de los trainees con la sala)
    Route::post('joinRoom', [UsersRoomController::class, 'joinRoom']); // Unirse a un room
    Route::post('leaveRoom/{room}', [UsersRoomController::class, 'leaveRoom']); // Salir de un room (aunque tengo dudas de este)
    Route::get('getMyJoinedRooms', [UsersRoomController::class, 'getMyJoinedRooms']); // Obtener las rooms a las que se ha unido el trainee
    
    // ROUTINE
    Route::post('createRoutine', [RoutineController::class, 'createRoutine']);
});
