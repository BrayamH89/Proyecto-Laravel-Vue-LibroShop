<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Compra;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VentasController extends Controller
{
    // Listar todas las ventas (para admin)
    public function index()
    {
        try {
            Log::info('Intentando cargar ventas...');
            
            $ventas = Compra::with(['user', 'libro'])
                ->orderByDesc('created_at')
                ->get()
                ->map(function ($compra) {
                    return [
                        'id' => $compra->id,
                        'user_id' => $compra->user_id,
                        'nombre' => optional($compra->user)->name ?? 'Usuario no disponible',
                        'email' => optional($compra->user)->email ?? 'N/A',
                        'libro' => optional($compra->libro)->titulo ?? 'Libro no disponible',
                        'metodo_pago' => $compra->metodo_pago ?? 'No especificado',
                        'estado_pago' => $compra->estado_pago ?? 'pendiente',
                        'total_cents' => $compra->total_cents ?? 0,
                        'created_at' => $compra->created_at ? $compra->created_at->format('Y-m-d H:i:s') : null,
                    ];
                });

            Log::info('Ventas cargadas exitosamente: ' . $ventas->count());
            
            return response()->json($ventas, 200);
            
        } catch (\Exception $e) {
            Log::error('Error en VentasController@index: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'error' => 'Error al cargar las ventas',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // Detalle de una venta
    public function show($id)
    {
        try {
            $compra = Compra::with(['user', 'libro'])->findOrFail($id);
            
            return response()->json([
                'id' => $compra->id,
                'user_id' => $compra->user_id,
                'nombre' => optional($compra->user)->name ?? 'N/A',
                'email' => optional($compra->user)->email ?? 'N/A',
                'libro' => [
                    'id' => optional($compra->libro)->id,
                    'titulo' => optional($compra->libro)->titulo ?? 'N/A',
                    'autor' => optional($compra->libro)->autor ?? 'N/A',
                    'portada_url' => optional($compra->libro)->imagen_url,
                ],
                'metodo_pago' => $compra->metodo_pago ?? 'No especificado',
                'estado_pago' => $compra->estado_pago ?? 'pendiente',
                'total_cents' => $compra->total_cents,
                'created_at' => $compra->created_at->format('Y-m-d H:i:s'),
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Error en VentasController@show: ' . $e->getMessage());
            return response()->json(['error' => 'Venta no encontrada'], 404);
        }
    }

    // Actualizar estado (patch) - CORREGIDO PARA INCLUIR 'rechazado'
    public function updateEstado(Request $request, $id)
    {
        try {
            $request->validate([
                'estado_pago' => 'required|in:pendiente,pagado,rechazado', // Cambiado de 'fallido' a 'rechazado'
            ]);

            $compra = Compra::findOrFail($id);
            $compra->estado_pago = $request->input('estado_pago');
            $compra->save();

            Log::info("Estado de compra #{$id} actualizado a: {$compra->estado_pago}");

            return response()->json([
                'message' => 'Estado actualizado exitosamente',
                'compra' => [
                    'id' => $compra->id,
                    'estado_pago' => $compra->estado_pago,
                ]
            ], 200);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Datos inválidos',
                'messages' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            Log::error('Error al actualizar estado: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error al actualizar el estado'
            ], 500);
        }
    }
}