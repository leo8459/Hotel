<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('habitaciones', function (Blueprint $table) {
            $table->index('estado_texto', 'habitaciones_estado_texto_idx');
            $table->index('habitacion', 'habitaciones_habitacion_idx');
        });

        Schema::table('alquiler', function (Blueprint $table) {
            $table->index('estado', 'alquiler_estado_idx');
            $table->index('entrada', 'alquiler_entrada_idx');
            $table->index(['habitacion_id', 'estado'], 'alquiler_habitacion_estado_idx');
        });
    }

    public function down(): void
    {
        Schema::table('habitaciones', function (Blueprint $table) {
            $table->dropIndex('habitaciones_estado_texto_idx');
            $table->dropIndex('habitaciones_habitacion_idx');
        });

        Schema::table('alquiler', function (Blueprint $table) {
            $table->dropIndex('alquiler_estado_idx');
            $table->dropIndex('alquiler_entrada_idx');
            $table->dropIndex('alquiler_habitacion_estado_idx');
        });
    }
};
