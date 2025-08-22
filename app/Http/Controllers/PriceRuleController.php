<?php

namespace App\Http\Controllers;

use App\Models\PriceRule;
use Illuminate\Http\Request;

class PriceRuleController extends Controller
{
    public function index()
    {
        try{

            $price_rules = PriceRule::orderBy('id', 'desc')->paginate();

            return response()->json([
                'success' => true,
                'data'    => $price_rules
            ], 201);
        
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al mostrar las reglas de los precios por fechas',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try{
            $price_rule = new PriceRule();

            $price_rule->start_date = $request->start_date;
            $price_rule->end_date = $request->end_date;
            $price_rule->name = $request->name;
            $price_rule->value_pct = $request->value_pct;
            #$price_rule->range = $request->range;

            $price_rule->save();

            return response()->json([
                'success' => true,
                'message' => 'El precio por fecha fue creado correctamente',
            ], 201);
        
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el precio por fecha',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function show(PriceRule $price_rule)
    {
        try{
            return response()->json([
                'success' => true,
                'data'    => $price_rule
            ], 201);
        
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al mostrar el precio por fecha',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, PriceRule $price_rule)
    {
        try{
            $price_rule->start_date = $request->start_date;
            $price_rule->end_date = $request->end_date;
            $price_rule->name = $request->name;
            $price_rule->value_pct = $request->value_pct;
            #$price_rule->range = $request->range;

            $price_rule->save();

            return response()->json([
                'success' => true,
                'message' => "Se actualizo correctamente el precio por fecha"
            ], 201);
        
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al editar el precio por fecha',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(PriceRule $price_rule)
    {
        try{
            $price_rule->delete();
        
            return response()->json([
                'success' => true,
                'message' => "Se elimino correctamente el precio por fecha"
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el precio por fecha',
                'error'   => $e->getMessage()
            ], 500);
        } 
    }
}
