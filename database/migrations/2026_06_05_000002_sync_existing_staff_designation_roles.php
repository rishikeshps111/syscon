<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $staffRole = DB::table('roles')
            ->where('name', 'Staff')
            ->where('guard_name', 'web')
            ->first();

        if (! $staffRole) {
            return;
        }

        $profiles = DB::table('staff_profiles')
            ->join('designations', 'designations.id', '=', 'staff_profiles.designation_id')
            ->whereNotNull('designations.role_id')
            ->get(['staff_profiles.user_id', 'designations.role_id']);

        foreach ($profiles as $profile) {
            $this->attachRole((int) $staffRole->id, (int) $profile->user_id);
            $this->attachRole((int) $profile->role_id, (int) $profile->user_id);
        }
    }

    public function down(): void
    {
        $roleIds = DB::table('designations')
            ->whereNotNull('role_id')
            ->pluck('role_id')
            ->all();

        if (! $roleIds) {
            return;
        }

        $staffUserIds = DB::table('staff_profiles')->pluck('user_id')->all();

        if (! $staffUserIds) {
            return;
        }

        DB::table('model_has_roles')
            ->whereIn('role_id', $roleIds)
            ->whereIn('model_id', $staffUserIds)
            ->where('model_type', User::class)
            ->delete();
    }

    private function attachRole(int $roleId, int $userId): void
    {
        $exists = DB::table('model_has_roles')
            ->where('role_id', $roleId)
            ->where('model_type', User::class)
            ->where('model_id', $userId)
            ->exists();

        if ($exists) {
            return;
        }

        DB::table('model_has_roles')->insert([
            'role_id' => $roleId,
            'model_type' => User::class,
            'model_id' => $userId,
        ]);
    }
};
