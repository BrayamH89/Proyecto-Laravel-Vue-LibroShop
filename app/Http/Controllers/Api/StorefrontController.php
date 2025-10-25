<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Libro;
use App\Models\Categoria;

class StorefrontController extends Controller 
{
    /**
     * Listar libros con filtros y paginación
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request) 
    {
        try {
            $query = Libro::with('categorias'); // Eager loading de categorías

            // Filtro por precio mínimo
            if ($request->filled('min')) {
                $minCents = intval($request->min) * 100;
                $query->where('precio_cents', '>=', $minCents);
            }

            // Filtro por precio máximo
            if ($request->filled('max')) {
                $maxCents = intval($request->max) * 100;
                $query->where('precio_cents', '<=', $maxCents);
            }

            // Filtro por categoría (usando slug)
            if ($request->filled('categoria')) {
                $slug = $request->categoria;
                $query->whereHas('categorias', function($q) use ($slug) {
                    $q->where('slug', $slug);
                });
            }

            // Búsqueda por título o autor (opcional)
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('titulo', 'LIKE', "%{$search}%")
                      ->orWhere('autor', 'LIKE', "%{$search}%");
                });
            }

            // Ordenamiento
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Paginación
            $perPage = $request->get('per_page', 12);
            $libros = $query->paginate($perPage);

            return response()->json($libros, 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al cargar los libros',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar detalles de un libro específico
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id) 
    {
        try {
            $libro = Libro::with('categorias')->findOrFail($id);

            return response()->json($libro, 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Libro no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al cargar el libro',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}