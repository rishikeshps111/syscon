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
            'Kerala',
            'Karnataka',
            'Maharashtra'
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
