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
        Schema::create('eventos', function (Blueprint $table) {
            $table->id();
            $table->string('articulo');
            $table->decimal('precio', 8, 2);
            $table->integer('stock');
            $table->integer('vendido');
            $table->decimal('precio_vendido', 8, 2);

            $table->foreignId('habitacion_id')->nullable('habitaciones')->onDelete('cascade'); // Relación con habitaciones
            $table->foreignId('inventario_id')->nullable()->constrained('inventarios')->onDelete('cascade'); // Relación con inventarios, nullable
            $table->foreignId('usuario_id')->nullable()->constrained('users')->onDelete('set null')->after('estado');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('eventos');
    }
};
