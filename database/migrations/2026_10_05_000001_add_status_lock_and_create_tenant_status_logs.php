<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            // Enquanto esta data não passar, a cobrança automática não altera a situação (RN17).
            $table->timestamp('status_locked_until')->nullable();
        });

        Schema::create('tenant_status_logs', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->string('from_status', 20);
            $table->string('to_status', 20);
            $table->string('source', 20);
            $table->foreignId('central_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->timestamp('locked_until')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnUpdate()->restrictOnDelete();
            $table->index(['tenant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_status_logs');

        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('status_locked_until');
        });
    }
};
