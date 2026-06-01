<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('depots')) {
            return;
        }

        Schema::table('depots', function (Blueprint $table) {
            if (! Schema::hasColumn('depots', 'state_id')) {
                $table->foreignId('state_id')->nullable()->after('id')->constrained('states')->nullOnDelete();
            }

            if (! Schema::hasColumn('depots', 'district_id')) {
                $table->foreignId('district_id')->nullable()->after('state_id')->constrained('districts')->nullOnDelete();
            }

            if (! Schema::hasColumn('depots', 'short_name')) {
                $table->string('short_name', 50)->nullable()->after('name');
            }
        });

        DB::table('depots')
            ->whereNull('state_id')
            ->orWhereNull('district_id')
            ->orderBy('id')
            ->get(['id', 'location_id'])
            ->each(function ($depot) {
                $location = DB::table('locations')->where('id', $depot->location_id)->first(['state_id', 'district_id']);

                if (! $location) {
                    return;
                }

                DB::table('depots')->where('id', $depot->id)->update([
                    'state_id' => $location->state_id,
                    'district_id' => $location->district_id,
                ]);
            });
    }

    public function down(): void
    {
        if (! Schema::hasTable('depots')) {
            return;
        }

        Schema::table('depots', function (Blueprint $table) {
            if (Schema::hasColumn('depots', 'district_id')) {
                $table->dropConstrainedForeignId('district_id');
            }

            if (Schema::hasColumn('depots', 'state_id')) {
                $table->dropConstrainedForeignId('state_id');
            }

            if (Schema::hasColumn('depots', 'short_name')) {
                $table->dropColumn('short_name');
            }
        });
    }
};
