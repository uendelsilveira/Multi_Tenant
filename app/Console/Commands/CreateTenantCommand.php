<?php

declare(strict_types=1);

/*
 By Uendel Silveira
 Developer Web
 IDE: PhpStorm
 Created: 29/07/2026 20:05
*/

namespace App\Console\Commands;

use App\Enums\DomainPanel;
use App\Enums\DomainStatus;
use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\TenantUser;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Atalho de desenvolvimento. Não passa pelas regras de cadastro (plano,
 * documento, domínios) e cria o admin com senha conhecida. Será substituído
 * pelo provisionamento da fatia 2 (RF10, RF11).
 */
final class CreateTenantCommand extends Command
{
    protected $signature = 'tenant:create {id} {--domain=} {--name=} {--email=} {--password=password}';

    protected $description = '[dev] Cria um tenant com banco próprio e usuário admin, sem as regras de cadastro';

    public function handle(): int
    {
        $id = Str::slug((string) $this->argument('id'));
        $subdomain = (string) ($this->option('domain') ?? $id);
        $name = (string) ($this->option('name') ?? ucfirst($id).' Admin');
        $email = (string) ($this->option('email') ?? "admin@{$subdomain}.com");
        $password = (string) $this->option('password');

        /** @var list<string> $centralDomains */
        $centralDomains = (array) config('tenancy.central_domains', []);
        $host = $subdomain.'.'.($centralDomains[0] ?? 'localhost');

        if (Tenant::withTrashed()->find($id) !== null) {
            $this->error("Tenant '{$id}' já existe!");

            return Command::FAILURE;
        }

        $this->info("Criando tenant '{$id}' com banco de dados dedicado...");

        $tenant = Tenant::create([
            'id' => $id,
            'legal_name' => $id,
        ]);

        $tenant->domains()->create([
            'domain' => $host,
            'panel' => DomainPanel::Admin->value,
            'status' => DomainStatus::Active->value,
        ]);

        $this->info('Inicializando contexto do tenant e criando usuário admin...');

        tenancy()->initialize($tenant);

        TenantUser::create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'role' => UserRole::Admin,
        ]);

        $this->newLine();
        $this->info('Tenant criado com sucesso!');
        $this->table(
            ['ID', 'Domínio', 'URL Admin', 'Admin Email', 'Senha Admin'],
            [
                [$id, $host, "http://{$host}/admin", $email, $password],
            ]
        );

        return Command::SUCCESS;
    }
}
