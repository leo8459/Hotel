<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('habitaciones', function (Blueprint $table) {
            $table->json('freezer_stock')->nullable()->after('color');
            // freezer_stock: { "1": 5, "2": 10 }  (inventario_id => cantidad)
        });
    }

    public function down(): void
    {
        Schema::table('habitaciones', function (Blueprint $table) {
            $table->dropColumn('freezer_stock');
        });
    }
};
