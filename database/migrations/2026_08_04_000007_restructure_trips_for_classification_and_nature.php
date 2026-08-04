<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->dropForeign(['service_type_id']);
            $table->unsignedBigInteger('service_type_id')->nullable()->change();
            $table->foreign('service_type_id')->references('id')->on('service_types')->nullOnDelete();
            $table->enum('schedule_type', ['daily', 'weekly', 'monthly'])->nullable()->change();
            $table->time('start_time')->nullable()->change();
            $table->time('end_time')->nullable()->change();
            $table->foreignId('vehicle_classification_id')->nullable()->after('route_id')->constrained('vehicle_classifications')->nullOnDelete();
            $table->foreignId('trip_nature_id')->nullable()->after('vehicle_classification_id')->constrained('trip_natures')->nullOnDelete();
            $table->unsignedInteger('rounds_per_trip')->default(1)->after('schedule_km');
            $table->unsignedInteger('total_trips')->default(1)->after('rounds_per_trip');
        });

        DB::table('trips')->join('routes', 'trips.route_id', '=', 'routes.id')->update([
            'trips.schedule_km' => DB::raw('routes.total_distance_km * 2 * trips.rounds_per_trip'),
        ]);
    }

    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->dropConstrainedForeignId('trip_nature_id');
            $table->dropConstrainedForeignId('vehicle_classification_id');
            $table->dropColumn(['rounds_per_trip', 'total_trips']);
            $table->dropForeign(['service_type_id']);
            $table->unsignedBigInteger('service_type_id')->nullable(false)->change();
            $table->foreign('service_type_id')->references('id')->on('service_types')->cascadeOnDelete();
            $table->enum('schedule_type', ['daily', 'weekly', 'monthly'])->nullable(false)->change();
            $table->time('start_time')->nullable(false)->change();
            $table->time('end_time')->nullable(false)->change();
        });
    }
};
