<?php

namespace Database\Seeders;

use App\Models\Complaint;
use App\Models\ComplaintCategory;
use App\Models\User;
use Illuminate\Database\Seeder;

class ComplaintSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $controller = User::role('Controller')->first();
        $supervisor = User::role('Supervisor')->first();
        $driver = User::role('Driver')->first();
        $lateReporting = ComplaintCategory::where('name', 'Late Reporting')->first();
        $misconduct = ComplaintCategory::where('name', 'Misconduct')->first();
        $behaviorIssue = ComplaintCategory::where('name', 'Behavior Issue')->first();

        if (! $controller || ! $supervisor || ! $driver) {
            return;
        }

        $records = [
            [
                'complaint_date' => now()->toDateString(),
                'reported_by_role' => 'controller',
                'reported_by_user_id' => $controller->id,
                'against_role' => 'driver',
                'against_user_id' => $driver->id,
                'complaint_category_id' => $lateReporting?->id,
                'description' => 'Driver reported late for assigned duty.',
                'severity' => 'medium',
                'status' => 'pending',
            ],
            [
                'complaint_date' => now()->subDay()->toDateString(),
                'reported_by_role' => 'supervisor',
                'reported_by_user_id' => $supervisor->id,
                'against_role' => 'driver',
                'against_user_id' => $driver->id,
                'complaint_category_id' => $misconduct?->id,
                'description' => 'Misconduct reported during shift handover.',
                'severity' => 'high',
                'status' => 'in_review',
            ],
            [
                'complaint_date' => now()->subDays(2)->toDateString(),
                'reported_by_role' => 'supervisor',
                'reported_by_user_id' => $supervisor->id,
                'against_role' => 'controller',
                'against_user_id' => $controller->id,
                'complaint_category_id' => $behaviorIssue?->id,
                'description' => 'Behavior issue reported for internal review.',
                'severity' => 'low',
                'status' => 'closed',
            ],
        ];

        foreach ($records as $record) {
            if (! $record['complaint_category_id']) {
                continue;
            }

            $complaint = Complaint::firstOrCreate(
                [
                    'reported_by_user_id' => $record['reported_by_user_id'],
                    'against_user_id' => $record['against_user_id'],
                    'complaint_category_id' => $record['complaint_category_id'],
                    'complaint_date' => $record['complaint_date'],
                ],
                $record
            );

            if (! $complaint->code) {
                $complaint->code = generate_code('Complaint Module', $complaint->id, 3, 'CMP');
                $complaint->save();
            }
        }
    }
}
