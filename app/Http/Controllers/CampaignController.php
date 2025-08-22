<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    public function index()
    {
        try{

            $campaigns = Campaign::orderBy('id', 'desc')->paginate();

            return response()->json([
                'success' => true,
                'data'    => $campaigns
            ], 201);
        
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
        try{
            $campaign = new Campaign();

            $campaign->name = $request->name;
            $campaign->start_date = $request->start_date;
            $campaign->end_date = $request->end_date;
            $campaign->total = $request->total;
            $campaign->currency = $request->currency;

            $campaign->save();

            return response()->json([
                'success' => true,
                'message' => 'La campaña fue creada correctamente',
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
        try{
            return response()->json([
                'success' => true,
                'data'    => $campaign
            ], 201);
        
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
        try{
            $campaign->name = $request->name;
            $campaign->start_date = $request->start_date;
            $campaign->end_date = $request->end_date;
            $campaign->total = $request->total;
            $campaign->currency = $request->currency;

            $campaign->save();

            return response()->json([
                'success' => true,
                'message' => "Se actualizo correctamente la campaña"
            ], 201);
        
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
        try{
            $campaign->delete();
        
            return response()->json([
                'success' => true,
                'message' => "Se elimino correctamente la campaña"
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la campaña',
                'error'   => $e->getMessage()
            ], 500);
        } 
    }
}
