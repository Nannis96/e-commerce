<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Campaign;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
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

    public function store(Request $request)
    {
        try {
            $user = Auth::user();

            // Validaciones
            $validatedData = $request->validate([
                'campaign_id' => 'required|exists:campaigns,id'
            ], [
                'campaign_id.required' => 'El ID de la campaña es obligatorio',
                'campaign_id.exists' => 'La campaña seleccionada no existe'
            ]);

            $campaign = Campaign::findOrFail($validatedData['campaign_id']);

            if ($campaign->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para pagar esta campaña'
                ], 403);
            }

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
