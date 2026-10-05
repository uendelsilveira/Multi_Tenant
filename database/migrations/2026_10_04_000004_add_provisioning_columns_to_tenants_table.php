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
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('provisioning_status', 20)->default('pending');
            $table->text('provisioning_error')->nullable();
            $table->timestamp('provisioned_at')->nullable();
        });

        // Tenants que já existiam foram criados pelo pipeline síncrono e têm banco.
        DB::table('tenants')->update([
            'provisioning_status' => 'ready',
            'provisioned_at' => DB::raw('created_at'),
        ]);
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['provisioning_status', 'provisioning_error', 'provisioned_at']);
        });
    }
};
