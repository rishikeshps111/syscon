<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_device_tokens', function (Blueprint $table) {
            $table->string('app_type', 30)->default('operations')->after('platform');
            $table->index(['app_type', 'user_id'], 'device_tokens_app_user_index');
        });

        $driverRoleId = Role::where('name', 'Driver')->where('guard_name', 'web')->value('id');

        if ($driverRoleId) {
            $driverIds = DB::table('model_has_roles')
                ->where('role_id', $driverRoleId)
                ->where('model_type', User::class)
                ->pluck('model_id');

            DB::table('user_device_tokens')->whereIn('user_id', $driverIds)->update(['app_type' => 'driver']);
        }
    }

    public function down(): void
    {
        Schema::table('user_device_tokens', function (Blueprint $table) {
            $table->dropIndex('device_tokens_app_user_index');
            $table->dropColumn('app_type');
        });
    }
};
