<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'cpd@ficct.edu.bo'],
            [
                'name' => 'CPD FICCT',
                'username' => 'cpd',
                'phone' => '70000000',
                'role' => 'CPD',
                'status' => 'ACTIVO',
                'password' => Hash::make('cpd123456'),
                'must_change_password' => false,
            ]
        );
    }
}
