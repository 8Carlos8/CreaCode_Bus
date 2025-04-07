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
        Schema::create('incidentes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_autobus');
            $table->unsignedBigInteger('id_corrida')->nullable();
            $table->string('tipo_incidencia');
            $table->text('descripcion');
            $table->binary('evidencia');
            $table->time('tiempo_estima_retraso');
            $table->dateTime('fecha');
            $table->timestamps();

            $table->foreign('id_autobus')->references('id')->on('autobus')->onDelete('cascade');
            $table->foreign('id_corrida')->references('id')->on('corrida');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incidente');
    }
};
