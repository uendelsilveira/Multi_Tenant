# Estado atual do código × documentação

Leitura do repositório em 2026-10-04, no commit `6df1941` (branch `main`). Compara o que existe com o que foi decidido em `docs/` e com o padrão técnico (`laravel-tech-standard`, `laravel-filament-specialist`).

Este arquivo é um retrato. Quando as divergências forem resolvidas, ele deve ser removido, não mantido.

## O que existe

- **Stack:** Laravel 13, PHP 8.3, `stancl/tenancy` 3.8, Jetstream 5 com Livewire 3, Sanctum, Sail (MySQL e Redis), Larastan, Pint, PHPUnit 12.
- **Central:** tabela `users` com coluna `role` (enum `UserRole`: super_admin, admin, manager, operator); CRUD de tenants em Livewire (`TenantIndex`, `CreateTenant`, `EditTenant`); comando `tenant:create`.
- **Tenant:** tabela `tenant_users` com a mesma coluna `role`; migrations em `database/migrations/tenant/`; `routes/tenant.php` com uma única rota que redireciona para o login.
- **Painéis:** nenhum. O Filament foi removido no commit `7e05b9f`.
- **Testes:** apenas os que vêm com o Jetstream. Nada cobre tenancy.

## Divergências em relação à documentação

| Tema | Documentado | Código hoje |
|---|---|---|
| Painéis | Quatro painéis em Filament (ADR-0002) | Sem Filament; telas em Livewire + Blade do Jetstream |
| Resolução | Middleware próprio: domínio → tenant + painel, só domínio ativo (RF08) | Dois mecanismos ao mesmo tempo: grupo `universal` com `InitializeTenancyBySubdomain` em `web.php` e `InitializeTenancyByDomain` em `tenant.php` |
| Domínios | `panel`, `status`, `verified_at`, `verified_by` (RF02, RF03) | Só `domain` e `tenant_id` |
| Tenant | `plan_id`, `status`, `status_locked_until` | Só `id` e `data` (JSON), com `tenant_name` dentro do JSON |
| Planos e funcionalidades | `plans`, `features`, `feature_plan`, `feature_settings` | Não existem |
| Cobrança | `subscriptions`, `webhook_events`, `tenant_status_logs` | Não existem |
| Pessoas do tenant | Tabela `users`, perfil em `roles` com `base_type`, permissões, `customer_user` (ADR-0003) | Tabela `tenant_users` com `role` fixo em enum; sem perfis, permissões ou clientes |
| Tipos de pessoa | Admin, Usuário, Cliente | SuperAdmin, Admin, Manager, Operator, nomes que o glossário marca como "evitar" |
| Provisionamento | Job assíncrono; admin com senha provisória por e-mail e troca obrigatória (RF10, RF11) | Pipeline síncrono (`shouldBeQueued(false)`); a tela de criação não cria admin; o comando cria admin com senha padrão `password` exibida no terminal |
| Exclusão de tenant | Não previsto em nenhum RF | `TenantIndex::deleteTenant` exclui o tenant, e o pipeline apaga o banco |

## Problemas que já afetam o funcionamento

1. **Formato de domínio inconsistente.** O comando `tenant:create` grava só o subdomínio (`foo`), que é o formato esperado por `InitializeTenancyBySubdomain`. A tela `CreateTenant` grava o host completo (`foo.localhost`), formato esperado por `InitializeTenancyByDomain`. Como os dois middlewares estão em uso em arquivos de rota diferentes, um tenant criado por um caminho não resolve nas rotas do outro.
2. **Login de tenant sem guard.** `config/auth.php` tem apenas o guard `web`, apontando para o model central `User`. Não há guard nem provider para `TenantUser`, então a autenticação dentro do tenant não usa a tabela `tenant_users`.
3. **Exclusão de tenant sem proteção.** Um clique apaga o banco inteiro da empresa contratante, sem confirmação forte, sem registro e sem requisito que peça isso.

## Divergências em relação ao padrão técnico

| Regra do padrão | Código hoje |
|---|---|
| Fluxo em camadas (DTO → Action → Service → Repository) | Não há `Actions`, `DTOs`, `Services` nem `Repositories` de domínio. Os componentes Livewire e o comando chamam `Tenant::create`, `->update` e `->delete` direto |
| `declare(strict_types=1)` em todo arquivo | 21 arquivos em `app/` sem a declaração |
| `pint.json` com `final_class` e `declare_strict_types` | Arquivo não existe |
| PHPStan nível 8 | `phpstan.neon` está no nível 7 |
| Jobs disparados só por Listeners | Ainda não há eventos nem listeners de domínio |
| Exceções de domínio | Não há |
| Cobertura ≥ 80% em Services e Actions | Não há Services nem Actions |
| Filament v5 e Livewire v4 | Sem Filament; Livewire em `^3.6.4` |

## Decisão pendente: Filament

A documentação inteira assume painéis em Filament, mas o repositório removeu o Filament de propósito e passou a construir as telas em Livewire com Jetstream. Antes de qualquer código novo é preciso escolher:

- **Voltar ao Filament.** Alinha com a documentação e com o padrão. Exige reinstalar o Filament, migrar o Livewire para a versão que ele requer e descartar as telas Livewire de tenant já feitas.
- **Seguir em Livewire + Jetstream.** Aproveita o que existe. Exige reescrever a ADR-0002 e a parte de painéis da arquitetura, e construir à mão o que o Filament entrega pronto (tabelas, formulários, navegação por painel).

Qualquer que seja a escolha, ela vira ADR, porque é difícil de reverter.

## Ordem sugerida de adequação

Segue "Adopting in an existing project" do padrão técnico.

1. **Ferramental:** criar `pint.json`, subir o PHPStan para o nível 8, aplicar o Pint um diretório por vez rodando os testes a cada passo.
2. **Decidir Filament** e registrar a ADR.
3. **Corrigir a resolução de domínio:** um único middleware, um único formato de domínio gravado.
4. **Fatia 1 do plano de construção** (`visao-geral.md`): cadastro de plano, tenant e domínio, já em camadas, substituindo as chamadas diretas ao model.
5. **Fatia 2:** provisionamento assíncrono com admin e senha provisória, substituindo o comando e a senha padrão.
6. Demais fatias na ordem do plano.
