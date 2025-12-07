<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

use App\Services\FirebaseNotificationService;

class UserController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'USR_Name' => 'required|string|min:2|max:50',
            'USR_LastName' => 'required|string|min:2|max:50',
            'USR_Email' => 'required|email|unique:Users,USR_Email|max:255',
            'USR_Phone' => 'required|unique:Users,USR_Phone|max:10',
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

        if ($request->USR_UserRole === 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'No permitido'
            ], 403);
        }

        try {
            $user = User::create([
                'USR_Name' => $request->USR_Name,
                'USR_LastName' => $request->USR_LastName,
                'USR_Email' => $request->USR_Email,
                'USR_Phone' => $request->USR_Phone,
                'USR_Password' => Hash::make($request->USR_Password),
                'USR_UserRole' => $request->USR_UserRole,
                'USR_FCM' => 'token_hardcodeado_temporal'
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

// Solo guardar el token FCM si se proporciona CAMBIO AQUI
            $fcmRegistered = false;
            if ($request->has('fcm_token') && $request->fcm_token) {
                $user->USR_FCM = $request->fcm_token;
                $user->save();
                $fcmRegistered = true;
            }
            return response()->json([
                'success' => true,
                'message' => 'Inicio de sesión exitoso',
                'data' => $user,
                'token' => $token,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al intentar iniciar sesión',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());

            return response()->json([
                'success' => true,
                'message' => 'Cierre de sesión exitoso'
            ], 200);

        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cerrar sesión',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function editUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'USR_Name' => 'sometimes|required|string|min:2|max:50',
            'USR_LastName' => 'sometimes|required|string|min:2|max:50',
            'USR_Email' => 'sometimes|required|email|max:255',
            'USR_Phone' => 'sometimes|required|max:10',
            'USR_Password' => 'sometimes|required|string|min:8|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        if ($request->has('USR_UserRole')) {
            return response()->json([
                'success' => false,
                'message' => 'No puedes cambiar tu rol de usuario'
            ], 403);
        }

        try {
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            // Validar email único si se está actualizando
            if ($request->has('USR_Email') && $request->USR_Email !== $user->USR_Email) {
                $emailExists = User::where('USR_Email', $request->USR_Email)->exists();
                if ($emailExists) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Error de validación',
                        'errors' => ['USR_Email' => ['El email ya está en uso']]
                    ], 422);
                }
            }

            // Validar teléfono único si se está actualizando
            if ($request->has('USR_Phone') && $request->USR_Phone !== $user->USR_Phone) {
                $phoneExists = User::where('USR_Phone', $request->USR_Phone)->exists();
                if ($phoneExists) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Error de validación',
                        'errors' => ['USR_Phone' => ['El teléfono ya está en uso']]
                    ], 422);
                }
            }

            if ($request->has('USR_Password')) {
                $request->merge(['USR_Password' => Hash::make($request->USR_Password)]);
            }

            $user->update($request->only([
                'USR_Name',
                'USR_LastName',
                'USR_Email',
                'USR_Phone',
                'USR_Password',
            ]));

            $user->refresh();
            $user->makeHidden(['USR_Password']);

            return response()->json([
                'success' => true,
                'message' => 'Usuario actualizado con éxito',
                'data' => $user
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateUser(Request $request, $userId)
    {
        try {
            $currentUser = JWTAuth::parseToken()->authenticate();

            if ($currentUser->USR_UserRole !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo los administradores pueden editar usuarios'
                ], 403);
            }

            $user = User::find($userId);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'USR_Name' => 'sometimes|required|string|min:2|max:50',
                'USR_LastName' => 'sometimes|required|string|min:2|max:50',
                'USR_Email' => 'sometimes|required|email|max:255',
                'USR_Phone' => 'sometimes|required|max:10',
                'USR_Password' => 'sometimes|required|string|min:8|max:255',
                'USR_UserRole' => 'sometimes|required|in:trainer,trainee,admin'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Validar email único si se está actualizando
            if ($request->has('USR_Email') && $request->USR_Email !== $user->USR_Email) {
                $emailExists = User::where('USR_Email', $request->USR_Email)->exists();
                if ($emailExists) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Error de validación',
                        'errors' => ['USR_Email' => ['El email ya está en uso']]
                    ], 422);
                }
            }

            // Validar teléfono único si se está actualizando
            if ($request->has('USR_Phone') && $request->USR_Phone !== $user->USR_Phone) {
                $phoneExists = User::where('USR_Phone', $request->USR_Phone)->exists();
                if ($phoneExists) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Error de validación',
                        'errors' => ['USR_Phone' => ['El teléfono ya está en uso']]
                    ], 422);
                }
            }

            if ($request->has('USR_Password')) {
                $request->merge(['USR_Password' => Hash::make($request->USR_Password)]);
            }

            $user->update($request->only([
                'USR_Name',
                'USR_LastName',
                'USR_Email',
                'USR_Phone',
                'USR_Password',
                'USR_UserRole'
            ]));

            $user->refresh();
            $user->makeHidden(['USR_Password']);

            return response()->json([
                'success' => true,
                'message' => 'Usuario actualizado exitosamente por el administrador',
                'data' => $user
            ], 200);

        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token inválido o expirado'
            ], 401);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function refreshToken(Request $request)
    {
        try {
            $newToken = JWTAuth::refresh(JWTAuth::getToken());

            return response()->json([
                'success' => true,
                'message' => 'Token renovado con éxito',
                'token' => $newToken
            ], 200);

        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al renovar el token',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getUser(Request $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $user
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function createUser(Request $request)
    {
        try {
            $currentUser = JWTAuth::parseToken()->authenticate();

            if ($currentUser->USR_UserRole !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo los administradores pueden crear usuarios'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'USR_Name' => 'required|string|min:2|max:50',
                'USR_LastName' => 'required|string|min:2|max:50',
                'USR_Email' => 'required|email|unique:Users,USR_Email|max:255',
                'USR_Phone' => 'required|unique:Users,USR_Phone|max:10',
                'USR_Password' => 'required|string|min:8|max:255',
                'USR_UserRole' => 'required|in:trainer,trainee',
                'USR_FCM' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Los datos proporcionados no son válidos. Verifica la información e intenta nuevamente.'
                ], 422);
            }

            if ($request->USR_UserRole === 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede crear usuarios con rol admin'
                ], 403);
            }

            $user = User::create([
                'USR_Name' => $request->USR_Name,
                'USR_LastName' => $request->USR_LastName,
                'USR_Email' => $request->USR_Email,
                'USR_Phone' => $request->USR_Phone,
                'USR_Password' => Hash::make($request->USR_Password),
                'USR_UserRole' => $request->USR_UserRole,
                'USR_FCM' => $request->USR_FCM ?? 'token_hardcodeado_temporal'
            ]);

            $user->makeHidden(['USR_Password']);

            return response()->json([
                'success' => true,
                'message' => 'Usuario creado exitosamente por el administrador',
                'data' => $user
            ], 201);

        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token inválido o expirado'
            ], 401);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getAllUsers($filter)
    {
        try {
            $currentUser = JWTAuth::parseToken()->authenticate();

            if ($currentUser->USR_UserRole !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Acceso denegado'
                ], 403);
            }

            $validator = Validator::make(['filter' => $filter], [
                'filter' => 'required|in:trainer,trainee,All'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Filtro inválido',
                    'errors' => $validator->errors()
                ], 422);
            }


            switch ($filter) {
                case 'trainer':
                    $users = User::where('USR_UserRole', 'trainer')
                            ->select(['USR_ID', 'USR_Name', 'USR_LastName', 'USR_Email', 'USR_Phone', 'USR_UserRole', 'created_at'])
                            ->get();
                    $message = 'Trainers obtenidos exitosamente';
                    break;
                    
                case 'trainee':
                    $users = User::where('USR_UserRole', 'trainee')
                            ->select(['USR_ID', 'USR_Name', 'USR_LastName', 'USR_Email', 'USR_Phone', 'USR_UserRole', 'created_at'])
                            ->get();
                    $message = 'Trainees obtenidos exitosamente';
                    break;
                    
                case 'All':
                    $users = User::whereIn('USR_UserRole', ['trainer', 'trainee'])
                            ->select(['USR_ID', 'USR_Name', 'USR_LastName', 'USR_Email', 'USR_Phone', 'USR_UserRole', 'created_at'])
                            ->get();
                    $message = 'Todos los usuarios obtenidos exitosamente';
                    break;
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'filter_applied' => $filter,
                'total_users' => $users->count(),
                'data' => $users
            ], 200);

        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token inválido o expirado'
            ], 401);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los usuarios',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function deleteUser($userId)
    {
        try {
            $currentUser = JWTAuth::parseToken()->authenticate();

            if ($currentUser->USR_UserRole !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo los administradores pueden eliminar usuarios'
                ], 403);
            }

            $user = User::find($userId);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            // No permitir eliminar al propio admin
            if ($user->USR_ID === $currentUser->USR_ID) {
                return response()->json([
                    'success' => false,
                    'message' => 'No puedes eliminarte a ti mismo'
                ], 403);
            }

            // Eliminar permanentemente el usuario
            $userName = $user->USR_Name . ' ' . $user->USR_LastName;
            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'Usuario eliminado permanentemente',
                'deleted_user' => $userName
            ], 200);

        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token inválido o expirado'
            ], 401);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
