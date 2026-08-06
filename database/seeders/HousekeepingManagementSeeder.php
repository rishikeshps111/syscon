<?php

namespace Database\Seeders;

use App\Models\BranchLocation;
use App\Models\Depot;
use App\Models\Location;
use App\Models\User;
use App\Support\SalaryComponents;
use App\Support\UserCodeGenerator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HousekeepingManagementSeeder extends Seeder
{
    public function run(): void
    {
        $depot = Depot::first(); $branch = BranchLocation::first(); $location = Location::first();
        if (!$depot || !$branch || !$location) return;
        DB::transaction(function () use ($depot, $branch, $location) {
            foreach ([['name' => 'Ravi Housekeeping', 'email' => 'ravi.housekeeping@example.com', 'phone' => '9888811001', 'aadhaar' => '799999999901'], ['name' => 'Lakshmi Housekeeping', 'email' => 'lakshmi.housekeeping@example.com', 'phone' => '9888811002', 'aadhaar' => '799999999902']] as $row) {
                $user = User::firstOrCreate(['email' => $row['email']], ['name' => $row['name'], 'country_code' => '+91', 'phone' => $row['phone'], 'password' => str()->random(40), 'is_active' => true]);
                if (!$user->code) $user->update(['code' => UserCodeGenerator::generate('Housekeeping', $depot->id, $user->id)]);
                $user->assignRole('Housekeeping');
                $user->housekeepingProfile()->updateOrCreate(['user_id' => $user->id], ['alternate_country_code' => '+91', 'aadhaar_number' => $row['aadhaar'], 'country' => 'India', 'state_id' => $location->state_id, 'district_id' => $location->district_id, 'location_id' => $location->id, 'pincode' => $location->pincode ?: '500001', 'address' => $location->name, 'employment_type' => 'contract', 'joining_date' => now()->subYear()->toDateString(), 'salary' => 15000, 'depot_id' => $depot->id, 'branch_location_id' => $branch->id, 'account_number' => 'HSK'.str_pad((string)$user->id, 10, '0', STR_PAD_LEFT), 'ifsc_code' => 'SBIN0001200', 'emergency_contact_name' => 'Family Contact', 'emergency_country_code' => '+91', 'emergency_contact_no' => '9000000000', 'medical_fitness_expiry' => now()->addYear()->toDateString(), 'police_verification_status' => 'verified', 'verification_status' => 'verified']);
                $components = SalaryComponents::forRole('Housekeeping');
                if ($components->isNotEmpty()) SalaryComponents::sync($user, $components->mapWithKeys(fn($c) => [$c->id => $c->component_name === 'Basic' ? 15000 : 0])->all());
            }
        });
    }
}
