<?php

namespace Database\Seeders;

use App\Models\District;
use App\Models\Location;
use App\Models\Oem;
use App\Models\State;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $verifier = User::whereHas('roles', fn($query) => $query->where('name', 'Super Admin'))->first()
            ?? User::first();

        $records = [
            [
                'state' => 'Telangana',
                'district' => 'Hyderabad',
                'city' => 'Madhapur',
                'oem_code' => 'OEM001',
                'oem_name' => 'Alpha Motors Private Limited',
                'short_name' => 'Alpha Motors',
                'oem_type' => '12m',
                'registration_type' => 'Company',
                'gst_number' => '36AALCA1234A1Z5',
                'pan_number' => 'AALCA1234A',
                'cin_number' => 'U34100TG2020PTC001001',
                'status' => 'Active',
                'is_verified' => true,
                'remarks' => 'Primary electric vehicle OEM partner.',
                'contacts' => [
                    [
                        'contact_person' => 'Rahul Menon',
                        'designation' => 'Regional Manager',
                        'phone_country_code' => '+91',
                        'phone' => '9876543210',
                        'alternate_phone_country_code' => '+91',
                        'alternate_phone' => '9876500001',
                        'email' => 'rahul.menon@alphamotors.example',
                        'is_primary' => true,
                    ],
                ],
                'addresses' => [
                    [
                        'address_type' => 'HQ',
                        'address_line1' => 'HITEC City Road, Madhapur',
                        'address_line2' => 'Tower A, 5th Floor',
                        'pincode' => '500081',
                        'latitude' => '17.4483',
                        'longitude' => '78.3915',
                    ],
                    [
                        'address_type' => 'Service',
                        'address_line1' => 'Service Hub, Secunderabad Road',
                        'address_line2' => null,
                        'pincode' => '500003',
                        'latitude' => '17.4399',
                        'longitude' => '78.4983',
                    ],
                ],
            ],
            [
                'state' => 'Telangana',
                'district' => 'Rangareddy',
                'city' => 'Gachibowli',
                'oem_code' => 'OEM002',
                'oem_name' => 'Metro Mobility Services',
                'short_name' => 'Metro Mobility',
                'oem_type' => 'city bus',
                'registration_type' => 'Partnership',
                'gst_number' => '36ABNFM5678B1Z2',
                'pan_number' => 'ABNFM5678B',
                'cin_number' => null,
                'status' => 'Active',
                'is_verified' => true,
                'remarks' => 'Approved service and maintenance vendor.',
                'contacts' => [
                    [
                        'contact_person' => 'Ananya Rao',
                        'designation' => 'Service Head',
                        'phone_country_code' => '+91',
                        'phone' => '9988776655',
                        'alternate_phone_country_code' => null,
                        'alternate_phone' => null,
                        'email' => 'ananya.rao@metromobility.example',
                        'is_primary' => true,
                    ],
                ],
                'addresses' => [
                    [
                        'address_type' => 'Service',
                        'address_line1' => 'Financial District Service Yard',
                        'address_line2' => 'Unit 18',
                        'pincode' => '500032',
                        'latitude' => '17.4401',
                        'longitude' => '78.3489',
                    ],
                ],
            ],
            [
                'state' => 'Telangana',
                'district' => 'Warangal',
                'city' => 'Hanamkonda',
                'oem_code' => 'OEM003',
                'oem_name' => 'Transit Parts Dealers',
                'short_name' => 'Transit Parts',
                'oem_type' => '12m',
                'registration_type' => 'Proprietor',
                'gst_number' => '36AATPT9012C1Z8',
                'pan_number' => 'AATPT9012C',
                'cin_number' => null,
                'status' => 'Inactive',
                'is_verified' => false,
                'remarks' => 'Pending verification of dealer documents.',
                'contacts' => [
                    [
                        'contact_person' => 'Vikram Shah',
                        'designation' => 'Owner',
                        'phone_country_code' => '+91',
                        'phone' => '9123456780',
                        'alternate_phone_country_code' => '+91',
                        'alternate_phone' => '9123400001',
                        'email' => 'vikram@transitparts.example',
                        'is_primary' => true,
                    ],
                ],
                'addresses' => [
                    [
                        'address_type' => 'Billing',
                        'address_line1' => 'Hanamkonda Parts Market Road',
                        'address_line2' => 'Shop 14',
                        'pincode' => '506001',
                        'latitude' => '18.0072',
                        'longitude' => '79.5584',
                    ],
                ],
            ],
        ];

        DB::transaction(function () use ($records, $verifier) {
            foreach ($records as $record) {
                $state = State::where('name', $record['state'])->first();

                if (! $state) {
                    continue;
                }

                $oem = Oem::updateOrCreate(
                    [
                        'state_id' => $state->id,
                        'oem_code' => $record['oem_code'],
                    ],
                    [
                        'oem_name' => $record['oem_name'],
                        'short_name' => $record['short_name'],
                        'oem_type' => $record['oem_type'],
                        'registration_type' => $record['registration_type'],
                        'gst_number' => $record['gst_number'],
                        'pan_number' => $record['pan_number'],
                        'cin_number' => $record['cin_number'],
                        'status' => $record['status'],
                        'is_verified' => $record['is_verified'],
                        'verified_by' => $record['is_verified'] ? $verifier?->id : null,
                        'verified_at' => $record['is_verified'] ? now() : null,
                        'created_by' => $verifier?->id,
                        'updated_by' => $verifier?->id,
                        'remarks' => $record['remarks'],
                    ]
                );

                foreach ($record['contacts'] as $contact) {
                    $oem->contacts()->updateOrCreate(
                        [
                            'contact_person' => $contact['contact_person'],
                            'phone' => $contact['phone'],
                        ],
                        $contact
                    );
                }

                $district = District::where('state_id', $state->id)
                    ->where('name', $record['district'])
                    ->first();
                $city = $district
                    ? Location::where('district_id', $district->id)->where('name', $record['city'])->first()
                    : null;

                foreach ($record['addresses'] as $address) {
                    $oem->addresses()->updateOrCreate(
                        [
                            'address_type' => $address['address_type'],
                            'address_line1' => $address['address_line1'],
                        ],
                        $address + [
                            'state_id' => $state->id,
                            'district_id' => $district?->id,
                            'city_id' => $city?->id,
                        ]
                    );
                }
            }
        });
    }
}
