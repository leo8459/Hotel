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
        Schema::create('alquiler', function (Blueprint $table) {
            $table->id();
            $table->string('tipoingreso', 50); // Campo tipoingreso, texto con límite de 50 caracteres
            $table->string('tipopago', 50);    // Campo tipopago, texto con límite de 50 caracteres
            $table->boolean('aireacondicionado'); // Campo aireacondicionado, booleano
            $table->decimal('total', 10, 2);   // Campo total, decimal con 10 dígitos de los cuales 2 son decimales
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alquiler');
    }
};
