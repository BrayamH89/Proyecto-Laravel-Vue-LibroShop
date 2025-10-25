<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Compra;
use App\Models\Libro;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            // Ventas de hoy en centavos
            $ventasHoyCents = Compra::whereDate('created_at', today())->sum('total_cents') ?? 0;
            
            // Total de todas las ventas
            $totalVentas = Compra::sum('total_cents') ?? 0;
            
            // Total de libros
            $totalLibros = Libro::count();

            // Últimas 20 ventas con relaciones
            $ventas = Compra::with(['user', 'libro'])
                ->latest()
                ->take(20)
                ->get()
                ->map(function ($compra) {
                    return [
                        'id' => $compra->id,
                        'libro' => optional($compra->libro)->titulo ?? 'N/A',
                        'cliente' => optional($compra->user)->name ?? 'N/A',
                        'cantidad' => 1,
                        'total' => $compra->total_cents ?? 0,
                        'fecha' => $compra->created_at->format('Y-m-d H:i'),
                        'estado' => $compra->estado_pago ?? 'pendiente',
                    ];
                });

            return response()->json([
                'ventasHoy' => $ventasHoyCents,
                'totalVentas' => $totalVentas,
                'totalLibros' => $totalLibros,
                'ventas' => $ventas,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error en DashboardController: ' . $e->getMessage());
            return response()->json([
                'ventasHoy' => 0,
                'totalVentas' => 0,
                'totalLibros' => 0,
                'ventas' => [],
            ]);
        }
    }
}