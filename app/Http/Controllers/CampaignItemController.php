<?php

namespace App\Http\Controllers;

use App\Models\CampaignItem;
use Illuminate\Http\Request;

class CampaignItemController extends Controller
{
    public function index()
    {
        try{

            $campaign_items = CampaignItem::orderBy('id', 'desc')->paginate();

            return response()->json([
                'success' => true,
                'data'    => $campaign_items
            ], 201);
        
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al mostrar los medios de la camapaña',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try
        {
            $campaign_item = new CampaignItem();

            $campaign_item->range = $request->range;
            $campaign_item->days = $request->days;
            $campaign_item->price_per_days = $request->price_per_days;
            $campaign_item->subtotal = $request->subtotal;
            $campaign_item->provider_status = $request->provider_status;
            $campaign_item->campaign_id = $request->campaign_id;
            $campaign_item->media_id = $request->media_id;

            $campaign_item->save();

            return response()->json([
                'success' => true,
                'message' => 'El medio de la campaña fue creado correctamente',
            ], 201);
        
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el medio de la camapaña',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function show(CampaignItem $campaign_item)
    {
        try{
            return response()->json([
                'success' => true,
                'data'    => $campaign_item
            ], 201);
        
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
        try{
            $campaign_item->range = $request->range;
            $campaign_item->days = $request->days;
            $campaign_item->price_per_days = $request->price_per_days;
            $campaign_item->subtotal = $request->subtotal;
            $campaign_item->provider_status = $request->provider_status;
            $campaign_item->campaign_id = $request->campaign_id;
            $campaign_item->media_id = $request->media_id;

            $campaign_item->save();

            return response()->json([
                'success' => true,
                'message' => "Se actualizo correctamente el medio de la campaña"
            ], 201);
        
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
        try{
            $campaign_item->delete();
        
            return response()->json([
                'success' => true,
                'message' => "Se elimino correctamente medio de la campaña"
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar medio de la campaña',
                'error'   => $e->getMessage()
            ], 500);
        } 
    }
}
