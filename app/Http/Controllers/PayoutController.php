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

class PayoutController extends Controller
{
    /**
     * Display a listing of the resource.
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
                            'status' => 'Pending',
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
