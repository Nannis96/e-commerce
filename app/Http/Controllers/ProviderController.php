<?php

namespace App\Http\Controllers;

use App\Models\Provider;
use Illuminate\Http\Request;

class ProviderController extends Controller
{
    public function index()
    {
        try{

            $provider = Provider::orderBy('id', 'desc')->paginate();

            return response()->json([
                'success' => true,
                'data'    => $provider
            ], 201);
        
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al mostrar los provvedores',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try{
            $provider = new Provider();

            $provider->business_name = $request->business_name;
            $provider->tax = $request->tax;
            $provider->commission = $request->commission;
            $provider->bank_account = $request->bank_account;
            $provider->clabe = $request->clabe;
            $provider->user_id = $request->user_id;

            $provider->save();

            return response()->json([
                'success' => true,
                'message' => 'Proveedor creado correctamente',
            ], 201);
        
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el proveedor',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function show(Provider $provider)
    {
        try{
            return response()->json([
                'success' => true,
                'data'    => $provider
            ], 201);
        
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al mostrar el proveedor',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, Provider $provider)
    {
        try{
            $provider->business_name = $request->business_name;
            $provider->tax = $request->tax;
            $provider->commission = $request->commission;
            $provider->bank_account = $request->bank_account;
            $provider->clabe = $request->clabe;

            $provider->save();

            return response()->json([
                'success' => true,
                'message' => "Se actualizo correctamente el proveedor"
            ], 201);
        
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al editar el proveedor',
                'error'   => $e->getMessage()
            ], 500);
        }

    }

    public function destroy(Provider $provider)
    {
        try{
            $provider->delete();
        
            return response()->json([
                'success' => true,
                'message' => "Se elimino correctamente el proveedor"
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar al proveedor',
                'error'   => $e->getMessage()
            ], 500);
        }
            
    }
}
