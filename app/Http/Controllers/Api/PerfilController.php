<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class PerfilController extends Controller
{
    /**
     * Obtener información del perfil del usuario autenticado
     */
    public function show(Request $request)
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
            'avatar' => $user->avatar ? url('storage/' . $user->avatar) : null,
            'created_at' => $user->created_at->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Actualizar perfil del usuario
     */
    public function update(Request $request)
    {
        $user = $request->user();

        // Validación
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => ['nullable', 'confirmed', Password::min(6)],
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Max 2MB
        ], [
            'name.required' => 'El nombre es obligatorio',
            'email.required' => 'El email es obligatorio',
            'email.email' => 'El email debe ser válido',
            'email.unique' => 'Este email ya está registrado',
            'password.confirmed' => 'Las contraseñas no coinciden',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres',
            'avatar.image' => 'El archivo debe ser una imagen',
            'avatar.mimes' => 'La imagen debe ser jpg, jpeg, png o gif',
            'avatar.max' => 'La imagen no debe superar 2MB',
        ]);

        try {
            // Actualizar nombre y email
            $user->name = $validated['name'];
            $user->email = $validated['email'];

            // Actualizar contraseña si se proporcionó
            if ($request->filled('password')) {
                $user->password = Hash::make($validated['password']);
            }

            // Manejar avatar
            if ($request->hasFile('avatar')) {
                // Eliminar avatar anterior si existe
                if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                    Storage::disk('public')->delete($user->avatar);
                }

                // Guardar nuevo avatar
                $avatarPath = $request->file('avatar')->store('avatars', 'public');
                $user->avatar = $avatarPath;
            }

            $user->save();

            // Recargar relación con rol
            $user->load('role');
            
            $roleText = 'user';
            if ($user->role_id == 1) {
                $roleText = 'admin';
            } elseif ($user->role) {
                $roleText = $user->role->nombre;
            }

            return response()->json([
                'message' => 'Perfil actualizado exitosamente',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $roleText,
                    'role_id' => $user->role_id,
                    'avatar' => $user->avatar ? url('storage/' . $user->avatar) : null,
                ]
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Error al actualizar perfil: ' . $e->getMessage());
            
            return response()->json([
                'message' => 'Error al actualizar el perfil',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar avatar del usuario
     */
    public function deleteAvatar(Request $request)
    {
        $user = $request->user();

        try {
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
                $user->avatar = null;
                $user->save();

                return response()->json([
                    'message' => 'Avatar eliminado exitosamente'
                ], 200);
            }

            return response()->json([
                'message' => 'No hay avatar para eliminar'
            ], 404);

        } catch (\Exception $e) {
            \Log::error('Error al eliminar avatar: ' . $e->getMessage());
            
            return response()->json([
                'message' => 'Error al eliminar el avatar'
            ], 500);
        }
    }
}