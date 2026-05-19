<?php

namespace Database\Seeders;

use App\Models\Depot;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SupervisorManagementSeeder extends Seeder
{
    public function run(): void
    {
        $depot = Depot::query()->with('location')->first();

        if (! $depot) {
            return;
        }

        $location = $depot->location;
        $records = [
            [
                'name' => 'Nithin Supervisor',
                'email' => 'nithin.supervisor@example.com',
                'country_code' => '+91',
                'phone' => '9876543230',
                'depot_id' => $depot->id,
                'employment_type' => 'full_time',
                'father_name' => 'Soman Pillai',
                'date_of_birth' => '1989-06-22',
                'aadhaar_number' => '823412341234',
                'pan_number' => 'HJKLM8901N',
                'date_of_joining' => '2023-06-01',
                'uan' => '100200300430',
                'esic_wc' => 'ESIC030',
                'country' => 'India',
                'state_id' => $location?->state_id,
                'district_id' => $location?->district_id,
                'location_id' => $location?->id,
                'bank_account_number' => '123456789030',
                'ifsc_code' => 'SBIN0001250',
                'basic' => 28000,
                'vda' => 4500,
                'basic_vda' => 32500,
                'hra' => 12000,
                'special_allowance' => 8000,
                'conveyance_allowance' => 3000,
                'bonus' => 5000,
                'gross_salary' => 60500,
                'is_active' => true,
            ],
            [
                'name' => 'Manu Supervisor',
                'email' => 'manu.supervisor@example.com',
                'country_code' => '+91',
                'phone' => '9876543231',
                'depot_id' => $depot->id,
                'employment_type' => 'full_time',
                'father_name' => 'Haridas Menon',
                'date_of_birth' => '1987-10-04',
                'aadhaar_number' => '823412341235',
                'pan_number' => 'HJKLM8902N',
                'date_of_joining' => '2021-09-20',
                'uan' => '100200300431',
                'esic_wc' => 'ESIC031',
                'country' => 'India',
                'state_id' => $location?->state_id,
                'district_id' => $location?->district_id,
                'location_id' => $location?->id,
                'bank_account_number' => '123456789031',
                'ifsc_code' => 'SBIN0001251',
                'basic' => 30000,
                'vda' => 4800,
                'basic_vda' => 34800,
                'hra' => 13500,
                'special_allowance' => 8600,
                'conveyance_allowance' => 3400,
                'bonus' => 6500,
                'gross_salary' => 66800,
                'is_active' => true,
            ],
            [
                'name' => 'Deepak Supervisor',
                'email' => 'deepak.supervisor@example.com',
                'country_code' => '+91',
                'phone' => '9876543232',
                'depot_id' => $depot->id,
                'employment_type' => 'contract',
                'father_name' => 'Mohan Das',
                'date_of_birth' => '1990-01-17',
                'aadhaar_number' => '823412341236',
                'pan_number' => 'HJKLM8903N',
                'date_of_joining' => '2022-11-07',
                'uan' => '100200300432',
                'esic_wc' => 'ESIC032',
                'country' => 'India',
                'state_id' => $location?->state_id,
                'district_id' => $location?->district_id,
                'location_id' => $location?->id,
                'bank_account_number' => '123456789032',
                'ifsc_code' => 'SBIN0001252',
                'basic' => 26500,
                'vda' => 4200,
                'basic_vda' => 30700,
                'hra' => 11900,
                'special_allowance' => 7200,
                'conveyance_allowance' => 3000,
                'bonus' => 5100,
                'gross_salary' => 57900,
                'is_active' => true,
            ],
            [
                'name' => 'Akhil Supervisor',
                'email' => 'akhil.supervisor@example.com',
                'country_code' => '+91',
                'phone' => '9876543233',
                'depot_id' => $depot->id,
                'employment_type' => 'part_time',
                'father_name' => 'Balan Nair',
                'date_of_birth' => '1993-04-28',
                'aadhaar_number' => '823412341237',
                'pan_number' => 'HJKLM8904N',
                'date_of_joining' => '2024-03-12',
                'uan' => '100200300433',
                'esic_wc' => 'ESIC033',
                'country' => 'India',
                'state_id' => $location?->state_id,
                'district_id' => $location?->district_id,
                'location_id' => $location?->id,
                'bank_account_number' => '123456789033',
                'ifsc_code' => 'SBIN0001253',
                'basic' => 19500,
                'vda' => 3200,
                'basic_vda' => 22700,
                'hra' => 8600,
                'special_allowance' => 4800,
                'conveyance_allowance' => 2200,
                'bonus' => 3400,
                'gross_salary' => 41700,
                'is_active' => false,
            ],
            [
                'name' => 'Sandeep Supervisor',
                'email' => 'sandeep.supervisor@example.com',
                'country_code' => '+91',
                'phone' => '9876543234',
                'depot_id' => $depot->id,
                'employment_type' => 'full_time',
                'father_name' => 'Venu Gopal',
                'date_of_birth' => '1986-12-09',
                'aadhaar_number' => '823412341238',
                'pan_number' => 'HJKLM8905N',
                'date_of_joining' => '2020-05-25',
                'uan' => '100200300434',
                'esic_wc' => 'ESIC034',
                'country' => 'India',
                'state_id' => $location?->state_id,
                'district_id' => $location?->district_id,
                'location_id' => $location?->id,
                'bank_account_number' => '123456789034',
                'ifsc_code' => 'SBIN0001254',
                'basic' => 32000,
                'vda' => 5200,
                'basic_vda' => 37200,
                'hra' => 14500,
                'special_allowance' => 9300,
                'conveyance_allowance' => 3800,
                'bonus' => 7000,
                'gross_salary' => 71800,
                'is_active' => true,
            ],
        ];

        DB::transaction(function () use ($records) {
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
                    $user->code = generate_code('Supervisor Management Module', $user->id, 3, 'SUP');
                    $user->save();
                }

                $user->assignRole('Supervisor');
                $user->supervisorProfile()->updateOrCreate(
                    ['user_id' => $user->id],
                    collect($record)->except(['name', 'email', 'country_code', 'phone', 'is_active'])->all()
                );
            }
        });
    }
}
