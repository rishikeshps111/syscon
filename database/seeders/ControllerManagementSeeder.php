<?php

namespace Database\Seeders;

use App\Models\Depot;
use App\Models\User;
use App\Support\UserCodeGenerator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ControllerManagementSeeder extends Seeder
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
                'name' => 'Vishnu Controller',
                'email' => 'vishnu.controller@example.com',
                'country_code' => '+91',
                'phone' => '9876543220',
                'depot_id' => $depot->id,
                'employment_type' => 'full_time',
                'father_name' => 'Madhavan Nair',
                'date_of_birth' => '1990-03-14',
                'aadhaar_number' => '723412341234',
                'pan_number' => 'GHJKL7890M',
                'date_of_joining' => '2023-04-01',
                'uan' => '100200300420',
                'esic_wc' => 'ESIC020',
                'country' => 'India',
                'state_id' => $location?->state_id,
                'district_id' => $location?->district_id,
                'location_id' => $location?->id,
                'bank_account_number' => '123456789020',
                'ifsc_code' => 'SBIN0001240',
                'basic' => 26000,
                'vda' => 4000,
                'basic_vda' => 30000,
                'hra' => 12000,
                'special_allowance' => 7000,
                'conveyance_allowance' => 3000,
                'bonus' => 5000,
                'gross_salary' => 57000,
                'is_active' => true,
            ],
            [
                'name' => 'Arjun Controller',
                'email' => 'arjun.controller@example.com',
                'country_code' => '+91',
                'phone' => '9876543221',
                'depot_id' => $depot->id,
                'employment_type' => 'full_time',
                'father_name' => 'Ravi Menon',
                'date_of_birth' => '1988-07-19',
                'aadhaar_number' => '723412341235',
                'pan_number' => 'GHJKL7891M',
                'date_of_joining' => '2022-08-10',
                'uan' => '100200300421',
                'esic_wc' => 'ESIC021',
                'country' => 'India',
                'state_id' => $location?->state_id,
                'district_id' => $location?->district_id,
                'location_id' => $location?->id,
                'bank_account_number' => '123456789021',
                'ifsc_code' => 'SBIN0001241',
                'basic' => 27500,
                'vda' => 4200,
                'basic_vda' => 31700,
                'hra' => 12600,
                'special_allowance' => 7400,
                'conveyance_allowance' => 3200,
                'bonus' => 5200,
                'gross_salary' => 60100,
                'is_active' => true,
            ],
            [
                'name' => 'Kiran Controller',
                'email' => 'kiran.controller@example.com',
                'country_code' => '+91',
                'phone' => '9876543222',
                'depot_id' => $depot->id,
                'employment_type' => 'contract',
                'father_name' => 'Prakash Kumar',
                'date_of_birth' => '1992-11-05',
                'aadhaar_number' => '723412341236',
                'pan_number' => 'GHJKL7892M',
                'date_of_joining' => '2023-01-18',
                'uan' => '100200300422',
                'esic_wc' => 'ESIC022',
                'country' => 'India',
                'state_id' => $location?->state_id,
                'district_id' => $location?->district_id,
                'location_id' => $location?->id,
                'bank_account_number' => '123456789022',
                'ifsc_code' => 'SBIN0001242',
                'basic' => 24500,
                'vda' => 3800,
                'basic_vda' => 28300,
                'hra' => 11000,
                'special_allowance' => 6500,
                'conveyance_allowance' => 2800,
                'bonus' => 4500,
                'gross_salary' => 53100,
                'is_active' => true,
            ],
            [
                'name' => 'Sreejith Controller',
                'email' => 'sreejith.controller@example.com',
                'country_code' => '+91',
                'phone' => '9876543223',
                'depot_id' => $depot->id,
                'employment_type' => 'part_time',
                'father_name' => 'Krishnan Nair',
                'date_of_birth' => '1994-02-26',
                'aadhaar_number' => '723412341237',
                'pan_number' => 'GHJKL7893M',
                'date_of_joining' => '2024-02-05',
                'uan' => '100200300423',
                'esic_wc' => 'ESIC023',
                'country' => 'India',
                'state_id' => $location?->state_id,
                'district_id' => $location?->district_id,
                'location_id' => $location?->id,
                'bank_account_number' => '123456789023',
                'ifsc_code' => 'SBIN0001243',
                'basic' => 18500,
                'vda' => 3000,
                'basic_vda' => 21500,
                'hra' => 8200,
                'special_allowance' => 4600,
                'conveyance_allowance' => 2200,
                'bonus' => 3200,
                'gross_salary' => 39700,
                'is_active' => false,
            ],
            [
                'name' => 'Rohit Controller',
                'email' => 'rohit.controller@example.com',
                'country_code' => '+91',
                'phone' => '9876543224',
                'depot_id' => $depot->id,
                'employment_type' => 'full_time',
                'father_name' => 'Mahesh Sharma',
                'date_of_birth' => '1991-09-12',
                'aadhaar_number' => '723412341238',
                'pan_number' => 'GHJKL7894M',
                'date_of_joining' => '2021-12-15',
                'uan' => '100200300424',
                'esic_wc' => 'ESIC024',
                'country' => 'India',
                'state_id' => $location?->state_id,
                'district_id' => $location?->district_id,
                'location_id' => $location?->id,
                'bank_account_number' => '123456789024',
                'ifsc_code' => 'SBIN0001244',
                'basic' => 29500,
                'vda' => 4600,
                'basic_vda' => 34100,
                'hra' => 13200,
                'special_allowance' => 8400,
                'conveyance_allowance' => 3500,
                'bonus' => 6200,
                'gross_salary' => 65400,
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
                    $user->code = UserCodeGenerator::generate('Controller', $depot->id, $user->id);
                    $user->save();
                }

                $user->assignRole('Controller');
                $user->controllerProfile()->updateOrCreate(
                    ['user_id' => $user->id],
                    collect($record)->except(['name', 'email', 'country_code', 'phone', 'is_active'])->all()
                );
            }
        });
    }
}
