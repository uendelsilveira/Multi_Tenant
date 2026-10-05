<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // O que o admin do tenant ligou. Sem linha, a funcionalidade está desligada (RN39).
        Schema::create('feature_settings', function (Blueprint $table) {
            $table->string('feature_key')->primary();
            $table->boolean('enabled')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feature_settings');
    }
};
