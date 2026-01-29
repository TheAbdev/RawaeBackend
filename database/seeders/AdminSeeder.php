<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
        public function run(): void
    {

        User::updateOrCreate(
            ['email' => 'admin@rawae.com'],
            [
                'name' => 'مدير النظام',
                'username' => 'admin',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'phone' => '+966500000001',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'donor@rawae.com'],
            [
                'name' => 'متبرع تجريبي',
                'username' => 'donor',
                'password' => Hash::make('donor123'),
                'role' => 'donor',
                'phone' => '+966500000002',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );


        User::updateOrCreate(
            ['email' => 'mosque@rawae.com'],
            [
                'name' => 'مدير مسجد',
                'username' => 'mosque_admin',
                'password' => Hash::make('mosque123'),
                'role' => 'mosque_admin',
                'phone' => '+966500000003',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );


        User::updateOrCreate(
            ['email' => 'auditor@rawae.com'],
            [
                'name' => 'مدقق حسابات',
                'username' => 'auditor',
                'password' => Hash::make('auditor123'),
                'role' => 'auditor',
                'phone' => '+966500000004',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );


        User::updateOrCreate(
            ['email' => 'investor@rawae.com'],
            [
                'name' => 'مستثمر',
                'username' => 'investor',
                'password' => Hash::make('investor123'),
                'role' => 'investor',
                'phone' => '+966500000005',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        User::updateOrCreate(
            ['email' => 'driver@rawae.com'],
            [
                'name' => 'سائق شاحنة',
                'username' => 'driver',
                'password' => Hash::make('driver123'),
                'role' => 'driver',
                'phone' => '+966500000006',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );


        User::updateOrCreate(
            ['email' => 'logistics@rawae.com'],
            [
                'name' => 'مشرف لوجستي',
                'username' => 'logistics',
                'password' => Hash::make('logistics123'),
                'role' => 'logistics_supervisor',
                'phone' => '+966500000007',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $this->command->info('Users seeded successfully!');
        $this->command->table(
            ['Role', 'Email', 'Password'],
            [
                ['admin', 'admin@rawae.com', 'admin123'],
                ['donor', 'donor@rawae.com', 'donor123'],
                ['mosque_admin', 'mosque@rawae.com', 'mosque123'],
                ['auditor', 'auditor@rawae.com', 'auditor123'],
                ['investor', 'investor@rawae.com', 'investor123'],
                ['logistics_supervisor', 'logistics@rawae.com', 'logistics123'],
                ['driver', 'driver@rawae.com', 'driver123'],
            ]
        );
    }
}

