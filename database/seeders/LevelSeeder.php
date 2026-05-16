<?php

namespace Database\Seeders;

use App\Models\Level;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LevelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $records = [
            ['name' => 'Entry Level', 'is_active' => true],
            ['name' => 'Junior', 'is_active' => true],
            ['name' => 'Mid Level', 'is_active' => true],
            ['name' => 'Senior', 'is_active' => true],
            ['name' => 'Lead', 'is_active' => true],
            ['name' => 'Manager', 'is_active' => false],
        ];

        DB::transaction(function () use ($records) {
            foreach ($records as $record) {
                $level = Level::firstOrCreate(
                    ['name' => $record['name']],
                    ['is_active' => $record['is_active']]
                );

                if (! $level->code) {
                    $level->code = generate_code('Level Module', $level->id, 3, 'LVL');
                    $level->save();
                }
            }
        });
    }
}
