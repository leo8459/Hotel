<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ResetHotelBaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        DB::table('eventos')->truncate();
        DB::table('alquiler')->truncate();
        DB::table('habitaciones')->truncate();
        DB::table('inventarios')->truncate();

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $inventarios = [
                [
                    'articulo' => 'Agua',
                    'precio' => 5.00,
                    'precio_entrada' => 3.00,
                    'total_compra' => 180.00,
                    'stock' => 60,
                    'estado' => 1,
                ],
                [
                    'articulo' => 'Coca Cola',
                    'precio' => 10.00,
                    'precio_entrada' => 7.00,
                    'total_compra' => 280.00,
                    'stock' => 40,
                    'estado' => 1,
                ],
                [
                    'articulo' => 'Pepsi',
                    'precio' => 10.00,
                    'precio_entrada' => 7.00,
                    'total_compra' => 280.00,
                    'stock' => 40,
                    'estado' => 1,
                ],
                [
                    'articulo' => 'Cerveza',
                    'precio' => 15.00,
                    'precio_entrada' => 11.00,
                    'total_compra' => 330.00,
                    'stock' => 30,
                    'estado' => 1,
                ],
                [
                    'articulo' => 'Energizante',
                    'precio' => 12.00,
                    'precio_entrada' => 8.50,
                    'total_compra' => 170.00,
                    'stock' => 20,
                    'estado' => 1,
                ],
            ];

        DB::table('inventarios')->insert(
            collect($inventarios)->map(fn ($item) => array_merge($item, [
                'created_at' => now(),
                'updated_at' => now(),
            ]))->all()
        );

        $freezerBase = [
            '1' => 4,
            '2' => 3,
            '3' => 3,
            '4' => 2,
            '5' => 2,
        ];

        $habitaciones = [
            ['habitacion' => '1', 'tipo' => 'CLASE 1', 'preciohora' => 10, 'precio_extra' => 20, 'tarifa_opcion1' => 150],
            ['habitacion' => '2', 'tipo' => 'CLASE 1', 'preciohora' => 10, 'precio_extra' => 20, 'tarifa_opcion1' => 150],
            ['habitacion' => '3', 'tipo' => 'CLASE 1', 'preciohora' => 10, 'precio_extra' => 20, 'tarifa_opcion1' => 150],
            ['habitacion' => '4', 'tipo' => 'CLASE 2', 'preciohora' => 20, 'precio_extra' => 20, 'tarifa_opcion1' => 150],
            ['habitacion' => '5', 'tipo' => 'CLASE 2', 'preciohora' => 20, 'precio_extra' => 20, 'tarifa_opcion1' => 150],
            ['habitacion' => '6', 'tipo' => 'CLASE 2', 'preciohora' => 20, 'precio_extra' => 20, 'tarifa_opcion1' => 150],
            ['habitacion' => '7', 'tipo' => 'CLASE 3', 'preciohora' => 50, 'precio_extra' => 30, 'tarifa_opcion1' => 150],
            ['habitacion' => '8', 'tipo' => 'CLASE 3', 'preciohora' => 50, 'precio_extra' => 30, 'tarifa_opcion1' => 150],
            ['habitacion' => '9', 'tipo' => 'CLASE 3', 'preciohora' => 50, 'precio_extra' => 30, 'tarifa_opcion1' => 150],
            ['habitacion' => '10', 'tipo' => 'CLASE 3', 'preciohora' => 50, 'precio_extra' => 30, 'tarifa_opcion1' => 150],
        ];

        DB::table('habitaciones')->insert(
            collect($habitaciones)->map(fn ($item) => array_merge($item, [
                'tarifa_opcion2' => null,
                'tarifa_opcion3' => null,
                'tarifa_opcion4' => null,
                'estado' => 1,
                'estado_texto' => 'Disponible',
                'color' => 'bg-success text-white',
                'freezer_stock' => json_encode($freezerBase),
                'created_at' => now(),
                'updated_at' => now(),
            ]))->all()
        );
    }
}
