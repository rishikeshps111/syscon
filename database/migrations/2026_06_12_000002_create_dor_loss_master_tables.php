<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dor_account_responsibles', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->nullable();
            $table->string('name')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('dor_kilometer_loss_reasons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dor_account_responsible_id')->constrained('dor_account_responsibles')->cascadeOnDelete();
            $table->string('code')->unique()->nullable();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['dor_account_responsible_id', 'name'], 'dor_loss_reason_account_name_unique');
        });

        Schema::table('trip_sheet_entry_dors', function (Blueprint $table) {
            $table->foreignId('dor_account_responsible_id')->nullable()->after('difference')->constrained('dor_account_responsibles')->nullOnDelete();
            $table->foreignId('dor_kilometer_loss_reason_id')->nullable()->after('account_responsible')->constrained('dor_kilometer_loss_reasons')->nullOnDelete();
            $table->decimal('dor_kwh', 10, 2)->nullable()->after('dor_kwh_per_km_act');
            $table->decimal('dcr_kwh_per_km_odo', 10, 4)->nullable()->after('dor_kwh');
            $table->decimal('dcr_kwh_per_km_act', 10, 4)->nullable()->after('dcr_kwh_per_km_odo');
        });

        $now = now();
        foreach (['Operation', 'Authority', 'Maintenance'] as $index => $name) {
            DB::table('dor_account_responsibles')->insert([
                'code' => 'DAR' . str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                'name' => $name,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $accounts = DB::table('dor_account_responsibles')->pluck('id', 'name');
        $reasons = [
            'Operation' => ['Accident', 'Want of drivers'],
            'Maintenance' => ['Breakdown - enroute'],
            'Authority' => ['Bus not allowed to depart due to poor passenger count'],
        ];

        foreach ($reasons as $accountName => $items) {
            if (! isset($accounts[$accountName])) {
                continue;
            }

            foreach ($items as $itemIndex => $reason) {
                DB::table('dor_kilometer_loss_reasons')->insert([
                    'dor_account_responsible_id' => $accounts[$accountName],
                    'code' => 'DKL' . str_pad((string) (DB::table('dor_kilometer_loss_reasons')->count() + 1), 3, '0', STR_PAD_LEFT),
                    'name' => $reason,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('trip_sheet_entry_dors', function (Blueprint $table) {
            $table->dropConstrainedForeignId('dor_kilometer_loss_reason_id');
            $table->dropConstrainedForeignId('dor_account_responsible_id');
            $table->dropColumn(['dor_kwh', 'dcr_kwh_per_km_odo', 'dcr_kwh_per_km_act']);
        });

        Schema::dropIfExists('dor_kilometer_loss_reasons');
        Schema::dropIfExists('dor_account_responsibles');
    }
};
