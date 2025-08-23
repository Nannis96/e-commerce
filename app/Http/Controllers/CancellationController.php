<?php

namespace App\Http\Controllers;

use App\Models\Cancellation;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CancellationController extends Controller
{

    public function index()
    {
        try {
            $cancellations = Cancellation::orderBy('id', 'desc')->paginate();

            return response()->json([
                'success' => true,
                'data'    => $cancellations
            ], 200);
        
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al mostrar los tipos de cancelación',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'start_days' => 'required|integer|min:0',
                'end_days' => 'required|integer|min:0|gte:start_days',
                'commission' => 'required|integer|min:0|max:100'
            ], [
                'start_days.required' => 'Los días de inicio son obligatorios',
                'start_days.integer' => 'Los días de inicio deben ser un número entero',
                'start_days.min' => 'Los días de inicio no pueden ser negativos',
                'end_days.required' => 'Los días de fin son obligatorios',
                'end_days.integer' => 'Los días de fin deben ser un número entero',
                'end_days.min' => 'Los días de fin no pueden ser negativos',
                'end_days.gte' => 'Los días de fin deben ser mayores o iguales a los días de inicio',
                'commission.required' => 'La comisión es obligatoria',
                'commission.integer' => 'La comisión debe ser un número entero',
                'commission.min' => 'La comisión no puede ser negativa',
                'commission.max' => 'La comisión no puede ser mayor a 100%'
            ]);

            $cancellation = Cancellation::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Tipo de cancelación creado correctamente',
                'data' => $cancellation
            ], 201);
        
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el tipo de cancelación',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function show(Cancellation $cancellation)
    {
        try {
            return response()->json([
                'success' => true,
                'data'    => $cancellation
            ], 200);
        
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al mostrar el tipo de cancelación',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, Cancellation $cancellation)
    {
        try {
            $validatedData = $request->validate([
                'start_days' => 'required|integer|min:0',
                'end_days' => 'required|integer|min:0|gte:start_days',
                'commission' => 'required|integer|min:0|max:100'
            ], [
                'start_days.required' => 'Los días de inicio son obligatorios',
                'start_days.integer' => 'Los días de inicio deben ser un número entero',
                'start_days.min' => 'Los días de inicio no pueden ser negativos',
                'end_days.required' => 'Los días de fin son obligatorios',
                'end_days.integer' => 'Los días de fin deben ser un número entero',
                'end_days.min' => 'Los días de fin no pueden ser negativos',
                'end_days.gte' => 'Los días de fin deben ser mayores o iguales a los días de inicio',
                'commission.required' => 'La comisión es obligatoria',
                'commission.integer' => 'La comisión debe ser un número entero',
                'commission.min' => 'La comisión no puede ser negativa',
                'commission.max' => 'La comisión no puede ser mayor a 100%'
            ]);

            $cancellation->update($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Tipo de cancelación actualizado correctamente',
                'data' => $cancellation->fresh()
            ], 200);
        
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al editar el tipo de cancelación',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Cancellation $cancellation)
    {
        try {
            if ($cancellation->media()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar este tipo de cancelación porque está siendo usado por uno o más medios'
                ], 422);
            }

            $cancellation->delete();
        
            return response()->json([
                'success' => true,
                'message' => 'Tipo de cancelación eliminado correctamente'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el tipo de cancelación',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
