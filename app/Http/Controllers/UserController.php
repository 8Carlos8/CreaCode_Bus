<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\Log;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;


class UserController extends Controller
{
    public function register(Request $request)
{
    $validator = Validator::make($request->all(), [
        'name' => 'required|string|max:255',
        'apellido_p' => 'required|string|max:255',
        'apellido_m' => 'required|string|max:255',
        'email' => 'required|string|email|max:255|unique:users',
        'telefono' => 'required|string|max:15',
        'password' => 'required|string|min:12',
    ]);

    if ($validator->fails()) {
        return response()->json($validator->errors(), 422);
    }

    // Crear el usuario
    $user = User::create([
        'name' => $request->name,
        'apellido_p' => $request->apellido_p,
        'apellido_m' => $request->apellido_m,
        'email' => $request->email,
        'telefono' => $request->telefono,
        'password' => bcrypt($request->password),
    ]);

    // Generar código de verificación
    $verificationCode = rand(100000, 999999);

    // Guardar el código de verificación en la base de datos
    $user->codigo_verificacion = $verificationCode;
    $user->save();

    // Enviar correo con el código de verificación
    $user->notify(new VerifyEmailNotification($verificationCode));

    return response()->json([
        'message' => 'Usuario registrado con éxito. Se ha enviado un correo para verificar tu cuenta.',
    ], 201);
}
    

    public function registerU(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'apellido_p' => 'required|string|max:255',
            'apellido_m' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'telefono' => 'required|string|max:15',
            'password' => [
                'required',
                'string',
                'min:12',
                'regex:/[A-Z]/',
                'regex:/[!@#$%^&*(),.?":{}|<>]/',
                'regex:/^(?!.*(\d)\1{2}).*$/',
            ],
            'rol' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::create([
            'name' => $request->name,
            'apellido_p' => $request->apellido_p,
            'apellido_m' => $request->apellido_m,
            'email' => $request->email,
            'telefono' => $request->telefono,
            'password' => Hash::make($request->password),
            'rol' => $request->rol,
        ]);

        return response()->json(['message' => 'Usuario registrado con éxito', 'user' => $user], 201);
    }

    // Dentro del controlador de login
public function login(Request $request) {
    // Validar los datos recibidos
    $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    // Buscar el usuario por correo
    $user = User::where('email', $request->email)->first();

    if (!$user) {
        return response()->json(['message' => 'Usuario no encontrado.'], 404);
    }

    // Verificar la contraseña
    if (!Hash::check($request->password, $user->password)) {
        return response()->json(['message' => 'Contraseña incorrecta.'], 400);
    }

    $token = $user->createToken('Token')->plainTextToken;
    return response()->json([
        'token' => $token,
        'id' => $user->id,
        'rol' => $user->rol
    ]);
}

public function verificarCorreo(Request $request)
{
    $request->validate([
        'id' => 'required|numeric',
        'codigo_verificacion' => 'required|numeric',
    ]);

    // Buscar el usuario por ID
    $user = User::find($request->id);

    if (!$user) {
        return response()->json(['message' => 'Usuario no encontrado.'], 404);
    }

    // Verificar si el correo ya está verificado
    if ($user->email_verified_at) {
        return response()->json(['message' => 'El correo ya está verificado.'], 200);
    }

    // Verificar el código de verificación
    if ($user->codigo_verificacion != $request->codigo_verificacion) {
        return response()->json(['message' => 'Código de verificación incorrecto.'], 400);
    }

    // Código correcto, verificar el correo
    $user->email_verified_at = now();
    $user->save();

    return response()->json(['message' => 'Correo verificado con éxito.'], 200);
}


public function verifyEmail(Request $request)
{
    $request->validate([
        'email' => 'required|email',
        'codigo_verificacion' => 'required|numeric',
    ]);

    $user = User::where('email', $request->email)->first();

    if (!$user) {
        return response()->json(['message' => 'Usuario no encontrado.'], 404);
    }

    // Verificar el código almacenado en la base de datos
    if ($user->codigo_verificacion != $request->codigo_verificacion) {
        return response()->json(['message' => 'Código de verificación incorrecto.'], 400);
    }

    

    dd($user);  // Muestra los datos del usuario después de la actualización
    dd($user->codigo_verificacion, $request->codigo_verificacion);

    
    // Limpiar el código de verificación
    $user->codigo_verificacion = null;
    $user->save();

    return response()->json(['message' => 'Correo verificado exitosamente.'], 200);
}

    

