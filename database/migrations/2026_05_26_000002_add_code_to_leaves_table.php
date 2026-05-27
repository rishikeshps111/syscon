<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
            $table->string('code')->nullable()->unique()->after('id');
        });

        if (! DB::table('prefixes')->where('module', 'Leave Management Module')->exists()) {
            DB::table('prefixes')->insert([
                'module' => 'Leave Management Module',
                'prefix' => 'LVM',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $prefix = DB::table('prefixes')
            ->where('module', 'Leave Management Module')
            ->where('is_active', true)
            ->value('prefix') ?? 'LVM';

        $year = DB::table('general_settings')->value('financial_year') ?? now()->year;

        DB::table('leaves')
            ->whereNull('code')
            ->orderBy('id')
            ->get(['id'])
            ->each(function ($leave) use ($prefix, $year) {
                DB::table('leaves')
                    ->where('id', $leave->id)
                    ->update([
                        'code' => $prefix . $year . '#' . str_pad((string) $leave->id, 3, '0', STR_PAD_LEFT),
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->dropColumn('code');
        });
    }
};
