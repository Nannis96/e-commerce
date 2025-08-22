<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class UserController extends Controller
{

    public function index()
    {
        try{

            $users = User::orderBy('id', 'desc')->paginate();

            return response()->json([
                'success' => true,
                'data'    => $users
            ], 201);
        
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al mostrar los usuarios',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try{
            $user = new User();

            $password = Str::random(10);

            $user->name = $request->name;
            $user->last_name = $request->last_name;
            $user->number_phone = $request->number_phone;
            $user->email = $request->email;
            $user->password = Hash::make($password);
            $user->rol = $request->rol;

            $user->save();

            return response()->json([
                'success' => true,
                'data'    => [
                    'user'     => $request->email,
                    'password' => $password
                ],
                'message' => 'Usuario creado correctamente',
            ], 201);
        
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el usuario',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function show(User $user)
    {
        try{
            return response()->json([
                'success' => true,
                'data'    => $user
            ], 201);
        
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al mostrar el usuario',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, User $user)
    {
        try{

            $user->name = $request->name;
            $user->last_name = $request->last_name;
            $user->number_phone = $request->number_phone;
            $user->email = $request->email;
            $user->rol = $request->rol;

            $user->save();

            return response()->json([
                'success' => true,
                'message' => "Se actualizo correctamente el usuario"
            ], 201);
        
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al editar el usuario',
                'error'   => $e->getMessage()
            ], 500);
        }

    }

    public function destroy(User $user)
    {
        try{
            $user->delete();
        
            return response()->json([
                'success' => true,
                'message' => "Se elimino correctamente el usuario"
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el usuario',
                'error'   => $e->getMessage()
            ], 500);
        }
            
    }
}
