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

class MediaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Media::with(['user', 'images', 'cancellation']);

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

    public function store(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:100',
                'type' => 'required|string|max:100',
                'location' => 'required|string|max:100',
                'period_limit' => 'required|string|max:100',
                'price_limit' => 'required|numeric|min:0|max:999999.99',
                'status' => 'nullable|in:Available,Busy',
                'user_id' => 'nullable|exists:users,id',
                'cancellation_id' => 'nullable|exists:cancellations,id',
            ]);

            if (!isset($validatedData['user_id'])) {
                $validatedData['user_id'] = Auth::user()->id;
            }

            $media = Media::create($validatedData);
            $media->load(['user', 'images', 'cancellation']);

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

    public function show(Media $media): JsonResponse
    {
        try {
            $media->load(['user', 'images', 'cancellation']);

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

    public function update(Request $request, Media $media): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'name' => 'sometimes|required|string|max:100',
                'type' => 'sometimes|required|string|max:100',
                'location' => 'sometimes|required|string|max:100',
                'period_limit' => 'sometimes|required|string|max:100',
                'price_limit' => 'sometimes|required|numeric|min:0|max:999999.99',
                'status' => 'sometimes|nullable|in:Available,Busy',
                'user_id' => 'sometimes|nullable|exists:users,id',
                'cancellation_id' => 'sometimes|nullable|exists:cancellations,id',
            ]);

            $media->update($validatedData);
            $media->load(['user', 'images', 'cancellation']);

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

    /**
     * Get all price rules for a specific media
     */
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

    /**
     * Get active price rules for a specific media
     */
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

    /**
     * Attach price rules to media
     */
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

    /**
     * Detach price rules from media
     */
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

    /**
     * Sync price rules with media (replace all)
     */
    public function syncPriceRules(Request $request, Media $media): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'price_rule_ids' => 'sometimes|array',
                'price_rule_ids.*' => 'exists:price_rules,id'
            ]);

            // Sync price rules (this will replace all existing relationships)
            $priceRuleIds = $validatedData['price_rule_ids'] ?? [];
            $media->priceRules()->sync($priceRuleIds);

            $syncedPriceRules = $media->priceRules()->get();

            return response()->json([
                'success' => true,
                'data' => PriceRuleResource::collection($syncedPriceRules),
                'message' => 'Reglas de precios sincronizadas correctamente con el medio'
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
                'message' => 'Error al sincronizar reglas de precios con el medio',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate price for media with active price rules
     */
    public function calculatePrice(Media $media): JsonResponse
    {
        try {
            $basePrice = $media->price_limit;
            $activePriceRules = $media->activePriceRules()->get();
            
            $finalPrice = $basePrice;
            $appliedRules = [];

            foreach ($activePriceRules as $rule) {
                $discount = ($basePrice * $rule->value_pct) / 100;
                $finalPrice -= $discount;
                
                $appliedRules[] = [
                    'rule_name' => $rule->name,
                    'discount_percentage' => $rule->value_pct,
                    'discount_amount' => $discount
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'media_id' => $media->id,
                    'media_name' => $media->name,
                    'base_price' => $basePrice,
                    'final_price' => max(0, $finalPrice), // No negative prices
                    'total_discount' => $basePrice - max(0, $finalPrice),
                    'applied_rules' => $appliedRules,
                    'active_rules_count' => count($appliedRules)
                ],
                'message' => 'Precio calculado correctamente'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al calcular el precio del medio',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