    public function logout(Request $request)
    {
        $token = $request->input('token');
        $accessToken = PersonalAccessToken::findToken($token);

        if ($accessToken) {
            $accessToken->delete();
        }

        return response()->json(['message' => 'Sesión cerrada con éxito']);
    }

    public function solicitarContraseña(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        // Buscar el usuario por email
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'El correo no está registrado.'], 404);
        }

        // Generar un código de verificación de 6 dígitos
        $codigo = rand(100000, 999999);

        // Guardar el código en la base de datos (campo `codigo_verificacion`)
        $user->codigo_verificacion = $codigo;
        $user->save();

        // Enviar el correo con el código de recuperación
        Mail::raw("Tu código de recuperación es: $codigo", function ($message) use ($user) {
            $message->to($user->email)
                    ->subject('Recuperación de contraseña');
        });

        return response()->json(['message' => 'Se ha enviado un correo con el código de recuperación.'], 200);
    }

    public function cambiarContraseña(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'codigo_verificacion' => 'required|numeric',
            'new_password' => 'required|string|min:12',
        ]);

        // Buscar el usuario
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado.'], 404);
        }

        // Validar que el código de verificación sea correcto
        if ($user->codigo_verificacion != $request->codigo_verificacion) {
            return response()->json(['message' => 'Código de verificación incorrecto.'], 400);
        }

        // Actualizar la contraseña
        $user->password = Hash::make($request->new_password);
        $user->codigo_verificacion = null; // Limpiar el código de verificación
        $user->save();

        return response()->json(['message' => 'Contraseña actualizada con éxito.'], 200);
    }

    public function update(Request $request)
    {
        $user = User::find($request->input('id'));

        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        $user->update($request->all());
        return response()->json(['message' => 'Usuario actualizado con éxito', 'user' => $user]);
    }

    public function delete(Request $request)
    {
        $user = User::find($request->input('id'));

        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        $user->delete();
        return response()->json(['message' => 'Usuario eliminado con éxito']);
    }
    public function verDetallesUsuario(Request $request)
{
    $token = $request->input('token');
    if (!$this->validateToken($token)) {
        return response()->json(['message' => 'Token inválido'], 401);
    }

    $id = $request->input('id'); // ID del usuario a consultar
    if (!$id) {
        return response()->json(['message' => 'El ID del usuario es requerido'], 400);
    }

    $usuario = User::find($id); // Busca al usuario por ID
    if (!$usuario) {
        return response()->json(['message' => 'Usuario no encontrado'], 404);
    }

    return response()->json(['usuario' => $usuario], 200);
}
public function listaUsuarios(Request $request)
{
    $token = $request->input('token');
    Log::info("Token recibido: " . $token);
    
    if (!$this->validateToken($token)) {
        Log::error("Token inválido");
        return response()->json(['message' => 'Token inválido'], 401);
    }

    try {
        $usuarios = User::all();
    } catch (\Exception $e) {
        Log::error("Error al obtener usuarios: " . $e->getMessage());
        return response()->json(['message' => 'Error interno'], 500);
    }

    return response()->json(['usuarios' => $usuarios], 200);
}

private function validateToken($token)
{
    $accessToken = PersonalAccessToken::findToken($token);
    return $accessToken && $accessToken->tokenable_type === 'App\Models\User';
}

}
