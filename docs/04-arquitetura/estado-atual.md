# Estado atual do código × documentação

Retrato do repositório em 2026-10-04, na branch `main`, depois da fatia 6. Compara o que existe com o que foi decidido em `docs/` e com o padrão técnico (`laravel-tech-standard`, `laravel-filament-specialist`).

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
| 3 | Resolução por domínio, três painéis de tenant, verificação de domínio (RF03, RF08, RF09) | Concluída |
| 4 | Pessoas e perfis (RF12, RF13, RF26) | Concluída |
| 5 | Funcionalidades: liga/desliga e troca de plano (RF05, RF14, RF15, RF20) | Concluída |
| 6 | Clientes e vínculo N:N (RF16, RF17) | **Concluída** |
| 7 | Situação manual e somente leitura (RF06, RF07, RF19) | Pendente |
| 8 | Cobrança automática (RF18) | Pendente |

## O que a fatia 6 entregou

- Tela **Clientes** no painel do usuário: ele cadastra, vê e edita só os clientes vinculados a ele.
- Tela **Clientes** no painel admin: todos os clientes, com a definição de quem atende cada um e a desativação. O admin não cadastra.
- Tabelas `customer_user` (vínculo N:N) e `customer_profiles` (telefone, documento, observações) em cada tenant.
- Todo cliente recebe acesso ao portal por senha provisória, com o link do domínio do painel de cliente.
- E-mail único entre todas as pessoas do tenant; todo cliente com pelo menos um usuário responsável.
- Duas permissões novas: gerenciar os próprios clientes (tipo usuário) e gerenciar todos os clientes (tipo admin).
- A gestão de pessoas deixou de listar e de alcançar clientes.

## Para rodar em desenvolvimento

- **A fila precisa de um worker.** O provisionamento só acontece com `php artisan queue:work` rodando (ou `composer dev`). Sem worker, o tenant fica em "Aguardando".
- **O e-mail vai para o log.** Com `MAIL_MAILER=log`, a mensagem com a senha provisória é gravada em `storage/logs/laravel.log`. Em produção, um mailer real precisa estar configurado.
- **Todo domínio novo precisa ser verificado.** Um tenant recém-cadastrado só abre depois que o domínio dele é marcado como verificado em Domínios, no painel central. O e-mail com a senha provisória sai antes disso; se o admin clicar no link com o domínio ainda pendente, verá "não encontrado".

## Divergências que continuam

| Tema | Documentado | Código hoje |
|---|---|---|
| Situação do tenant | Suspensão em somente leitura, trava manual, histórico (RF06, RF07, RF19) | Coluna `status` existe e nasce `active`; nada a altera nem a aplica |
| Cobrança | `subscriptions`, `webhook_events`, `tenant_status_logs` | Não existem |
| Conteúdo do portal do cliente | Telas do cliente | O cliente entra no portal e vê só o painel de controle padrão. O que ele faz ali depende dos módulos |
| Catálogo de permissões | Permissões dos módulos | Só as duas da plataforma; os módulos ainda não existem |
| Funcionalidades em uso | Módulos que respeitam liga/desliga (RF15) | O mecanismo existe e é testado com um módulo fictício. Nenhum módulo real existe ainda, então o catálogo em `config/features.php` está vazio e a tela de funcionalidades aparece sem itens |
| Certificado para domínio próprio | Emissão automática sob demanda | Não configurado. Em desenvolvimento tudo roda em HTTP |

## Problemas conhecidos

1. **O comando `db:seed` está sequestrado pelo pacote de tenancy.** Nesta versão do Laravel, o comando `tenants:seed` do `stancl/tenancy` acaba registrado com o nome `db:seed`. `php artisan db:seed` tenta semear tenants em vez do banco central.
2. **Tenants antigos estão incompletos.** Os criados antes da fatia 1 não têm documento, plano nem contato. Seus usuários foram convertidos para os perfis de sistema e continuam com a senha de desenvolvimento. Dois deles, criados no período do Jetstream, tinham uma tabela `users` daquela época; ela foi preservada como `legacy_users` e pode ser removida.
3. **Upload de arquivo em painel de tenant não foi tratado.** As rotas de upload e de pré-visualização de arquivo do Livewire não resolvem o tenant. Quando a primeira tela com upload existir em um painel de tenant, elas precisam do mesmo tratamento dado à rota de atualização.
4. **"Esqueci minha senha" do tenant está habilitado, mas pouco exercitado.** A tela abre e é coberta por teste. O envio do e-mail de redefinição e a troca pelo link não foram testados de ponta a ponta.
5. **Não há como desfazer uma verificação.** Um domínio verificado por engano só deixa de responder se for removido do tenant.
. **Desativar uma pessoa não derruba a sessão na hora por conta própria.** A próxima requisição dela recebe "acesso negado", mas a sessão continua existindo até expirar.
. **Permissões não estão ligadas a funcionalidades.** Uma permissão de um módulo continua aparecendo na tela de perfis mesmo com a funcionalidade dele desligada. Quando o primeiro módulo entrar, vale decidir se a permissão declara a funcionalidade de que depende.
. **Tenant sem domínio para o painel de cliente.** O cliente é cadastrado e recebe o e-mail de acesso mesmo que o tenant não tenha nenhum domínio apontando para o painel de cliente. Nesse caso o link do e-mail leva a um painel em que ele não entra. Falta decidir se o cadastro deve ser recusado ou avisado nessa situação.
12. **Usuário desativado continua responsável pelos clientes dele.** Desativar um usuário não redistribui a carteira: os clientes ficam vinculados a alguém que não entra mais, até o admin trocar os responsáveis. Ele deixa de poder ser escolhido como responsável novo.
13. **Cliente usa sempre o perfil de sistema Cliente.** Perfis customizados de tipo cliente podem ser criados, mas não há tela para atribuí-los a um cliente.

## Divergências em relação ao padrão técnico

| Regra do padrão | Código hoje |
|---|---|
| Fluxo em camadas | Atendido em planos, tenants, provisionamento, domínios, pessoas, perfis, funcionalidades e clientes |
| `strict_types`, `final`, `pint.json`, PHPStan nível 8 | Atendido |
| Jobs disparados só por Listeners | Atendido |
| Job em contexto de tenant inicializa a tenancy | Atendido de duas formas: o provisionamento roda no contexto central e troca de contexto no serviço; a emissão de senha provisória é despachada de dentro do tenant e o pacote de tenancy a executa no mesmo tenant, o que o job confere antes de agir |
| Resolução de tenant por middleware | Atendido, com middleware próprio no lugar do middleware do pacote (ADR-0008) |
| Cache com tag do tenant | Não se aplica: o único cache é o de resolução de domínio, que é dado do central. As funcionalidades não usam cache, de propósito |
| Observer só para efeito técnico | Atendido: `DomainObserver` e `TenantObserver` apenas limpam cache |
| Integração externa assíncrona | Duas exceções deliberadas, comentadas no código: a notificação da senha provisória é enviada de dentro de um job, sem ser enfileirada; e o teste de DNS é síncrono, por ser uma conferência interativa |
| Exceções de domínio | Atendido |
| Cobertura ≥ 80% em Services e Actions | Atendido: Actions em 100% e Services de regra entre 96% e 100%; total do projeto em 94,5% (178 testes). O adaptador de DNS do sistema (`SystemDnsLookup`) não tem teste, porque consulta DNS de verdade; nos testes ele é substituído por um falso |
