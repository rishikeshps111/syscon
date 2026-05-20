<?php

namespace Database\Seeders;

use App\Models\ComplaintCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ComplaintCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $records = [
            ['name' => 'Misconduct', 'is_active' => true],
            ['name' => 'Late Reporting', 'is_active' => true],
            ['name' => 'Absenteeism', 'is_active' => true],
            ['name' => 'Behavior Issue', 'is_active' => true],
            ['name' => 'Safety Violation', 'is_active' => true],
            ['name' => 'Other', 'is_active' => true],
        ];

        DB::transaction(function () use ($records) {
            foreach ($records as $record) {
                $complaintCategory = ComplaintCategory::firstOrCreate(
                    ['name' => $record['name']],
                    ['is_active' => $record['is_active']]
                );

                if (! $complaintCategory->code) {
                    $complaintCategory->code = generate_code('Complaint Category Module', $complaintCategory->id, 3, 'CC');
                    $complaintCategory->save();
                }
            }
        });
    }
}
