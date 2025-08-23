<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Tag(
 *     name="Campaigns",
 *     description="API endpoints for managing advertising campaigns"
 * )
 */
class CampaignController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/campaigns",
     *     summary="Get all campaigns",
     *     description="Retrieve a paginated list of campaigns with role-based access control. Admins see all campaigns, Providers see campaigns using their media, Clients see only their own campaigns",
     *     operationId="getCampaigns",
     *     tags={"Campaigns"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of items per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=15, minimum=1, maximum=100)
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer", default=1, minimum=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Campaigns retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="per_page", type="integer", example=15),
     *                 @OA\Property(property="total", type="integer", example=25),
     *                 @OA\Property(
     *                     property="data",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="Campaña Navideña 2025"),
     *                         @OA\Property(property="start_date", type="string", format="date", example="2025-12-01"),
     *                         @OA\Property(property="end_date", type="string", format="date", example="2025-12-31"),
     *                         @OA\Property(property="total", type="number", format="float", example=1500.50),
     *                         @OA\Property(property="currency", type="string", example="USD"),
     *                         @OA\Property(property="status", type="string", example="Confirmed"),
     *                         @OA\Property(property="user_id", type="integer", example=1),
     *                         @OA\Property(property="created_at", type="string", format="date-time"),
     *                         @OA\Property(property="updated_at", type="string", format="date-time"),
     *                         @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true),
     *                         @OA\Property(
     *                             property="user",
     *                             type="object",
     *                             @OA\Property(property="id", type="integer", example=1),
     *                             @OA\Property(property="name", type="string", example="Juan Pérez"),
     *                             @OA\Property(property="email", type="string", example="juan@example.com")
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Invalid role",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="No tienes permisos para ver campañas")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Error al mostrar las campañas"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function index()
    {
        try {
            $user = Auth::user();
            $query = Campaign::query();

            switch ($user->role) {
                case 'Admin':
                    $query = Campaign::with(['user']);
                    break;
                    
                case 'Provider':
                    $query = Campaign::with(['user'])
                        ->whereHas('campaignItems.media', function($q) use ($user) {
                            $q->where('user_id', $user->id);
                        });
                    break;
                    
                case 'Client':
                    $query = Campaign::with(['user'])
                        ->where('user_id', $user->id);
                    break;
                    
                default:
                    $query = Campaign::whereRaw('1 = 0');
            }

            $campaigns = $query->orderBy('id', 'desc')->paginate();

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

    /**
     * @OA\Post(
     *     path="/api/v1/campaigns",
     *     summary="Create a new campaign",
     *     description="Create a new advertising campaign with date validation and unique name constraint",
     *     operationId="createCampaign",
     *     tags={"Campaigns"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Campaign data",
     *         @OA\JsonContent(
     *             required={"name", "start_date", "end_date", "currency"},
     *             @OA\Property(property="name", type="string", maxLength=100, example="Campaña Navideña 2025", description="Unique campaign name"),
     *             @OA\Property(property="start_date", type="string", format="date", example="2025-12-01", description="Campaign start date (must be today or future)"),
     *             @OA\Property(property="end_date", type="string", format="date", example="2025-12-31", description="Campaign end date (must be after start_date)"),
     *             @OA\Property(property="currency", type="string", enum={"USD", "EUR", "COP", "MXN", "ARS"}, example="USD", description="Campaign currency"),
     *             @OA\Property(property="status", type="string", enum={"Confirmed", "Paid", "Active", "Finished", "Pending", "Cancelled"}, example="Pending", description="Optional campaign status"),
     *             @OA\Property(property="user_id", type="integer", example=1, description="Optional user ID (defaults to authenticated user)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Campaign created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="La campaña fue creada correctamente"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Campaña Navideña 2025"),
     *                 @OA\Property(property="start_date", type="string", format="date", example="2025-12-01"),
     *                 @OA\Property(property="end_date", type="string", format="date", example="2025-12-31"),
     *                 @OA\Property(property="total", type="number", format="float", nullable=true, example=null),
     *                 @OA\Property(property="currency", type="string", example="USD"),
     *                 @OA\Property(property="status", type="string", example="Pending"),
     *                 @OA\Property(property="user_id", type="integer", example=1),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time"),
     *                 @OA\Property(
     *                     property="user",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Juan Pérez"),
     *                     @OA\Property(property="email", type="string", example="juan@example.com")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="name",
     *                     type="array",
     *                     @OA\Items(type="string", example="Ya existe una campaña con este nombre")
     *                 ),
     *                 @OA\Property(
     *                     property="start_date",
     *                     type="array",
     *                     @OA\Items(type="string", example="La fecha de inicio no puede ser anterior a hoy")
     *                 ),
     *                 @OA\Property(
     *                     property="end_date",
     *                     type="array",
     *                     @OA\Items(type="string", example="La fecha de fin debe ser posterior a la fecha de inicio")
     *                 ),
     *                 @OA\Property(
     *                     property="currency",
     *                     type="array",
     *                     @OA\Items(type="string", example="La moneda debe ser una de las siguientes: USD, EUR, COP, MXN, ARS")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Error al crear la campaña"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        try {
            // Validaciones
            $validatedData = $request->validate([
                'name' => 'required|string|max:100|unique:campaigns,name',
                'start_date' => 'required|date|after_or_equal:today',
                'end_date' => 'required|date|after:start_date',
                'currency' => 'required|string|max:100|in:USD,EUR,COP,MXN,ARS',
                'user_id' => 'nullable|exists:users,id'
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
                'currency.required' => 'La moneda es obligatoria',
                'currency.string' => 'La moneda debe ser una cadena de texto',
                'currency.max' => 'La moneda no puede tener más de 100 caracteres',
                'currency.in' => 'La moneda debe ser una de las siguientes: USD, EUR, COP, MXN, ARS',
                'user_id.exists' => 'El usuario seleccionado no existe'
            ]);

            if (!isset($validatedData['user_id'])) {
                $validatedData['user_id'] = Auth::user()->id;
            }

            $campaign = Campaign::create($validatedData);
            $campaign->load(['user']);

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

    /**
     * @OA\Get(
     *     path="/api/v1/campaigns/{id}",
     *     summary="Get a specific campaign",
     *     description="Retrieve a specific campaign by its ID with user information",
     *     operationId="getCampaignById",
     *     tags={"Campaigns"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Campaign ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Campaign retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Campaña Navideña 2025"),
     *                 @OA\Property(property="start_date", type="string", format="date", example="2025-12-01"),
     *                 @OA\Property(property="end_date", type="string", format="date", example="2025-12-31"),
     *                 @OA\Property(property="total", type="number", format="float", example=1500.50),
     *                 @OA\Property(property="currency", type="string", example="USD"),
     *                 @OA\Property(property="status", type="string", example="Confirmed"),
     *                 @OA\Property(property="user_id", type="integer", example=1),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time"),
     *                 @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true),
     *                 @OA\Property(
     *                     property="user",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Juan Pérez"),
     *                     @OA\Property(property="email", type="string", example="juan@example.com"),
     *                     @OA\Property(property="role", type="string", example="Client")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Campaign not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No query results for model [App\\Models\\Campaign].")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Error al mostrar la campaña"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function show(Campaign $campaign)
    {
        try {
            $campaign->load(['user']);
            
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

    /**
     * @OA\Put(
     *     path="/api/v1/campaigns/{id}",
     *     summary="Update a campaign",
     *     description="Update an existing campaign. Date fields are not updated to preserve campaign integrity",
     *     operationId="updateCampaign",
     *     tags={"Campaigns"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Campaign ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Campaign data to update",
     *         @OA\JsonContent(
     *             required={"name", "currency"},
     *             @OA\Property(property="name", type="string", maxLength=100, example="Campaña Navideña 2025 Actualizada", description="Updated unique campaign name"),
     *             @OA\Property(property="currency", type="string", enum={"USD", "EUR", "COP", "MXN", "ARS"}, example="EUR", description="Updated campaign currency"),
     *             @OA\Property(property="status", type="string", enum={"Confirmed", "Paid", "Active", "Finished", "Pending", "Cancelled"}, example="Confirmed", description="Updated campaign status"),
     *             @OA\Property(property="user_id", type="integer", example=2, description="Updated user ID")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Campaign updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="La campaña se actualizó correctamente"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Campaña Navideña 2025 Actualizada"),
     *                 @OA\Property(property="start_date", type="string", format="date", example="2025-12-01"),
     *                 @OA\Property(property="end_date", type="string", format="date", example="2025-12-31"),
     *                 @OA\Property(property="total", type="number", format="float", example=1500.50),
     *                 @OA\Property(property="currency", type="string", example="EUR"),
     *                 @OA\Property(property="status", type="string", example="Confirmed"),
     *                 @OA\Property(property="user_id", type="integer", example=2),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time"),
     *                 @OA\Property(
     *                     property="user",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=2),
     *                     @OA\Property(property="name", type="string", example="María García"),
     *                     @OA\Property(property="email", type="string", example="maria@example.com")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Campaign not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No query results for model [App\\Models\\Campaign].")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="name",
     *                     type="array",
     *                     @OA\Items(type="string", example="Ya existe una campaña con este nombre")
     *                 ),
     *                 @OA\Property(
     *                     property="currency",
     *                     type="array",
     *                     @OA\Items(type="string", example="La moneda debe ser una de las siguientes: USD, EUR, COP, MXN, ARS")
     *                 ),
     *                 @OA\Property(
     *                     property="user_id",
     *                     type="array",
     *                     @OA\Items(type="string", example="El usuario seleccionado no existe")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Error al editar la campaña"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function update(Request $request, Campaign $campaign)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:100|unique:campaigns,name,' . $campaign->id,
                'currency' => 'required|string|max:100|in:USD,EUR,COP,MXN,ARS',
                'status' => 'nullable|in:Confirmed,Paid,Active,Finished,Pending,Cancelled',
                'user_id' => 'nullable|exists:users,id'
            ], [
                'name.required' => 'El nombre de la campaña es obligatorio',
                'name.string' => 'El nombre debe ser una cadena de texto',
                'name.max' => 'El nombre no puede tener más de 100 caracteres',
                'name.unique' => 'Ya existe una campaña con este nombre',
                'currency.required' => 'La moneda es obligatoria',
                'currency.string' => 'La moneda debe ser una cadena de texto',
                'currency.max' => 'La moneda no puede tener más de 100 caracteres',
                'currency.in' => 'La moneda debe ser una de las siguientes: USD, EUR, COP, MXN, ARS',
                'status.in' => 'El estado del proveedor debe ser uno de los siguientes: Confirmed, Paid, Active, Finished, Pending, Cancelled',
                'user_id.exists' => 'El usuario seleccionado no existe'
            ]);

            $campaign->update($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'La campaña se actualizó correctamente',
                'data' => $campaign->fresh(['user'])
            ], 200);
        
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al editar la campaña',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/campaigns/{id}",
     *     summary="Delete a campaign",
     *     description="Delete an existing campaign (soft delete)",
     *     operationId="deleteCampaign",
     *     tags={"Campaigns"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Campaign ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Campaign deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="La campaña se eliminó correctamente")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Campaign not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No query results for model [App\\Models\\Campaign].")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Error al eliminar la campaña"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
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

    /**
     * @OA\Patch(
     *     path="/api/v1/campaigns/{id}/cancel",
     *     summary="Cancel a campaign",
     *     description="Cancel a campaign with penalty calculation based on days until start date. Campaigns within 7 days of start have 50% penalty, campaigns with more than 7 days have no penalty. Already started, finished, or cancelled campaigns cannot be cancelled",
     *     operationId="cancelCampaign",
     *     tags={"Campaigns"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Campaign ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Campaign cancelled successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="La campaña fue cancelada correctamente"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="campaign",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Campaña Navideña 2025"),
     *                     @OA\Property(property="start_date", type="string", format="date", example="2025-12-01"),
     *                     @OA\Property(property="end_date", type="string", format="date", example="2025-12-31"),
     *                     @OA\Property(property="total", type="number", format="float", example=1500.50),
     *                     @OA\Property(property="currency", type="string", example="USD"),
     *                     @OA\Property(property="status", type="string", example="Cancelled"),
     *                     @OA\Property(property="user_id", type="integer", example=1),
     *                     @OA\Property(
     *                         property="user",
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="Juan Pérez"),
     *                         @OA\Property(property="email", type="string", example="juan@example.com")
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="cancellation_details",
     *                     type="object",
     *                     @OA\Property(property="cancellation_date", type="string", format="date", example="2025-08-22"),
     *                     @OA\Property(property="original_total", type="number", format="float", example=1500.50),
     *                     @OA\Property(property="penalty_percentage", type="integer", example=50),
     *                     @OA\Property(property="penalty_amount", type="number", format="float", example=750.25),
     *                     @OA\Property(property="penalty_reason", type="string", example="Cancelación realizada a 7 días o menos del inicio")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Campaign not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No query results for model [App\\Models\\Campaign].")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Campaign cannot be cancelled",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="La campaña ya está cancelada"),
     *             @OA\AdditionalProperties(
     *                 oneOf={
     *                     @OA\Schema(
     *                         @OA\Property(property="message", type="string", example="No se puede cancelar una campaña que ya ha terminado")
     *                     ),
     *                     @OA\Schema(
     *                         @OA\Property(property="message", type="string", example="No se puede cancelar una campaña que ya ha comenzado")
     *                     )
     *                 }
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Error al cancelar la campaña"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    /**
     * Cancel a campaign with penalty calculation based on days until start
     */
    public function cancel(Campaign $campaign)
    {
        try {
            // Verificar que la campaña no esté ya cancelada
            if ($campaign->status === 'Cancelled') {
                return response()->json([
                    'success' => false,
                    'message' => 'La campaña ya está cancelada'
                ], 422);
            }

            // Verificar que la campaña no haya terminado
            if ($campaign->status === 'Finished') {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede cancelar una campaña que ya ha terminado'
                ], 422);
            }

            // Calcular días hasta el inicio de la campaña
            $today = \Carbon\Carbon::now()->startOfDay();
            $startDate = \Carbon\Carbon::parse($campaign->start_date)->startOfDay();
            $daysUntilStart = $today->diffInDays($startDate, false); // false para obtener valor negativo si ya pasó

            // Si la campaña ya comenzó, no se puede cancelar
            if ($daysUntilStart < 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede cancelar una campaña que ya ha comenzado'
                ], 422);
            }

            // Calcular penalización
            $originalTotal = $campaign->total ?? 0; // Usar 0 si total es null
            $penaltyAmount = 0;
            $refundAmount = $originalTotal;
            $penaltyPercentage = 0;

            if ($daysUntilStart <= 7) {
                // Si está a 7 días o menos, se cobra 50% de penalización
                $penaltyPercentage = 50;
                $penaltyAmount = ($originalTotal * $penaltyPercentage) / 100;
                $refundAmount = $originalTotal - $penaltyAmount;
            }
            // Si está a más de 7 días, no hay penalización (valores por defecto)

            // Actualizar el estado de la campaña
            $campaign->update([
                'status' => 'Cancelled'
            ]);

            // Cargar relaciones para la respuesta
            $campaign->load(['user']);

            return response()->json([
                'success' => true,
                'message' => 'La campaña fue cancelada correctamente',
                'data' => [
                    'campaign' => $campaign,
                    'cancellation_details' => [
                        'cancellation_date' => $today->toDateString(),
                        'original_total' => $originalTotal,
                        'penalty_percentage' => $penaltyPercentage,
                        'penalty_amount' => $penaltyAmount,
                        'penalty_reason' => $daysUntilStart <= 7 
                            ? 'Cancelación realizada a 7 días o menos del inicio' 
                            : 'Cancelación realizada con más de 7 días de anticipación'
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cancelar la campaña',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
