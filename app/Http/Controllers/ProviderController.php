<?php

namespace App\Http\Controllers;

use App\Models\Provider;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

/**
 * @OA\Tag(
 *     name="Providers",
 *     description="API Endpoints para gestión de proveedores"
 * )
 */
class ProviderController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/providers",
     *     operationId="getProvidersList",
     *     tags={"Providers"},
     *     summary="Obtener lista de proveedores",
     *     description="Retorna una lista paginada de todos los proveedores con información del usuario",
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
     *         description="Lista de proveedores obtenida exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="data", type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="business_name", type="string", example="Empresa ABC S.A."),
     *                         @OA\Property(property="tax", type="string", example="ABC123456789"),
     *                         @OA\Property(property="commission", type="integer", example=15),
     *                         @OA\Property(property="bank_account", type="string", example="1234567890123456"),
     *                         @OA\Property(property="clabe", type="string", example="123456789012345678"),
     *                         @OA\Property(property="user_id", type="integer", example=1),
     *                         @OA\Property(property="user", type="object",
     *                             @OA\Property(property="id", type="integer", example=1),
     *                             @OA\Property(property="name", type="string", example="Juan"),
     *                             @OA\Property(property="email", type="string", example="juan@empresa.com")
     *                         ),
     *                         @OA\Property(property="created_at", type="string", example="2025-08-22T10:00:00.000000Z"),
     *                         @OA\Property(property="updated_at", type="string", example="2025-08-22T10:00:00.000000Z")
     *                     )
     *                 ),
     *                 @OA\Property(property="total", type="integer", example=25),
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
     *             @OA\Property(property="message", type="string", example="Error al mostrar los proveedores"),
     *             @OA\Property(property="error", type="string", example="Mensaje de error específico")
     *         )
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        try {
            $providers = Provider::with('user:id,name,email')
                ->orderBy('id', 'desc')
                ->paginate();

            return response()->json([
                'success' => true,
                'data'    => $providers
            ], 200);
        
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al mostrar los proveedores',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/providers",
     *     operationId="createProvider",
     *     tags={"Providers"},
     *     summary="Crear un nuevo proveedor (Solo Admin)",
     *     description="Crea un nuevo proveedor con los datos proporcionados. Solo usuarios con rol Admin pueden acceder.",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"business_name","tax","commission","bank_account","clabe","user_id"},
     *             @OA\Property(property="business_name", type="string", example="Empresa ABC S.A."),
     *             @OA\Property(property="tax", type="string", example="ABC123456789"),
     *             @OA\Property(property="commission", type="integer", minimum=0, maximum=100, example=15),
     *             @OA\Property(property="bank_account", type="string", example="1234567890123456"),
     *             @OA\Property(property="clabe", type="string", example="123456789012345678"),
     *             @OA\Property(property="user_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Proveedor creado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Proveedor creado correctamente"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="business_name", type="string", example="Empresa ABC S.A."),
     *                 @OA\Property(property="tax", type="string", example="ABC123456789"),
     *                 @OA\Property(property="commission", type="integer", example=15),
     *                 @OA\Property(property="bank_account", type="string", example="1234567890123456"),
     *                 @OA\Property(property="clabe", type="string", example="123456789012345678"),
     *                 @OA\Property(property="user_id", type="integer", example=1)
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
     *             @OA\Property(property="message", type="string", example="Error al crear el proveedor"),
     *             @OA\Property(property="error", type="string", example="Mensaje de error específico")
     *         )
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'business_name' => 'required|string|max:255',
                'tax' => 'required|string|max:50',
                'commission' => 'required|integer|min:0|max:100',
                'bank_account' => 'required|string|max:20',
                'clabe' => 'required|string|size:18|unique:providers,clabe',
                'user_id' => 'required|integer|exists:users,id|unique:providers,user_id'
            ], [
                'business_name.required' => 'El nombre de la empresa es obligatorio',
                'business_name.string' => 'El nombre de la empresa debe ser una cadena de texto',
                'business_name.max' => 'El nombre de la empresa no puede tener más de 255 caracteres',
                'tax.required' => 'El RFC/TAX es obligatorio',
                'tax.string' => 'El RFC/TAX debe ser una cadena de texto',
                'tax.max' => 'El RFC/TAX no puede tener más de 50 caracteres',
                'tax.unique' => 'Este RFC/TAX ya está registrado con otro proveedor',
                'commission.required' => 'La comisión es obligatoria',
                'commission.integer' => 'La comisión debe ser un número entero',
                'commission.min' => 'La comisión debe ser mínimo 0%',
                'commission.max' => 'La comisión debe ser máximo 100%',
                'bank_account.required' => 'La cuenta bancaria es obligatoria',
                'bank_account.string' => 'La cuenta bancaria debe ser una cadena de texto',
                'bank_account.max' => 'La cuenta bancaria no puede tener más de 20 caracteres',
                'clabe.required' => 'La CLABE es obligatoria',
                'clabe.string' => 'La CLABE debe ser una cadena de texto',
                'clabe.size' => 'La CLABE debe tener exactamente 18 caracteres',
                'clabe.unique' => 'Esta CLABE ya está registrada con otro proveedor',
                'user_id.required' => 'El ID del usuario es obligatorio',
                'user_id.integer' => 'El ID del usuario debe ser un número entero',
                'user_id.exists' => 'El usuario seleccionado no existe',
                'user_id.unique' => 'Este usuario ya está asignado a otro proveedor'
            ]);

            $provider = Provider::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Proveedor creado correctamente',
                'data' => $provider
            ], 201);
        
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el proveedor',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/providers/{id}",
     *     operationId="getProviderById",
     *     tags={"Providers"},
     *     summary="Obtener un proveedor específico",
     *     description="Retorna los datos de un proveedor específico por su ID con información del usuario",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del proveedor",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Proveedor obtenido exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="business_name", type="string", example="Empresa ABC S.A."),
     *                 @OA\Property(property="tax", type="string", example="ABC123456789"),
     *                 @OA\Property(property="commission", type="integer", example=15),
     *                 @OA\Property(property="bank_account", type="string", example="1234567890123456"),
     *                 @OA\Property(property="clabe", type="string", example="123456789012345678"),
     *                 @OA\Property(property="user_id", type="integer", example=1),
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Juan"),
     *                     @OA\Property(property="email", type="string", example="juan@empresa.com")
     *                 ),
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
     *         description="Proveedor no encontrado"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Error al mostrar el proveedor"),
     *             @OA\Property(property="error", type="string", example="Mensaje de error específico")
     *         )
     *     )
     * )
     */
    public function show(Provider $provider): JsonResponse
    {
        try {
            $provider->load('user:id,name,email');
            
            return response()->json([
                'success' => true,
                'data'    => $provider
            ], 200);
        
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al mostrar el proveedor',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/providers/{id}",
     *     operationId="updateProvider",
     *     tags={"Providers"},
     *     summary="Actualizar un proveedor",
     *     description="Actualiza los datos de un proveedor específico",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del proveedor",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="business_name", type="string", example="Empresa XYZ S.A."),
     *             @OA\Property(property="tax", type="string", example="XYZ987654321"),
     *             @OA\Property(property="commission", type="integer", minimum=0, maximum=100, example=20),
     *             @OA\Property(property="bank_account", type="string", example="9876543210987654"),
     *             @OA\Property(property="clabe", type="string", example="987654321098765432")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Proveedor actualizado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Se actualizó correctamente el proveedor"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="business_name", type="string", example="Empresa XYZ S.A."),
     *                 @OA\Property(property="tax", type="string", example="XYZ987654321"),
     *                 @OA\Property(property="commission", type="integer", example=20),
     *                 @OA\Property(property="bank_account", type="string", example="9876543210987654"),
     *                 @OA\Property(property="clabe", type="string", example="987654321098765432")
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
     *         description="Proveedor no encontrado"
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
     *             @OA\Property(property="message", type="string", example="Error al editar el proveedor"),
     *             @OA\Property(property="error", type="string", example="Mensaje de error específico")
     *         )
     *     )
     * )
     */
    public function update(Request $request, Provider $provider): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'business_name' => 'sometimes|required|string|max:255',
                'tax' => [
                    'sometimes',
                    'required',
                    'string',
                    'max:50',
                    Rule::unique('providers', 'tax')->ignore($provider->id)
                ],
                'commission' => 'sometimes|required|integer|min:0|max:100',
                'bank_account' => 'sometimes|required|string|max:20',
                'clabe' => [
                    'sometimes',
                    'required',
                    'string',
                    'size:18',
                    Rule::unique('providers', 'clabe')->ignore($provider->id)
                ]
            ], [
                'business_name.required' => 'El nombre de la empresa es obligatorio',
                'business_name.string' => 'El nombre de la empresa debe ser una cadena de texto',
                'business_name.max' => 'El nombre de la empresa no puede tener más de 255 caracteres',
                'tax.required' => 'El RFC/TAX es obligatorio',
                'tax.string' => 'El RFC/TAX debe ser una cadena de texto',
                'tax.max' => 'El RFC/TAX no puede tener más de 50 caracteres',
                'tax.unique' => 'Este RFC/TAX ya está registrado con otro proveedor',
                'commission.required' => 'La comisión es obligatoria',
                'commission.integer' => 'La comisión debe ser un número entero',
                'commission.min' => 'La comisión debe ser mínimo 0%',
                'commission.max' => 'La comisión debe ser máximo 100%',
                'bank_account.required' => 'La cuenta bancaria es obligatoria',
                'bank_account.string' => 'La cuenta bancaria debe ser una cadena de texto',
                'bank_account.max' => 'La cuenta bancaria no puede tener más de 20 caracteres',
                'clabe.required' => 'La CLABE es obligatoria',
                'clabe.string' => 'La CLABE debe ser una cadena de texto',
                'clabe.size' => 'La CLABE debe tener exactamente 18 caracteres',
                'clabe.unique' => 'Esta CLABE ya está registrada con otro proveedor'
            ]);

            $provider->update($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Se actualizó correctamente el proveedor',
                'data' => $provider
            ], 200);
        
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al editar el proveedor',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/providers/{id}",
     *     operationId="deleteProvider",
     *     tags={"Providers"},
     *     summary="Eliminar un proveedor",
     *     description="Elimina un proveedor específico del sistema (soft delete)",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del proveedor",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Proveedor eliminado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Se eliminó correctamente el proveedor")
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
     *         description="Proveedor no encontrado"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Error al eliminar el proveedor"),
     *             @OA\Property(property="error", type="string", example="Mensaje de error específico")
     *         )
     *     )
     * )
     */
    public function destroy(Provider $provider): JsonResponse
    {
        try {
            $provider->delete();
        
            return response()->json([
                'success' => true,
                'message' => 'Se eliminó correctamente el proveedor'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el proveedor',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
