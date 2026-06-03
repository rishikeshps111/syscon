<?php

namespace Database\Seeders;

use App\Models\State;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $states = [
            'Andhra Pradesh',
            'Arunachal Pradesh',
            'Assam',
            'Bihar',
            'Chhattisgarh',
            'Goa',
            'Gujarat',
            'Haryana',
            'Himachal Pradesh',
            'Jharkhand',
            'Kerala',
            'Karnataka',
            'Madhya Pradesh',
            'Maharashtra',
            'Manipur',
            'Meghalaya',
            'Mizoram',
            'Nagaland',
            'Odisha',
            'Punjab',
            'Rajasthan',
            'Sikkim',
            'Tamil Nadu',
            'Telangana',
            'Tripura',
            'Uttar Pradesh',
            'Uttarakhand',
            'West Bengal',
            'Andaman and Nicobar Islands',
            'Chandigarh',
            'Dadra and Nagar Haveli and Daman and Diu',
            'Delhi',
            'Jammu and Kashmir',
            'Ladakh',
            'Lakshadweep',
            'Puducherry',
        ];

        DB::transaction(function () use ($states) {
            $hasDefault = State::where('is_default', true)->exists();

            foreach ($states as $index => $name) {
                $state = State::firstOrCreate(
                    ['name' => $name],
                    [
                        'is_active' => true,
                        'is_default' => ! $hasDefault && $index === 0,
                    ]
                );

                if (! $state->code) {
                    $state->code = generate_code('State Module', $state->id, 3, 'ST');
                    $state->save();
                }
            }
        });
    }
}
