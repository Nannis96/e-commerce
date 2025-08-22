<?php

namespace App\Http\Controllers;

use App\Models\Media;
use Illuminate\Http\Request;

class MediaController extends Controller
{
    public function index()
    {
        try{

            $medias = Media::orderBy('id', 'desc')->paginate();

            return response()->json([
                'success' => true,
                'data'    => $medias
            ], 201);
        
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al mostrar los medios',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try{

            $media = new Media();

            $media->name = $request->name;
            $media->type = $request->type;
            $media->location = $request->location;
            $media->period_limit = $request->period_limit;
            $media->price_limit = $request->price_limit;
            $media->status = $request->status;
            $media->provider_id = $request->provider_id;

            $media->save();

            return response()->json([
                'success' => true,
                'message' => 'Medio creado correctamente',
            ], 201);
        
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el medio',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function show(Media $media)
    {
        try{
            return response()->json([
                'success' => true,
                'data'    => $media
            ], 201);
        
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al mostrar el medio',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, Media $media)
    {
        try{
            $media->name = $request->name;
            $media->type = $request->type;
            $media->location = $request->location;
            $media->period_limit = $request->period_limit;
            $media->price_limit = $request->price_limit;
            $media->status = $request->status;
            $media->provider_id = $request->provider_id;

            $media->save();

            return response()->json([
                'success' => true,
                'message' => "Se actualizo correctamente el medio"
            ], 201);
        
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al editar el medio',
                'error'   => $e->getMessage()
            ], 500);
        }

    }

    public function destroy(Media $media)
    {
        try{
            $media->delete();
        
            return response()->json([
                'success' => true,
                'message' => "Se elimino correctamente el medio"
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar al medio',
                'error'   => $e->getMessage()
            ], 500);
        }
            
    }
}
