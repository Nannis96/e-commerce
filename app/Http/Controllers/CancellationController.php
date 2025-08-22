<?php

namespace App\Http\Controllers;

use App\Models\Cancellation;
use Illuminate\Http\Request;

class CancellationController extends Controller
{
    public function index()
    {
        try{

            $cancellations = Cancellation::orderBy('id', 'desc')->paginate();

            return response()->json([
                'success' => true,
                'data'    => $cancellations
            ], 201);
        
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al mostrar el tipo de cancelación',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try{

            $cancellation = new Cancellation();

            $cancellation->name = $request->start_days;
            $cancellation->type = $request->end_days;
            $cancellation->location = $request->commission;

            $cancellation->save();

            return response()->json([
                'success' => true,
                'message' => 'Tipo de cancelación creado correctamente',
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
        try{
            return response()->json([
                'success' => true,
                'data'    => $cancellation
            ], 201);
        
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
        try{
            $cancellation->name = $request->start_days;
            $cancellation->type = $request->end_days;
            $cancellation->location = $request->commission;

            $cancellation->save();

            return response()->json([
                'success' => true,
                'message' => "Se actualizo correctamente el tipo de cancelación"
            ], 201);
        
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
        try{
            $cancellation->delete();
        
            return response()->json([
                'success' => true,
                'message' => "Se elimino correctamente el tipo de cancelación"
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el tipo de cancelación',
                'error'   => $e->getMessage()
            ], 500);
        }
            
    }
}
