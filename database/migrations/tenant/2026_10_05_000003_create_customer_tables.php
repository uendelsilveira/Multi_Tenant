<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Dados que só cliente tem, fora da tabela de pessoas (ADR-0003).
        Schema::create('customer_profiles', function (Blueprint $table) {
            $table->foreignId('user_id')->primary()->constrained('users')->cascadeOnDelete();
            $table->string('phone', 20)->nullable();
            $table->string('document', 14)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Vínculo N:N entre usuário e cliente. As duas pontas são pessoas do tenant.
        Schema::create('customer_user', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('created_at')->nullable();
            $table->primary(['user_id', 'customer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_user');
        Schema::dropIfExists('customer_profiles');
    }
};
