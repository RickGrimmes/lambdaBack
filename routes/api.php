<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RoutineController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\ExcerciseController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\UsersRoomController;

// USER (ENTRAN LOS 3 TIPOS DE USUARIO DE ALGUNA FORMA)
Route::post('login', [UserController::class, 'login']);
Route::post('register', [UserController::class, 'register']);

Route::middleware('auth:api')->group(function () {

    #region ADMIN
    Route::prefix('admin')->group(function () {
        Route::post('createUser', [UserController::class, 'createUser']);
        Route::put('updateUser/{userId}', [UserController::class, 'updateUser']);
        Route::delete('deleteUser/{userId}', [UserController::class, 'deleteUser']);
        Route::get('getAllUsers/{filter}', [UserController::class, 'getAllUsers']);
    });
    #endregion
    
    #region TRAINER

    // ROOM
    Route::get('getMyRooms', [RoomController::class, 'getMyRooms']); 
    Route::post('createRoom', [RoomController::class, 'createRoom']);
    Route::put('editRoom/{room}', [RoomController::class, 'editRoom']);
    Route::get('getRoomExcercises/{room}', [RoomController::class, 'getRoomExcercises']); // Obtener excercises de un room

    // EXCERCISE
    Route::post('createExcercise', [ExcerciseController::class, 'createExcercise']);

    // ROUTINE
    Route::post('createRoutine', [RoutineController::class, 'createRoutine']);

    #endregion

    #region TRAINEE

    // ROOM
    Route::post('searchRoom', [RoomController::class, 'searchRoom']); //para que el usuario trainee busque una room por su code

    // USERS_ROOM (unión de los trainees con la sala)
    Route::post('joinRoom', [UsersRoomController::class, 'joinRoom']); // Unirse a un room
    Route::post('leaveRoom/{room}', [UsersRoomController::class, 'leaveRoom']); // Salir de un room (aunque tengo dudas de este)
    Route::get('getMyJoinedRooms', [UsersRoomController::class, 'getMyJoinedRooms']); // Obtener las rooms a las que se ha unido el trainee

    #endregion

    #region TRAINER / TRAINEE (OSEA QUE LE PEGA A LOS 2)

    // USER
    Route::post('logout', [UserController::class, 'logout']);
    Route::put('editUser', [UserController::class, 'editUser']);
    Route::post('refreshToken', [UserController::class, 'refreshToken']);
    Route::get('getUser', [UserController::class, 'getUser']);

    // EXCERCISE
    Route::get('getExcercisesByRoom/{room}', [ExcerciseController::class, 'getExcercisesByRoom']);
    Route::get('getExcercise/{excercise}', [ExcerciseController::class, 'getExcercise']); // ejercicio con media también

    #endregion


    // EXCERCISE MEDIA (ESTOS YA NO JALAN, ACOPLAR A EXCERCISE)
    Route::post('uploadMedia', [MediaController::class, 'uploadMedia']);
    Route::get('getMediaByExcercise/{excercise}', [MediaController::class, 'getMediaByExcercise']);
    Route::put('updateMedia/{media}', [MediaController::class, 'updateMedia']); // para editar imágenes o url de video, permite también que si dejas nulo eso cuenta como borrar

    
});
