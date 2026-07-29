<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\TenantUser;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CreateTenantCommand extends Command
{
    protected $signature = 'tenant:create {id} {--domain=} {--name=} {--email=} {--password=password}';

    protected $description = 'Cria um novo tenant com banco de dados próprio e usuário admin';

    public function handle(): int
    {
        $id = Str::slug($this->argument('id'));
        $subdomain = $this->option('domain') ?? $id;
        $name = $this->option('name') ?? ucfirst($id).' Admin';
        $email = $this->option('email') ?? "admin@{$subdomain}.com";
        $password = $this->option('password');

        if (Tenant::find($id)) {
            $this->error("Tenant '{$id}' já existe!");

            return Command::FAILURE;
        }

        $this->info("Criando tenant '{$id}' com banco de dados dedicado...");

        $tenant = Tenant::create([
            'id' => $id,
            'tenant_name' => $subdomain,
        ]);
        $tenant->domains()->create(['domain' => $subdomain]);

        $this->info('Inicializando contexto do tenant e criando usuário admin...');

        tenancy()->initialize($tenant);

        $user = TenantUser::create([
            'name' => $name,
            'email' => $email,
            'password' => bcrypt($password),
            'role' => \App\Enums\UserRole::Admin,
        ]);

        $this->newLine();
        $this->info('✅ Tenant criado com sucesso!');
        $this->table(
            ['ID', 'Subdomínio', 'URL Filament', 'Admin Email', 'Senha Admin'],
            [
                [$id, $subdomain, "http://{$subdomain}.localhost/admin", $email, $password],
            ]
        );

        return Command::SUCCESS;
    }
}
