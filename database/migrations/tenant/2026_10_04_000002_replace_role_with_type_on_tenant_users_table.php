<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenant_users', function (Blueprint $table) {
            $table->string('type', 20)->default('user');
        });

        // Os quatro papéis antigos viram dois dos três tipos base (RN08).
        DB::table('tenant_users')->whereIn('role', ['super_admin', 'admin'])->update(['type' => 'admin']);
        DB::table('tenant_users')->whereNotIn('role', ['super_admin', 'admin'])->update(['type' => 'user']);

        Schema::table('tenant_users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }

    public function down(): void
    {
        Schema::table('tenant_users', function (Blueprint $table) {
            $table->string('role')->default('operator');
        });

        DB::table('tenant_users')->where('type', 'admin')->update(['role' => 'admin']);
        DB::table('tenant_users')->where('type', '!=', 'admin')->update(['role' => 'operator']);

        Schema::table('tenant_users', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
