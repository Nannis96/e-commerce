<?php

namespace App\Http\Controllers;

use App\Models\PriceRule;
use App\Http\Requests\StorePriceRuleRequest;
use App\Http\Requests\UpdatePriceRuleRequest;
use App\Http\Resources\PriceRuleResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PriceRuleController extends Controller
{

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
