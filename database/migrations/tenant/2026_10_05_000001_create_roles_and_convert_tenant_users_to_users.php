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
        // Pode rodar de novo depois de uma falha no meio: o MySQL não desfaz DDL.
        if (! Schema::hasTable('roles')) {
            Schema::create('roles', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('base_type', 20);
                $table->boolean('is_system')->default(false);
                // Chaves do catálogo em config/permissions.php. Perfil de sistema não usa: tem todas as do seu tipo.
                $table->json('permissions')->nullable();
                $table->timestamps();
            });
        }

        // Os três perfis de sistema existem em todo tenant (RN09).
        $now = now();
        $systemRoles = [];

        foreach (['admin' => 'Admin', 'user' => 'Usuário', 'customer' => 'Cliente'] as $baseType => $name) {
            $systemRoles[$baseType] = DB::table('roles')->where('is_system', true)->where('base_type', $baseType)->value('id')
                ?? DB::table('roles')->insertGetId([
                    'name' => $name,
                    'base_type' => $baseType,
                    'is_system' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
        }

        // Tenants criados no período em que o projeto usou Jetstream têm uma tabela
        // `users` daquela época. Ela é preservada com outro nome, não apagada.
        if (Schema::hasTable('users') && Schema::hasTable('tenant_users')) {
            Schema::rename('users', 'legacy_users');
        }

        Schema::rename('tenant_users', 'users');

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->constrained('roles')->restrictOnDelete();
            $table->boolean('is_active')->default(true);
        });

        // O tipo que a pessoa carregava passa a vir do perfil de sistema correspondente.
        foreach ($systemRoles as $baseType => $roleId) {
            DB::table('users')->where('type', $baseType)->update(['role_id' => $roleId]);
        }

        DB::table('users')->whereNull('role_id')->update(['role_id' => $systemRoles['user']]);

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('type', 20)->default('user');
        });

        foreach (DB::table('roles')->get(['id', 'base_type']) as $role) {
            DB::table('users')->where('role_id', $role->id)->update(['type' => $role->base_type]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('role_id');
            $table->dropColumn('is_active');
        });

        Schema::rename('users', 'tenant_users');
        Schema::dropIfExists('roles');

        if (Schema::hasTable('legacy_users')) {
            Schema::rename('legacy_users', 'users');
        }
    }
};
