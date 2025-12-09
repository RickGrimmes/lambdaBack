<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use ReCaptcha\ReCaptcha;
use Illuminate\Support\Facades\Mail;

class UserController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'USR_Name' => 'required|string|min:2|max:50',
            'USR_LastName' => 'required|string|min:2|max:50',
            'USR_Email' => 'required|email|unique:Users,USR_Email|max:255',
            'USR_Phone' => 'required|unique:Users,USR_Phone|max:10',
            'USR_Password' => 'required|string|min:8|max:255|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/',
            'USR_UserRole' => 'required|in:trainer,trainee',
            'recaptcha_token' => 'required|string'
        ], [
            'USR_Password.regex' => 'La contraseña debe contener al menos: 1 letra minúscula, 1 mayúscula y 1 número.',
            'USR_Password.min' => 'La contraseña debe tener al menos 8 caracteres.'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        // el recaptcha
        $recaptcha = new ReCaptcha(env('RECAPTCHA_SECRET_KEY'));
        $response = $recaptcha->verify($request->recaptcha_token, $request->ip());
        
        if (!$response->isSuccess()) {
            return response()->json([
                'success' => false,
                'message' => 'Verificación de seguridad fallida. Inténtalo de nuevo.'
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
            'USR_Password' => 'required|string|min:8',
            'USR_2FA_Code' => 'nullable|string|size:6',
        ], [
            'USR_Email.required' => 'El campo email es obligatorio.',
            'USR_Email.email' => 'El campo email debe ser una dirección de correo electrónico válida.',
            'USR_Password.required' => 'El campo contraseña es obligatorio.',
            'USR_Password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'USR_2FA_Code.size' => 'El código debe tener exactamente 6 caracteres.'
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

            if (!$user->USR_2FA_Enabled) {
                $token = JWTAuth::fromUser($user);

                if ($request->has('fcm_token') && $request->fcm_token) {
                    $user->USR_FCM = $request->fcm_token;
                    $user->save();
                }
                
                return response()->json([
                    'success' => true,
                    'message' => 'Inicio de sesión exitoso',
                    'data' => $user,
                    'token' => $token,
                ], 200);
            }

            if (!$request->has('USR_2FA_Code') || empty($request->USR_2FA_Code)) {
            $code = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
            
            $user->update([
                'USR_2FA_Code' => $code,
                'USR_2FA_Expires' => now()->addMinutes(5)
            ]);

            // Enviar email
            try {
                    Mail::raw("Tu código de verificación es: {$code}\n\n⏰ Expira en 5 minutos.\n\nSi no solicitaste este código, ignora este mensaje.", 
                    function($message) use ($user) {
                        $message->to($user->USR_Email)
                            ->subject('Código de verificación - Lambda App');
                    });
                } catch (\Exception $e) {
                    // na
                }

                return response()->json([
                    'success' => false, 
                    'message' => 'Código de verificación enviado a tu email',
                    'requires_2fa' => true,
                    'email_sent' => true
                ], 200); 
            }

            if (!$user->USR_2FA_Code || 
                $user->USR_2FA_Code !== $request->USR_2FA_Code || 
                now()->isAfter($user->USR_2FA_Expires)) {
                
                return response()->json([
                    'success' => false,
                    'message' => 'Código inválido o expirado. Intenta nuevamente.',
                    'requires_2fa' => true
                ], 401);
            }

            $user->update([
                'USR_2FA_Code' => null,
                'USR_2FA_Expires' => null
            ]);

            $token = JWTAuth::fromUser($user);

            if ($request->has('fcm_token') && $request->fcm_token) {
                $user->USR_FCM = $request->fcm_token;
                $user->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Inicio de sesión exitoso con 2FA',
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

    public function toggle2FA(Request $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            // Obtener el estado actual ANTES del toggle
            $currentState = (bool)$user->USR_2FA_Enabled;
            $newState = !$currentState;

            $user->update([
                'USR_2FA_Enabled' => $newState,
                'USR_2FA_Code' => null,
                'USR_2FA_Expires' => null
            ]);

            // Refrescar el modelo para obtener el valor actualizado
            $user->refresh();

            return response()->json([
                'success' => true,
                'message' => $newState ? 
                            'Autenticación 2FA habilitada' : 
                            'Autenticación 2FA deshabilitada',
                'is_2fa_enabled' => (bool)$user->USR_2FA_Enabled, // Cast a boolean
                'previous_state' => $currentState, // Para debug
                'new_state' => $newState // Para debug
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar configuración 2FA',
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
            'USR_Password' => 'sometimes|required|string|min:8|max:255|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/',
        ], [
            'USR_Password.regex' => 'La contraseña debe contener al menos: 1 letra minúscula, 1 mayúscula y 1 número.',
            'USR_Password.min' => 'La contraseña debe tener al menos 8 caracteres.'
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
                'USR_Password' => 'sometimes|required|string|min:8|max:255|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/',
                'USR_UserRole' => 'sometimes|required|in:trainer,trainee,admin'
            ], [
                'USR_Password.regex' => 'La contraseña debe contener al menos: 1 letra minúscula, 1 mayúscula y 1 número.',
                'USR_Password.min' => 'La contraseña debe tener al menos 8 caracteres.'
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
                'USR_Password' => 'required|string|min:8|max:255|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/',
                'USR_UserRole' => 'required|in:trainer,trainee',
                'USR_FCM' => 'nullable|string'
            ], [
                'USR_Password.regex' => 'La contraseña debe contener al menos: 1 letra minúscula, 1 mayúscula y 1 número.',
                'USR_Password.min' => 'La contraseña debe tener al menos 8 caracteres.'
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
