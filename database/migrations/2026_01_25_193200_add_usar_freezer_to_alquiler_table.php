<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('alquiler', function (Blueprint $table) {
            $table->boolean('usar_freezer')->default(false)->after('inventario_detalle');
        });
    }

    public function down(): void
    {
        Schema::table('alquiler', function (Blueprint $table) {
            $table->dropColumn('usar_freezer');
        });
    }
};
