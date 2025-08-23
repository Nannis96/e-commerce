<?php

namespace App\Http\Controllers;

use App\Models\Media;
use App\Models\PriceRule;
use App\Http\Resources\PriceRuleResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Tag(
 *     name="Media",
 *     description="API endpoints for managing media"
 * )
 */
class MediaController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/media",
     *     summary="Get all media",
     *     description="Retrieve a paginated list of all media with their associated user and images",
     *     operationId="getMedia",
     *     tags={"Media"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of items per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=15, minimum=1, maximum=100)
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer", default=1, minimum=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Media retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Medios obtenidos correctamente"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="per_page", type="integer", example=15),
     *                 @OA\Property(property="total", type="integer", example=50),
     *                 @OA\Property(
     *                     property="data",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="Billboard Centro"),
     *                         @OA\Property(property="type", type="string", example="Billboard"),
     *                         @OA\Property(property="location", type="string", example="Centro Ciudad"),
     *                         @OA\Property(property="price_per_day", type="number", format="float", example=150.50),
     *                         @OA\Property(property="status", type="string", example="Available"),
     *                         @OA\Property(property="active", type="boolean", example=true),
     *                         @OA\Property(property="user_id", type="integer", example=1),
     *                         @OA\Property(property="created_at", type="string", format="date-time"),
     *                         @OA\Property(property="updated_at", type="string", format="date-time")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Error al mostrar los medios"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Media::with(['user', 'images', 'priceRules']);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $medias = $query->orderBy('id', 'desc')->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $medias,
                'message' => 'Medios obtenidos correctamente'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al mostrar los medios',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/media",
     *     summary="Create a new media",
     *     description="Create a new media entry with optional price rules assignment",
     *     operationId="createMedia",
     *     tags={"Media"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Media data",
     *         @OA\JsonContent(
     *             required={"name", "type", "location", "price_per_day"},
     *             @OA\Property(property="name", type="string", maxLength=100, example="Billboard Centro"),
     *             @OA\Property(property="type", type="string", maxLength=100, example="Billboard"),
     *             @OA\Property(property="location", type="string", maxLength=100, example="Centro Ciudad"),
     *             @OA\Property(property="price_per_day", type="number", format="float", minimum=0, maximum=999999.99, example=150.50),
     *             @OA\Property(property="status", type="string", enum={"Available", "Busy"}, example="Available"),
     *             @OA\Property(property="active", type="boolean", example=true),
     *             @OA\Property(property="user_id", type="integer", example=1, description="Optional, defaults to authenticated user"),
     *             @OA\Property(
     *                 property="price_rule_ids", 
     *                 type="array", 
     *                 description="Array of price rule IDs to assign to this media",
     *                 @OA\Items(type="integer", example=1)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Media created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Medio creado correctamente"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Billboard Centro"),
     *                 @OA\Property(property="type", type="string", example="Billboard"),
     *                 @OA\Property(property="location", type="string", example="Centro Ciudad"),
     *                 @OA\Property(property="price_per_day", type="number", format="float", example=150.50),
     *                 @OA\Property(property="status", type="string", example="Available"),
     *                 @OA\Property(property="active", type="boolean", example=true),
     *                 @OA\Property(property="user_id", type="integer", example=1),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time"),
     *                 @OA\Property(
     *                     property="price_rules",
     *                     type="array",
     *                     description="Assigned price rules",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="Descuento Navideño"),
     *                         @OA\Property(property="value_pct", type="integer", example=20),
     *                         @OA\Property(property="start_date", type="string", format="date", example="2025-12-01"),
     *                         @OA\Property(property="end_date", type="string", format="date", example="2025-12-31")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="name",
     *                     type="array",
     *                     @OA\Items(type="string", example="The name field is required.")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Error al crear el medio"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:100',
                'type' => 'required|string|max:100',
                'location' => 'required|string|max:100',
                'price_per_day' => 'required|numeric|min:0|max:999999.99',
                'status' => 'nullable|in:Available,Busy',
                'active' => 'nullable|boolean',
                'user_id' => 'nullable|exists:users,id',
                'price_rule_ids' => 'nullable|array',
                'price_rule_ids.*' => 'exists:price_rules,id'
            ], [
                'name.required' => 'El nombre del medio es obligatorio',
                'name.string' => 'El nombre del medio debe ser una cadena de texto',
                'name.max' => 'El nombre del medio no puede tener más de 100 caracteres',
                'type.required' => 'El tipo de medio es obligatorio',
                'type.string' => 'El tipo de medio debe ser una cadena de texto',
                'type.max' => 'El tipo de medio no puede tener más de 100 caracteres',
                'location.required' => 'La ubicación es obligatoria',
                'location.string' => 'La ubicación debe ser una cadena de texto',
                'location.max' => 'La ubicación no puede tener más de 100 caracteres',
                'price_per_day.required' => 'El precio por día es obligatorio',
                'price_per_day.numeric' => 'El precio por día debe ser un número',
                'price_per_day.min' => 'El precio por día debe ser mínimo 0',
                'price_per_day.max' => 'El precio por día no puede ser mayor a 999,999.99',
                'status.in' => 'El estado debe ser Available o Busy',
                'active.boolean' => 'El campo activo debe ser verdadero o falso',
                'user_id.exists' => 'El usuario seleccionado no existe',
                'price_rule_ids.array' => 'Las reglas de precio deben ser un array',
                'price_rule_ids.*.exists' => 'Una o más reglas de precio seleccionadas no existen'
            ]);

            if (!isset($validatedData['user_id'])) {
                $validatedData['user_id'] = Auth::user()->id;
            }

            // Extract price_rule_ids before creating media
            $priceRuleIds = $validatedData['price_rule_ids'] ?? [];
            unset($validatedData['price_rule_ids']);

            $media = Media::create($validatedData);

            // Assign price rules if provided
            if (!empty($priceRuleIds)) {
                $media->priceRules()->sync($priceRuleIds);
            }

            $media->load(['user', 'images', 'priceRules']);

            return response()->json([
                'success' => true,
                'data' => $media,
                'message' => 'Medio creado correctamente',
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el medio',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/media/{id}",
     *     summary="Get a specific media",
     *     description="Retrieve a specific media by its ID with user and images",
     *     operationId="getMediaById",
     *     tags={"Media"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Media ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Media retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Medio obtenido correctamente"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Billboard Centro"),
     *                 @OA\Property(property="type", type="string", example="Billboard"),
     *                 @OA\Property(property="location", type="string", example="Centro Ciudad"),
     *                 @OA\Property(property="price_per_day", type="number", format="float", example=150.50),
     *                 @OA\Property(property="status", type="string", example="Available"),
     *                 @OA\Property(property="active", type="boolean", example=true),
     *                 @OA\Property(property="user_id", type="integer", example=1),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Media not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No query results for model [App\\Models\\Media].")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Error al mostrar el medio"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function show(Media $media): JsonResponse
    {
        try {
            $media->load(['user', 'images', 'priceRules']);

            return response()->json([
                'success' => true,
                'data' => $media,
                'message' => 'Medio obtenido correctamente'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al mostrar el medio',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/media/{id}",
     *     summary="Update a media",
     *     description="Update an existing media entry with optional price rules management",
     *     operationId="updateMedia",
     *     tags={"Media"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Media ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Media data to update",
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", maxLength=100, example="Billboard Centro Actualizado"),
     *             @OA\Property(property="type", type="string", maxLength=100, example="Digital Billboard"),
     *             @OA\Property(property="location", type="string", maxLength=100, example="Centro Ciudad Norte"),
     *             @OA\Property(property="price_per_day", type="number", format="float", minimum=0, maximum=999999.99, example=175.75),
     *             @OA\Property(property="status", type="string", enum={"Available", "Busy"}, example="Busy"),
     *             @OA\Property(property="active", type="boolean", example=false),
     *             @OA\Property(property="user_id", type="integer", example=2),
     *             @OA\Property(
     *                 property="price_rule_ids", 
     *                 type="array", 
     *                 description="Array of price rule IDs to assign to this media (replaces existing ones)",
     *                 @OA\Items(type="integer", example=1)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Media updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Se actualizó correctamente el medio"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Billboard Centro Actualizado"),
     *                 @OA\Property(property="type", type="string", example="Digital Billboard"),
     *                 @OA\Property(property="location", type="string", example="Centro Ciudad Norte"),
     *                 @OA\Property(property="price_per_day", type="number", format="float", example=175.75),
     *                 @OA\Property(property="status", type="string", example="Busy"),
     *                 @OA\Property(property="active", type="boolean", example=false),
     *                 @OA\Property(property="user_id", type="integer", example=2),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Media not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No query results for model [App\\Models\\Media].")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Error de validación"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="price_per_day",
     *                     type="array",
     *                     @OA\Items(type="string", example="The price per day must be at least 0.")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Error al editar el medio"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function update(Request $request, Media $media): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'name' => 'sometimes|required|string|max:100',
                'type' => 'sometimes|required|string|max:100',
                'location' => 'sometimes|required|string|max:100',
                'price_per_day' => 'sometimes|required|numeric|min:0|max:999999.99',
                'status' => 'sometimes|nullable|in:Available,Busy',
                'active' => 'sometimes|nullable|boolean',
                'user_id' => 'sometimes|nullable|exists:users,id',
                'price_rule_ids' => 'sometimes|nullable|array',
                'price_rule_ids.*' => 'exists:price_rules,id'
            ], [
                'name.required' => 'El nombre del medio es obligatorio',
                'name.string' => 'El nombre del medio debe ser una cadena de texto',
                'name.max' => 'El nombre del medio no puede tener más de 100 caracteres',
                'type.required' => 'El tipo de medio es obligatorio',
                'type.string' => 'El tipo de medio debe ser una cadena de texto',
                'type.max' => 'El tipo de medio no puede tener más de 100 caracteres',
                'location.required' => 'La ubicación es obligatoria',
                'location.string' => 'La ubicación debe ser una cadena de texto',
                'location.max' => 'La ubicación no puede tener más de 100 caracteres',
                'price_per_day.required' => 'El precio por día es obligatorio',
                'price_per_day.numeric' => 'El precio por día debe ser un número',
                'price_per_day.min' => 'El precio por día debe ser mínimo 0',
                'price_per_day.max' => 'El precio por día no puede ser mayor a 999,999.99',
                'status.in' => 'El estado debe ser Available o Busy',
                'active.boolean' => 'El campo activo debe ser verdadero o falso',
                'user_id.exists' => 'El usuario seleccionado no existe',
                'price_rule_ids.array' => 'Las reglas de precio deben ser un array',
                'price_rule_ids.*.exists' => 'Una o más reglas de precio seleccionadas no existen'
            ]);

            // Extract price_rule_ids if present
            $priceRuleIds = null;
            if (array_key_exists('price_rule_ids', $validatedData)) {
                $priceRuleIds = $validatedData['price_rule_ids'];
                unset($validatedData['price_rule_ids']);
            }

            $media->update($validatedData);

            // Update price rules if provided
            if ($priceRuleIds !== null) {
                $media->priceRules()->sync($priceRuleIds);
            }

            $media->load(['user', 'images', 'priceRules']);

            return response()->json([
                'success' => true,
                'data' => $media,
                'message' => 'Se actualizó correctamente el medio'
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al editar el medio',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/media/{id}",
     *     summary="Delete a media",
     *     description="Delete an existing media entry",
     *     operationId="deleteMedia",
     *     tags={"Media"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Media ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Media deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Se eliminó correctamente el medio")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Media not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No query results for model [App\\Models\\Media].")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Error al eliminar el medio"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function destroy(Media $media): JsonResponse
    {
        try {
            $media->delete();

            return response()->json([
                'success' => true,
                'message' => 'Se eliminó correctamente el medio'
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el medio',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getPriceRules(Media $media): JsonResponse
    {
        try {
            $priceRules = $media->priceRules()->get();

            return response()->json([
                'success' => true,
                'data' => PriceRuleResource::collection($priceRules),
                'message' => 'Reglas de precios del medio obtenidas correctamente'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las reglas de precios del medio',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getActivePriceRules(Media $media): JsonResponse
    {
        try {
            $activePriceRules = $media->activePriceRules()->get();

            return response()->json([
                'success' => true,
                'data' => PriceRuleResource::collection($activePriceRules),
                'message' => 'Reglas de precios activas del medio obtenidas correctamente'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las reglas de precios activas del medio',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function attachPriceRules(Request $request, Media $media): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'price_rule_ids' => 'required|array',
                'price_rule_ids.*' => 'exists:price_rules,id'
            ]);

            // Attach the price rules (avoiding duplicates)
            $media->priceRules()->syncWithoutDetaching($validatedData['price_rule_ids']);

            $attachedPriceRules = $media->priceRules()->get();

            return response()->json([
                'success' => true,
                'data' => PriceRuleResource::collection($attachedPriceRules),
                'message' => 'Reglas de precios asignadas correctamente al medio'
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al asignar reglas de precios al medio',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function detachPriceRules(Request $request, Media $media): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'price_rule_ids' => 'required|array',
                'price_rule_ids.*' => 'exists:price_rules,id'
            ]);

            // Detach the specified price rules
            $media->priceRules()->detach($validatedData['price_rule_ids']);

            return response()->json([
                'success' => true,
                'message' => 'Reglas de precios desasignadas correctamente del medio'
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al desasignar reglas de precios del medio',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function syncPriceRules(Request $request, Media $media): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'price_rule_ids' => 'sometimes|array',
                'price_rule_ids.*' => 'exists:price_rules,id'
            ]);

            $priceRuleIds = $validatedData['price_rule_ids'] ?? [];
            $media->priceRules()->sync($priceRuleIds);

            $syncedPriceRules = $media->priceRules()->get();

            return response()->json([
                'success' => true,
                'data' => PriceRuleResource::collection($syncedPriceRules),
                'message' => 'Reglas de precios sincronizadas correctamente con el medio'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al sincronizar reglas de precios con el medio',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/catalog/media",
     *     summary="Get media catalog",
     *     description="Retrieve a filtered and paginated catalog of active media available for booking",
     *     operationId="getMediaCatalog",
     *     tags={"Media"},
     *     @OA\Parameter(
     *         name="name",
     *         in="query",
     *         description="Filter by media name (partial match)",
     *         required=false,
     *         @OA\Schema(type="string", example="Billboard")
     *     ),
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Filter by media type (partial match)",
     *         required=false,
     *         @OA\Schema(type="string", example="Digital")
     *     ),
     *     @OA\Parameter(
     *         name="location",
     *         in="query",
     *         description="Filter by location (partial match)",
     *         required=false,
     *         @OA\Schema(type="string", example="Centro")
     *     ),
     *     @OA\Parameter(
     *         name="price_per_day",
     *         in="query",
     *         description="Filter by maximum price per day",
     *         required=false,
     *         @OA\Schema(type="number", format="float", example=200.00)
     *     ),
     *     @OA\Parameter(
     *         name="min_price",
     *         in="query",
     *         description="Filter by minimum price per day",
     *         required=false,
     *         @OA\Schema(type="number", format="float", example=50.00)
     *     ),
     *     @OA\Parameter(
     *         name="max_price",
     *         in="query",
     *         description="Filter by maximum price per day",
     *         required=false,
     *         @OA\Schema(type="number", format="float", example=300.00)
     *     ),
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         description="Start date for availability check (YYYY-MM-DD)",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2025-09-01")
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         description="End date for availability check (YYYY-MM-DD)",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2025-09-15")
     *     ),
     *     @OA\Parameter(
     *         name="sort_by",
     *         in="query",
     *         description="Field to sort by",
     *         required=false,
     *         @OA\Schema(type="string", enum={"name", "type", "location", "price_per_day", "created_at"}, default="created_at")
     *     ),
     *     @OA\Parameter(
     *         name="sort_order",
     *         in="query",
     *         description="Sort order",
     *         required=false,
     *         @OA\Schema(type="string", enum={"asc", "desc"}, default="desc")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of items per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=15, minimum=1, maximum=100)
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer", default=1, minimum=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Media catalog retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Catálogo de medios obtenido correctamente"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="per_page", type="integer", example=15),
     *                 @OA\Property(property="total", type="integer", example=25),
     *                 @OA\Property(
     *                     property="data",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="Billboard Centro"),
     *                         @OA\Property(property="type", type="string", example="Billboard"),
     *                         @OA\Property(property="location", type="string", example="Centro Ciudad"),
     *                         @OA\Property(property="price_per_day", type="number", format="float", example=150.50),
     *                         @OA\Property(property="status", type="string", example="Available"),
     *                         @OA\Property(property="active", type="boolean", example=true),
     *                         @OA\Property(property="user_id", type="integer", example=1),
     *                         @OA\Property(property="created_at", type="string", format="date-time"),
     *                         @OA\Property(property="updated_at", type="string", format="date-time")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error (invalid date format or logic)",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Formato de fecha inválido. Use el formato YYYY-MM-DD")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Error al obtener el catálogo de medios"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function catalog(Request $request): JsonResponse
    {
        try {
            $query = Media::with(['user', 'images'])
                ->where('active', true);

            // Apply filters if provided
            if ($request->has('name') && $request->filled('name')) {
                $query->where('name', 'like', '%' . $request->get('name') . '%');
            }

            if ($request->has('type') && $request->filled('type')) {
                $query->where('type', 'like', '%' . $request->get('type') . '%');
            }

            if ($request->has('location') && $request->filled('location')) {
                $query->where('location', 'like', '%' . $request->get('location') . '%');
            }

            if ($request->has('price_per_day') && $request->filled('price_per_day')) {
                $query->where('price_per_day', '<=', $request->get('price_per_day'));
            }

            if ($request->has('min_price') && $request->filled('min_price')) {
                $query->where('price_per_day', '>=', $request->get('min_price'));
            }

            if ($request->has('max_price') && $request->filled('max_price')) {
                $query->where('price_per_day', '<=', $request->get('max_price'));
            }

            // Filter by date availability
            if ($request->has('start_date') && $request->has('end_date') && 
                $request->filled('start_date') && $request->filled('end_date')) {
                
                $startDate = $request->get('start_date');
                $endDate = $request->get('end_date');
                
                // Validate date format and logic
                try {
                    $startDateObj = \Carbon\Carbon::parse($startDate);
                    $endDateObj = \Carbon\Carbon::parse($endDate);
                    
                    if ($endDateObj->lt($startDateObj)) {
                        return response()->json([
                            'success' => false,
                            'message' => 'La fecha de fin debe ser posterior a la fecha de inicio'
                        ], 422);
                    }
                    
                    // Exclude media that are already booked in the specified date range
                    $query->whereDoesntHave('campaignItems', function($q) use ($startDate, $endDate) {
                        $q->whereHas('campaign', function($campaignQuery) use ($startDate, $endDate) {
                            $campaignQuery->where(function($dateQuery) use ($startDate, $endDate) {
                                // Check for date overlap: campaign dates overlap with requested dates
                                $dateQuery->where('start_date', '<=', $endDate)
                                    ->where('end_date', '>=', $startDate);
                            });
                        });
                    });
                    
                } catch (\Exception $e) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Formato de fecha inválido. Use el formato YYYY-MM-DD'
                    ], 422);
                }
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            
            $allowedSortFields = ['name', 'type', 'location', 'price_per_day', 'created_at'];
            if (in_array($sortBy, $allowedSortFields)) {
                $query->orderBy($sortBy, $sortOrder);
            } else {
                $query->orderBy('created_at', 'desc');
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            $medias = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $medias,
                'message' => 'Catálogo de medios obtenido correctamente'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el catálogo de medios',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
