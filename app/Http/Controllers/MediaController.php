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
            $query = Media::with(['user', 'images']);

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
                'price_per_day' => 'required|numeric|min:0|max:999999.99',
                'status' => 'nullable|in:Available,Busy',
                'active' => 'nullable|boolean',
                'user_id' => 'nullable|exists:users,id',
            ]);

            if (!isset($validatedData['user_id'])) {
                $validatedData['user_id'] = Auth::user()->id;
            }

            $media = Media::create($validatedData);
            $media->load(['user', 'images']);

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
            $media->load(['user', 'images']);

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
                'price_per_day' => 'sometimes|required|numeric|min:0|max:999999.99',
                'status' => 'sometimes|nullable|in:Available,Busy',
                'active' => 'sometimes|nullable|boolean',
                'user_id' => 'sometimes|nullable|exists:users,id',
            ]);

            $media->update($validatedData);
            $media->load(['user', 'images']);

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
