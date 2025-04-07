<?php

namespace App\Http\Controllers;

use App\Models\Boleto;
use App\Models\Corrida;
use App\Models\Notificacion;
use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use App\Services\TwilioService; // Asegúrate de tener el servicio de Twilio

class BoletoController extends Controller
{

    protected $twilioService;

    public function __construct(TwilioService $twilioService)
    {
        $this->twilioService = $twilioService;
    }

    // Compra de boletos
    public function comprarBoleto(Request $request)
    {
        $token = $request->input('token');
        $user = $this->getUserByToken($token);
    
        if (!$user) {
            return response()->json(['message' => 'Token inválido'], 401);
        }
    
        $validated = $request->validate([
            'id_corrida' => 'required|exists:corrida,id',
            'asientos' => 'required|array|min:1',
            'asientos.*' => 'integer|min:1',
        ]);
    
        $corrida = Corrida::find($validated['id_corrida']);
    
        // Validar disponibilidad de asientos
        $asientosDisponibles = collect($corrida->asientos);
        foreach ($validated['asientos'] as $asiento) {
            $asientoEncontrado = $asientosDisponibles->firstWhere('numero', $asiento);
            if (!$asientoEncontrado || $asientoEncontrado['estado'] !== 'disponible') {
                return response()->json(['message' => "Asiento {$asiento} no disponible"], 400);
            }
        }
    
        // Marcar los asientos como ocupados
        $corrida->asientos = $asientosDisponibles->map(function ($asiento) use ($validated) {
            if (in_array($asiento['numero'], $validated['asientos'])) {
                $asiento['estado'] = 'ocupado';
            }
            return $asiento;
        })->toArray();
        $corrida->save();
    
        // Crear boleto
        $boleto = Boleto::create([
            'num_boleto' => uniqid('BOL'),
            'id_usuario' => $user->id,
            'id_corrida' => $validated['id_corrida'],
            'num_asientos' => count($validated['asientos']),
            'fecha_compra' => now(),
            'monto' => $corrida->precio * count($validated['asientos']),
            'descuento' => 0,
            'id_pago' => 1, // Simulado
            'estado' => 1,  // Activo
        ]);

        // Enviar mensaje de confirmación por WhatsApp
        try {
            $this->twilioService->sendSms(
                'whatsapp:+521' . $user->telefono,
                "¡Hola {$user->name}!\nTu compra de boleto fue exitosa.\nBoleto número: {$boleto->num_boleto}\nAsientos: " . implode(", ", $validated['asientos']) . "\nMonto: $ {$boleto->monto}\nFecha de compra: {$boleto->fecha_compra}\nGracias por elegirnos."
            );
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al enviar notificación por WhatsApp: ' . $e->getMessage()], 500);
        }
    
        return response()->json(['message' => 'Boleto comprado con éxito', 'boleto' => $boleto]);
    }
    
    // Cancelar boletos
    public function cancelarBoleto(Request $request)
    {
        $token = $request->input('token');
        $user = $this->getUserByToken($token);

        if (!$user) {
            return response()->json(['message' => 'Token inválido'], 401);
        }

        $validated = $request->validate([
            'id_boleto' => 'required|exists:boleto,id',
        ]);

        $boleto = Boleto::find($validated['id_boleto']);

        if ($boleto->id_usuario !== $user->id) {
            return response()->json(['message' => 'No autorizado para cancelar este boleto'], 403);
        }

        $boleto->estado = 0; // Cancelado
        $boleto->save();

        // Enviar mensaje de cancelación por WhatsApp
        try {
            $this->twilioService->sendSms(
                'whatsapp:' . $user->telefono,
                "Tu boleto ha sido cancelado. Boleto número: {$boleto->num_boleto}."
            );
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al enviar notificación de cancelación por WhatsApp: ' . $e->getMessage()], 500);
        }

        return response()->json(['message' => 'Boleto cancelado con éxito']);
    }
    
    // Visualizar boletos
    public function visualizarBoletos(Request $request)
    {
        $token = $request->input('token');
        $user = $this->getUserByToken($token);

        if (!$user) {
            return response()->json(['message' => 'Token inválido'], 401);
        }

        $boletos = Boleto::where('id_usuario', $user->id)->get();

        return response()->json(['message' => 'Boletos obtenidos con éxito', 'boletos' => $boletos]);
    }
    public function reporteBoletosVendidos(Request $request)
    {
        // --- Autenticación y Autorización ---
        $token = $request->input('token');
        $user = $this->getUserByToken($token);

        // Verifica si el usuario existe y tiene el rol adecuado (ej. 'admin')
        // Ajusta 'admin' al valor real que uses para identificar administradores en tu campo 'rol'
        if (!$user || $user->rol !== '1') {
            return response()->json(['message' => 'No autorizado para generar este reporte'], 403);
        }
        // Alternativamente, si usas autenticación web/session, podrías usar:
        // if (!auth()->check() || auth()->user()->rol !== 'admin') {
        //     return response()->json(['message' => 'No autorizado'], 403);
        // }

        // --- Lógica del Reporte ---
        $query = Boleto::query()
            ->with([
                'user:id,name,apellido_p,email', // Carga usuario relacionado (selecciona columnas específicas)
                'corrida:id,origen,destino,fecha,hora_salida' // Carga corrida relacionada
            ]);

        // --- Filtrado (Ejemplos) ---

        // 1. Filtrar por Rango de Fechas de Compra
        if ($request->has('fecha_inicio') && $request->has('fecha_fin')) {
            $request->validate([
                'fecha_inicio' => 'required|date_format:Y-m-d',
                'fecha_fin' => 'required|date_format:Y-m-d|after_or_equal:fecha_inicio',
            ]);
            // Asegúrate que la comparación incluya todo el día final
            $query->whereBetween('fecha_compra', [$request->fecha_inicio . ' 00:00:00', $request->fecha_fin . ' 23:59:59']);
        }

        // 2. Filtrar por Estado del Boleto (1: Activo, 0: Cancelado)
        if ($request->filled('estado')) {
            $request->validate(['estado' => 'integer|in:0,1']);
            $query->where('estado', $request->estado);
        } else {
            // Por defecto, podrías mostrar solo los activos si no se especifica estado
             $query->where('estado', 1);
        }

        // 3. Filtrar por ID de Corrida específica
        if ($request->filled('id_corrida')) {
            $request->validate(['id_corrida' => 'integer|exists:corrida,id']);
            $query->where('id_corrida', $request->id_corrida);
        }

        // --- Obtener y Devolver Resultados ---
        // Ordenar y paginar para manejar grandes cantidades de datos
        $boletosReporte = $query->orderBy('fecha_compra', 'desc')->paginate(50); // Muestra 50 por página

        return response()->json([
            'message' => 'Reporte de boletos vendidos generado con éxito',
            'reporte' => $boletosReporte // Contiene los datos paginados
        ]);
    }
    // Helper para obtener usuario por token
    private function getUserByToken($token)
    {
        $accessToken = PersonalAccessToken::findToken($token);
        return $accessToken ? $accessToken->tokenable : null;
    }
    
}
