<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trip_sheet_entries', function (Blueprint $table) {
            $table->unsignedInteger('ending_km')->nullable()->after('starting_km');
            $table->unsignedTinyInteger('ending_electric_charge')->nullable()->after('starting_electric_charge');

            $table->renameColumn('is_verified_by_supervisor', 'is_initial_verified');
            $table->renameColumn('verified_by_supervisor', 'initial_verification_by');
            $table->renameColumn('verified_by_supervisor_at', 'initial_verification_at');
            $table->renameColumn('is_verified_by_controller', 'is_final_verified');
            $table->renameColumn('verified_by_controller', 'final_verification_by');
            $table->renameColumn('verified_by_controller_at', 'final_verification_at');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE trip_sheets MODIFY status ENUM('pending','partial','completed','initial_verification_completed','verification_completed','cancelled') NOT NULL DEFAULT 'pending'");
        }

        DB::table('trip_sheets')->where('status', 'partial')->update(['status' => 'initial_verification_completed']);
        DB::table('trip_sheets')->where('status', 'completed')->update(['status' => 'verification_completed']);

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE trip_sheets MODIFY status ENUM('pending','initial_verification_completed','verification_completed','cancelled') NOT NULL DEFAULT 'pending'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE trip_sheets MODIFY status ENUM('pending','partial','completed','initial_verification_completed','verification_completed','cancelled') NOT NULL DEFAULT 'pending'");
        }

        DB::table('trip_sheets')->where('status', 'initial_verification_completed')->update(['status' => 'partial']);
        DB::table('trip_sheets')->where('status', 'verification_completed')->update(['status' => 'completed']);

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE trip_sheets MODIFY status ENUM('pending','partial','completed','cancelled') NOT NULL DEFAULT 'pending'");
        }

        Schema::table('trip_sheet_entries', function (Blueprint $table) {
            $table->renameColumn('is_initial_verified', 'is_verified_by_supervisor');
            $table->renameColumn('initial_verification_by', 'verified_by_supervisor');
            $table->renameColumn('initial_verification_at', 'verified_by_supervisor_at');
            $table->renameColumn('is_final_verified', 'is_verified_by_controller');
            $table->renameColumn('final_verification_by', 'verified_by_controller');
            $table->renameColumn('final_verification_at', 'verified_by_controller_at');

            $table->dropColumn(['ending_km', 'ending_electric_charge']);
        });
    }
};
