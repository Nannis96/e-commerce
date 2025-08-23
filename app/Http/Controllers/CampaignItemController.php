<?php

namespace App\Http\Controllers;

use App\Models\CampaignItem;
use App\Models\Campaign;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * @OA\Tag(
 *     name="Campaign Items",
 *     description="API endpoints for managing campaign media items"
 * )
 */
class CampaignItemController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/campaign-items",
     *     summary="Get all campaign items",
     *     description="Retrieve a paginated list of all campaign items with their campaign and media information",
     *     operationId="getCampaignItems",
     *     tags={"Campaign Items"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of items per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=10, minimum=1, maximum=100)
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
     *         description="Campaign items retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="per_page", type="integer", example=10),
     *                 @OA\Property(property="total", type="integer", example=25),
     *                 @OA\Property(
     *                     property="data",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="range", type="string", example="Morning"),
     *                         @OA\Property(property="days", type="integer", nullable=true, example=7),
     *                         @OA\Property(property="price_per_day", type="number", format="float", example=150.50),
     *                         @OA\Property(property="subtotal", type="number", format="float", example=1053.50),
     *                         @OA\Property(property="provider_status", type="string", enum={"Pending", "Accepted", "Rejected"}, example="Pending"),
     *                         @OA\Property(property="description", type="string", nullable=true, example="Rechazado por conflicto de horarios"),
     *                         @OA\Property(property="campaign_id", type="integer", example=1),
     *                         @OA\Property(property="media_id", type="integer", example=1),
     *                         @OA\Property(property="created_at", type="string", format="date-time"),
     *                         @OA\Property(property="updated_at", type="string", format="date-time"),
     *                         @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true),
     *                         @OA\Property(
     *                             property="campaign",
     *                             type="object",
     *                             @OA\Property(property="id", type="integer", example=1),
     *                             @OA\Property(property="name", type="string", example="Campaña Navideña")
     *                         ),
     *                         @OA\Property(
     *                             property="media",
     *                             type="object",
     *                             @OA\Property(property="id", type="integer", example=1),
     *                             @OA\Property(property="name", type="string", example="Billboard Centro"),
     *                             @OA\Property(property="type", type="string", example="Billboard")
     *                         )
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
     *             @OA\Property(property="message", type="string", example="Error al mostrar los medios de la campaña"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function index()
    {
        try {
            $campaign_items = CampaignItem::with(['campaign', 'media'])
                ->orderBy('id', 'desc')
                ->paginate(10);

            return response()->json([
                'success' => true,
                'data'    => $campaign_items
            ], 200);
        
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al mostrar los medios de la campaña',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/campaign-items",
     *     summary="Create a new campaign item",
     *     description="Add a media item to a campaign with automatic price calculation, discount application, and availability validation. Prevents double booking and calculates pricing based on active price rules",
     *     operationId="createCampaignItem",
     *     tags={"Campaign Items"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Campaign item data",
     *         @OA\JsonContent(
     *             required={"range", "campaign_id", "media_id"},
     *             @OA\Property(property="range", type="string", maxLength=100, example="Morning", description="Time range for the media display"),
     *             @OA\Property(property="campaign_id", type="integer", example=1, description="ID of the campaign"),
     *             @OA\Property(property="media_id", type="integer", example=1, description="ID of the media to add to campaign")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Campaign item created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="El medio de la campaña fue creado correctamente"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="range", type="string", example="Morning"),
     *                 @OA\Property(property="price_per_day", type="number", format="float", example=150.50),
     *                 @OA\Property(property="subtotal", type="number", format="float", example=1053.50),
     *                 @OA\Property(property="provider_status", type="string", example="Pending"),
     *                 @OA\Property(property="campaign_id", type="integer", example=1),
     *                 @OA\Property(property="media_id", type="integer", example=1),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time"),
     *                 @OA\Property(
     *                     property="campaign",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Campaña Navideña"),
     *                     @OA\Property(property="start_date", type="string", format="date", example="2025-12-01"),
     *                     @OA\Property(property="end_date", type="string", format="date", example="2025-12-31")
     *                 ),
     *                 @OA\Property(
     *                     property="media",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Billboard Centro"),
     *                     @OA\Property(property="price_per_day", type="number", format="float", example=200.00)
     *                 ),
     *                 @OA\Property(
     *                     property="calculation_details",
     *                     type="object",
     *                     @OA\Property(property="total_days", type="integer", example=7),
     *                     @OA\Property(property="base_price_per_day", type="number", format="float", example=200.00),
     *                     @OA\Property(property="final_price_per_day", type="number", format="float", example=150.50),
     *                     @OA\Property(
     *                         property="applied_discounts",
     *                         type="array",
     *                         @OA\Items(
     *                             @OA\Property(property="rule_name", type="string", example="Descuento Navideño"),
     *                             @OA\Property(property="discount_percentage", type="integer", example=25),
     *                             @OA\Property(property="discount_amount", type="number", format="float", example=49.50)
     *                         )
     *                     ),
     *                     @OA\Property(property="total_discount_amount", type="number", format="float", example=49.50),
     *                     @OA\Property(property="subtotal_calculation", type="string", example="150.5 x 7 días = 1053.5")
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
     *         description="Validation or business logic error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Este medio ya está asignado a la campaña seleccionada"),
     *             @OA\AdditionalProperties(
     *                 oneOf={
     *                     @OA\Schema(
     *                         @OA\Property(property="message", type="string", example="Este medio no está disponible en las fechas de la campaña seleccionada. Ya está asignado a otra campaña en ese período.")
     *                     ),
     *                     @OA\Schema(
     *                         @OA\Property(property="message", type="string", example="The given data was invalid."),
     *                         @OA\Property(
     *                             property="errors",
     *                             type="object",
     *                             @OA\Property(
     *                                 property="campaign_id",
     *                                 type="array",
     *                                 @OA\Items(type="string", example="La campaña seleccionada no existe")
     *                             ),
     *                             @OA\Property(
     *                                 property="media_id",
     *                                 type="array",
     *                                 @OA\Items(type="string", example="El medio seleccionado no existe")
     *                             )
     *                         )
     *                     )
     *                 }
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Error al crear el medio de la campaña"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        try {
            // Validaciones
            $validatedData = $request->validate([
                'range' => 'required|string|max:100',
                'campaign_id' => 'required|exists:campaigns,id',
                'media_id' => 'required|exists:media,id'
            ], [
                'range.required' => 'El rango es obligatorio',
                'range.string' => 'El rango debe ser una cadena de texto',
                'range.max' => 'El rango no puede tener más de 100 caracteres',
                'campaign_id.required' => 'El ID de la campaña es obligatorio',
                'campaign_id.exists' => 'La campaña seleccionada no existe',
                'media_id.required' => 'El ID del medio es obligatorio',
                'media_id.exists' => 'El medio seleccionado no existe'
            ]);

            // Verificar que no exista el mismo medio en la misma campaña
            $existingItem = CampaignItem::where('campaign_id', $validatedData['campaign_id'])
                ->where('media_id', $validatedData['media_id'])
                ->exists();

            if ($existingItem) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este medio ya está asignado a la campaña seleccionada'
                ], 422);
            }

            // Verificar disponibilidad del medio en las fechas de la campaña
            $campaign = Campaign::findOrFail($validatedData['campaign_id']);
            $conflictingItems = CampaignItem::where('media_id', $validatedData['media_id'])
                ->whereHas('campaign', function($query) use ($campaign) {
                    $query->where(function($q) use ($campaign) {
                        // Verificar si hay solapamiento de fechas
                        $q->where(function($dateQuery) use ($campaign) {
                            // La campaña nueva empieza antes de que termine una existente
                            $dateQuery->where('start_date', '<=', $campaign->end_date)
                                      ->where('end_date', '>=', $campaign->start_date);
                        });
                    });
                })
                ->exists();

            if ($conflictingItems) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este medio no está disponible en las fechas de la campaña seleccionada. Ya está asignado a otra campaña en ese período.'
                ], 422);
            }

            // Calcular automáticamente price_per_day y subtotal
            $media = Media::findOrFail($validatedData['media_id']);
            $campaign = Campaign::findOrFail($validatedData['campaign_id']);
            
            // Calcular número de días de la campaña
            $startDate = \Carbon\Carbon::parse($campaign->start_date);
            $endDate = \Carbon\Carbon::parse($campaign->end_date);
            $totalDays = $startDate->diffInDays($endDate) + 1; // +1 para incluir ambos días
            
            // Obtener precio base del medio
            $basePricePerDay = $media->price_per_day;
            
            // Aplicar reglas de precio activas durante las fechas de la campaña
            $activePriceRules = $media->priceRules()
                ->where('start_date', '<=', $campaign->end_date)
                ->where('end_date', '>=', $campaign->start_date)
                ->get();
            
            $finalPricePerDay = $basePricePerDay;
            $appliedDiscounts = [];
            
            foreach ($activePriceRules as $rule) {
                $discount = ($basePricePerDay * $rule->value_pct) / 100;
                $finalPricePerDay -= $discount;
                
                $appliedDiscounts[] = [
                    'rule_name' => $rule->name,
                    'discount_percentage' => $rule->value_pct,
                    'discount_amount' => $discount
                ];
            }
            
            // Asegurar que el precio no sea negativo
            $finalPricePerDay = max(0, $finalPricePerDay);
            
            // Calcular subtotal
            $subtotal = $finalPricePerDay * $totalDays;
            
            // Asignar valores calculados
            $validatedData['price_per_day'] = $finalPricePerDay;
            $validatedData['subtotal'] = $subtotal;

            $campaign_item = CampaignItem::create($validatedData);
            $campaign_item->load(['campaign', 'media']);

            // Actualizar el total de la campaña sumando el subtotal
            $campaign->increment('total', $subtotal);

            // Agregar información de cálculo a la respuesta
            $responseData = $campaign_item->toArray();
            $responseData['calculation_details'] = [
                'total_days' => $totalDays,
                'base_price_per_day' => $basePricePerDay,
                'final_price_per_day' => $finalPricePerDay,
                'applied_discounts' => $appliedDiscounts,
                'total_discount_amount' => $basePricePerDay - $finalPricePerDay,
                'subtotal_calculation' => "$finalPricePerDay x $totalDays días = $subtotal"
            ];

            return response()->json([
                'success' => true,
                'message' => 'El medio de la campaña fue creado correctamente',
                'data' => $responseData
            ], 201);
        
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el medio de la campaña',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/campaign-items/{id}",
     *     summary="Get a specific campaign item",
     *     description="Retrieve a specific campaign item by its ID with campaign and media information",
     *     operationId="getCampaignItemById",
     *     tags={"Campaign Items"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Campaign item ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Campaign item retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="range", type="string", example="Morning"),
     *                 @OA\Property(property="days", type="integer", nullable=true, example=7),
     *                 @OA\Property(property="price_per_day", type="number", format="float", example=150.50),
     *                 @OA\Property(property="subtotal", type="number", format="float", example=1053.50),
     *                 @OA\Property(property="provider_status", type="string", example="Accepted"),
     *                 @OA\Property(property="description", type="string", nullable=true, example=null),
     *                 @OA\Property(property="campaign_id", type="integer", example=1),
     *                 @OA\Property(property="media_id", type="integer", example=1),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time"),
     *                 @OA\Property(
     *                     property="campaign",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Campaña Navideña"),
     *                     @OA\Property(property="start_date", type="string", format="date", example="2025-12-01"),
     *                     @OA\Property(property="end_date", type="string", format="date", example="2025-12-31"),
     *                     @OA\Property(property="status", type="string", example="Confirmed")
     *                 ),
     *                 @OA\Property(
     *                     property="media",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Billboard Centro"),
     *                     @OA\Property(property="type", type="string", example="Billboard"),
     *                     @OA\Property(property="location", type="string", example="Centro Ciudad"),
     *                     @OA\Property(property="price_per_day", type="number", format="float", example=200.00)
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
     *         response=404,
     *         description="Campaign item not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No query results for model [App\\Models\\CampaignItem].")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Error al mostrar el medio de la campaña"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function show(CampaignItem $campaign_item)
    {
        try {
            $campaign_item->load(['campaign', 'media']);
            
            return response()->json([
                'success' => true,
                'data'    => $campaign_item
            ], 200);
        
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al mostrar el medio de la campaña',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/campaign-items/{id}",
     *     summary="Update a campaign item",
     *     description="Update an existing campaign item. Only range and campaign_id can be updated. Media cannot be changed after creation",
     *     operationId="updateCampaignItem",
     *     tags={"Campaign Items"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Campaign item ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Campaign item data to update",
     *         @OA\JsonContent(
     *             required={"range", "campaign_id"},
     *             @OA\Property(property="range", type="string", maxLength=100, example="Evening", description="Updated time range for the media display"),
     *             @OA\Property(property="campaign_id", type="integer", example=2, description="Updated campaign ID")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Campaign item updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="El medio de la campaña se actualizó correctamente")
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
     *         description="Campaign item not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No query results for model [App\\Models\\CampaignItem].")
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
     *                     property="range",
     *                     type="array",
     *                     @OA\Items(type="string", example="El rango es obligatorio")
     *                 ),
     *                 @OA\Property(
     *                     property="campaign_id",
     *                     type="array",
     *                     @OA\Items(type="string", example="La campaña seleccionada no existe")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Error al editar el medio de la campaña"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function update(Request $request, CampaignItem $campaign_item)
    {
        try {
            // Validaciones
            $validatedData = $request->validate([
                'range' => 'required|string|max:100',
                'campaign_id' => 'required|exists:campaigns,id'
            ], [
                'range.required' => 'El rango es obligatorio',
                'range.string' => 'El rango debe ser una cadena de texto',
                'range.max' => 'El rango no puede tener más de 100 caracteres',
                'campaign_id.required' => 'El ID de la campaña es obligatorio',
                'campaign_id.exists' => 'La campaña seleccionada no existe'
            ]);

            $campaign_item->update($validatedData);
            $campaign_item->load(['campaign', 'media']);

            return response()->json([
                'success' => true,
                'message' => 'El medio de la campaña se actualizó correctamente',
            ], 200);
        
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al editar el medio de la campaña',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/campaign-items/{id}",
     *     summary="Delete a campaign item",
     *     description="Delete an existing campaign item and automatically update the campaign total by subtracting the subtotal",
     *     operationId="deleteCampaignItem",
     *     tags={"Campaign Items"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Campaign item ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Campaign item deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="El medio de la campaña se eliminó correctamente")
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
     *         description="Campaign item not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No query results for model [App\\Models\\CampaignItem].")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Error al eliminar el medio de la campaña"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function destroy(CampaignItem $campaign_item)
    {
        try {
            $subtotal = $campaign_item->subtotal ?? 0;
            $campaign = $campaign_item->campaign;
            
            $campaign_item->delete();
            
            if ($campaign && $subtotal > 0) {
                $campaign->decrement('total', $subtotal);
            }
        
            return response()->json([
                'success' => true,
                'message' => 'El medio de la campaña se eliminó correctamente'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el medio de la campaña',
                'error'   => $e->getMessage()
            ], 500);
        } 
    }

    /**
     * @OA\Patch(
     *     path="/api/v1/campaign-items/{id}/accept",
     *     summary="Accept a campaign item",
     *     description="Accept a campaign item request by provider. Sets provider_status to 'Accepted' and clears any rejection description",
     *     operationId="acceptCampaignItem",
     *     tags={"Campaign Items"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Campaign item ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Campaign item accepted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="El medio de la campaña fue aceptado correctamente"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="range", type="string", example="Morning"),
     *                 @OA\Property(property="provider_status", type="string", example="Accepted"),
     *                 @OA\Property(property="description", type="string", nullable=true, example=null),
     *                 @OA\Property(property="campaign_id", type="integer", example=1),
     *                 @OA\Property(property="media_id", type="integer", example=1),
     *                 @OA\Property(property="updated_at", type="string", format="date-time"),
     *                 @OA\Property(
     *                     property="campaign",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Campaña Navideña")
     *                 ),
     *                 @OA\Property(
     *                     property="media",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Billboard Centro")
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
     *         response=404,
     *         description="Campaign item not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No query results for model [App\\Models\\CampaignItem].")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Error al aceptar el medio de la campaña"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function accept(CampaignItem $campaign_item)
    {
        try {
            $campaign_item->update([
                'provider_status' => 'Accepted',
                'description' => null // Clear description when accepting
            ]);

            $campaign_item->load(['campaign', 'media']);

            return response()->json([
                'success' => true,
                'message' => 'El medio de la campaña fue aceptado correctamente',
                'data' => $campaign_item
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al aceptar el medio de la campaña',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Patch(
     *     path="/api/v1/campaign-items/{id}/reject",
     *     summary="Reject a campaign item",
     *     description="Reject a campaign item request by provider. Sets provider_status to 'Rejected' and requires a description explaining the rejection reason",
     *     operationId="rejectCampaignItem",
     *     tags={"Campaign Items"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Campaign item ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Rejection details",
     *         @OA\JsonContent(
     *             required={"description"},
     *             @OA\Property(property="description", type="string", maxLength=255, example="Conflicto con otra campaña en las mismas fechas", description="Reason for rejection")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Campaign item rejected successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="El medio de la campaña fue rechazado correctamente"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="range", type="string", example="Morning"),
     *                 @OA\Property(property="provider_status", type="string", example="Rejected"),
     *                 @OA\Property(property="description", type="string", example="Conflicto con otra campaña en las mismas fechas"),
     *                 @OA\Property(property="campaign_id", type="integer", example=1),
     *                 @OA\Property(property="media_id", type="integer", example=1),
     *                 @OA\Property(property="updated_at", type="string", format="date-time"),
     *                 @OA\Property(
     *                     property="campaign",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Campaña Navideña")
     *                 ),
     *                 @OA\Property(
     *                     property="media",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Billboard Centro")
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
     *         response=404,
     *         description="Campaign item not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No query results for model [App\\Models\\CampaignItem].")
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
     *                     property="description",
     *                     type="array",
     *                     @OA\Items(type="string", example="La descripción del rechazo es obligatoria")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Error al rechazar el medio de la campaña"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function reject(Request $request, CampaignItem $campaign_item)
    {
        try {
            $validatedData = $request->validate([
                'description' => 'required|string|max:255'
            ], [
                'description.required' => 'La descripción del rechazo es obligatoria',
                'description.string' => 'La descripción debe ser una cadena de texto',
                'description.max' => 'La descripción no puede tener más de 255 caracteres'
            ]);

            $campaign_item->update([
                'provider_status' => 'Rejected',
                'description' => $validatedData['description']
            ]);

            $campaign_item->load(['campaign', 'media']);

            return response()->json([
                'success' => true,
                'message' => 'El medio de la campaña fue rechazado correctamente',
                'data' => $campaign_item
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
                'message' => 'Error al rechazar el medio de la campaña',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
