<?php

namespace App\Http\Controllers;

use App\Models\Corrida;
use App\Models\Incidente;
use App\Models\Boleto;
use App\Models\Notificacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\TwilioService;

class IncidenteController extends Controller
{

    protected $correoService;

    public function __construct(TwilioService $correoService)
    {
        $this->correoService = $correoService;
    }

    // Registrar incidente y notificar
    public function registrarIncidente(Request $request)
    {
        $validated = $request->validate([
            'id_corrida' => 'required|exists:corrida,id',
            'tipo_incidencia' => 'required|string|max:255',
            'descripcion' => 'required|string',
            'evidencia' => 'nullable|file',
            'tiempo_estima_retraso' => 'required|date_format:H:i:s',
        ]);

        // Crear incidente
        $incidente = Incidente::create([
            'id_autobus' => Corrida::find($validated['id_corrida'])->id_autobus,
            'id_corrida' => $validated['id_corrida'],
            'tipo_incidencia' => $validated['tipo_incidencia'],
            'descripcion' => $validated['descripcion'],
            'evidencia' => $validated['evidencia'] ? $request->file('evidencia')->store('evidencias') : null,
            'tiempo_estima_retraso' => $validated['tiempo_estima_retraso'],
            'fecha' => now(),
        ]);

         // Recuperar todos los boletos de la corrida afectada
        $boletos = Boleto::where('id_corrida', $validated['id_corrida'])->get();

        // Recorrer cada boleto para notificar a los usuarios
        foreach ($boletos as $boleto) {
            Notificacion::create([
                'id_boleto' => $boleto->id,
                'id_incidente' => $incidente->id,
                'tipo' => 1, // Notificación de incidente
                'mensaje' => "Incidente registrado: {$validated['tipo_incidencia']} - {$validated['descripcion']}",
                'fecha_envio' => now(),
            ]);

            // Obtener usuario y enviar mensaje
            $usuario = $boleto->usuario; // Relación usuario en el modelo Boleto

            if ($usuario) {
                try {
                    // Crear mensaje detallado para el usuario>>
                    $mensaje = "¡Hola {$usuario->name}!\n";
                    $mensaje .= "Incidente registrado en tu corrida:\n";
                    $mensaje .= "Tipo de incidencia: {$validated['tipo_incidencia']}\n";
                    $mensaje .= "Descripción: {$validated['descripcion']}\n";
                    $mensaje .= "Tiempo estimado de retraso: {$validated['tiempo_estima_retraso']}\n";
                    $mensaje .= "Gracias por tu comprensión y paciencia.\n";
                    $mensaje .= "¡Estamos trabajando para resolverlo lo antes posible!";
            
                    // Enviar mensaje de WhatsApp
                    $this->correoService->sendSms(
                        'whatsapp:+521' . $usuario->telefono,
                        $mensaje
                    );
                } catch (\Exception $e) {
                    Log::error("Error al enviar WhatsApp a {$usuario->telefono}: " . $e->getMessage());
                }
            } else {
                Log::warning("Usuario no encontrado para el boleto ID: {$boleto->id}");
            }
        }

        return response()->json(['message' => 'Incidente registrado y notificado a todos los usuarios afectados.']);
    }
}
