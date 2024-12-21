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
        Schema::create('habitaciones', function (Blueprint $table) {
            $table->id();
            $table->string('habitacion'); // Nombre o identificador de la habitación
            $table->string('tipo'); // Tipo de la habitación
            $table->timestamp('entrada')->nullable(); // Fecha y hora de entrada
            $table->timestamp('salida')->nullable(); // Fecha y hora de salida
            $table->integer('horas')->nullable(); // Duración en horas
            $table->timestamps(); // created_at y updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('habitaciones');
    }
};
