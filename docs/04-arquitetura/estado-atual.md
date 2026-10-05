# Estado atual do código × documentação

Retrato do repositório em 2026-10-04, na branch `main`, depois da fatia 2. Compara o que existe com o que foi decidido em `docs/` e com o padrão técnico (`laravel-tech-standard`, `laravel-filament-specialist`).

Quando as divergências forem resolvidas, este arquivo deve ser removido, não mantido.

## Branches

- **`main`** é a base do projeto, com Filament v5 (ADR-0007). A branch `filament` aponta para o mesmo commit.
- **`jetstream`** guarda o trabalho feito com Jetstream e Livewire depois da remoção do Filament. Fica fora da base.

## Fatias

| # | Fatia | Situação |
|---|---|---|
| — | Ferramental (`pint.json`, PHPStan nível 8, `strict_types`, `final`) | Concluído |
| 1 | Central cadastra plano, tenant e domínio (RF01, RF02, RF04, RF21, RF22) | Concluída |
| 2 | Provisionamento assíncrono e primeiro acesso do admin (RF10, RF11, RF23, RF24, RF25) | **Concluída** |
| 3 | Resolução por domínio, três painéis de tenant, verificação de domínio (RF03, RF08, RF09) | Pendente |
| 4 | Usuários e perfis (RF12, RF13) | Pendente |
| 5 | Funcionalidades: liga/desliga e troca de plano (RF05, RF14, RF15, RF20) | Pendente |
| 6 | Clientes e vínculo N:N (RF16, RF17) | Pendente |
| 7 | Situação manual e somente leitura (RF06, RF07, RF19) | Pendente |
| 8 | Cobrança automática (RF18) | Pendente |

## O que a fatia 2 entregou

- O cadastro do tenant só grava no banco central e responde na hora. O evento `TenantRegistered` aciona um Listener, que enfileira o `ProvisionTenantJob`.
- O provisionamento cria o banco, roda as migrations e cria o admin inicial com senha provisória, enviada por e-mail. Pode ser repetido sem efeito colateral.
- A listagem de tenants mostra a situação do ambiente e o motivo de uma falha, e oferece "Provisionar novamente" e "Reenviar senha provisória".
- No painel do tenant, quem entra com senha provisória só alcança a tela de troca. Senha provisória vencida encerra a sessão.
- Domínio de tenant ainda não pronto mostra uma página de espera (503). Domínio desconhecido responde 404.
- O comando de desenvolvimento `tenant:create` foi removido.

## Para rodar em desenvolvimento

- **A fila precisa de um worker.** O provisionamento só acontece com `php artisan queue:work` rodando (ou `composer dev`, que já sobe um). Sem worker, o tenant fica em "Aguardando".
- **O e-mail vai para o log.** Com `MAIL_MAILER=log`, a mensagem com a senha provisória é gravada em `storage/logs/laravel.log`. É de lá que se copia a senha em desenvolvimento. Em produção, um mailer real precisa estar configurado, e o log nunca deve ser usado como mailer.

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
| Perfis de sistema no provisionamento | Semeados junto com o admin (RF11) | Não existem; o admin inicial nasce com o papel fixo `admin` |
| Funcionalidades no tenant | `feature_settings` e tela de liga/desliga (RF14, RF15) | Não existem |

## Problemas conhecidos

1. **O comando `db:seed` está sequestrado pelo pacote de tenancy.** Nesta versão do Laravel, o comando `tenants:seed` do `stancl/tenancy` acaba registrado com o nome `db:seed`, substituindo o original. `php artisan db:seed` tenta semear tenants em vez do banco central. O provisionamento não depende mais de seed, mas o conflito continua.
2. **Tenants antigos estão incompletos.** Os tenants criados antes da fatia 1 não têm documento, plano nem contato, e foram marcados como prontos porque já tinham banco. Não têm admin criado pelo provisionamento. Ao editá-los, o formulário exige o preenchimento.
3. **Autenticação do tenant depende de troca de configuração em tempo de execução.** O `TenancyServiceProvider` troca o model do provider `users` para `TenantUser` quando a tenancy inicializa. Funciona, mas não há guard próprio por painel; isso é resolvido na fatia 3.
4. **"Esqueci minha senha" do tenant está habilitado, mas pouco exercitado.** A tela de recuperação do painel do tenant abre e é coberta por teste. O envio do e-mail de redefinição e a troca pelo link ainda não foram testados de ponta a ponta.
5. **`TenantUserSeeder` ficou sem uso.** Ele cria usuários com senha conhecida e não é mais chamado por nada. Deve ser removido ou restrito a ambiente de desenvolvimento.
6. **RNF03 ainda não foi medido em condição real.** O tempo de provisionamento é registrado no log (`tenant.provisioned`), mas só há medições de teste automatizado, na casa de 1 segundo, sem carga e sem fila concorrente.

## Divergências em relação ao padrão técnico

| Regra do padrão | Código hoje |
|---|---|
| Fluxo em camadas | Atendido em planos, tenants e provisionamento |
| `strict_types`, `final`, `pint.json`, PHPStan nível 8 | Atendido |
| Jobs disparados só por Listeners | Atendido: `DispatchTenantProvisioning` e `DispatchProvisionalPasswordResend` |
| Job em contexto de tenant inicializa a tenancy | Atendido de outra forma: os jobs rodam no contexto central e a troca de contexto acontece no serviço, por `TenantEnvironmentInterface::run`, só no trecho que toca o banco do tenant |
| Exceções de domínio | Atendido |
| Cobertura ≥ 80% em Services e Actions | Atendido: Actions em 100% e Services entre 90% e 100%; total do projeto em 94% (87 testes, `php artisan test --coverage`) |
| Transação no Repository | Atendido. A criação do tenant voltou a usar transação, já que o banco dele não é mais criado no cadastro |
| Integração externa assíncrona | Atendido: o e-mail da senha provisória é enviado de dentro do job. A notificação em si não é enfileirada de propósito, para a senha em texto não ficar gravada no payload de uma fila |
