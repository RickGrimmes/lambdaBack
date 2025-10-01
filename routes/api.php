<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RoutineController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\ExcerciseController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\MediaController;

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
    Route::post('editUser', [UserController::class, 'editUser']);
    Route::post('refresh-token', [UserController::class, 'refreshToken']);
    Route::get('getUser', [UserController::class, 'getUser']);

    // ROOM (SON LOS QUE YO COMO TRAINER CREO)
    Route::get('getMyRooms', [RoomController::class, 'getMyRooms']); // Solo para el trainer
    Route::post('createRoom', [RoomController::class, 'createRoom']);
    Route::get('getRoom', [RoomController::class, 'getRoom']);

    // USERS_ROOM

    // EXCERCISE
    Route::get('getExcercise', [ExcerciseController::class, 'getExcercise']);
    Route::post('createExcercise', [ExcerciseController::class, 'createExcercise']);
    Route::get('getExcercisesByRoom/{roomId}', [ExcerciseController::class, 'getExcercisesByRoom']);

    // ROUTINE
    
    // EXCERCISE_MEDIA
});
