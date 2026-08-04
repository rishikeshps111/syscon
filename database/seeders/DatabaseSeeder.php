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
            OemTypeSeeder::class,
            OemSeeder::class,
            BranchLocationSeeder::class,
            DepotSeeder::class,
            ServiceTypeSeeder::class,
            RouteSeeder::class,
            VehicleSeeder::class,
            VehicleClassificationSeeder::class,
            TripNatureSeeder::class,
            DocumentTypeSeeder::class,
            ComplaintCategorySeeder::class,
            TripSeeder::class,
            DepartmentSeeder::class,
            LevelSeeder::class,
            DesignationSeeder::class,
            SalaryComponentSeeder::class,
            HrLetterTemplateSeeder::class,
            HrmsDocumentTypeSeeder::class,
            LeaveTypeSeeder::class,
            ShiftSettingSeeder::class,
            HolidaySeeder::class,
            StaffManagementSeeder::class,
            ControllerManagementSeeder::class,
            SupervisorManagementSeeder::class,
            DriverManagementSeeder::class,
            UserSalaryComponentValueSeeder::class,
            TripManagementSeeder::class,
            RosterSeeder::class,
            LeaveSeeder::class,
            ComplaintSeeder::class,
        ]);

        // Create Super Admin user
        $superAdmin = User::firstOrCreate(
            ['email' => 'renjith@gmail.com'],
            [
                'name' => 'Renjith',
                'password' => 'password',
                'email_verified_at' => now(),
            ]
        );
        $superAdmin->assignRole('Super Admin');
    }
}
