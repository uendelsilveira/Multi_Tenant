# Estado atual do código × documentação

Retrato do repositório em 2026-10-04, na branch `main`, depois da fatia 3. Compara o que existe com o que foi decidido em `docs/` e com o padrão técnico (`laravel-tech-standard`, `laravel-filament-specialist`).

Quando as divergências forem resolvidas, este arquivo deve ser removido, não mantido.

## Branches

- **`main`** é a base do projeto, com Filament v5 (ADR-0007). A branch `filament` aponta para o mesmo commit.
- **`jetstream`** guarda o trabalho feito com Jetstream e Livewire depois da remoção do Filament. Fica fora da base.

## Fatias

| # | Fatia | Situação |
|---|---|---|
| — | Ferramental (`pint.json`, PHPStan nível 8, `strict_types`, `final`) | Concluído |
| 1 | Central cadastra plano, tenant e domínio (RF01, RF02, RF04, RF21, RF22) | Concluída |
| 2 | Provisionamento assíncrono e primeiro acesso do admin (RF10, RF11, RF23, RF24, RF25) | Concluída |
| 3 | Resolução por domínio, três painéis de tenant, verificação de domínio (RF03, RF08, RF09) | **Concluída** |
| 4 | Usuários e perfis (RF12, RF13) | Pendente |
| 5 | Funcionalidades: liga/desliga e troca de plano (RF05, RF14, RF15, RF20) | Pendente |
| 6 | Clientes e vínculo N:N (RF16, RF17) | Pendente |
| 7 | Situação manual e somente leitura (RF06, RF07, RF19) | Pendente |
| 8 | Cobrança automática (RF18) | Pendente |

## O que a fatia 3 entregou

- Resolução própria por domínio: identifica tenant e painel, só para domínio verificado de tenant não excluído. O resto responde 404.
- Três painéis de tenant (admin em `/admin`, usuário em `/app`, cliente em `/portal`), cada um acessível apenas pelo domínio que aponta para ele.
- Guard `tenant` próprio, no lugar da troca de model em tempo de execução. Cada pessoa só entra no painel do seu tipo base; usuário central só entra no painel central.
- Tipos base no tenant (admin, usuário, cliente) no lugar dos quatro papéis antigos.
- Listagem de domínios no painel central, com "Testar DNS" e "Marcar como verificado".
- Rota de atualização do Livewire ciente do tenant. Sem isso, nenhuma interação de tela funcionaria em domínio de tenant.
- Cache de resolução de domínio, limpo na hora quando um domínio muda ou um tenant é excluído.

## Para rodar em desenvolvimento

- **A fila precisa de um worker.** O provisionamento só acontece com `php artisan queue:work` rodando (ou `composer dev`). Sem worker, o tenant fica em "Aguardando".
- **O e-mail vai para o log.** Com `MAIL_MAILER=log`, a mensagem com a senha provisória é gravada em `storage/logs/laravel.log`. Em produção, um mailer real precisa estar configurado.
- **Todo domínio novo precisa ser verificado.** Um tenant recém-cadastrado só abre depois que o domínio dele é marcado como verificado em Domínios, no painel central. O e-mail com a senha provisória sai antes disso; se o admin clicar no link com o domínio ainda pendente, verá "não encontrado".

## Divergências que continuam

| Tema | Documentado | Código hoje |
|---|---|---|
| Situação do tenant | Suspensão em somente leitura, trava manual, histórico (RF06, RF07, RF19) | Coluna `status` existe e nasce `active`; nada a altera nem a aplica |
| Cobrança | `subscriptions`, `webhook_events`, `tenant_status_logs` | Não existem |
| Pessoas do tenant | Tabela `users`, perfis com `base_type`, permissões, `customer_user` (ADR-0003) | Tabela `tenant_users` com o tipo base direto na coluna `type`; sem perfis nem permissões |
| Cadastro de pessoas | Admin gerencia usuários e clientes (RF12, RF16) | Não há tela. Só existe o admin inicial; os painéis de usuário e de cliente abrem, mas ninguém tem como ser cadastrado neles pela aplicação |
| Conteúdo dos painéis de tenant | Telas de cada tipo de pessoa | Só o painel de controle padrão e a troca de senha provisória |
| Perfis de sistema no provisionamento | Semeados junto com o admin (RF11) | Não existem |
| Funcionalidades no tenant | `feature_settings` e tela de liga/desliga (RF14, RF15) | Não existem |
| Certificado para domínio próprio | Emissão automática sob demanda | Não configurado. Em desenvolvimento tudo roda em HTTP |

## Problemas conhecidos

1. **O comando `db:seed` está sequestrado pelo pacote de tenancy.** Nesta versão do Laravel, o comando `tenants:seed` do `stancl/tenancy` acaba registrado com o nome `db:seed`. `php artisan db:seed` tenta semear tenants em vez do banco central.
2. **Tenants antigos estão incompletos.** Os criados antes da fatia 1 não têm documento, plano nem contato. Os usuários que eles já tinham foram convertidos: `super_admin` e `admin` viraram tipo admin; `manager` e `operator`, tipo usuário. Continuam com a senha de desenvolvimento.
3. **Upload de arquivo em painel de tenant não foi tratado.** As rotas de upload e de pré-visualização de arquivo do Livewire não resolvem o tenant. Quando a primeira tela com upload existir em um painel de tenant, elas precisam do mesmo tratamento dado à rota de atualização.
4. **"Esqueci minha senha" do tenant está habilitado, mas pouco exercitado.** A tela abre e é coberta por teste. O envio do e-mail de redefinição e a troca pelo link não foram testados de ponta a ponta.
5. **Não há como desfazer uma verificação.** Um domínio verificado por engano só deixa de responder se for removido do tenant.
6. **RNF02 e RNF03 não foram medidos em condição real.** A resolução de domínio usa cache e o provisionamento registra a duração no log, mas só há números de teste automatizado, sem carga.

## Divergências em relação ao padrão técnico

| Regra do padrão | Código hoje |
|---|---|
| Fluxo em camadas | Atendido em planos, tenants, provisionamento e domínios |
| `strict_types`, `final`, `pint.json`, PHPStan nível 8 | Atendido |
| Jobs disparados só por Listeners | Atendido |
| Job em contexto de tenant inicializa a tenancy | Atendido de outra forma: os jobs rodam no contexto central e a troca de contexto acontece no serviço, por `TenantEnvironmentInterface::run` |
| Resolução de tenant por middleware | Atendido, com middleware próprio no lugar do middleware do pacote (ADR-0008) |
| Cache com tag do tenant | Não se aplica ao único cache existente: a resolução de domínio é dado do central e acontece antes de haver tenant |
| Observer só para efeito técnico | Atendido: `DomainObserver` e `TenantObserver` apenas limpam cache |
| Integração externa assíncrona | Duas exceções deliberadas, comentadas no código: a notificação da senha provisória é enviada de dentro de um job, sem ser enfileirada; e o teste de DNS é síncrono, por ser uma conferência interativa |
| Exceções de domínio | Atendido |
| Cobertura ≥ 80% em Services e Actions | Atendido: Actions em 100% e Services de regra entre 96% e 100%; total do projeto em 94% (113 testes). O adaptador de DNS do sistema (`SystemDnsLookup`) não tem teste, porque consulta DNS de verdade; nos testes ele é substituído por um falso |
