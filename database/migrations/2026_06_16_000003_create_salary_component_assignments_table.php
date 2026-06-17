<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('salary_component_assignments')) {
            Schema::create('salary_component_assignments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('salary_component_id')->constrained('salary_components')->cascadeOnDelete();
                $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
                $table->foreignId('designation_id')->nullable()->constrained('designations')->nullOnDelete();
                $table->timestamps();

                $table->unique(['salary_component_id', 'role_id', 'designation_id'], 'salary_component_assignments_unique');
            });
        }

        if (Schema::hasColumn('salary_components', 'role_id')) {
            DB::table('salary_components')
                ->whereNotNull('role_id')
                ->orderBy('id')
                ->get()
                ->each(function ($component) {
                    DB::table('salary_component_assignments')->insertOrIgnore([
                        'salary_component_id' => $component->id,
                        'role_id' => $component->role_id,
                        'designation_id' => $component->designation_id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                });
        }

        $duplicates = DB::table('salary_components')
            ->select('component_name', DB::raw('MIN(id) as keep_id'))
            ->groupBy('component_name')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            $duplicateIds = DB::table('salary_components')
                ->where('component_name', $duplicate->component_name)
                ->where('id', '!=', $duplicate->keep_id)
                ->pluck('id');

            DB::table('salary_component_assignments')
                ->whereIn('salary_component_id', $duplicateIds)
                ->get()
                ->each(function ($assignment) use ($duplicate) {
                    DB::table('salary_component_assignments')->insertOrIgnore([
                        'salary_component_id' => $duplicate->keep_id,
                        'role_id' => $assignment->role_id,
                        'designation_id' => $assignment->designation_id,
                        'created_at' => $assignment->created_at,
                        'updated_at' => $assignment->updated_at,
                    ]);
                });

            DB::table('salary_component_assignments')
                ->whereIn('salary_component_id', $duplicateIds)
                ->delete();

            DB::table('salary_components')
                ->whereIn('id', $duplicateIds)
                ->delete();
        }

        if (Schema::hasColumn('salary_components', 'role_id')) {
            Schema::table('salary_components', function (Blueprint $table) {
                $table->dropForeign(['role_id']);
                $table->dropForeign(['designation_id']);
                $table->dropUnique('salary_components_role_designation_name_unique');
                $table->dropColumn(['role_id', 'designation_id']);
            });
        }

        if (! $this->hasIndex('salary_components', 'salary_components_component_name_unique')) {
            Schema::table('salary_components', function (Blueprint $table) {
                $table->unique('component_name');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('salary_components', 'role_id')) {
            Schema::table('salary_components', function (Blueprint $table) {
                $table->dropUnique(['component_name']);
                $table->foreignId('role_id')->nullable()->after('id')->constrained('roles')->nullOnDelete();
                $table->foreignId('designation_id')->nullable()->after('role_id')->constrained('designations')->nullOnDelete();
                $table->unique(['role_id', 'designation_id', 'component_name'], 'salary_components_role_designation_name_unique');
            });
        }

        if (Schema::hasTable('salary_component_assignments')) {
            DB::table('salary_component_assignments')
                ->orderBy('salary_component_id')
                ->get()
                ->each(function ($assignment) {
                    DB::table('salary_components')
                        ->where('id', $assignment->salary_component_id)
                        ->update([
                            'role_id' => $assignment->role_id,
                            'designation_id' => $assignment->designation_id,
                        ]);
                });
        }

        Schema::dropIfExists('salary_component_assignments');
    }

    private function hasIndex(string $table, string $index): bool
    {
        return collect(DB::select("SHOW INDEX FROM {$table}"))
            ->contains(fn ($row) => $row->Key_name === $index);
    }
};
