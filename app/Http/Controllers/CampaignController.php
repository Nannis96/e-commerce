<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;

class CampaignController extends Controller
{
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

    public function store(Request $request)
    {
        try {
            // Validaciones
            $validatedData = $request->validate([
                'name' => 'required|string|max:100|unique:campaigns,name',
                'start_date' => 'required|date|after_or_equal:today',
                'end_date' => 'required|date|after:start_date',
                'currency' => 'required|string|max:100|in:USD,EUR,COP,MXN,ARS',
                'status' => 'nullable|in:Confirmed,Paid,Active,Finished,Pending,Cancelled',
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
                'status.in' => 'El estado del proveedor debe ser uno de los siguientes: Confirmed, Paid, Active, Finished, Pending, Cancelled',
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
