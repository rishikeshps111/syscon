<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('trips')) {
            return;
        }

        DB::table('trips')
            ->where('status', 'Assigned')
            ->update(['status' => 'Active', 'is_active' => true]);

        DB::table('trips')
            ->where('status', 'Trip Completed')
            ->update(['status' => 'Inactive', 'is_active' => false]);
    }

    public function down(): void
    {
        //
    }
};
