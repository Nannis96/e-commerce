<?php

namespace App\Http\Controllers;

use App\Models\CampaignItem;
use App\Models\Campaign;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CampaignItemController extends Controller
{
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

            // Calcular automáticamente price_per_days y subtotal
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
            $validatedData['price_per_days'] = $finalPricePerDay;
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
