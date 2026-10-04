# Estado atual do código × documentação

Retrato do repositório em 2026-10-04, na branch `main`, depois da fatia 1. Compara o que existe com o que foi decidido em `docs/` e com o padrão técnico (`laravel-tech-standard`, `laravel-filament-specialist`).

Quando as divergências forem resolvidas, este arquivo deve ser removido, não mantido.

## Branches

- **`main`** é a base do projeto, com Filament v5 (ADR-0007). A branch `filament` aponta para o mesmo commit.
- **`jetstream`** guarda o trabalho feito com Jetstream e Livewire depois da remoção do Filament. Fica fora da base.

## Fatias

| # | Fatia | Situação |
|---|---|---|
| — | Ferramental (`pint.json`, PHPStan nível 8, `strict_types`, `final`) | Concluído |
| 1 | Central cadastra plano, tenant e domínio (RF01, RF02, RF04, RF21, RF22) | **Concluída** |
| 2 | Provisionamento assíncrono e primeiro acesso do admin (RF10, RF11) | Pendente |
| 3 | Resolução por domínio, três painéis de tenant, verificação de domínio (RF03, RF08, RF09) | Pendente |
| 4 | Usuários e perfis (RF12, RF13) | Pendente |
| 5 | Funcionalidades: liga/desliga e troca de plano (RF05, RF14, RF15, RF20) | Pendente |
| 6 | Clientes e vínculo N:N (RF16, RF17) | Pendente |
| 7 | Situação manual e somente leitura (RF06, RF07, RF19) | Pendente |
| 8 | Cobrança automática (RF18) | Pendente |

## O que a fatia 1 entregou

- Cadastro de planos com preço por ciclo e funcionalidades, e catálogo de funcionalidades em `config/features.php` com o comando `features:sync`.
- Cadastro de tenant com slug, dados cadastrais, plano, ciclo e domínios, em camadas: página Filament → DTO → Action → Service → Repository.
- Exclusão lógica e restauração de tenant. O job que apagava o banco foi retirado do pipeline.
- Policies para plano e tenant no painel central.
- Resolução do painel de tenant por domínio completo, no lugar de subdomínio.
- 63 testes: unitários de Service e Action, integração de Repository, feature das telas e um teste em MySQL real que prova que a exclusão mantém o banco.

## Divergências que continuam

| Tema | Documentado | Código hoje |
|---|---|---|
| Painéis | Central + três de tenant (ADR-0002) | Central + um único painel de tenant |
| Resolução | Domínio → tenant + painel, só domínio verificado (RF08) | Domínio → tenant. `panel` e `status` são gravados, mas ainda não são conferidos na requisição |
| Verificação de domínio | Central marca como verificado (RF03) | Todo domínio nasce pendente e não há tela para verificar |
| Situação do tenant | Suspensão em somente leitura, trava manual, histórico (RF06, RF07, RF19) | Coluna `status` existe e nasce `active`; nada a altera nem a aplica |
| Cobrança | `subscriptions`, `webhook_events`, `tenant_status_logs` | Não existem |
| Pessoas do tenant | Tabela `users`, perfis com `base_type`, permissões, `customer_user` (ADR-0003) | Tabela `tenant_users` com `role` fixo em enum |
| Tipos de pessoa | Admin, Usuário, Cliente | SuperAdmin, Admin, Manager, Operator, nomes que o glossário marca como "evitar" |
| Provisionamento | Job assíncrono; admin com senha provisória e troca obrigatória (RF10, RF11) | Pipeline síncrono cria e migra o banco; nenhum admin é criado pelo painel |
| Funcionalidades no tenant | `feature_settings` e tela de liga/desliga (RF14, RF15) | Não existem |

## Problemas conhecidos

1. **O comando `db:seed` está sequestrado pelo pacote de tenancy.** Nesta versão do Laravel, o comando `tenants:seed` do `stancl/tenancy` acaba registrado com o nome `db:seed`, substituindo o original. Consequências: `tenants:seed` não existe, e `php artisan db:seed` tenta semear tenants em vez do banco central. O job de seed foi retirado do pipeline de criação de tenant, porque derrubava o cadastro depois de o banco já ter sido criado. O conflito do comando em si continua e precisa de correção própria.
2. **Tenant recém-criado não tem admin.** Até a fatia 2, o único caminho para criar o usuário admin é o comando de desenvolvimento `tenant:create`, que não passa pelas regras de cadastro.
3. **Tenants antigos estão incompletos.** Os tenants criados antes desta fatia não têm documento, plano, ciclo nem os demais dados. Receberam apenas a razão social, copiada do nome antigo. Ao editá-los, o formulário exige o preenchimento.
4. **Autenticação do tenant depende de troca de configuração em tempo de execução.** O `TenancyServiceProvider` troca o model do provider `users` para `TenantUser` quando a tenancy inicializa. Funciona, mas não há guard próprio por painel; isso é resolvido na fatia 3.

## Divergências em relação ao padrão técnico

| Regra do padrão | Código hoje |
|---|---|
| Fluxo em camadas | Atendido em planos e tenants. O comando `tenant:create` ainda grava direto no model, marcado como atalho de desenvolvimento |
| `strict_types`, `final`, `pint.json`, PHPStan nível 8 | Atendido |
| Jobs disparados só por Listeners | Não há jobs de domínio ainda. `TenantRegistered` já é emitido e será o gatilho do provisionamento |
| Exceções de domínio | Atendido: `App\Exceptions\Plan` e `App\Exceptions\Tenant` |
| Cobertura ≥ 80% em Services e Actions | Atendido: Actions em 100% e Services entre 96% e 100% (medido com `php artisan test --coverage`) |
| Transação no Repository | Atendido, com uma exceção documentada no código: a criação do tenant não usa transação, porque criar o banco dele encerra qualquer transação aberta no MySQL |
