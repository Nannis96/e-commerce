<?php

namespace App\Http\Controllers;

use App\Models\CampaignItem;
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
                'days' => 'required|string|max:100',
                'price_per_days' => 'required|numeric|min:0|max:999999.99',
                'subtotal' => 'required|numeric|min:0|max:999999.99',
                'provider_status' => 'nullable|in:Accepted,Rejected',
                'campaign_id' => 'required|exists:campaigns,id',
                'media_id' => 'required|exists:media,id'
            ], [
                'range.required' => 'El rango es obligatorio',
                'range.string' => 'El rango debe ser una cadena de texto',
                'range.max' => 'El rango no puede tener más de 100 caracteres',
                'days.required' => 'Los días son obligatorios',
                'days.string' => 'Los días deben ser una cadena de texto',
                'days.max' => 'Los días no pueden tener más de 100 caracteres',
                'price_per_days.required' => 'El precio por día es obligatorio',
                'price_per_days.numeric' => 'El precio por día debe ser un número',
                'price_per_days.min' => 'El precio por día no puede ser negativo',
                'price_per_days.max' => 'El precio por día no puede ser mayor a 999,999.99',
                'subtotal.required' => 'El subtotal es obligatorio',
                'subtotal.numeric' => 'El subtotal debe ser un número',
                'subtotal.min' => 'El subtotal no puede ser negativo',
                'subtotal.max' => 'El subtotal no puede ser mayor a 999,999.99',
                'provider_status.in' => 'El estado del proveedor debe ser Accepted o Rejected',
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

            $campaign_item = CampaignItem::create($validatedData);
            $campaign_item->load(['campaign', 'media']);

            return response()->json([
                'success' => true,
                'message' => 'El medio de la campaña fue creado correctamente',
                'data' => $campaign_item
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
                'days' => 'required|string|max:100',
                'price_per_days' => 'required|numeric|min:0|max:999999.99',
                'subtotal' => 'required|numeric|min:0|max:999999.99',
                'provider_status' => 'nullable|in:Accepted,Rejected',
                'campaign_id' => 'required|exists:campaigns,id',
                'media_id' => 'required|exists:media,id'
            ], [
                'range.required' => 'El rango es obligatorio',
                'range.string' => 'El rango debe ser una cadena de texto',
                'range.max' => 'El rango no puede tener más de 100 caracteres',
                'days.required' => 'Los días son obligatorios',
                'days.string' => 'Los días deben ser una cadena de texto',
                'days.max' => 'Los días no pueden tener más de 100 caracteres',
                'price_per_days.required' => 'El precio por día es obligatorio',
                'price_per_days.numeric' => 'El precio por día debe ser un número',
                'price_per_days.min' => 'El precio por día no puede ser negativo',
                'price_per_days.max' => 'El precio por día no puede ser mayor a 999,999.99',
                'subtotal.required' => 'El subtotal es obligatorio',
                'subtotal.numeric' => 'El subtotal debe ser un número',
                'subtotal.min' => 'El subtotal no puede ser negativo',
                'subtotal.max' => 'El subtotal no puede ser mayor a 999,999.99',
                'provider_status.in' => 'El estado del proveedor debe ser Accepted o Rejected',
                'campaign_id.required' => 'El ID de la campaña es obligatorio',
                'campaign_id.exists' => 'La campaña seleccionada no existe',
                'media_id.required' => 'El ID del medio es obligatorio',
                'media_id.exists' => 'El medio seleccionado no existe'
            ]);

            // Verificar que no exista el mismo medio en la misma campaña (excluyendo el registro actual)
            $existingItem = CampaignItem::where('campaign_id', $validatedData['campaign_id'])
                ->where('media_id', $validatedData['media_id'])
                ->where('id', '!=', $campaign_item->id)
                ->exists();

            if ($existingItem) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este medio ya está asignado a la campaña seleccionada'
                ], 422);
            }

            $campaign_item->update($validatedData);
            $campaign_item->load(['campaign', 'media']);

            return response()->json([
                'success' => true,
                'message' => 'El medio de la campaña se actualizó correctamente',
                'data' => $campaign_item
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
            $campaign_item->delete();
        
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
}
