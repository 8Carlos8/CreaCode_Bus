<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('incidente', function (Blueprint $table) {
            // Cambia la columna 'evidencia' de binary a string y permite nulos
            $table->string('evidencia')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('incidente', function (Blueprint $table) {
            // Revierte el cambio a binary si es necesario.
            // ¡Advertencia! Si ya guardaste rutas, revertir a binary puede fallar o corromper datos.
             // Considera el tipo de dato original que tenías. Si era solo ->binary('evidencia') sin nullable, quítalo aquí también.
            $table->binary('evidencia')->nullable()->change();
        });
    }
};
