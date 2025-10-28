<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class DemoUsersSeeder extends Seeder
{
    public function run(): void
    {
        // Usuario CPD por defecto (ajusta si tu tabla tiene otros nombres)
        User::updateOrCreate(
            ['email' => 'cpd@ficct.edu.bo'],
            [
                'name' => 'CPD FICCT',
                'username' => 'cpd',              // borra si tu tabla no tiene username
                'phone' => '70000000',            // borra si no tienes phone
                'role' => 'CPD',
                'status' => 'ACTIVO',
                'password' => Hash::make('cpd123456'),
                'must_change_password' => false,  // borra si no existe esta columna
            ]
        );
    }
}
