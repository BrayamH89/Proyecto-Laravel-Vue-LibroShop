<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Listar todos los usuarios (solo admin)
     */
    public function index()
    {
        $usuarios = User::select('id', 'name', 'email', 'role_id', 'created_at')
            ->with('role:id,nombre')
            ->orderBy('id', 'desc')
            ->get();

        return response()->json($usuarios);
    }

    /**
     * Login de usuario (público)
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            $token = $user->createToken('auth_token')->plainTextToken;
            $userRoleText = ($user->role_id === 1) ? 'admin' : 'user';

            return response()->json([
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'role' => $userRoleText,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
            ]);
        }

        return response()->json(['message' => 'Credenciales inválidas'], 401);
    }

    /**
     * Registro de usuario público (role_id = 2 por defecto)
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name'=>'required|string|max:255',
            'email'=>'required|email|unique:users,email',
            'password'=>'required|confirmed|min:6'
        ], [
            'name.required' => 'El nombre es obligatorio',
            'email.required' => 'El email es obligatorio',
            'email.email' => 'El email debe ser válido',
            'email.unique' => 'Este email ya está registrado',
            'password.required' => 'La contraseña es obligatoria',
            'password.confirmed' => 'Las contraseñas no coinciden',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres',
        ]);

        $user = User::create([
            'name'=>$validated['name'],
            'email'=>$validated['email'],
            'password'=>Hash::make($validated['password']),
            'role_id'=> 2 // Usuario normal por defecto
        ]);

        $token = $user->createToken('token')->plainTextToken;
        
        return response()->json([
            'token'=>$token,
            'user'=>[
                'id'=>$user->id,
                'name'=>$user->name,
                'email'=>$user->email,
                'role'=>'user',
                'role_id' => 2
            ]
        ], 201);
    }

    /**
     * Cerrar sesión (eliminar tokens)
     */
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json(['message'=>'Sesión cerrada']);
    }

    /**
     * Obtener información del usuario autenticado
     */
    public function me(Request $request)
    {
        $user = $request->user();
        $user->load('role');
        
        $roleText = 'user';
        if ($user->role_id == 1) {
            $roleText = 'admin';
        } elseif ($user->role) {
            $roleText = $user->role->nombre;
        }
        
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $roleText,
            'role_id' => $user->role_id,
            'avatar' => $user->avatar_url ?? null
        ]);
    }

    /**
     * Registrar usuario desde el admin (permite elegir rol)
     * Solo accesible por administradores
     */
    public function adminRegister(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|confirmed|min:6',
            'role_id' => 'required|in:1,2',
        ], [
            'name.required' => 'El nombre es obligatorio',
            'email.required' => 'El email es obligatorio',
            'email.email' => 'El email debe ser válido',
            'email.unique' => 'Este email ya está registrado',
            'password.required' => 'La contraseña es obligatoria',
            'password.confirmed' => 'Las contraseñas no coinciden',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres',
            'role_id.required' => 'Debes seleccionar un rol',
            'role_id.in' => 'El rol seleccionado no es válido',
        ]);

        try {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role_id' => $validated['role_id'],
            ]);

            $user->load('role');
            $roleText = $user->role_id == 1 ? 'admin' : 'user';

            \Log::info('Nuevo usuario registrado por admin', [
                'user_id' => $user->id,
                'email' => $user->email,
                'role' => $roleText,
                'registered_by' => $request->user()->id,
            ]);

            return response()->json([
                'message' => 'Usuario registrado exitosamente',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $roleText,
                    'role_id' => $user->role_id,
                ]
            ], 201);

        } catch (\Exception $e) {
            \Log::error('Error al registrar usuario desde admin: ' . $e->getMessage());
            
            return response()->json([
                'message' => 'Error al registrar el usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar usuario (solo admin)
     */
    public function destroy($id)
    {
        try {
            $user = User::findOrFail($id);
            
            // Evitar que el admin se elimine a sí mismo
            if ($user->id === auth()->id()) {
                return response()->json([
                    'message' => 'No puedes eliminar tu propia cuenta'
                ], 403);
            }

            // Evitar eliminar al único admin
            if ($user->role_id == 1) {
                $adminCount = User::where('role_id', 1)->count();
                if ($adminCount <= 1) {
                    return response()->json([
                        'message' => 'No puedes eliminar el único administrador del sistema'
                    ], 403);
                }
            }

            $userName = $user->name;
            $user->delete();

            \Log::info('Usuario eliminado por admin', [
                'deleted_user_id' => $id,
                'deleted_user_name' => $userName,
                'deleted_by' => auth()->id(),
            ]);

            return response()->json([
                'message' => 'Usuario eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error al eliminar usuario: ' . $e->getMessage());
            
            return response()->json([
                'message' => 'Error al eliminar el usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}