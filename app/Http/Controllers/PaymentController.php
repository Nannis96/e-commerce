<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Campaign;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * @OA\Tag(
 *     name="Payments",
 *     description="API endpoints for managing payments"
 * )
 */
class PaymentController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/payments",
     *     summary="Get all payments",
     *     description="Retrieve a paginated list of payments with role-based access control. Admins see all payments, Clients see only their own campaign payments",
     *     operationId="getPayments",
     *     tags={"Payments"},
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
     *         description="Payments retrieved successfully",
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
     *                         @OA\Property(property="amount", type="number", format="float", example=1500.50),
     *                         @OA\Property(property="status", type="string", example="Success"),
     *                         @OA\Property(property="campaign_id", type="integer", example=1),
     *                         @OA\Property(property="created_at", type="string", format="date-time"),
     *                         @OA\Property(property="updated_at", type="string", format="date-time"),
     *                         @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true),
     *                         @OA\Property(
     *                             property="campaign",
     *                             type="object",
     *                             @OA\Property(property="id", type="integer", example=1),
     *                             @OA\Property(property="name", type="string", example="Campaña Navideña"),
     *                             @OA\Property(property="status", type="string", example="Paid")
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
     *             @OA\Property(property="message", type="string", example="No tienes permisos para ver pagos")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Error al mostrar los pagos"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function index()
    {
        try {
            $user = Auth::user();
            $query = Payment::query()->with(['campaign']);

            switch ($user->role) {
                case 'Admin':
                    break;
                    
                case 'Client':
                    $query->whereHas('campaign', function($q) use ($user) {
                        $q->where('user_id', $user->id);
                    });
                    break;
                    
                default:
                    $query->whereRaw('1 = 0');
            }

            $payments = $query->orderBy('id', 'desc')->paginate();

            return response()->json([
                'success' => true,
                'data'    => $payments
            ], 200);
        
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al mostrar los pagos',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/payments",
     *     summary="Create a payment for a campaign (Admin only)",
     *     description="Process payment for a confirmed campaign. Only administrators can process payments for any campaign. Campaign must be in 'Confirmed' status and have a valid total amount",
     *     operationId="createPayment",
     *     tags={"Payments"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Payment data",
     *         @OA\JsonContent(
     *             required={"campaign_id"},
     *             @OA\Property(property="campaign_id", type="integer", example=1, description="ID of the campaign to pay for")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Payment processed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="El pago se realizó correctamente"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="payment",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="amount", type="number", format="float", example=1500.50),
     *                     @OA\Property(property="status", type="string", example="Success"),
     *                     @OA\Property(property="campaign_id", type="integer", example=1),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time"),
     *                     @OA\Property(
     *                         property="campaign",
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="Campaña Navideña"),
     *                         @OA\Property(property="status", type="string", example="Paid"),
     *                         @OA\Property(property="total", type="number", format="float", example=1500.50)
     *                     )
     *                 ),
     *                 @OA\Property(property="campaign_status_updated", type="string", example="Paid")
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
     *         description="Forbidden - Admin role required",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Solo los administradores pueden procesar pagos")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation or business logic error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Esta campaña ya ha sido pagada"),
     *             @OA\AdditionalProperties(
     *                 oneOf={
     *                     @OA\Schema(
     *                         @OA\Property(property="message", type="string", example="Solo se pueden pagar campañas confirmadas")
     *                     ),
     *                     @OA\Schema(
     *                         @OA\Property(property="message", type="string", example="La campaña debe tener un monto total válido para ser pagada")
     *                     ),
     *                     @OA\Schema(
     *                         @OA\Property(property="message", type="string", example="Ya existe un pago exitoso para esta campaña")
     *                     ),
     *                     @OA\Schema(
     *                         @OA\Property(property="message", type="string", example="Error de validación"),
     *                         @OA\Property(
     *                             property="errors",
     *                             type="object",
     *                             @OA\Property(
     *                                 property="campaign_id",
     *                                 type="array",
     *                                 @OA\Items(type="string", example="La campaña seleccionada no existe")
     *                             )
     *                         )
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
     *             @OA\Property(property="message", type="string", example="Error al procesar el pago"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        try {
            $user = Auth::user();

            // Solo los administradores pueden procesar pagos
            if ($user->role !== 'Admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo los administradores pueden procesar pagos'
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

            if ($campaign->status === 'Paid') {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta campaña ya ha sido pagada'
                ], 422);
            }

            if ($campaign->status !== 'Confirmed') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden pagar campañas confirmadas'
                ], 422);
            }

            if (!$campaign->total || $campaign->total <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'La campaña debe tener un monto total válido para ser pagada'
                ], 422);
            }

            $existingPayment = Payment::where('campaign_id', $campaign->id)
                ->where('status', 'Success')
                ->exists();

            if ($existingPayment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya existe un pago exitoso para esta campaña'
                ], 422);
            }

            $payment = Payment::create([
                'amount' => $campaign->total,
                'status' => 'Success',
                'campaign_id' => $campaign->id
            ]);

            $campaign->update([
                'status' => 'Paid'
            ]);

            $payment->load(['campaign']);

            return response()->json([
                'success' => true,
                'message' => 'El pago se realizó correctamente',
                'data' => [
                    'payment' => $payment,
                    'campaign_status_updated' => 'Paid'
                ]
            ], 201);
        
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el pago',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/payments/{id}",
     *     summary="Get a specific payment",
     *     description="Retrieve a specific payment by its ID with role-based access control. Admins can see any payment, Clients can only see payments for their own campaigns",
     *     operationId="getPaymentById",
     *     tags={"Payments"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Payment ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="amount", type="number", format="float", example=1500.50),
     *                 @OA\Property(property="status", type="string", example="Success"),
     *                 @OA\Property(property="campaign_id", type="integer", example=1),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time"),
     *                 @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true),
     *                 @OA\Property(
     *                     property="campaign",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Campaña Navideña"),
     *                     @OA\Property(property="status", type="string", example="Paid"),
     *                     @OA\Property(property="total", type="number", format="float", example=1500.50),
     *                     @OA\Property(property="start_date", type="string", format="date", example="2025-12-01"),
     *                     @OA\Property(property="end_date", type="string", format="date", example="2025-12-31"),
     *                     @OA\Property(property="user_id", type="integer", example=1)
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
     *         description="Payment not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No query results for model [App\\Models\\Payment].")
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
    public function show(Payment $payment)
    {
        try {
            $user = Auth::user();
            
            if ($user->role === 'Client') {
                if ($payment->campaign->user_id !== $user->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No tienes permisos para ver este pago'
                    ], 403);
                }
            } elseif ($user->role !== 'Admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para ver pagos'
                ], 403);
            }

            $payment->load(['campaign']);
            
            return response()->json([
                'success' => true,
                'data'    => $payment
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
