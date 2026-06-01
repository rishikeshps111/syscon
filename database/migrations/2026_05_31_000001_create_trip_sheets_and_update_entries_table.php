<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('trip_sheets')) {
            Schema::create('trip_sheets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('trip_id')->constrained('trips')->cascadeOnDelete();
                $table->string('code')->nullable();
                $table->date('date');
                $table->enum('status', ['pending', 'partial', 'completed', 'cancelled'])->default('pending');
                $table->timestamps();

                $table->unique(['trip_id', 'date']);
                $table->unique('code');
            });
        }

        Schema::table('trip_sheet_entries', function (Blueprint $table) {
            if (! Schema::hasColumn('trip_sheet_entries', 'trip_sheet_id')) {
                $table->foreignId('trip_sheet_id')->nullable()->after('id')->constrained('trip_sheets')->cascadeOnDelete();
            }
            if (! Schema::hasColumn('trip_sheet_entries', 'side')) {
                $table->enum('side', ['up', 'down'])->nullable()->after('trip_sheet_id');
            }
            if (! Schema::hasColumn('trip_sheet_entries', 'starting_km')) {
                $table->unsignedInteger('starting_km')->nullable()->after('arrival_time');
            }
            if (! Schema::hasColumn('trip_sheet_entries', 'starting_electric_charge')) {
                $table->unsignedTinyInteger('starting_electric_charge')->nullable()->after('starting_km');
            }
            if (! Schema::hasColumn('trip_sheet_entries', 'vehicle_condition')) {
                $table->text('vehicle_condition')->nullable()->after('starting_electric_charge');
            }
            if (! Schema::hasColumn('trip_sheet_entries', 'is_vehicle_verified')) {
                $table->boolean('is_vehicle_verified')->default(false)->after('vehicle_condition');
            }
            if (! Schema::hasColumn('trip_sheet_entries', 'vehicle_verified_by')) {
                $table->string('vehicle_verified_by')->nullable()->after('is_vehicle_verified');
            }
            if (! Schema::hasColumn('trip_sheet_entries', 'vehicle_verified_at')) {
                $table->timestamp('vehicle_verified_at')->nullable()->after('vehicle_verified_by');
            }
            if (! Schema::hasColumn('trip_sheet_entries', 'is_driver_verified')) {
                $table->boolean('is_driver_verified')->default(false)->after('vehicle_verified_at');
            }
            if (! Schema::hasColumn('trip_sheet_entries', 'driver_verified_by')) {
                $table->string('driver_verified_by')->nullable()->after('is_driver_verified');
            }
            if (! Schema::hasColumn('trip_sheet_entries', 'driver_verified_at')) {
                $table->timestamp('driver_verified_at')->nullable()->after('driver_verified_by');
            }
            if (! Schema::hasColumn('trip_sheet_entries', 'is_verified_by_supervisor')) {
                $table->boolean('is_verified_by_supervisor')->default(false)->after('driver_verified_at');
            }
            if (! Schema::hasColumn('trip_sheet_entries', 'verified_by_supervisor')) {
                $table->string('verified_by_supervisor')->nullable()->after('is_verified_by_supervisor');
            }
            if (! Schema::hasColumn('trip_sheet_entries', 'verified_by_supervisor_at')) {
                $table->timestamp('verified_by_supervisor_at')->nullable()->after('verified_by_supervisor');
            }
            if (! Schema::hasColumn('trip_sheet_entries', 'is_verified_by_driver')) {
                $table->boolean('is_verified_by_driver')->default(false)->after('verified_by_supervisor_at');
            }
            if (! Schema::hasColumn('trip_sheet_entries', 'verified_by_driver')) {
                $table->string('verified_by_driver')->nullable()->after('is_verified_by_driver');
            }
            if (! Schema::hasColumn('trip_sheet_entries', 'verified_by_driver_at')) {
                $table->timestamp('verified_by_driver_at')->nullable()->after('verified_by_driver');
            }
        });

        if (Schema::hasColumn('trip_sheet_entries', 'trip_id') && Schema::hasColumn('trip_sheet_entries', 'trip_date')) {
            $entries = DB::table('trip_sheet_entries')
                ->whereNull('trip_sheet_id')
                ->orderBy('id')
                ->get();

            foreach ($entries as $entry) {
                $trip = DB::table('trips')->where('id', $entry->trip_id)->first();

                if (! $trip) {
                    continue;
                }

                $sheet = DB::table('trip_sheets')
                    ->where('trip_id', $entry->trip_id)
                    ->where('date', $entry->trip_date)
                    ->first();

                if (! $sheet) {
                    $sheetId = DB::table('trip_sheets')->insertGetId([
                        'trip_id' => $entry->trip_id,
                        'code' => $this->sheetCode($trip->code, $entry->trip_date),
                        'date' => $entry->trip_date,
                        'status' => 'pending',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } else {
                    $sheetId = $sheet->id;
                }

                DB::table('trip_sheet_entries')
                    ->where('id', $entry->id)
                    ->update([
                        'trip_sheet_id' => $sheetId,
                        'side' => $trip->trip_side === 'down' ? 'down' : 'up',
                        'updated_at' => now(),
                    ]);
            }
        }

        if (! $this->indexExists('trip_sheet_entries', 'trip_sheet_entries_trip_sheet_id_side_unique')) {
            Schema::table('trip_sheet_entries', function (Blueprint $table) {
                $table->unique(['trip_sheet_id', 'side']);
            });
        }

        Schema::table('trip_sheet_entries', function (Blueprint $table) {
            foreach (['trip_id', 'driver_profile_id', 'vehicle_id'] as $column) {
                if (Schema::hasColumn('trip_sheet_entries', $column)) {
                    $table->dropConstrainedForeignId($column);
                }
            }

            $dropColumns = array_values(array_filter([
                Schema::hasColumn('trip_sheet_entries', 'trip_date') ? 'trip_date' : null,
                Schema::hasColumn('trip_sheet_entries', 'verified_by') ? 'verified_by' : null,
                Schema::hasColumn('trip_sheet_entries', 'approved_by') ? 'approved_by' : null,
                Schema::hasColumn('trip_sheet_entries', 'shift') ? 'shift' : null,
            ]));

            if ($dropColumns) {
                $table->dropColumn($dropColumns);
            }
        });
    }

    public function down(): void
    {
        Schema::table('trip_sheet_entries', function (Blueprint $table) {
            $table->foreignId('trip_id')->nullable()->after('id')->constrained('trips')->cascadeOnDelete();
            $table->date('trip_date')->nullable()->after('trip_id');
            $table->string('verified_by')->nullable();
            $table->string('approved_by')->nullable();
            $table->string('shift', 20)->nullable();
            $table->foreignId('driver_profile_id')->nullable()->constrained('driver_profiles')->nullOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
            $table->index(['trip_id', 'trip_date']);

            $table->dropUnique(['trip_sheet_id', 'side']);
            $table->dropConstrainedForeignId('trip_sheet_id');
            $table->dropColumn([
                'side',
                'starting_km',
                'starting_electric_charge',
                'vehicle_condition',
                'is_vehicle_verified',
                'vehicle_verified_by',
                'vehicle_verified_at',
                'is_driver_verified',
                'driver_verified_by',
                'driver_verified_at',
                'is_verified_by_supervisor',
                'verified_by_supervisor',
                'verified_by_supervisor_at',
                'is_verified_by_driver',
                'verified_by_driver',
                'verified_by_driver_at',
            ]);
        });

        Schema::dropIfExists('trip_sheets');
    }

    private function sheetCode(?string $tripCode, string $date): string
    {
        return ($tripCode ?: 'TRIP') . '-' . str_replace('-', '', $date);
    }

    private function indexExists(string $table, string $index): bool
    {
        return (bool) DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $table)
            ->where('index_name', $index)
            ->exists();
    }
};
