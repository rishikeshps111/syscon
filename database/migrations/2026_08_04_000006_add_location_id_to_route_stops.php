<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('route_stops', function (Blueprint $table) {
            $table->foreignId('location_id')->nullable()->after('route_id')->constrained('locations')->nullOnDelete();
        });

        DB::table('route_stops')->orderBy('id')->get(['id', 'route_id', 'name'])->each(function ($stop) {
            $stateId = DB::table('routes')->where('id', $stop->route_id)->value('state_id');
            $locationId = DB::table('locations')->where('state_id', $stateId)->where('name', $stop->name)->value('id');
            DB::table('route_stops')->where('id', $stop->id)->update(['location_id' => $locationId]);
        });
    }

    public function down(): void
    {
        Schema::table('route_stops', function (Blueprint $table) {
            $table->dropConstrainedForeignId('location_id');
        });
    }
};
