<?php

namespace App\Http\Controllers;

use App\Models\MediaImage;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class MediaImageController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $query = MediaImage::with('media');

            // Pagination
            $perPage = $request->get('per_page', 15);
            $images = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $images,
                'message' => 'Imágenes de medios obtenidas correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las imágenes de medios',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'route' => 'required|string|max:250',
                'media_id' => 'required|exists:media,id',
            ]);

            $mediaImage = MediaImage::create($validatedData);
            $mediaImage->load('media');

            return response()->json([
                'success' => true,
                'data' => $mediaImage,
                'message' => 'Imagen de medio creada correctamente'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la imagen de medio',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(MediaImage $mediaImage): JsonResponse
    {
        try {
            $mediaImage->load('media');

            return response()->json([
                'success' => true,
                'data' => $mediaImage,
                'message' => 'Imagen de medio obtenida correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la imagen de medio',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, MediaImage $mediaImage): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'route' => 'sometimes|required|string|max:250',
                'media_id' => 'sometimes|required|exists:media,id',
            ]);

            $mediaImage->update($validatedData);
            $mediaImage->load('media');

            return response()->json([
                'success' => true,
                'data' => $mediaImage,
                'message' => 'Imagen de medio actualizada correctamente'
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la imagen de medio',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(MediaImage $mediaImage): JsonResponse
    {
        try {
            $mediaImage->delete();

            return response()->json([
                'success' => true,
                'message' => 'Imagen de medio eliminada correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la imagen de medio',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getByMedia(Media $media): JsonResponse
    {
        try {
            $images = $media->images()->paginate(15);

            return response()->json([
                'success' => true,
                'data' => $images,
                'message' => 'Imágenes de medios obtenidas correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las imágenes de medios',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
