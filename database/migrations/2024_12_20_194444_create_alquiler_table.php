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
            $table->string('tipoingreso', 50)->nullable(); // Campo tipoingreso, texto con límite de 50 caracteres
            $table->string('tipopago', 50)->nullable();    // Campo tipopago, texto con límite de 50 caracteres
            $table->boolean('aireacondicionado')->nullable(); // Campo aireacondicionado, booleano
            $table->timestamp('entrada')->nullable(); // Fecha y hora de entrada
            $table->timestamp('salida')->nullable(); // Fecha y hora de salida
            $table->integer('horas')->nullable(); // Duración en horas
            $table->decimal('total', 10, 2)->nullable();   // Campo total, decimal con 10 dígitos de los cuales 2 son decimales
            $table->string('estado', 20)->default('alquilado'); // Campo estado, texto con límite de 20 caracteres, por defecto 'alquilado'
            $table->text('inventario_detalle')->nullable(); // Detalle del inventario consumido
            $table->string('tarifa_seleccionada')->nullable();
            $table->timestamp('aire_inicio')->nullable(); // Hora de inicio del aire acondicionado
            $table->timestamp('aire_fin')->nullable(); // Hora de fin del aire acondicionado

            $table->timestamps();



            $table->foreignId('habitacion_id')->constrained('habitaciones')->onDelete('cascade'); // Relación con habitaciones
            $table->foreignId('inventario_id')->nullable()->constrained('inventarios')->onDelete('cascade'); // Relación con inventarios, nullable


        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alquiler');
        Schema::dropIfExists('habitaciones');

    }
};
