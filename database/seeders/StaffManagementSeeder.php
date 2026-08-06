<?php

namespace Database\Seeders;

use App\Models\Designation;
use App\Models\Depot;
use App\Models\Location;
use App\Models\User;
use App\Support\UserCodeGenerator;
use App\Support\SalaryComponents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StaffManagementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $designation = Designation::where('name', 'Operations')->first()
            ?? Designation::query()->first();
        $designationTwo = Designation::where('name', 'HR')->first()
            ?? Designation::query()->first();
        $location = Location::query()->with(['state', 'district'])->first();
        $depots = Depot::where('is_active', true)->orderBy('id')->get(['id']);

        if (! $designation || $depots->isEmpty()) {
            return;
        }

        $records = [
            [
                'name' => 'Sachin',
                'email' => 'sachin@gmail.com',
                'country_code' => '+91',
                'phone' => '9876543210',
                'designation_id' => $designation->id,
                'category' => 'managerial',
                'employment_type' => 'full_time',
                'father_name' => 'Ramesh Kumar',
                'date_of_birth' => '1990-05-12',
                'aadhaar_number' => '123412341234',
                'pan_number' => 'ABCDE1234F',
                'date_of_joining' => '2022-05-02',
                'uan' => '100200300400',
                'esic_wc' => 'ESIC001',
                'country' => 'India',
                'state_id' => $location?->state_id,
                'district_id' => $location?->district_id,
                'location_id' => $location?->id,
                'bank_account_number' => '123456789012',
                'ifsc_code' => 'SBIN0001234',
                'basic' => 30000,
                'vda' => 5000,
                'hra' => 15000,
                'special_allowance' => 10000,
                'conveyance_allowance' => 5000,
                'bonus' => 10000,
                'is_active' => true,
            ],
            [
                'name' => 'Divya',
                'email' => 'divya@gmail.com',
                'country_code' => '+91',
                'phone' => '9876543211',
                'designation_id' => $designationTwo->id,
                'category' => 'skilled',
                'employment_type' => 'full_time',
                'father_name' => 'Suresh Nair',
                'date_of_birth' => '1992-08-18',
                'aadhaar_number' => '223412341234',
                'pan_number' => 'BCDEF2345G',
                'date_of_joining' => '2021-07-15',
                'uan' => '100200300401',
                'esic_wc' => 'ESIC002',
                'country' => 'India',
                'state_id' => $location?->state_id,
                'district_id' => $location?->district_id,
                'location_id' => $location?->id,
                'bank_account_number' => '123456789013',
                'ifsc_code' => 'SBIN0001235',
                'basic' => 24000,
                'vda' => 4000,
                'hra' => 11000,
                'special_allowance' => 7000,
                'conveyance_allowance' => 3000,
                'bonus' => 6000,
                'is_active' => true,
            ],
            [
                'name' => 'Rahul Menon',
                'email' => 'rahul.staff@example.com',
                'country_code' => '+91',
                'phone' => '9876543212',
                'designation_id' => $designation->id,
                'category' => 'managerial',
                'employment_type' => 'contract',
                'father_name' => 'Vijay Menon',
                'date_of_birth' => '1988-11-03',
                'aadhaar_number' => '323412341234',
                'pan_number' => 'CDEFG3456H',
                'date_of_joining' => '2020-02-10',
                'uan' => '100200300402',
                'esic_wc' => 'ESIC003',
                'country' => 'India',
                'state_id' => $location?->state_id,
                'district_id' => $location?->district_id,
                'location_id' => $location?->id,
                'bank_account_number' => '123456789014',
                'ifsc_code' => 'SBIN0001236',
                'basic' => 28000,
                'vda' => 4500,
                'hra' => 13000,
                'special_allowance' => 8500,
                'conveyance_allowance' => 4000,
                'bonus' => 8000,
                'is_active' => true,
            ],
            [
                'name' => 'Anjali Das',
                'email' => 'anjali.staff@example.com',
                'country_code' => '+91',
                'phone' => '9876543213',
                'designation_id' => $designation->id,
                'category' => 'skilled',
                'employment_type' => 'part_time',
                'father_name' => 'Mohan Das',
                'date_of_birth' => '1995-01-24',
                'aadhaar_number' => '423412341234',
                'pan_number' => 'DEFGH4567J',
                'date_of_joining' => '2023-01-05',
                'uan' => '100200300403',
                'esic_wc' => 'ESIC004',
                'country' => 'India',
                'state_id' => $location?->state_id,
                'district_id' => $location?->district_id,
                'location_id' => $location?->id,
                'bank_account_number' => '123456789015',
                'ifsc_code' => 'SBIN0001237',
                'basic' => 18000,
                'vda' => 3000,
                'hra' => 8000,
                'special_allowance' => 5000,
                'conveyance_allowance' => 2500,
                'bonus' => 3500,
                'is_active' => true,
            ],
            [
                'name' => 'Sanjay Thomas',
                'email' => 'sanjay.staff@example.com',
                'country_code' => '+91',
                'phone' => '9876543214',
                'designation_id' => $designation->id,
                'category' => 'unskilled',
                'employment_type' => 'full_time',
                'father_name' => 'Joseph Thomas',
                'date_of_birth' => '1991-04-30',
                'aadhaar_number' => '523412341234',
                'pan_number' => 'EFGHJ5678K',
                'date_of_joining' => '2022-09-19',
                'uan' => '100200300404',
                'esic_wc' => 'ESIC005',
                'country' => 'India',
                'state_id' => $location?->state_id,
                'district_id' => $location?->district_id,
                'location_id' => $location?->id,
                'bank_account_number' => '123456789016',
                'ifsc_code' => 'SBIN0001238',
                'basic' => 20000,
                'vda' => 3500,
                'hra' => 9000,
                'special_allowance' => 5500,
                'conveyance_allowance' => 3000,
                'bonus' => 4500,
                'is_active' => false,
            ],
            [
                'name' => 'Priya Sharma',
                'email' => 'priya.staff@example.com',
                'country_code' => '+91',
                'phone' => '9876543215',
                'designation_id' => $designation->id,
                'category' => 'managerial',
                'employment_type' => 'full_time',
                'father_name' => 'Rajesh Sharma',
                'date_of_birth' => '1989-12-09',
                'aadhaar_number' => '623412341234',
                'pan_number' => 'FGHJK6789L',
                'date_of_joining' => '2019-11-25',
                'uan' => '100200300405',
                'esic_wc' => 'ESIC006',
                'country' => 'India',
                'state_id' => $location?->state_id,
                'district_id' => $location?->district_id,
                'location_id' => $location?->id,
                'bank_account_number' => '123456789017',
                'ifsc_code' => 'SBIN0001239',
                'basic' => 32000,
                'vda' => 6000,
                'hra' => 16000,
                'special_allowance' => 12000,
                'conveyance_allowance' => 6000,
                'bonus' => 12000,
                'is_active' => true,
            ],
        ];

        DB::transaction(function () use ($records, $depots) {
            foreach ($records as $index => $record) {
                $record['depot_id'] = $depots[$index % $depots->count()]->id;
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
                    $user->code = UserCodeGenerator::generate('Staff', $depot->id, $user->id);
                    $user->save();
                }

                $componentAmounts = $this->salaryComponentAmounts($record);
                $salaryData = SalaryComponents::legacyProfileSalaryData($componentAmounts);
                $profileData = collect($record)
                    ->except(['name', 'email', 'country_code', 'phone', 'is_active'])
                    ->merge(collect($salaryData)->only([
                        'basic',
                        'vda',
                        'basic_vda',
                        'hra',
                        'special_allowance',
                        'conveyance_allowance',
                        'bonus',
                        'gross_salary',
                    ]));

                $user->staffProfile()->updateOrCreate(
                    ['user_id' => $user->id],
                    $profileData->all()
                );
                SalaryComponents::sync($user, $componentAmounts);

                $roles = ['Staff'];
                $designation = Designation::with('role')->find($record['designation_id']);

                if ($designation?->role) {
                    $roles[] = $designation->role->name;
                }

                $user->syncRoles($roles);
            }
        });
    }

    private function salaryComponentAmounts(array $record): array
    {
        return SalaryComponents::forRole('Staff', (int) $record['designation_id'])
            ->mapWithKeys(function ($component) use ($record) {
                $key = str($component->component_name)
                    ->lower()
                    ->replace([' + ', ' / ', ' ', '-'], '_')
                    ->toString();

                return [
                    $component->id => $record[$key] ?? (float) $component->default_value,
                ];
            })
            ->all();
    }
}
