<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class SyncUserRolesFromDesignations extends Command
{
    protected $signature = 'users:sync-roles-from-designations
        {--dry-run : Display changes without writing to the database}
        {--force : Skip the confirmation prompt}';

    protected $description = 'Convert employee profiles and synchronize user roles from designation role types.';

    private const DESIGNATION_ROLES = ['Staff', 'Driver', 'Controller', 'Supervisor', 'Housekeeping'];

    public function handle(): int
    {
        $isDryRun = (bool) $this->option('dry-run');

        if (! $isDryRun && ! $this->option('force') && ! $this->confirm('Update user roles from their designation role types?')) {
            $this->info('No changes made.');

            return self::SUCCESS;
        }

        $roles = Role::query()
            ->where('guard_name', 'web')
            ->whereIn('name', self::DESIGNATION_ROLES)
            ->pluck('name')
            ->all();

        $missingRoles = array_diff(self::DESIGNATION_ROLES, $roles);
        if ($missingRoles) {
            $this->error('Missing web roles: '.implode(', ', $missingRoles));

            return self::FAILURE;
        }

        $updated = 0;
        $skipped = 0;
        $unchanged = 0;

        User::query()
            ->with(['roles', 'staffProfile.designation', 'driverProfile.designation', 'housekeepingProfile.designation', 'controllerProfile.designation', 'supervisorProfile.designation'])
            ->where(function ($query): void {
                foreach (['staffProfile', 'driverProfile', 'housekeepingProfile', 'controllerProfile', 'supervisorProfile'] as $relation) {
                    $query->orWhereHas($relation, fn ($profile) => $profile->whereNotNull('designation_id'));
                }
            })
            ->orderBy('id')
            ->chunkById(100, function ($users) use ($isDryRun, &$updated, &$skipped, &$unchanged): void {
                foreach ($users as $user) {
                    $profile = $this->profileWithDesignation($user);
                    $designation = $profile?->designation;
                    $targetRole = $designation?->role_type;

                    if (! $targetRole || ! in_array($targetRole, self::DESIGNATION_ROLES, true)) {
                        $skipped++;
                        $this->warn("Skipped user {$user->id}: missing or invalid designation role type.");
                        continue;
                    }

                    $sourceRole = $profile ? $this->roleForRelation($profile) : null;
                    $currentRoles = $user->roles->pluck('name')->all();
                    if ($sourceRole === $targetRole) {
                        $hasStaleDesignationRole = collect(self::DESIGNATION_ROLES)
                            ->contains(fn (string $role) => $role !== $targetRole && in_array($role, $currentRoles, true));
                        $needsRoleSync = ! in_array($targetRole, $currentRoles, true) || $hasStaleDesignationRole;
                        $this->line("User {$user->id} ({$user->name}): {$targetRole} profile; roles ".implode(', ', $currentRoles ?: ['none'])." -> {$targetRole}");
                        if ($needsRoleSync) {
                            if (! $isDryRun) {
                                DB::transaction(function () use ($user, $targetRole): void {
                                    foreach (self::DESIGNATION_ROLES as $role) {
                                        if ($role !== $targetRole) {
                                            $user->removeRole($role);
                                        }
                                    }
                                    $user->assignRole($targetRole);
                                });
                            }
                            $updated++;
                        } else {
                            $unchanged++;
                        }
                        continue;
                    }

                    $destinationData = $this->destinationProfileData($user, $profile, $targetRole);
                    if (isset($destinationData['missing'])) {
                        $skipped++;
                        $this->warn("Skipped user {$user->id} ({$user->name}): {$targetRole} profile needs: ".implode(', ', $destinationData['missing']));
                        continue;
                    }

                    $keepSourceProfile = $this->sourceProfileHasHistory($profile);
                    $historyNote = $keepSourceProfile ? '; historical source profile will be retained without designation' : '';
                    $this->line("User {$user->id} ({$user->name}): ".($sourceRole ?? 'unknown profile')." -> {$targetRole}; designation {$designation->id} ({$designation->name}){$historyNote}");

                    if (! $isDryRun) {
                        DB::transaction(function () use ($user, $profile, $targetRole, $designation, $destinationData, $keepSourceProfile): void {
                            $this->createDestinationProfile($user, $targetRole, $designation->id, $destinationData);
                            if ($profile) {
                                if ($keepSourceProfile) {
                                    $profile->forceFill(['designation_id' => null])->save();
                                } else {
                                    $profile->delete();
                                }
                            }
                            foreach (self::DESIGNATION_ROLES as $role) {
                                if ($role !== $targetRole) {
                                    $user->removeRole($role);
                                }
                            }
                            $user->assignRole($targetRole);
                        });
                        if ($keepSourceProfile) {
                            $this->warn("User {$user->id}: retained the old ".class_basename($profile)." profile without its designation because it has linked history.");
                        }
                    }

                    $updated++;
                }
            });

        $this->info(($isDryRun ? 'Dry run complete. ' : '')."{$updated} user(s) converted or role-synced; {$unchanged} already aligned; {$skipped} skipped.");

        return self::SUCCESS;
    }

    private function profileWithDesignation(User $user): mixed
    {
        foreach (['staffProfile', 'driverProfile', 'housekeepingProfile', 'controllerProfile', 'supervisorProfile'] as $relation) {
            $profile = $user->{$relation};
            if ($profile?->designation) {
                return $profile;
            }
        }

        return null;
    }

    private function roleForRelation(mixed $profile): ?string
    {
        return match (class_basename($profile)) {
            'StaffProfile' => 'Staff',
            'DriverProfile' => 'Driver',
            'HousekeepingProfile' => 'Housekeeping',
            'ControllerProfile' => 'Controller',
            'SupervisorProfile' => 'Supervisor',
            default => null,
        };
    }

    private function destinationProfileData(User $user, mixed $source, string $role): array
    {
        $existing = match ($role) {
            'Staff' => $user->staffProfile,
            'Driver' => $user->driverProfile,
            'Housekeeping' => $user->housekeepingProfile,
            'Controller' => $user->controllerProfile,
            'Supervisor' => $user->supervisorProfile,
        };
        $existingData = $existing?->getAttributes() ?? [];
        $data = array_replace(
            $this->sharedProfileData($source),
            array_filter($existingData, fn ($value) => $value !== null)
        );
        $data['designation_id'] = $source->designation_id;

        if ($role === 'Housekeeping') {
            $data['joining_date'] = $data['joining_date'] ?? $data['date_of_joining'] ?? null;
            $data['account_number'] = $data['account_number'] ?? $data['bank_account_number'] ?? null;
        }

        if ($role === 'Driver') {
            $data['joining_date'] = $data['joining_date'] ?? $data['date_of_joining'] ?? null;
            $data['account_number'] = $data['account_number'] ?? $data['bank_account_number'] ?? null;
            $data['salary'] = $data['salary'] ?? $data['gross_salary'] ?? null;
            $data['wc_policy'] = $data['wc_policy'] ?? $data['esic_wc'] ?? null;
        }

        if ($role === 'Housekeeping') {
            $data['salary'] = $data['salary'] ?? $data['gross_salary'] ?? null;
            $data['pincode'] = $data['pincode'] ?? DB::table('locations')->where('id', $data['location_id'] ?? null)->value('pincode');

            if (blank($data['branch_location_id'] ?? null) && filled($data['depot_id'] ?? null)) {
                $branchIds = DB::table('depot_branch_location')
                    ->where('depot_id', $data['depot_id'])
                    ->pluck('branch_location_id');
                if ($branchIds->count() === 1) {
                    $data['branch_location_id'] = $branchIds->first();
                }
            }
        }

        if ($role === 'Driver') {
            $data['employment_type'] = match ($data['employment_type'] ?? null) {
                'full_time', 'part_time' => 'permanent',
                default => $data['employment_type'] ?? null,
            };
        } elseif (in_array($role, ['Staff', 'Controller', 'Supervisor', 'Housekeeping'], true)) {
            $data['employment_type'] = ($data['employment_type'] ?? null) === 'permanent' ? 'full_time' : ($data['employment_type'] ?? null);
        }

        $required = match ($role) {
            'Driver' => ['aadhaar_number', 'state_id', 'district_id', 'location_id', 'pincode', 'address', 'license_number', 'license_type', 'issue_date', 'expiry_date', 'employment_type', 'joining_date', 'salary', 'depot_id', 'branch_location_id', 'account_number', 'ifsc_code', 'emergency_contact_name', 'emergency_contact_no', 'medical_fitness_expiry'],
            'Housekeeping' => ['aadhaar_number', 'state_id', 'district_id', 'location_id', 'employment_type', 'joining_date', 'depot_id', 'account_number', 'ifsc_code'],
            default => [],
        };
        $missing = array_values(array_filter($required, fn (string $field) => blank($data[$field] ?? null)));

        return $missing ? ['missing' => $missing] : $data;
    }

    private function sharedProfileData(mixed $profile): array
    {
        if (! $profile) {
            return [];
        }

        $data = $profile->getAttributes();
        foreach (['reporting_to', 'employment_type', 'father_name', 'date_of_birth', 'aadhaar_number', 'pan_number', 'uan', 'esic_wc', 'country', 'state_id', 'district_id', 'location_id', 'depot_id', 'ifsc_code', 'basic', 'vda', 'basic_vda', 'hra', 'special_allowance', 'conveyance_allowance', 'bonus', 'gross_salary'] as $field) {
            if (array_key_exists($field, $data)) {
                continue;
            }
            $data[$field] = null;
        }
        if (! array_key_exists('date_of_joining', $data) && array_key_exists('joining_date', $data)) {
            $data['date_of_joining'] = $data['joining_date'];
        }
        if (! array_key_exists('bank_account_number', $data) && array_key_exists('account_number', $data)) {
            $data['bank_account_number'] = $data['account_number'];
        }
        if (! array_key_exists('esic_wc', $data) && array_key_exists('wc_policy', $data)) {
            $data['esic_wc'] = $data['wc_policy'];
        }
        if (! array_key_exists('gross_salary', $data) && array_key_exists('salary', $data)) {
            $data['gross_salary'] = $data['salary'];
        }

        return $data;
    }

    private function createDestinationProfile(User $user, string $role, int $designationId, array $data): void
    {
        $relation = match ($role) {
            'Staff' => 'staffProfile',
            'Driver' => 'driverProfile',
            'Housekeeping' => 'housekeepingProfile',
            'Controller' => 'controllerProfile',
            'Supervisor' => 'supervisorProfile',
        };
        $allowed = match ($role) {
            'Staff' => ['depot_id', 'designation_id', 'reporting_to', 'category', 'employment_type', 'father_name', 'date_of_birth', 'aadhaar_number', 'pan_number', 'date_of_joining', 'uan', 'esic_wc', 'country', 'state_id', 'district_id', 'location_id', 'bank_account_number', 'ifsc_code', 'basic', 'vda', 'basic_vda', 'hra', 'special_allowance', 'conveyance_allowance', 'bonus', 'gross_salary'],
            'Controller', 'Supervisor' => ['depot_id', 'designation_id', 'reporting_to', 'employment_type', 'father_name', 'date_of_birth', 'aadhaar_number', 'pan_number', 'date_of_joining', 'uan', 'esic_wc', 'country', 'state_id', 'district_id', 'location_id', 'bank_account_number', 'ifsc_code', 'basic', 'vda', 'basic_vda', 'hra', 'special_allowance', 'conveyance_allowance', 'bonus', 'gross_salary'],
            'Housekeeping' => ['designation_id', 'reporting_to', 'alternate_country_code', 'alternate_phone', 'father_name', 'date_of_birth', 'aadhaar_number', 'pan_number', 'uan', 'esic_wc', 'country', 'state_id', 'district_id', 'location_id', 'pincode', 'address', 'employment_type', 'joining_date', 'salary', 'depot_id', 'branch_location_id', 'account_number', 'ifsc_code', 'emergency_contact_name', 'emergency_country_code', 'emergency_contact_no', 'medical_fitness_expiry', 'police_verification_status', 'verification_status', 'basic', 'vda', 'basic_vda', 'hra', 'special_allowance', 'conveyance_allowance', 'bonus', 'gross_salary'],
            'Driver' => ['designation_id', 'alternate_country_code', 'alternate_phone', 'aadhaar_number', 'country', 'state_id', 'district_id', 'location_id', 'pincode', 'address', 'license_number', 'license_type', 'issue_date', 'expiry_date', 'badge_number', 'badge_expiry_date', 'employment_type', 'joining_date', 'uan', 'wc_policy', 'pan_number', 'salary', 'depot_id', 'branch_location_id', 'account_number', 'ifsc_code', 'emergency_contact_name', 'emergency_country_code', 'emergency_contact_no', 'medical_fitness_expiry', 'police_verification_status', 'verification_status'],
        };
        $payload = array_intersect_key($data, array_flip($allowed));
        if ($role === 'Staff' && ! isset($payload['category'])) {
            $payload['category'] = 'skilled';
        }
        $user->{$relation}()->updateOrCreate(['user_id' => $user->id], $payload + ['designation_id' => $designationId]);
    }

    private function sourceProfileHasHistory(mixed $profile): bool
    {
        $profileHistoryTables = match (class_basename($profile)) {
            'DriverProfile' => [
                ['trip_assignments', 'driver_profile_id'],
                ['trip_sheet_entries', 'driver_profile_id'],
                ['rosters', 'driver_profile_id'],
                ['driver_trip_notification_logs', 'driver_profile_id'],
                ['driver_document_expiry_notification_logs', 'driver_profile_id'],
                ['driver_license_expiry_alerts', 'driver_profile_id'],
            ],
            'ControllerProfile' => [
                ['rosters', 'controller_profile_id'],
                ['controller_trip_notification_logs', 'controller_profile_id'],
            ],
            'SupervisorProfile' => [
                ['rosters', 'supervisor_profile_id'],
                ['supervisor_trip_notification_logs', 'supervisor_profile_id'],
            ],
            default => [],
        };

        foreach ($profileHistoryTables as [$table, $column]) {
            if (\Illuminate\Support\Facades\Schema::hasTable($table)
                && \Illuminate\Support\Facades\Schema::hasColumn($table, $column)
                && DB::table($table)->where($column, $profile->id)->exists()) {
                return true;
            }
        }

        return false;
    }
}
