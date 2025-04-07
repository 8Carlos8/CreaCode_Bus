<?php

namespace App\Http\Controllers;

use App\Models\Boleto;   // Necesario para el reporte de boletos
use App\Models\Incidente; // Necesario para el reporte de incidentes
use App\Models\User;     // Necesario para la autenticación/autorización
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // Opcional, por si lo necesitas después
use Laravel\Sanctum\PersonalAccessToken; // Para la autenticación por token

class ReporteController extends Controller
{
    /**
     * Genera un reporte de boletos vendidos con filtros opcionales.
     * Requiere permisos de administrador.
     */
    public function reporteBoletosVendidos(Request $request)
    {
        // --- Autenticación y Autorización ---
        $token = $request->input('token');
        $user = $this->getUserByToken($token);

        if (!$user || !$this->esAdmin($user)) { // Usando helper de autorización
            return response()->json(['message' => 'No autorizado para generar este reporte'], 403);
        }

        // --- Lógica del Reporte ---
        // Asegúrate que el modelo Boleto tenga las relaciones user() y corrida() PÚBLICAS
        $query = Boleto::query()
            ->with([
                'user:id,name,apellido_p,email',
                'corrida:id,origen,destino,fecha,hora_salida'
            ]);

        // --- Filtrado ---
        if ($request->has('fecha_inicio') && $request->has('fecha_fin')) {
            $request->validate([
                'fecha_inicio' => 'required|date_format:Y-m-d',
                'fecha_fin' => 'required|date_format:Y-m-d|after_or_equal:fecha_inicio',
            ]);
            $query->whereBetween('fecha_compra', [$request->fecha_inicio . ' 00:00:00', $request->fecha_fin . ' 23:59:59']);
        }

        if ($request->filled('estado')) {
            $request->validate(['estado' => 'integer|in:0,1']);
            $query->where('estado', $request->estado);
        } else {
             $query->where('estado', 1); // Mostrar activos por defecto
        }

        if ($request->filled('id_corrida')) {
            $request->validate(['id_corrida' => 'integer|exists:corrida,id']);
            $query->where('id_corrida', $request->id_corrida);
        }

        // --- Obtener y Devolver Resultados ---
        $boletosReporte = $query->orderBy('fecha_compra', 'desc')->paginate(50);

        return response()->json([
            'message' => 'Reporte de boletos vendidos generado con éxito',
            'reporte' => $boletosReporte
        ]);
    }

    /**
     * Genera un reporte de incidentes con filtros opcionales.
     * Requiere permisos de administrador.
     */
    public function reporteIncidentes(Request $request)
    {
        // --- Autenticación y Autorización ---
        $token = $request->input('token');
        $user = $this->getUserByToken($token);

        if (!$user || !$this->esAdmin($user)) { // Usando helper de autorización
            return response()->json(['message' => 'No autorizado para generar este reporte'], 403);
        }

        // --- Lógica del Reporte ---
         // Asegúrate que el modelo Incidente tenga las relaciones autobus() y corrida() PÚBLICAS
        $query = Incidente::query()
            ->with([
                'autobus:id,numero_autobus,linea',
                'corrida:id,origen,destino,fecha,hora_salida'
            ]);

        // --- Filtrado ---
        if ($request->has('fecha_inicio') && $request->has('fecha_fin')) {
             $request->validate([
                'fecha_inicio' => 'required|date_format:Y-m-d',
                'fecha_fin' => 'required|date_format:Y-m-d|after_or_equal:fecha_inicio',
            ]);
             $query->whereBetween('fecha', [$request->fecha_inicio, $request->fecha_fin]);
        }

        if ($request->filled('tipo_incidencia')) {
            $request->validate(['tipo_incidencia' => 'string|max:100']);
            $query->where('tipo_incidencia', 'like', '%' . $request->tipo_incidencia . '%');
        }

        if ($request->filled('id_corrida')) {
            $request->validate(['id_corrida' => 'integer|exists:corrida,id']);
            $query->where('id_corrida', $request->id_corrida);
        }

        if ($request->filled('id_autobus')) {
            $request->validate(['id_autobus' => 'integer|exists:autobus,id']);
            $query->where('id_autobus', $request->id_autobus);
        }

        // --- Obtener y Devolver Resultados ---
        $incidentesReporte = $query->orderBy('fecha', 'desc')->paginate(50);

        return response()->json([
            'message' => 'Reporte de incidentes generado con éxito',
            'reporte' => $incidentesReporte
        ]);
    }

    /**
     * Helper para obtener usuario por token (duplicado de BoletoController,
     * considerar mover a un Trait o BaseController si se usa en muchos sitios).
     */
    private function getUserByToken($token)
    {
        if (!$token) {
            return null;
        }
        $accessToken = PersonalAccessToken::findToken($token);
        return $accessToken ? $accessToken->tokenable : null;
    }

    /**
     * Helper para verificar si un usuario es administrador.
     * Ajusta la lógica según tu implementación de roles.
     */
    private function esAdmin(User $user): bool
    {
        // Cambia 'admin' por el valor real que uses (puede ser un ID numérico, un string, etc.)
        // O implementa una lógica más compleja si usas paquetes de roles/permisos
        return $user->rol === '1';
    }
}