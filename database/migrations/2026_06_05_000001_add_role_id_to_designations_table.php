<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('designations', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('reporting_to')->constrained('roles')->nullOnDelete();
        });

        $designations = DB::table('designations')->whereNull('role_id')->get(['id', 'name']);

        foreach ($designations as $designation) {
            if (! $designation->name) {
                continue;
            }

            $role = DB::table('roles')
                ->where('name', $designation->name)
                ->where('guard_name', 'web')
                ->first();

            if (! $role) {
                $roleId = DB::table('roles')->insertGetId([
                    'name' => $designation->name,
                    'guard_name' => 'web',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $roleId = $role->id;
            }

            DB::table('designations')
                ->where('id', $designation->id)
                ->update(['role_id' => $roleId]);
        }
    }

    public function down(): void
    {
        Schema::table('designations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('role_id');
        });
    }
};
