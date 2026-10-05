<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->unique();
            $table->string('gateway', 20);
            $table->string('gateway_customer_id')->nullable();
            $table->string('gateway_subscription_id')->nullable();
            $table->string('status', 20)->default('pending');
            // Preenchido no primeiro aviso de vencimento; a carência conta a partir daqui.
            $table->timestamp('overdue_since')->nullable();
            $table->timestamp('last_paid_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnUpdate()->restrictOnDelete();
            $table->index(['gateway', 'gateway_customer_id']);
            $table->index(['gateway', 'gateway_subscription_id']);
            $table->index(['status', 'overdue_since']);
        });

        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('gateway', 20);
            $table->string('gateway_event_id');
            $table->string('type');
            $table->json('payload');
            $table->string('tenant_id')->nullable();
            $table->string('outcome', 20)->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('created_at')->nullable();

            // Gateways reenviam eventos: o mesmo evento só entra uma vez (RN18).
            $table->unique(['gateway', 'gateway_event_id']);
        });

        Schema::table('plans', function (Blueprint $table) {
            // O Stripe cobra por produto; cada plano vira um produto lá, criado no primeiro uso.
            $table->string('stripe_product_id')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('stripe_product_id');
        });

        Schema::dropIfExists('webhook_events');
        Schema::dropIfExists('subscriptions');
    }
};
