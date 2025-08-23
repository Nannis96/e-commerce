<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Users",
 *     description="API Endpoints para gestión de usuarios"
 * )
 */
class UserController extends Controller
{

    /**
     * @OA\Get(
     *     path="/api/users",
     *     operationId="getUsersList",
     *     tags={"Users"},
     *     summary="Obtener lista de usuarios",
     *     description="Retorna una lista paginada de todos los usuarios",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Número de página",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de usuarios obtenida exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="data", type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="Juan Pérez"),
     *                         @OA\Property(property="email", type="string", example="juan@example.com"),
     *                         @OA\Property(property="role", type="string", example="Client"),
     *                         @OA\Property(property="created_at", type="string", example="2025-08-22T10:00:00.000000Z"),
     *                         @OA\Property(property="updated_at", type="string", example="2025-08-22T10:00:00.000000Z")
     *                     )
     *                 ),
     *                 @OA\Property(property="total", type="integer", example=50),
     *                 @OA\Property(property="per_page", type="integer", example=15)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Error al mostrar los usuarios"),
     *             @OA\Property(property="error", type="string", example="Mensaje de error específico")
     *         )
     *     )
     * )
     */
    public function index()
    {
        try{

            $users = User::orderBy('id', 'desc')->paginate();

            return response()->json([
                'success' => true,
                'data'    => $users
            ], 200);
        
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al mostrar los usuarios',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/users",
     *     operationId="createUser",
     *     tags={"Users"},
     *     summary="Crear un nuevo usuario (Solo Admin)",
     *     description="Crea un nuevo usuario con los datos proporcionados. Solo usuarios con role Admin pueden acceder.",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email","role"},
     *             @OA\Property(property="name", type="string", maxLength=255, example="Juan Pérez"),
     *             @OA\Property(property="email", type="string", format="email", maxLength=255, example="juan@example.com"),
     *             @OA\Property(property="role", type="string", enum={"Admin", "Provider", "Client"}, example="Client")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Usuario creado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", type="string", example="juan@example.com"),
     *                 @OA\Property(property="password", type="string", example="randompass123")
     *             ),
     *             @OA\Property(property="message", type="string", example="Usuario creado correctamente")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Acceso denegado - Solo administradores",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Solo los administradores pueden acceder a este recurso")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Error al crear el usuario"),
     *             @OA\Property(property="error", type="string", example="Mensaje de error específico")
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        try{
            // Validaciones
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email|max:255',
                'role' => 'required|in:Admin,Provider,Client'
            ], [
                'name.required' => 'El nombre es obligatorio',
                'name.string' => 'El nombre debe ser una cadena de texto',
                'name.max' => 'El nombre no puede tener más de 255 caracteres',
                'email.required' => 'El email es obligatorio',
                'email.email' => 'El email debe tener un formato válido',
                'email.unique' => 'Este email ya está registrado',
                'email.max' => 'El email no puede tener más de 255 caracteres',
                'role.required' => 'El rol es obligatorio',
                'role.in' => 'El rol debe ser Admin, Provider o Client'
            ]);

            $password = Str::random(10);

            $user = User::create([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'password' => Hash::make($password),
                'role' => $validatedData['role']
            ]);

            return response()->json([
                'success' => true,
                'data'    => [
                    'user'     => $user->email,
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

    /**
     * @OA\Get(
     *     path="/api/users/{id}",
     *     operationId="getUserById",
     *     tags={"Users"},
     *     summary="Obtener un usuario específico",
     *     description="Retorna los datos de un usuario específico por su ID",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del usuario",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Usuario obtenido exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Juan Pérez"),
     *                 @OA\Property(property="email", type="string", example="juan@example.com"),
     *                 @OA\Property(property="role", type="string", example="Client"),
     *                 @OA\Property(property="created_at", type="string", example="2025-08-22T10:00:00.000000Z"),
     *                 @OA\Property(property="updated_at", type="string", example="2025-08-22T10:00:00.000000Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Usuario no encontrado"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Error al mostrar el usuario"),
     *             @OA\Property(property="error", type="string", example="Mensaje de error específico")
     *         )
     *     )
     * )
     */
    public function show(User $user)
    {
        try{
            return response()->json([
                'success' => true,
                'data'    => $user
            ], 200);
        
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al mostrar el usuario',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/users/{id}",
     *     operationId="updateUser",
     *     tags={"Users"},
     *     summary="Actualizar un usuario",
     *     description="Actualiza los datos de un usuario específico",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del usuario",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", maxLength=255, example="Juan Carlos Pérez"),
     *             @OA\Property(property="email", type="string", format="email", maxLength=255, example="juan.carlos@example.com"),
     *             @OA\Property(property="role", type="string", enum={"Admin", "Provider", "Client"}, example="Provider")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Usuario actualizado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Se actualizo correctamente el usuario")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Usuario no encontrado"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Error al editar el usuario"),
     *             @OA\Property(property="error", type="string", example="Mensaje de error específico")
     *         )
     *     )
     * )
     */
    public function update(Request $request, User $user)
    {
        try{
            // Validaciones
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255|unique:users,email,' . $user->id,
                'role' => 'required|in:Admin,Provider,Client'
            ], [
                'name.required' => 'El nombre es obligatorio',
                'name.string' => 'El nombre debe ser una cadena de texto',
                'name.max' => 'El nombre no puede tener más de 255 caracteres',
                'email.required' => 'El email es obligatorio',
                'email.email' => 'El email debe tener un formato válido',
                'email.unique' => 'Este email ya está registrado',
                'email.max' => 'El email no puede tener más de 255 caracteres',
                'role.required' => 'El rol es obligatorio',
                'role.in' => 'El rol debe ser Admin, Provider o Client'
            ]);

            $user->update($validatedData);

            return response()->json([
                'success' => true,
                'message' => "Se actualizo correctamente el usuario"
            ], 200);
        
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al editar el usuario',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/users/{id}",
     *     operationId="deleteUser",
     *     tags={"Users"},
     *     summary="Eliminar un usuario",
     *     description="Elimina un usuario específico del sistema",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del usuario",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Usuario eliminado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Se elimino correctamente el usuario")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Usuario no encontrado"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Error al eliminar el usuario"),
     *             @OA\Property(property="error", type="string", example="Mensaje de error específico")
     *         )
     *     )
     * )
     */
    public function destroy(User $user)
    {
        try{
            $user->delete();
        
            return response()->json([
                'success' => true,
                'message' => "Se elimino correctamente el usuario"
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el usuario',
                'error'   => $e->getMessage()
            ], 500);
        }
            
    }
}
