<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('locations')->whereNull('short_name')->orWhere('short_name', '')->orderBy('id')->eachById(function ($location) {
            DB::table('locations')->where('id', $location->id)->update([
                'short_name' => mb_substr($location->name, 0, 50),
            ]);
        }, 100, 'id', 'id');

        Schema::table('locations', function (Blueprint $table) {
            $table->string('short_name', 50)->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->string('short_name', 50)->nullable()->change();
        });
    }
};
