<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\{
    AuthController,
    LibroController,
    CategoriaController,
    CompraController,
    CheckoutController,
    DashboardController,
    StorefrontController,
    VentasController,
    PerfilController
};

/*
|--------------------------------------------------------------------------
| RUTAS PÚBLICAS (sin autenticación)
|--------------------------------------------------------------------------
*/
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// Catálogo público - Accesible para todos
Route::get('/libros', [StorefrontController::class, 'index']);
Route::get('/libros/{id}', [StorefrontController::class, 'show']);

// Categorías públicas (para filtros)
Route::get('/categorias', [CategoriaController::class, 'index']);

/*
|--------------------------------------------------------------------------
| RUTAS PROTEGIDAS (requieren token con Sanctum)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    // 👤 Perfil y logout
    Route::get('/user', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // 📝 Perfil de usuario
    Route::get('/perfil', [PerfilController::class, 'show']);
    Route::post('/perfil', [PerfilController::class, 'update']);
    Route::delete('/perfil/avatar', [PerfilController::class, 'deleteAvatar']);

    // 💳 Compras (usuario normal)
    Route::prefix('compras')->group(function () {
        Route::get('/estadisticas', [CompraController::class, 'estadisticas']); // ✅ DEBE IR ANTES de /{id}
        Route::get('/', [CompraController::class, 'misCompras']); // Listar mis compras
        Route::post('/', [CompraController::class, 'store']); // Crear compra
        Route::get('/{id}', [CompraController::class, 'show']); // Ver detalle de compra
        Route::patch('/{id}/cancelar', [CompraController::class, 'cancelar']); // ✅ AGREGAR ESTA RUTA
    });

    // 💰 Checkout (opcional - para pasarelas de pago)
    Route::post('/checkout', [CheckoutController::class, 'procesarPago']);

    /*
    |--------------------------------------------------------------------------
    | ADMINISTRACIÓN (solo admin)
    |--------------------------------------------------------------------------
    */
    Route::middleware('admin')->prefix('admin')->group(function () {
        
        // 📚 Libros - CRUD completo
        Route::apiResource('libros', LibroController::class);
        
        // 🏷️ Categorías - CRUD completo
        Route::apiResource('categorias', CategoriaController::class);
        
        // 📊 Dashboard
        Route::get('dashboard', [DashboardController::class, 'index']);
        
        // 💵 Ventas
        Route::get('ventas', [VentasController::class, 'index']);
        Route::get('ventas/{id}', [VentasController::class, 'show']);
        Route::patch('ventas/{id}/estado', [VentasController::class, 'updateEstado']);

        // 👥 Gestión de usuarios
        Route::post('register', [AuthController::class, 'adminRegister']); // ✅ CORREGIDO: 'register' en vez de 'usuarios/registrar'
        Route::get('usuarios', [AuthController::class, 'listarUsuarios']); // ✅ CORREGIDO: debe apuntar a 'listarUsuarios'
        Route::delete('usuarios/{id}', [AuthController::class, 'eliminarUsuario']); // ✅ CORREGIDO: debe apuntar a 'eliminarUsuario'
    });
});