# Estado atual do código × documentação

Leitura do repositório em 2026-10-04, na branch `filament`, depois da atualização para o Filament v5. Compara o que existe com o que foi decidido em `docs/` e com o padrão técnico (`laravel-tech-standard`, `laravel-filament-specialist`).

Este arquivo é um retrato. Quando as divergências forem resolvidas, ele deve ser removido, não mantido.

## Branches

- **`filament`** é a base do projeto (ADR-0007).
- **`main`** seguiu com Jetstream e Livewire depois de remover o Filament. Esse trabalho fica fora da base. A branch não foi apagada nem alterada.

## O que existe

- **Stack:** Laravel 13, PHP 8.4 no container, `stancl/tenancy` 3.8, Filament 5.9, Livewire 4.4, Sail (MySQL e Redis), Larastan, Pint, PHPUnit 12.
- **Central:** tabela `users` com coluna `role` (enum `UserRole`: super_admin, admin, manager, operator); painel `admin` em `localhost/admin` com o resource de tenants; comando `tenant:create`.
- **Tenant:** tabela `tenant_users` com a mesma coluna `role`; migrations em `database/migrations/tenant/`; painel `tenant` em `/admin`, com `InitializeTenancyBySubdomain` dentro do painel, ainda sem resources.
- **Testes:** dois testes de exemplo. Nada cobre tenancy nem os painéis.

## Divergências em relação à documentação

| Tema | Documentado | Código hoje |
|---|---|---|
| Painéis | Central + três de tenant: admin, usuário, cliente (ADR-0002) | Central + um único painel de tenant |
| Resolução | Middleware próprio: domínio → tenant + painel, só domínio ativo (RF08) | `InitializeTenancyBySubdomain`: só subdomínio do domínio central, sem noção de painel |
| Domínios | `panel`, `status`, `verified_at`, `verified_by` (RF02, RF03) | Só `domain` e `tenant_id`; um domínio por tenant, criado automaticamente igual ao nome |
| Tenant | `plan_id`, `status`, `status_locked_until` | Só `id` e `data` (JSON), com `tenant_name` dentro do JSON |
| Planos e funcionalidades | `plans`, `features`, `feature_plan`, `feature_settings` | Não existem |
| Cobrança | `subscriptions`, `webhook_events`, `tenant_status_logs` | Não existem |
| Pessoas do tenant | Tabela `users`, perfil em `roles` com `base_type`, permissões, `customer_user` (ADR-0003) | Tabela `tenant_users` com `role` fixo em enum; sem perfis, permissões ou clientes |
| Tipos de pessoa | Admin, Usuário, Cliente | SuperAdmin, Admin, Manager, Operator, nomes que o glossário marca como "evitar" |
| Provisionamento | Job assíncrono; admin com senha provisória por e-mail e troca obrigatória (RF10, RF11) | Pipeline síncrono (`shouldBeQueued(false)`); o painel não cria admin; o comando cria admin com senha padrão `password` exibida no terminal |
| Exclusão de tenant | Não previsto em nenhum RF | Ação de excluir na edição e exclusão em massa na listagem; o pipeline apaga o banco |

## Problemas que já afetam o funcionamento

1. **Login de tenant sem guard.** `config/auth.php` tem apenas o guard `web`, apontando para o model central `User`. O painel `tenant` não define guard, então a autenticação dentro do tenant não usa a tabela `tenant_users`.
2. **Exclusão de tenant sem proteção.** Apaga o banco inteiro da empresa contratante, inclusive em massa, sem registro e sem requisito que peça isso.
3. **Domínio derivado do nome.** `CreateTenant::afterCreate` grava como domínio o valor de `tenant_name`. Um nome com espaço ou acento vira um domínio inválido.

## Divergências em relação ao padrão técnico

| Regra do padrão | Código hoje |
|---|---|
| Fluxo em camadas (DTO → Action → Service → Repository) | Não há `Actions`, `DTOs`, `Services` nem `Repositories`. O resource grava direto no model e `CreateTenant::afterCreate` contém regra de provisionamento (marcado no código) |
| `declare(strict_types=1)` e classes `final` | Aplicado só em `app/Filament/Resources/Tenants/`. Falta no restante de `app/` |
| `pint.json` com `final_class` e `declare_strict_types` | Arquivo não existe |
| PHPStan nível 8 | `phpstan.neon` está no nível 7 (sem erros nesse nível) |
| Jobs disparados só por Listeners | Ainda não há eventos nem listeners de domínio |
| Exceções de domínio | Não há |
| Cobertura ≥ 80% em Services e Actions | Não há Services nem Actions |
| Filament v5, layout de diretórios do gerador v5 | Atendido para o resource de tenants |

## Ordem sugerida de adequação

Segue "Adopting in an existing project" do padrão técnico.

1. **Ferramental:** criar `pint.json`, subir o PHPStan para o nível 8, aplicar o Pint um diretório por vez rodando os testes a cada passo.
2. **Fatia 1** (`visao-geral.md`): cadastro de plano, tenant e domínio em camadas, substituindo a gravação direta e o `afterCreate`. Remove a exclusão de tenant até existir requisito.
3. **Fatia 2:** provisionamento assíncrono com admin e senha provisória, substituindo o comando e a senha padrão.
4. **Fatia 3:** middleware de resolução por registro de domínio, três painéis de tenant e guard próprio.
5. Demais fatias na ordem do plano.
