<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('today_trip_notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('trip_date');
            $table->unsignedInteger('trip_count');
            $table->unsignedInteger('sent_count')->default(0);
            $table->string('status', 20)->default('pending');
            $table->text('error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'trip_date'], 'today_trip_notification_unique');
        });

        $this->copyProfileLogs('controller_trip_notification_logs', 'controller_profiles', 'controller_profile_id');
        $this->copyProfileLogs('supervisor_trip_notification_logs', 'supervisor_profiles', 'supervisor_profile_id');

        Schema::dropIfExists('controller_trip_notification_logs');
        Schema::dropIfExists('supervisor_trip_notification_logs');
    }

    public function down(): void
    {
        $this->createProfileLogTable('controller_trip_notification_logs', 'controller_profile_id', 'controller_profiles');
        $this->createProfileLogTable('supervisor_trip_notification_logs', 'supervisor_profile_id', 'supervisor_profiles');

        Schema::dropIfExists('today_trip_notification_logs');
    }

    private function copyProfileLogs(string $logTable, string $profileTable, string $profileKey): void
    {
        if (! Schema::hasTable($logTable)) {
            return;
        }

        DB::table($logTable)
            ->join($profileTable, "{$profileTable}.id", '=', "{$logTable}.{$profileKey}")
            ->select([
                "{$profileTable}.user_id",
                "{$logTable}.trip_date",
                "{$logTable}.trip_count",
                "{$logTable}.sent_count",
                "{$logTable}.status",
                "{$logTable}.error",
                "{$logTable}.sent_at",
                "{$logTable}.created_at",
                "{$logTable}.updated_at",
            ])
            ->orderBy("{$logTable}.id")
            ->each(fn (object $log) => DB::table('today_trip_notification_logs')->insertOrIgnore((array) $log));
    }

    private function createProfileLogTable(string $tableName, string $profileKey, string $profileTable): void
    {
        Schema::create($tableName, function (Blueprint $table) use ($profileKey, $profileTable) {
            $table->id();
            $table->foreignId($profileKey)->constrained($profileTable)->cascadeOnDelete();
            $table->date('trip_date');
            $table->unsignedInteger('trip_count');
            $table->unsignedInteger('sent_count')->default(0);
            $table->string('status', 20)->default('pending');
            $table->text('error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->unique([$profileKey, 'trip_date']);
        });
    }
};
