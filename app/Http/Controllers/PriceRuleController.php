<?php

namespace App\Http\Controllers;

use App\Models\PriceRule;
use App\Http\Requests\StorePriceRuleRequest;
use App\Http\Requests\UpdatePriceRuleRequest;
use App\Http\Resources\PriceRuleResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Tag(
 *     name="Price Rules",
 *     description="API endpoints for managing price rules"
 * )
 */
class PriceRuleController extends Controller
{

    /**
     * @OA\Get(
     *     path="/api/v1/price-rules",
     *     summary="Get all price rules",
     *     description="Retrieve a paginated list of all price rules with their status and remaining days",
     *     operationId="getPriceRules",
     *     tags={"Price Rules"},
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
     *         description="Price rules retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Reglas de precios obtenidas correctamente"),
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
     *                         @OA\Property(property="start_date", type="string", format="date", example="2025-09-01"),
     *                         @OA\Property(property="end_date", type="string", format="date", example="2025-12-31"),
     *                         @OA\Property(property="name", type="string", example="Navideño"),
     *                         @OA\Property(property="value_pct", type="integer", example=20),
     *                         @OA\Property(property="is_active", type="boolean", example=true),
     *                         @OA\Property(property="days_remaining", type="integer", example=120),
     *                         @OA\Property(property="created_at", type="string", format="date-time"),
     *                         @OA\Property(property="updated_at", type="string", format="date-time"),
     *                         @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true)
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
     *             @OA\Property(property="message", type="string", example="Error al obtener las reglas de precios"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = PriceRule::query();

            $priceRules = $query->orderBy('id', 'desc')->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => PriceRuleResource::collection($priceRules),
                'message' => 'Reglas de precios obtenidas correctamente'
            ], 200);
        
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las reglas de precios',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/price-rules",
     *     summary="Create a new price rule",
     *     description="Create a new price rule with discount percentage and validity dates",
     *     operationId="createPriceRule",
     *     tags={"Price Rules"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Price rule data",
     *         @OA\JsonContent(
     *             required={"start_date", "end_date", "name", "value_pct"},
     *             @OA\Property(property="start_date", type="string", format="date", example="2025-09-01", description="Start date (must be today or future)"),
     *             @OA\Property(property="end_date", type="string", format="date", example="2025-12-31", description="End date (must be equal or after start_date)"),
     *             @OA\Property(property="name", type="string", maxLength=100, example="Navideño", description="Unique name for the price rule"),
     *             @OA\Property(property="value_pct", type="integer", minimum=0, maximum=100, example=20, description="Discount percentage (0-100)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Price rule created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Regla de precio creada correctamente"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="start_date", type="string", format="date", example="2025-09-01"),
     *                 @OA\Property(property="end_date", type="string", format="date", example="2025-12-31"),
     *                 @OA\Property(property="name", type="string", example="Navideño"),
     *                 @OA\Property(property="value_pct", type="integer", example=20),
     *                 @OA\Property(property="is_active", type="boolean", example=false),
     *                 @OA\Property(property="days_remaining", type="integer", example=120),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time"),
     *                 @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true)
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
     *                     @OA\Items(type="string", example="Ya existe una regla de precio con este nombre")
     *                 ),
     *                 @OA\Property(
     *                     property="start_date",
     *                     type="array",
     *                     @OA\Items(type="string", example="La fecha de inicio debe ser igual o posterior a hoy")
     *                 ),
     *                 @OA\Property(
     *                     property="end_date",
     *                     type="array",
     *                     @OA\Items(type="string", example="La fecha de fin debe ser igual o posterior a la fecha de inicio")
     *                 ),
     *                 @OA\Property(
     *                     property="value_pct",
     *                     type="array",
     *                     @OA\Items(type="string", example="El porcentaje no puede ser mayor a 100")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Error al crear la regla de precio"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function store(StorePriceRuleRequest $request): JsonResponse
    {
        try {
            $priceRule = PriceRule::create($request->validated());

            return response()->json([
                'success' => true,
                'data' => new PriceRuleResource($priceRule),
                'message' => 'Regla de precio creada correctamente'
            ], 201);
        
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la regla de precio',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/price-rules/{id}",
     *     summary="Get a specific price rule",
     *     description="Retrieve a specific price rule by its ID with status and remaining days information",
     *     operationId="getPriceRuleById",
     *     tags={"Price Rules"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Price rule ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Price rule retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Regla de precio obtenida correctamente"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="start_date", type="string", format="date", example="2025-09-01"),
     *                 @OA\Property(property="end_date", type="string", format="date", example="2025-12-31"),
     *                 @OA\Property(property="name", type="string", example="Navideño"),
     *                 @OA\Property(property="value_pct", type="integer", example=20),
     *                 @OA\Property(property="is_active", type="boolean", example=true),
     *                 @OA\Property(property="days_remaining", type="integer", example=120),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time"),
     *                 @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true)
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
     *         description="Price rule not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No query results for model [App\\Models\\PriceRule].")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Error al obtener la regla de precio"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function show(PriceRule $priceRule): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => new PriceRuleResource($priceRule),
                'message' => 'Regla de precio obtenida correctamente'
            ], 200);
        
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la regla de precio',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/price-rules/{id}",
     *     summary="Update a price rule",
     *     description="Update an existing price rule with new data",
     *     operationId="updatePriceRule",
     *     tags={"Price Rules"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Price rule ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Price rule data to update",
     *         @OA\JsonContent(
     *             @OA\Property(property="start_date", type="string", format="date", example="2025-09-15", description="New start date"),
     *             @OA\Property(property="end_date", type="string", format="date", example="2025-12-31", description="New end date (must be equal or after start_date)"),
     *             @OA\Property(property="name", type="string", maxLength=100, example="Navideño Actualizado", description="New unique name for the price rule"),
     *             @OA\Property(property="value_pct", type="integer", minimum=0, maximum=100, example=25, description="New discount percentage (0-100)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Price rule updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Regla de precio actualizada correctamente"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="start_date", type="string", format="date", example="2025-09-15"),
     *                 @OA\Property(property="end_date", type="string", format="date", example="2025-12-31"),
     *                 @OA\Property(property="name", type="string", example="Navideño Actualizado"),
     *                 @OA\Property(property="value_pct", type="integer", example=25),
     *                 @OA\Property(property="is_active", type="boolean", example=false),
     *                 @OA\Property(property="days_remaining", type="integer", example=120),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time"),
     *                 @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true)
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
     *         description="Price rule not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No query results for model [App\\Models\\PriceRule].")
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
     *                     @OA\Items(type="string", example="Ya existe una regla de precio con este nombre")
     *                 ),
     *                 @OA\Property(
     *                     property="end_date",
     *                     type="array",
     *                     @OA\Items(type="string", example="La fecha de fin debe ser igual o posterior a la fecha de inicio")
     *                 ),
     *                 @OA\Property(
     *                     property="value_pct",
     *                     type="array",
     *                     @OA\Items(type="string", example="El porcentaje no puede ser mayor a 100")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Error al actualizar la regla de precio"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function update(UpdatePriceRuleRequest $request, PriceRule $priceRule): JsonResponse
    {
        try {
            $priceRule->update($request->validated());

            return response()->json([
                'success' => true,
                'data' => new PriceRuleResource($priceRule->fresh()),
                'message' => 'Regla de precio actualizada correctamente'
            ], 200);
        
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la regla de precio',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/price-rules/{id}",
     *     summary="Delete a price rule",
     *     description="Delete an existing price rule (soft delete)",
     *     operationId="deletePriceRule",
     *     tags={"Price Rules"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Price rule ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Price rule deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Regla de precio eliminada correctamente")
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
     *         description="Price rule not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No query results for model [App\\Models\\PriceRule].")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Error al eliminar la regla de precio"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function destroy(PriceRule $priceRule): JsonResponse
    {
        try {
            $priceRule->delete();
        
            return response()->json([
                'success' => true,
                'message' => 'Regla de precio eliminada correctamente'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la regla de precio',
                'error' => $e->getMessage()
            ], 500);
        } 
    }
}
