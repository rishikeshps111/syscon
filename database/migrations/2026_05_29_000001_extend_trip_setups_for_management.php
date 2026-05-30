<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = Schema::hasTable('trips') ? 'trips' : 'trip_setups';

        if (! Schema::hasTable($tableName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) {
            $table->foreignId('depot_id')->nullable()->after('route_id')->constrained('depots')->nullOnDelete();
            $table->string('title')->nullable()->after('code');
            $table->date('from_date')->nullable()->after('end_time');
            $table->date('to_date')->nullable()->after('from_date');
            $table->string('status', 30)->default('Active')->after('to_date');
            $table->text('notes')->nullable()->after('status');
            $table->text('cancellation_reason')->nullable()->after('notes');
            $table->foreignId('created_by')->nullable()->after('is_active')->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        $tableName = Schema::hasTable('trips') ? 'trips' : 'trip_setups';

        if (! Schema::hasTable($tableName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) {
            $table->dropConstrainedForeignId('updated_by');
            $table->dropConstrainedForeignId('created_by');
            $table->dropColumn(['cancellation_reason', 'notes', 'status', 'to_date', 'from_date', 'title']);
            $table->dropConstrainedForeignId('depot_id');
        });
    }
};
