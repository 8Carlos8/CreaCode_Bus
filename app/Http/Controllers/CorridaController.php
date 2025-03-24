<?php

namespace App\Http\Controllers;

use App\Models\Corrida;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class CorridaController extends Controller
{
    public function create(Request $request)
    {
        $token = $request->input('token');
        if (!$this->validateToken($token)) {
            return response()->json(['message' => 'Token inválido'], 401);
        }
    
        $request->validate([
            'id_autobus' => 'required|exists:autobus,id',
            'origen' => 'required|string|max:255',
            'destino' => 'required|string|max:255',
            'fecha' => 'required|date',
            'hora_salida' => 'required|date_format:H:i',
            'hora_estima_llegada' => 'required|date_format:H:i',
            'tipo_corrida' => 'required|integer',
            'asientos_totales' => 'required|integer|min:1', // Nueva validación
            'precio' => 'required|numeric|min:0',
        ]);
    
        $corrida = Corrida::create([
            'id_autobus' => $request->input('id_autobus'),
            'origen' => $request->input('origen'),
            'destino' => $request->input('destino'),
            'fecha' => $request->input('fecha'),
            'hora_salida' => $request->input('hora_salida'),
            'hora_estima_llegada' => $request->input('hora_estima_llegada'),
            'tipo_corrida' => $request->input('tipo_corrida'),
            'precio' => $request->input('precio'),
            'asientos' => collect(range(1, $request->input('asientos_totales')))->map(function ($numero) {
                return ['numero' => $numero, 'estado' => 'disponible'];
            })->toArray(), // Genera asientos disponibles
        ]);
    
        return response()->json(['message' => 'Corrida creada con éxito', 'corrida' => $corrida], 201);
    }
    

    public function update(Request $request)
    {
        $token = $request->input('token');
        if (!$this->validateToken($token)) {
            return response()->json(['message' => 'Token inválido'], 401);
        }

        $corrida = Corrida::find($request->input('id'));
        if (!$corrida) {
            return response()->json(['message' => 'Corrida no encontrada'], 404);
        }

        $corrida->update($request->all());

        return response()->json(['message' => 'Corrida actualizada con éxito', 'corrida' => $corrida]);
    }

    public function delete(Request $request)
    {
        $token = $request->input('token');
        if (!$this->validateToken($token)) {
            return response()->json(['message' => 'Token inválido'], 401);
        }

        $corrida = Corrida::find($request->input('id'));
        if (!$corrida) {
            return response()->json(['message' => 'Corrida no encontrada'], 404);
        }

        $corrida->delete();

        return response()->json(['message' => 'Corrida eliminada con éxito']);
    }

    public function view(Request $request)
    {
        $token = $request->input('token');
        if (!$this->validateToken($token)) {
            return response()->json(['message' => 'Token inválido'], 401);
        }

        $corridas = Corrida::all();

        return response()->json(['corridas' => $corridas]);
    }
    
    public function detallesCorrida(Request $request)
    {
        $token = $request->input('token');
        if (!$this->validateToken($token)) {
            return response()->json(['message' => 'Token inválido'], 401);
        }
    
        $id = $request->input('id'); // ID de la corrida a consultar
        if (!$id) {
            return response()->json(['message' => 'El ID de la corrida es requerido'], 400);
        }
    
        $corrida = Corrida::find($id); // Busca la corrida por ID
        if (!$corrida) {
            return response()->json(['message' => 'Corrida no encontrada'], 404);
        }
    
        return response()->json(['corrida' => $corrida], 200);
    }
    
    public function search(Request $request)
    {
        $token = $request->input('token');
        if (!$this->validateToken($token)) {
            return response()->json(['message' => 'Token inválido'], 401);
        }

        $query = Corrida::query();

        if ($request->has('origen')) {
            $query->where('origen', 'like', '%' . $request->input('origen') . '%');
        }
        if ($request->has('destino')) {
            $query->where('destino', 'like', '%' . $request->input('destino') . '%');
        }
        if ($request->has('fecha')) {
            $query->where('fecha', $request->input('fecha'));
        }
        if ($request->has('fecha_inicio')) {  // Búsqueda por rango de fechas
           $query->where('fecha', '>=', $request->input('fecha_inicio'));
        }
        if ($request->has('fecha_fin')) {
            $query->where('fecha', '<=', $request->input('fecha_fin'));
        }
        if ($request->has('hora_salida')) {
            $query->where('hora_salida', $request->input('hora_salida'));
        }
           if ($request->has('hora_salida_inicio')) { //Rango de horas
            $query->where('hora_salida', '>=', $request->input('hora_salida_inicio'));
        }
         if ($request->has('hora_salida_fin')) {
            $query->where('hora_salida', '<=', $request->input('hora_salida_fin'));
        }

        if ($request->has('precio_minimo')) {
            $query->where('precio', '>=', $request->input('precio_minimo'));
        }
        if ($request->has('precio_maximo')) {
            $query->where('precio', '<=', $request->input('precio_maximo'));
        }

         if ($request->has('tipo_corrida')) {
            $query->where('tipo_corrida', $request->input('tipo_corrida'));
        }
        if ($request->has('id_autobus')) {
             $query->where('id_autobus', $request->input('id_autobus'));
        }


        $corridas = $query->get();  // Ejecuta la consulta y obtiene los resultados

        return response()->json(['corridas' => $corridas]);
    }

    private function validateToken($token)
    {
        $accessToken = PersonalAccessToken::findToken($token);
        return $accessToken && $accessToken->tokenable_type === 'App\Models\User';
    }
}
