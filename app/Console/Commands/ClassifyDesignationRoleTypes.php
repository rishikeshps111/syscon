<?php

namespace App\Console\Commands;

use App\Models\Designation;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ClassifyDesignationRoleTypes extends Command
{
    protected $signature = 'designations:classify-role-types
        {--apply : Save the proposed role_type values}
        {--designation= : Classify only the given designation ID}';

    protected $description = 'Classify designation role types from words in their names.';

    /** @var array<string, list<string>> */
    private const MATCHES = [
        'Driver' => ['driver'],
        'Controller' => ['controller'],
        'Supervisor' => ['supervisor'],
        'Housekeeping' => ['cleaner'],
    ];

    public function handle(): int
    {
        $designationId = $this->option('designation');
        if ($designationId !== null && (! ctype_digit((string) $designationId) || ! Designation::whereKey($designationId)->exists())) {
            $this->error('The supplied designation ID does not exist.');

            return self::FAILURE;
        }

        $counts = ['changed' => 0, 'unchanged' => 0];
        Designation::query()
            ->when($designationId !== null, fn ($query) => $query->whereKey($designationId))
            ->orderBy('id')
            ->chunkById(100, function ($designations) use (&$counts): void {
                foreach ($designations as $designation) {
                    $targetRole = $this->roleTypeFromName((string) $designation->name);
                    $currentRole = $designation->role_type;
                    $status = $currentRole === $targetRole ? 'unchanged' : 'changed';
                    $counts[$status]++;

                    $this->line("#{$designation->id} {$designation->name}: ".($currentRole ?: '(unset)')." -> {$targetRole}");

                    if ($status === 'changed' && $this->option('apply')) {
                        $designation->update(['role_type' => $targetRole]);
                    }
                }
            });

        $mode = $this->option('apply') ? 'Applied' : 'Dry run';
        $this->info("{$mode}: {$counts['changed']} role type(s) to change; {$counts['unchanged']} already correct.");

        return self::SUCCESS;
    }

    private function roleTypeFromName(string $name): string
    {
        $words = preg_split('/[^\pL\pN]+/u', Str::lower($name), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        foreach (self::MATCHES as $roleType => $keywords) {
            if (array_intersect($keywords, $words)) {
                return $roleType;
            }
        }

        return 'Staff';
    }
}
