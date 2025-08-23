<?php

namespace App\Http\Controllers;

use App\Models\Payout;
use App\Models\Campaign;
use App\Models\Provider;
use App\Models\CampaignItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * @OA\Tag(
 *     name="Payouts",
 *     description="API Endpoints para gestión de pagos a proveedores"
 * )
 */
class PayoutController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/payouts",
     *     summary="Get all payouts",
     *     description="Retrieve a paginated list of payouts with role-based access control. Admins see all payouts, Providers see only their own payouts",
     *     operationId="getPayouts",
     *     tags={"Payouts"},
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
     *         description="Payouts retrieved successfully",
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
     *                         @OA\Property(property="amount", type="number", format="float", example=450.75),
     *                         @OA\Property(property="status", type="string", example="Pending"),
     *                         @OA\Property(property="campaign_id", type="integer", example=1),
     *                         @OA\Property(property="user_id", type="integer", example=2),
     *                         @OA\Property(property="created_at", type="string", format="date-time"),
     *                         @OA\Property(property="updated_at", type="string", format="date-time"),
     *                         @OA\Property(
     *                             property="campaign",
     *                             type="object",
     *                             @OA\Property(property="id", type="integer", example=1),
     *                             @OA\Property(property="name", type="string", example="Campaña Navideña"),
     *                             @OA\Property(property="status", type="string", example="Paid")
     *                         ),
     *                         @OA\Property(
     *                             property="user",
     *                             type="object",
     *                             @OA\Property(property="id", type="integer", example=2),
     *                             @OA\Property(property="name", type="string", example="Proveedor García"),
     *                             @OA\Property(property="email", type="string", example="proveedor@example.com"),
     *                             @OA\Property(property="role", type="string", example="Provider")
     *                         ),
     *                         @OA\Property(
     *                             property="provider",
     *                             type="object",
     *                             @OA\Property(property="id", type="integer", example=1),
     *                             @OA\Property(property="company_name", type="string", example="Medios García S.A."),
     *                             @OA\Property(property="commission", type="number", format="float", example=30.0)
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
     *             @OA\Property(property="message", type="string", example="No tienes permisos para ver pagos a proveedores")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Error al mostrar los pagos a proveedores"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function index()
    {
        try {
            $user = Auth::user();
            $query = Payout::query()->with(['campaign', 'user', 'provider']);

            // Filtrar según el rol del usuario
            switch ($user->role) {
                case 'Admin':
                    break;
                    
                case 'Provider':
                    $query->where('user_id', $user->id);
                    break;
                    
                default:
                    $query->whereRaw('1 = 0');
            }

            $payouts = $query->orderBy('id', 'desc')->paginate();

            return response()->json([
                'success' => true,
                'data'    => $payouts
            ], 200);
        
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al mostrar los pagos a proveedores',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/payouts",
     *     operationId="createPayout",
     *     tags={"Payouts"},
     *     summary="Crear pagos a proveedores (Solo Admin)",
     *     description="Genera pagos automáticos a proveedores basados en una campaña completada. Solo usuarios con rol Admin pueden acceder.",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"campaign_id"},
     *             @OA\Property(property="campaign_id", type="integer", example=1, description="ID de la campaña pagada")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Pagos a proveedores creados exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Pagos a proveedores generados exitosamente"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="total_payouts", type="integer", example=3),
     *                 @OA\Property(property="total_amount", type="number", example=1500.00),
     *                 @OA\Property(property="campaign_id", type="integer", example=1)
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
     *             @OA\Property(property="message", type="string", example="Solo los administradores pueden crear pagos a proveedores")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación o reglas de negocio",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Solo se pueden generar pagos para campañas que han sido pagadas")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Error al crear los pagos"),
     *             @OA\Property(property="error", type="string", example="Mensaje de error específico")
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        try {
            $user = Auth::user();

            if ($user->role !== 'Admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo los administradores pueden crear pagos a proveedores'
                ], 403);
            }

            // Validaciones
            $validatedData = $request->validate([
                'campaign_id' => 'required|exists:campaigns,id'
            ], [
                'campaign_id.required' => 'El ID de la campaña es obligatorio',
                'campaign_id.exists' => 'La campaña seleccionada no existe'
            ]);

            $campaign = Campaign::findOrFail($validatedData['campaign_id']);

            if ($campaign->status !== 'Paid') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden generar pagos para campañas que han sido pagadas'
                ], 422);
            }

            $existingPayouts = Payout::where('campaign_id', $campaign->id)->exists();
            if ($existingPayouts) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya existen pagos generados para esta campaña'
                ], 422);
            }

            // Obtener todos los campaign items de la campaña con medios aceptados
            $campaignItems = CampaignItem::where('campaign_id', $campaign->id)
                ->where('provider_status', 'Accepted')
                ->with(['media.user.provider'])
                ->get();

            if ($campaignItems->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay medios aceptados en esta campaña para generar pagos'
                ], 422);
            }

            $createdPayouts = [];
            $totalPayoutAmount = 0;

            DB::beginTransaction();

            try {
                // Agrupar por usuario proveedor y calcular el total por proveedor
                $providerPayouts = [];

                foreach ($campaignItems as $item) {
                    $media = $item->media;
                    $providerUser = $media->user;
                    $provider = $providerUser->provider;

                    // Verificar que el usuario sea un proveedor válido
                    if ($providerUser->role !== 'Provider') {
                        continue; 
                    }

                    $subtotal = $item->subtotal ?? 0;
                    $commission = $provider->commission; // Porcentaje de comisión
                    $payoutAmount = ($subtotal * $commission) / 100;

                    if (!isset($providerPayouts[$providerUser->id])) {
                        $providerPayouts[$providerUser->id] = [
                            'user' => $providerUser,
                            'provider' => $provider,
                            'total_amount' => 0,
                            'items' => []
                        ];
                    }

                    $providerPayouts[$providerUser->id]['total_amount'] += $payoutAmount;
                    $providerPayouts[$providerUser->id]['items'][] = [
                        'campaign_item_id' => $item->id,
                        'media_name' => $media->name,
                        'subtotal' => $subtotal,
                        'commission_pct' => $commission,
                        'payout_amount' => $payoutAmount
                    ];
                }

                // Crear los pagos por proveedor
                foreach ($providerPayouts as $userId => $providerData) {
                    $providerUser = $providerData['user'];
                    $provider = $providerData['provider'];
                    $amount = $providerData['total_amount'];

                    if ($amount > 0) {
                        $payout = Payout::create([
                            'amount' => $amount,
                            'status' => 'Paid',
                            'campaign_id' => $campaign->id,
                            'user_id' => $userId
                        ]);

                        $payout->load(['campaign', 'user', 'provider']);

                        $payoutData = $payout->toArray();
                        $payoutData['calculation_details'] = [
                            'provider_commission_pct' => $provider->commission,
                            'items_breakdown' => $providerData['items'],
                            'total_items' => count($providerData['items'])
                        ];

                        $createdPayouts[] = $payoutData;
                        $totalPayoutAmount += $amount;
                    }
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Los pagos a proveedores fueron generados correctamente',
                    'data' => [
                        'payouts' => $createdPayouts,
                        'summary' => [
                            'campaign_id' => $campaign->id,
                            'campaign_name' => $campaign->name,
                            'total_providers' => count($createdPayouts),
                            'total_payout_amount' => $totalPayoutAmount,
                            'campaign_total' => $campaign->total
                        ]
                    ]
                ], 201);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar los pagos a proveedores',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/payouts/{id}",
     *     summary="Get a specific payout",
     *     description="Retrieve a specific payout by its ID with role-based access control. Admins can see any payout, Providers can only see their own payouts",
     *     operationId="getPayoutById",
     *     tags={"Payouts"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Payout ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payout retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="amount", type="number", format="float", example=450.75),
     *                 @OA\Property(property="status", type="string", example="Pending"),
     *                 @OA\Property(property="campaign_id", type="integer", example=1),
     *                 @OA\Property(property="user_id", type="integer", example=2),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time"),
     *                 @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true),
     *                 @OA\Property(
     *                     property="campaign",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Campaña Navideña"),
     *                     @OA\Property(property="status", type="string", example="Paid"),
     *                     @OA\Property(property="total", type="number", format="float", example=1500.00),
     *                     @OA\Property(property="start_date", type="string", format="date", example="2025-12-01"),
     *                     @OA\Property(property="end_date", type="string", format="date", example="2025-12-31")
     *                 ),
     *                 @OA\Property(
     *                     property="user",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=2),
     *                     @OA\Property(property="name", type="string", example="Proveedor García"),
     *                     @OA\Property(property="email", type="string", example="proveedor@example.com"),
     *                     @OA\Property(property="role", type="string", example="Provider"),
     *                     @OA\Property(
     *                         property="provider",
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="company_name", type="string", example="Medios García S.A."),
     *                         @OA\Property(property="commission", type="number", format="float", example=30.0),
     *                         @OA\Property(property="contact_phone", type="string", example="+1234567890"),
     *                         @OA\Property(property="address", type="string", example="Av. Principal 123")
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
     *         description="Forbidden - Access denied",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="No tienes permisos para ver este pago")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Payout not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No query results for model [App\\Models\\Payout].")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Error al mostrar el pago"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function show(Payout $payout)
    {
        try {
            $user = Auth::user();
            
            // Verificar permisos según el rol
            if ($user->role === 'Provider') {
                // Los proveedores solo pueden ver sus propios pagos
                if ($payout->user_id !== $user->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No tienes permisos para ver este pago'
                    ], 403);
                }
            } elseif ($user->role !== 'Admin') {
                // Solo admins y proveedores pueden ver pagos
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para ver pagos a proveedores'
                ], 403);
            }

            $payout->load(['campaign', 'user']);
            
            return response()->json([
                'success' => true,
                'data'    => $payout
            ], 200);
        
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al mostrar el pago',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
