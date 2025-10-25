<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Libro;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LibroController extends Controller
{
    // 📚 Listar todos los libros
    public function index(Request $request)
    {
        $libros = Libro::with('categorias')->latest()->paginate(10);

        $libros->getCollection()->transform(function ($libro) {
            // ✅ Asegurarse de que las categorías se procesen correctamente
            $categoriasNombres = $libro->categorias->pluck('nombre')->toArray();
            
            return [
                'id' => $libro->id,
                'titulo' => $libro->titulo,
                'autor' => $libro->autor,
                'descripcion' => $libro->descripcion,
                'precio_cents' => $libro->precio_cents,
                'precio' => number_format($libro->precio_cents / 100, 2),
                'moneda' => $libro->moneda,
                'imagen_path' => $libro->imagen_path,
                'portada_url' => $libro->imagen_url, // Usar el accessor
                'contenido_path' => $libro->contenido_path,
                'archivo_url' => $libro->contenido_url, // Usar el accessor
                'categoria' => !empty($categoriasNombres) ? implode(', ', $categoriasNombres) : 'Sin categoría',
                'categorias' => $libro->categorias, // Array completo de categorías
                'created_at' => $libro->created_at,
                'updated_at' => $libro->updated_at,
            ];
        });

        return response()->json($libros);
    }

    // 📖 Mostrar libro individual
    public function show(Libro $libro)
    {
        $libro->load('categorias');

        return response()->json([
            'id'            => $libro->id,
            'titulo'        => $libro->titulo,
            'autor'         => $libro->autor,
            'descripcion'   => $libro->descripcion,
            'precio'        => $libro->precio_cents / 100,
            'portada_url'   => $libro->imagen_url,
            'archivo_url'   => $libro->contenido_url,
            'categorias'    => $libro->categorias->pluck('id'),
            'categorias_nombres' => $libro->categorias->pluck('nombre')->implode(', '),
        ]);
    }

    // ➕ Crear nuevo libro
    public function store(Request $request)
    {
        $data = $request->validate([
            'titulo'      => 'required|string|max:255',
            'autor'       => 'nullable|string|max:255',
            'descripcion' => 'nullable|string',
            'precio'      => 'required|numeric|min:0',
            'imagen'      => 'nullable|image|max:2048',
            'pdf'         => 'nullable|mimes:pdf,epub,docx,txt|max:10240',
            'categorias'  => 'array'
        ]);

        $imagenPath = $request->file('imagen')?->store('libros/imagenes', 'public');
        $archivoPath = $request->file('pdf')?->store('libros/archivos', 'public');

        $libro = Libro::create([
            'titulo'         => $data['titulo'],
            'autor'          => $data['autor'] ?? null,
            'descripcion'    => $data['descripcion'] ?? null,
            'precio_cents'   => intval(round($data['precio'] * 100)),
            'imagen_path'    => $imagenPath,
            'contenido_path' => $archivoPath,
            'moneda'         => 'COP',
        ]);

        // ✅ Sincronizar categorías
        if (isset($data['categorias']) && !empty($data['categorias'])) {
            $libro->categorias()->sync($data['categorias']);
        }

        // Cargar las categorías para devolverlas en la respuesta
        $libro->load('categorias');

        return response()->json(['ok' => true, 'libro' => $libro], 201);
    }

    // ✏️ Actualizar libro existente
    public function update(Request $request, Libro $libro)
    {
        $data = $request->validate([
            'titulo'      => 'required|string|max:255',
            'autor'       => 'nullable|string|max:255',
            'descripcion' => 'nullable|string',
            'precio'      => 'required|numeric|min:0',
            'imagen'      => 'nullable|image|max:2048',
            'pdf'         => 'nullable|mimes:pdf,epub,docx,txt|max:10240',
            'categorias'  => 'array'
        ]);

        // 🖼️ Imagen nueva
        if ($request->hasFile('imagen')) {
            if ($libro->imagen_path) Storage::disk('public')->delete($libro->imagen_path);
            $libro->imagen_path = $request->file('imagen')->store('libros/imagenes', 'public');
        }

        // 📁 Archivo nuevo
        if ($request->hasFile('pdf')) {
            if ($libro->contenido_path) Storage::disk('public')->delete($libro->contenido_path);
            $libro->contenido_path = $request->file('pdf')->store('libros/archivos', 'public');
        }

        $libro->update([
            'titulo'        => $data['titulo'],
            'autor'         => $data['autor'] ?? null,
            'descripcion'   => $data['descripcion'] ?? null,
            'precio_cents'  => intval(round($data['precio'] * 100)),
        ]);

        // ✅ Sincronizar categorías
        if (isset($data['categorias'])) {
            $libro->categorias()->sync($data['categorias']);
        } else {
            // Si no se enviaron categorías, eliminar todas
            $libro->categorias()->detach();
        }

        $libro->load('categorias');
        return response()->json(['ok' => true, 'libro' => $libro]);
    }

    // 🗑️ Eliminar libro
    public function destroy(Libro $libro)
    {
        if ($libro->imagen_path) Storage::disk('public')->delete($libro->imagen_path);
        if ($libro->contenido_path) Storage::disk('public')->delete($libro->contenido_path);
        $libro->categorias()->detach();
        $libro->delete();

        return response()->json(['ok' => true, 'message' => 'Libro eliminado exitosamente']);
    }
}