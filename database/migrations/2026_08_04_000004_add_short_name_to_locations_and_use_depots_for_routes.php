<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->string('short_name', 50)->nullable()->after('name');
        });

        Schema::table('routes', function (Blueprint $table) {
            $table->dropForeign(['start_point_id']);
            $table->dropForeign(['end_point_id']);
            $table->unsignedBigInteger('start_point_id')->nullable()->change();
            $table->unsignedBigInteger('end_point_id')->nullable()->change();
            $table->enum('route_type', ['Intercity', 'Intracity'])->nullable()->default(null)->change();
            $table->enum('route_category', ['Passenger', 'Cargo'])->nullable()->default(null)->change();
        });

        DB::table('routes')->orderBy('id')->get(['id', 'start_point_id', 'end_point_id'])->each(function ($route) {
            $startDepotId = DB::table('depots')->where('location_id', $route->start_point_id)->value('id');
            $endDepotId = DB::table('depots')->where('location_id', $route->end_point_id)->value('id');

            DB::table('routes')->where('id', $route->id)->update([
                'start_point_id' => $startDepotId,
                'end_point_id' => $endDepotId,
            ]);
        });

        Schema::table('routes', function (Blueprint $table) {
            $table->foreign('start_point_id')->references('id')->on('depots')->nullOnDelete();
            $table->foreign('end_point_id')->references('id')->on('depots')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('routes', function (Blueprint $table) {
            $table->dropForeign(['start_point_id']);
            $table->dropForeign(['end_point_id']);
        });

        DB::table('routes')->orderBy('id')->get(['id', 'start_point_id', 'end_point_id'])->each(function ($route) {
            DB::table('routes')->where('id', $route->id)->update([
                'start_point_id' => DB::table('depots')->where('id', $route->start_point_id)->value('location_id'),
                'end_point_id' => DB::table('depots')->where('id', $route->end_point_id)->value('location_id'),
            ]);
        });

        Schema::table('routes', function (Blueprint $table) {
            $table->foreign('start_point_id')->references('id')->on('locations')->nullOnDelete();
            $table->foreign('end_point_id')->references('id')->on('locations')->nullOnDelete();
            $table->enum('route_type', ['Intercity', 'Intracity'])->nullable(false)->default('Intracity')->change();
            $table->enum('route_category', ['Passenger', 'Cargo'])->nullable(false)->default('Passenger')->change();
        });

        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn('short_name');
        });
    }
};
