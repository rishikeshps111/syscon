<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->string('user_type')->nullable()->after('year');
            $table->enum('half_day_period', ['morning', 'afternoon'])->nullable()->after('status');
            $table->index(['year', 'month', 'user_type']);
        });

        foreach (['Supervisor', 'Controller', 'Staff', 'Driver'] as $role) {
            DB::table('attendances')
                ->whereNull('user_type')
                ->whereIn('user_id', function ($query) use ($role) {
                    $query->select('model_has_roles.model_id')
                        ->from('model_has_roles')
                        ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                        ->where('roles.name', $role)
                        ->where('model_has_roles.model_type', User::class);
                })
                ->update(['user_type' => $role]);
        }
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex(['year', 'month', 'user_type']);
            $table->dropColumn(['user_type', 'half_day_period']);
        });
    }
};
