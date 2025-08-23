<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CampaignController extends Controller
{
    public function index()
    {
        try {
            $campaigns = Campaign::orderBy('id', 'desc')->paginate();

            return response()->json([
                'success' => true,
                'data'    => $campaigns
            ], 200);
        
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al mostrar las campañas',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            // Validaciones
            $validatedData = $request->validate([
                'name' => 'required|string|max:100|unique:campaigns,name',
                'start_date' => 'required|date|after_or_equal:today',
                'end_date' => 'required|date|after:start_date',
                'total' => 'required|numeric|min:0|max:999999.99',
                'currency' => 'required|string|max:100|in:USD,EUR,COP,MXN,ARS'
            ], [
                'name.required' => 'El nombre de la campaña es obligatorio',
                'name.string' => 'El nombre debe ser una cadena de texto',
                'name.max' => 'El nombre no puede tener más de 100 caracteres',
                'name.unique' => 'Ya existe una campaña con este nombre',
                'start_date.required' => 'La fecha de inicio es obligatoria',
                'start_date.date' => 'La fecha de inicio debe ser una fecha válida',
                'start_date.after_or_equal' => 'La fecha de inicio no puede ser anterior a hoy',
                'end_date.required' => 'La fecha de fin es obligatoria',
                'end_date.date' => 'La fecha de fin debe ser una fecha válida',
                'end_date.after' => 'La fecha de fin debe ser posterior a la fecha de inicio',
                'total.required' => 'El total es obligatorio',
                'total.numeric' => 'El total debe ser un número',
                'total.min' => 'El total no puede ser negativo',
                'total.max' => 'El total no puede ser mayor a 999,999.99',
                'currency.required' => 'La moneda es obligatoria',
                'currency.string' => 'La moneda debe ser una cadena de texto',
                'currency.max' => 'La moneda no puede tener más de 100 caracteres',
                'currency.in' => 'La moneda debe ser una de las siguientes: USD, EUR, COP, MXN, ARS'
            ]);

            $campaign = Campaign::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'La campaña fue creada correctamente',
                'data' => $campaign
            ], 201);
        
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la campaña',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function show(Campaign $campaign)
    {
        try {
            return response()->json([
                'success' => true,
                'data'    => $campaign
            ], 200);
        
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al mostrar la campaña',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, Campaign $campaign)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:100|unique:campaigns,name,' . $campaign->id,
                'start_date' => 'required|date',
                'end_date' => 'required|date|after:start_date',
                'total' => 'required|numeric|min:0|max:999999.99',
                'currency' => 'required|string|max:100|in:USD,EUR,COP,MXN,ARS'
            ], [
                'name.required' => 'El nombre de la campaña es obligatorio',
                'name.string' => 'El nombre debe ser una cadena de texto',
                'name.max' => 'El nombre no puede tener más de 100 caracteres',
                'name.unique' => 'Ya existe una campaña con este nombre',
                'start_date.required' => 'La fecha de inicio es obligatoria',
                'start_date.date' => 'La fecha de inicio debe ser una fecha válida',
                'end_date.required' => 'La fecha de fin es obligatoria',
                'end_date.date' => 'La fecha de fin debe ser una fecha válida',
                'end_date.after' => 'La fecha de fin debe ser posterior a la fecha de inicio',
                'total.required' => 'El total es obligatorio',
                'total.numeric' => 'El total debe ser un número',
                'total.min' => 'El total no puede ser negativo',
                'total.max' => 'El total no puede ser mayor a 999,999.99',
                'currency.required' => 'La moneda es obligatoria',
                'currency.string' => 'La moneda debe ser una cadena de texto',
                'currency.max' => 'La moneda no puede tener más de 100 caracteres',
                'currency.in' => 'La moneda debe ser una de las siguientes: USD, EUR, COP, MXN, ARS'
            ]);

            $campaign->update($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'La campaña se actualizó correctamente',
                'data' => $campaign->fresh()
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
                'message' => 'Error al editar la campaña',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Campaign $campaign)
    {
        try {
            $campaign->delete();
        
            return response()->json([
                'success' => true,
                'message' => 'La campaña se eliminó correctamente'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la campaña',
                'error'   => $e->getMessage()
            ], 500);
        } 
    }
}
