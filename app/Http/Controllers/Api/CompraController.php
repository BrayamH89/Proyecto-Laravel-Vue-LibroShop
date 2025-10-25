<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Compra;
use App\Models\Libro;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class CompraController extends Controller
{
    /**
     * Crear una nueva compra
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // Validación
        $validator = Validator::make($request->all(), [
            'libro_id' => 'required|exists:libros,id',
            'cantidad' => 'nullable|integer|min:1|max:10',
            'metodo_pago' => 'nullable|string|in:transferencia,tarjeta,paypal,efectivo'
        ], [
            'libro_id.required' => 'Debes seleccionar un libro',
            'libro_id.exists' => 'El libro seleccionado no existe',
            'cantidad.integer' => 'La cantidad debe ser un número entero',
            'cantidad.min' => 'La cantidad mínima es 1',
            'cantidad.max' => 'La cantidad máxima es 10',
            'metodo_pago.in' => 'Método de pago no válido'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Datos inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Obtener el libro
            $libro = Libro::with('categorias')->findOrFail($request->libro_id);
            $cantidad = $request->get('cantidad', 1);
            $metodoPago = $request->get('metodo_pago', 'no_especificado');

            // Verificar stock si tienes ese campo
            if (isset($libro->stock) && $libro->stock < $cantidad) {
                return response()->json([
                    'message' => 'Stock insuficiente. Solo quedan ' . $libro->stock . ' unidades disponibles.'
                ], 400);
            }

            // Calcular total
            $precioUnitario = $libro->precio_cents;
            $totalCents = $precioUnitario * $cantidad;

            // Crear la compra
            $compra = Compra::create([
                'user_id' => auth()->id(),
                'libro_id' => $libro->id,
                'cantidad' => $cantidad,
                'precio_unitario_cents' => $precioUnitario,
                'total_cents' => $totalCents,
                'moneda' => $libro->moneda ?? 'COP',
                'metodo_pago' => $metodoPago,
                'estado' => 'completada', // Cambia a 'pendiente' si usas pasarelas de pago
            ]);

            // Actualizar stock si corresponde
            if (isset($libro->stock)) {
                $libro->decrement('stock', $cantidad);
            }

            DB::commit();

            // Log de auditoría
            Log::info('Nueva compra registrada', [
                'compra_id' => $compra->id,
                'user_id' => auth()->id(),
                'libro_id' => $libro->id,
                'cantidad' => $cantidad,
                'total_cents' => $totalCents
            ]);

            // Cargar relaciones para la respuesta
            $compra->load(['libro.categorias', 'user']);

            return response()->json([
                'message' => '¡Compra realizada exitosamente!',
                'compra' => [
                    'id' => $compra->id,
                    'cantidad' => $compra->cantidad,
                    'total_cents' => $compra->total_cents,
                    'moneda' => $compra->moneda,
                    'metodo_pago' => $compra->metodo_pago,
                    'estado' => $compra->estado,
                    'created_at' => $compra->created_at->format('Y-m-d H:i:s'),
                    'libro' => [
                        'id' => $libro->id,
                        'titulo' => $libro->titulo,
                        'autor' => $libro->autor,
                        'imagen_url' => $libro->imagen_url,
                        'precio_cents' => $libro->precio_cents,
                    ]
                ]
            ], 201);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Libro no encontrado'
            ], 404);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error al procesar compra', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'libro_id' => $request->libro_id
            ]);
            
            return response()->json([
                'message' => 'Error al procesar la compra. Por favor intenta nuevamente.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Listar las compras del usuario autenticado
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function misCompras(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $estado = $request->get('estado'); // Filtro opcional por estado

            $query = Compra::with(['libro.categorias'])
                ->where('user_id', auth()->id());

            // Filtrar por estado si se proporciona
            if ($estado) {
                $query->where('estado', $estado);
            }

            $compras = $query->latest()->paginate($perPage);

            // Formatear la respuesta
            $compras->getCollection()->transform(function($compra) {
                return [
                    'id' => $compra->id,
                    'precio_unitario_cents' => $compra->precio_unitario_cents,
                    'total_cents' => $compra->total_cents,
                    'moneda' => $compra->moneda,
                    'metodo_pago' => $compra->metodo_pago ?? 'No especificado',
                    'estado' => $compra->estado,
                    'created_at' => $compra->created_at->format('Y-m-d H:i:s'),
                    'libro' => [
                        'id' => $compra->libro->id,
                        'titulo' => $compra->libro->titulo,
                        'autor' => $compra->libro->autor ?? 'Autor desconocido',
                        'imagen_url' => $compra->libro->imagen_url,
                        'categorias' => $compra->libro->categorias->pluck('nombre')->toArray()
                    ]
                ];
            });

            return response()->json($compras, 200);

        } catch (\Exception $e) {
            Log::error('Error al cargar compras del usuario', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'message' => 'Error al cargar tus compras',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Mostrar detalles de una compra específica
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $compra = Compra::with(['libro.categorias', 'user'])
                ->where('user_id', auth()->id())
                ->findOrFail($id);

            return response()->json([
                'id' => $compra->id,
                'precio_unitario_cents' => $compra->precio_unitario_cents,
                'total_cents' => $compra->total_cents,
                'moneda' => $compra->moneda,
                'metodo_pago' => $compra->metodo_pago ?? 'No especificado',
                'estado' => $compra->estado,
                'created_at' => $compra->created_at->format('Y-m-d H:i:s'),
                'libro' => [
                    'id' => $compra->libro->id,
                    'titulo' => $compra->libro->titulo,
                    'autor' => $compra->libro->autor ?? 'Autor desconocido',
                    'descripcion' => $compra->libro->descripcion,
                    'imagen_url' => $compra->libro->imagen_url,
                    'precio_cents' => $compra->libro->precio_cents,
                    'categorias' => $compra->libro->categorias
                ]
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Compra no encontrada o no tienes permiso para verla'
            ], 404);
            
        } catch (\Exception $e) {
            Log::error('Error al cargar detalle de compra', [
                'error' => $e->getMessage(),
                'compra_id' => $id,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'message' => 'Error al cargar la compra',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de compras del usuario
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function estadisticas()
    {
        try {
            $userId = auth()->id();

            $stats = [
                'total_compras' => Compra::where('user_id', $userId)->count(),
                'total_gastado_cents' => Compra::where('user_id', $userId)->sum('total_cents'),
                'compras_completadas' => Compra::where('user_id', $userId)
                    ->where('estado', 'completada')
                    ->count(),
                'compras_pendientes' => Compra::where('user_id', $userId)
                    ->where('estado', 'pendiente')
                    ->count(),
                'libro_mas_comprado' => Compra::where('user_id', $userId)
                    ->select('libro_id', DB::raw('COUNT(*) as total'))
                    ->groupBy('libro_id')
                    ->orderByDesc('total')
                    ->with('libro:id,titulo,autor')
                    ->first(),
            ];

            return response()->json($stats, 200);

        } catch (\Exception $e) {
            Log::error('Error al obtener estadísticas', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'message' => 'Error al obtener estadísticas',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Cancelar una compra (solo si está pendiente)
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancelar($id)
    {
        try {
            $compra = Compra::where('user_id', auth()->id())
                ->where('id', $id)
                ->firstOrFail();

            if ($compra->estado !== 'pendiente') {
                return response()->json([
                    'message' => 'Solo se pueden cancelar compras pendientes'
                ], 400);
            }

            DB::beginTransaction();

            // Restaurar stock si existe
            if (isset($compra->libro->stock)) {
                $compra->libro->increment('stock', $compra->cantidad);
            }

            $compra->estado = 'cancelada';
            $compra->save();

            DB::commit();

            Log::info('Compra cancelada', [
                'compra_id' => $compra->id,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'message' => 'Compra cancelada exitosamente',
                'compra' => $compra
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Compra no encontrada'
            ], 404);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error al cancelar compra', [
                'error' => $e->getMessage(),
                'compra_id' => $id,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'message' => 'Error al cancelar la compra',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}