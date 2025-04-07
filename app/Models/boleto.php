<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// --- Recomendación: Cambiar nombre de clase a PascalCase ---
class Boleto extends Model // Antes: class boleto
{
    use HasFactory;

    protected $table = 'boleto'; // Laravel puede inferir esto si la clase es Boleto

    protected $fillable = [
        'num_boleto',
        'id_usuario',
        'id_corrida',
        'num_asientos',
        'fecha_compra',
        'monto',
        'descuento',
        'id_pago',
        'estado',
    ];

    /**
     * Obtiene el usuario asociado con el boleto.
     */
    public function user() // <-- Cambiado a public
    {
        // --- Recomendación: Usar nombre de clase User::class ---
        return $this->belongsTo(User::class, 'id_usuario'); // Antes: user::class
    }

    /**
     * Obtiene la corrida asociada con el boleto.
     */
    public function corrida() // <-- Cambiado a public
    {
         // --- Recomendación: Usar nombre de clase Corrida::class ---
        return $this->belongsTo(Corrida::class, 'id_corrida'); // Antes: corrida::class
    }
}