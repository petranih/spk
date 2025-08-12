<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Create Admin User
        User::create([
            'name' => 'Administrator',
            'email' => 'admin@beasiswa.com',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
            'phone' => '08123456789',
            'address' => 'Jl. Admin No. 1',
        ]);

        // Create Validator Users
        User::create([
            'name' => 'Validator 1',
            'email' => 'validator1@beasiswa.com',
            'password' => Hash::make('validator123'),
            'role' => 'validator',
            'phone' => '08123456790',
            'address' => 'Jl. Validator No. 1',
        ]);

        User::create([
            'name' => 'Validator 2',
            'email' => 'validator2@beasiswa.com',
            'password' => Hash::make('validator123'),
            'role' => 'validator',
            'phone' => '08123456791',
            'address' => 'Jl. Validator No. 2',
        ]);

        // Create Student Users
        User::create([
            'name' => 'MOH. ALFIAN FARIS',
            'email' => 'alfian@student.com',
            'password' => Hash::make('student123'),
            'role' => 'student',
            'phone' => '08123456792',
            'address' => 'Jl. Student No. 1',
        ]);

        User::create([
            'name' => 'HOLISIAH',
            'email' => 'holisiah@student.com',
            'password' => Hash::make('student123'),
            'role' => 'student',
            'phone' => '08123456793',
            'address' => 'Jl. Student No. 2',
        ]);

        User::create([
            'name' => 'ZHAFRAN IZZAN ACHMAD ZAIN',
            'email' => 'zhafran@student.com',
            'password' => Hash::make('student123'),
            'role' => 'student',
            'phone' => '08123456794',
            'address' => 'Jl. Student No. 3',
        ]);

        User::create([
            'name' => 'FIANA LEGIYANANDA',
            'email' => 'fiana@student.com',
            'password' => Hash::make('student123'),
            'role' => 'student',
            'phone' => '08123456795',
            'address' => 'Jl. Student No. 4',
        ]);

        User::create([
            'name' => 'NAYZILA INTANA PUTRI',
            'email' => 'nayzila@student.com',
            'password' => Hash::make('student123'),
            'role' => 'student',
            'phone' => '08123456796',
            'address' => 'Jl. Student No. 5',
        ]);

        // Add more students as needed
        for ($i = 6; $i <= 20; $i++) {
            User::create([
                'name' => 'Student ' . $i,
                'email' => 'student' . $i . '@student.com',
                'password' => Hash::make('student123'),
                'role' => 'student',
                'phone' => '0812345679' . $i,
                'address' => 'Jl. Student No. ' . $i,
            ]);
        }
    }
}