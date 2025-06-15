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
            $table->integer('preciohora')->nullable(); // Precio por hora
            $table->integer('precio_extra')->nullable(); // Precio extra
            $table->integer('tarifa_opcion1')->nullable(); // Tarifa opción 1
            $table->integer('tarifa_opcion2')->nullable(); // Tarifa opción 2
            $table->integer('tarifa_opcion3')->nullable(); // Tarifa opción 3
            $table->integer('tarifa_opcion4')->nullable(); // Tarifa opción 4
            $table->boolean('estado')->default(1); // Estado de la habitación, por defecto 1
 $table->string('estado_texto', 30)->default('Disponible');
$table->string('color', 30)->default('bg-success');

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
