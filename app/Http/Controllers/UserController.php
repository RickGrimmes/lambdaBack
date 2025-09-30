<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class UserController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'USR_Name' => 'required|string|min:2|max:50',
            'USR_LastName' => 'required|string|min:2|max:50',
            'USR_Email' => 'required|email|unique:Users,USR_Email|max:255',
            'USR_Password' => 'required|string|min:8|max:255',
            'USR_UserRole' => 'required|in:trainer,trainee'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = User::create([
                'USR_Name' => $request->USR_Name,
                'USR_LastName' => $request->USR_LastName,
                'USR_Email' => $request->USR_Email,
                'USR_Password' => Hash::make($request->USR_Password),
                'USR_UserRole' => $request->USR_UserRole
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Usuario registrado con éxito',
                'data' => $user
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar el usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'USR_Email' => 'required|email',
            'USR_Password' => 'required|string|min:8'
        ], [
            'USR_Email.required' => 'El campo email es obligatorio.',
            'USR_Email.email' => 'El campo email debe ser una dirección de correo electrónico válida.',
            'USR_Password.required' => 'El campo contraseña es obligatorio.',
            'USR_Password.min' => 'La contraseña debe tener al menos 8 caracteres.'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = User::where('USR_Email', $request->USR_Email)->first();

            if (!$user || !Hash::check($request->USR_Password, $user->USR_Password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Credenciales inválidas'
                ], 401);
            }

            $token = JWTAuth::fromUser($user);

            return response()->json([
                'success' => true,
                'message' => 'Inicio de sesión exitoso',
                'data' => $user,
                'token' => $token
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al intentar iniciar sesión',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
