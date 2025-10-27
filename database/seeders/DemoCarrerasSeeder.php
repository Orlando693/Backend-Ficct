<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoCarrerasSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('academia.carreras')->insert([
            ['nombre' => 'Ingeniería de Sistemas',                   'sigla' => 'SIS', 'estado' => 'INACTIVA', 'created_at'=>now(),'updated_at'=>now()],
            ['nombre' => 'Ingeniería Informática',                   'sigla' => 'INF', 'estado' => 'ACTIVA',   'created_at'=>now(),'updated_at'=>now()],
            ['nombre' => 'Ingeniería de Redes y Telecomunicaciones', 'sigla' => 'IRT', 'estado' => 'INACTIVA', 'created_at'=>now(),'updated_at'=>now()],
        ]);
           $this->call(DemoCarrerasSeeder::class);
    }
}
