<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            PrefixSeeder::class,
            StateSeeder::class,
            DistrictSeeder::class,
            LocationSeeder::class,
            DepotSeeder::class,
            ServiceTypeSeeder::class,
            RouteSeeder::class,
            VehicleClassificationSeeder::class,
            DocumentTypeSeeder::class,
            TripSetupSeeder::class,
        ]);

        // Create Super Admin user
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@example.com'],
            [
                'name' => 'Super Admin',
                'password' => 'password',
                'email_verified_at' => now(),
            ]
        );
        $superAdmin->assignRole('Super Admin');
    }
}
