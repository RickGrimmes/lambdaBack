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

// ROOM
// USERS_ROOM
// EXCERCISE
// ROUTINE
// EXCERCISE_MEDIA
