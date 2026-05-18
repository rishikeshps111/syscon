<?php

namespace Database\Seeders;

use App\Models\BranchLocation;
use App\Models\Depot;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DriverManagementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $location = Location::query()->with(['state', 'district'])->first();
        $depot = Depot::query()->first();
        $branch = BranchLocation::query()->first();

        if (! $location || ! $depot || ! $branch) {
            return;
        }

        $records = [
            [
                'name' => 'Suresh Babu',
                'email' => 'suresh.driver@example.com',
                'country_code' => '+91',
                'phone' => '9888800001',
                'alternate_country_code' => '+91',
                'alternate_phone' => '9777700001',
                'aadhaar_number' => '700000000001',
                'license_number' => 'KL0120260001',
                'license_type' => 'transport',
                'issue_date' => now()->subYears(5)->toDateString(),
                'expiry_date' => now()->addYears(2)->toDateString(),
                'badge_number' => 'BDG0001',
                'badge_expiry_date' => now()->addYear()->toDateString(),
                'employment_type' => 'permanent',
                'joining_date' => now()->subYears(3)->toDateString(),
                'salary' => 42000,
                'emergency_contact_name' => 'Lakshmi Babu',
                'emergency_country_code' => '+91',
                'emergency_contact_no' => '9666600001',
                'medical_fitness_expiry' => now()->addMonths(8)->toDateString(),
                'police_verification_status' => 'verified',
                'verification_status' => 'verified',
                'is_active' => true,
            ],
            [
                'name' => 'Ravi Menon',
                'email' => 'ravi.driver@example.com',
                'country_code' => '+91',
                'phone' => '9888800002',
                'alternate_country_code' => '+91',
                'alternate_phone' => '9777700002',
                'aadhaar_number' => '700000000002',
                'license_number' => 'KL0120260002',
                'license_type' => 'hmv',
                'issue_date' => now()->subYears(4)->toDateString(),
                'expiry_date' => now()->addDays(20)->toDateString(),
                'badge_number' => 'BDG0002',
                'badge_expiry_date' => now()->addMonths(6)->toDateString(),
                'employment_type' => 'contract',
                'joining_date' => now()->subYear()->toDateString(),
                'salary' => 36000,
                'emergency_contact_name' => 'Anu Ravi',
                'emergency_country_code' => '+91',
                'emergency_contact_no' => '9666600002',
                'medical_fitness_expiry' => now()->addDays(25)->toDateString(),
                'police_verification_status' => 'pending',
                'verification_status' => 'pending',
                'is_active' => true,
            ],
            [
                'name' => 'Nithin Raj',
                'email' => 'nithin.driver@example.com',
                'country_code' => '+91',
                'phone' => '9888800003',
                'alternate_country_code' => '+91',
                'alternate_phone' => '9777700003',
                'aadhaar_number' => '700000000003',
                'license_number' => 'KL0120260003',
                'license_type' => 'lmv',
                'issue_date' => now()->subYears(6)->toDateString(),
                'expiry_date' => now()->subDays(10)->toDateString(),
                'badge_number' => 'BDG0003',
                'badge_expiry_date' => now()->subDays(5)->toDateString(),
                'employment_type' => 'permanent',
                'joining_date' => now()->subYears(5)->toDateString(),
                'salary' => 30000,
                'emergency_contact_name' => 'Rajamma',
                'emergency_country_code' => '+91',
                'emergency_contact_no' => '9666600003',
                'medical_fitness_expiry' => now()->subDays(3)->toDateString(),
                'police_verification_status' => 'verified',
                'verification_status' => 'rejected',
                'is_active' => false,
            ],
            [
                'name' => 'Arif Khan',
                'email' => 'arif.driver@example.com',
                'country_code' => '+91',
                'phone' => '9888800004',
                'alternate_country_code' => '+91',
                'alternate_phone' => '9777700004',
                'aadhaar_number' => '700000000004',
                'license_number' => 'KL0120260004',
                'license_type' => 'transport',
                'issue_date' => now()->subYears(3)->toDateString(),
                'expiry_date' => now()->addMonths(10)->toDateString(),
                'badge_number' => 'BDG0004',
                'badge_expiry_date' => now()->addMonths(10)->toDateString(),
                'employment_type' => 'contract',
                'joining_date' => now()->subMonths(8)->toDateString(),
                'salary' => 39000,
                'emergency_contact_name' => 'Fathima Khan',
                'emergency_country_code' => '+91',
                'emergency_contact_no' => '9666600004',
                'medical_fitness_expiry' => now()->addMonths(2)->toDateString(),
                'police_verification_status' => 'rejected',
                'verification_status' => 'pending',
                'is_active' => true,
            ],
        ];

        DB::transaction(function () use ($records, $location, $depot, $branch) {
            foreach ($records as $record) {
                $user = User::firstOrCreate(
                    ['email' => $record['email']],
                    [
                        'name' => $record['name'],
                        'country_code' => $record['country_code'],
                        'phone' => $record['phone'],
                        'password' => 'password',
                        'is_active' => $record['is_active'],
                    ]
                );

                if (! $user->code) {
                    $user->code = generate_code('Driver Management Module', $user->id, 3, 'DRV');
                    $user->save();
                }

                $user->assignRole('Driver');
                $user->driverProfile()->updateOrCreate(
                    ['user_id' => $user->id],
                    collect($record)->except(['name', 'email', 'country_code', 'phone', 'is_active'])->merge([
                        'country' => 'India',
                        'state_id' => $location->state_id,
                        'district_id' => $location->district_id,
                        'location_id' => $location->id,
                        'pincode' => $location->pincode,
                        'address' => $location->name . ' driver quarters',
                        'depot_id' => $depot->id,
                        'branch_location_id' => $branch->id,
                        'account_number' => 'DRVAC' . str_pad((string) $user->id, 8, '0', STR_PAD_LEFT),
                        'ifsc_code' => 'SBIN0001200',
                    ])->all()
                );
            }
        });
    }
}
