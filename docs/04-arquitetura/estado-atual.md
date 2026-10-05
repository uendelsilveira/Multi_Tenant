# Estado atual do código × documentação

Retrato do repositório na versão `v1.0.0-beta.1` (2026-10-05), com as oito fatias do plano concluídas. Compara o que existe com o que foi decidido em `docs/` e com o padrão técnico (`laravel-tech-standard`, `laravel-filament-specialist`).

Este arquivo deve encolher à medida que as pendências forem resolvidas, e ser removido quando não restar nenhuma.

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
| 6 | Clientes e vínculo N:N (RF16, RF17) | Concluída |
| 7 | Situação manual, bloqueio na suspensão e histórico (RF06, RF07, RF19) | Concluída |
| 8 | Cobrança automática (RF18, RF27, RF28, RF29, RF30) | **Concluída, sem teste contra os gateways reais** |

## O que a fatia 8 entregou

- Escolha do gateway (Asaas ou Stripe) no cadastro do tenant, e criação do pagador e da assinatura nele, em fila.
- Recebimento de webhooks dos dois gateways em `/webhooks/asaas` e `/webhooks/stripe`, com conferência de origem própria de cada um, gravação antes do processamento e garantia de efeito único.
- Carência de 10 dias: um comando agendado suspende quem passou do prazo. Pagamento confirmado reativa. Cancelamento suspende na hora.
- Respeito à trava manual do central em todos esses caminhos.
- Ajuste das próximas cobranças quando o plano ou o ciclo do tenant muda.
- No central: situação da cobrança na listagem de tenants, nova tentativa de criação da assinatura e listagem dos eventos recebidos.

## A cobrança não foi testada contra os gateways reais

Tudo o que fala com o Asaas e com o Stripe foi escrito a partir da documentação pública deles e testado contra respostas simuladas. **Nenhuma chamada foi feita a um gateway de verdade**, porque não há credenciais neste ambiente. Antes de usar com dinheiro real é preciso, em sandbox de cada gateway:

1. Cadastrar um tenant e conferir que o pagador e a assinatura aparecem no painel do gateway, com valor, ciclo e primeiro vencimento certos.
2. Cadastrar a URL do webhook e conferir que um evento real é aceito, gravado e aplicado.
3. Conferir os nomes dos eventos e o formato do corpo. Os pontos de maior risco de divergência:
   - No Asaas, a alteração de assinatura foi implementada como `PUT /subscriptions/{id}`; a documentação já descreveu essa operação também como `POST`.
   - No Stripe, a assinatura é criada com envio de fatura por e-mail (`collection_method=send_invoice`), sem cartão guardado. O evento de vencimento usado é `invoice.overdue`, cujo momento de disparo depende de configuração na conta.
   - No Stripe, a assinatura de uma fatura é localizada pelo pagador, porque o campo que liga a fatura à assinatura mudou de lugar entre versões da API.

## Para rodar

- **Variáveis de ambiente da cobrança:** `ASAAS_API_KEY`, `ASAAS_WEBHOOK_TOKEN`, `ASAAS_BASE_URL` (o padrão é o sandbox), `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET`. Opcionais: `BILLING_GRACE_DAYS` (10), `BILLING_FIRST_DUE_IN_DAYS` (7), `ASAAS_BILLING_TYPE` (`UNDEFINED`, em que o pagador escolhe a forma). Sem as credenciais de um gateway, a assinatura dos tenants dele fica como "Falhou ao criar", com o motivo, e pode ser tentada de novo depois.
- **O agendador precisa rodar.** A carência é aplicada pelo comando `billing:enforce-grace`, agendado de hora em hora. Sem `php artisan schedule:run` no cron (ou `schedule:work` em desenvolvimento), ninguém é suspenso por falta de pagamento.
- **A fila precisa de um worker.** Provisionamento, senhas provisórias, criação de assinatura e processamento de webhooks rodam em fila.
- **O e-mail vai para o log** em desenvolvimento (`MAIL_MAILER=log`). Em produção, um mailer real precisa estar configurado.
- **Todo domínio novo precisa ser verificado** no painel central antes de responder.
- **O webhook precisa ser alcançável pelo gateway.** Em desenvolvimento isso exige um túnel para a máquina local.

## Divergências que continuam

| Tema | Documentado | Código hoje |
|---|---|---|
| Conteúdo do portal do cliente | Telas do cliente | O cliente entra no portal e vê só o painel de controle padrão. O que ele faz ali depende dos módulos |
| Funcionalidades em uso | Módulos que respeitam liga/desliga (RF15) | O mecanismo existe e é testado com um módulo fictício. Nenhum módulo real existe ainda, então o catálogo em `config/features.php` está vazio |
| Catálogo de permissões | Permissões dos módulos | Só as da plataforma; os módulos ainda não existem |
| Certificado para domínio próprio | Emissão automática sob demanda | Não configurado. Em desenvolvimento tudo roda em HTTP |
| Perfis de sistema no provisionamento | Semeados junto com o admin (RF11) | São criados pela migration do tenant, não pelo provisionamento; o efeito é o mesmo |

## Problemas conhecidos

### Cobrança

1. **Excluir um tenant não cancela a assinatura no gateway.** A exclusão é lógica e reversível, e o gateway continua cobrando. O cancelamento precisa ser feito no painel do gateway.
2. **Alterar o preço de um plano não atualiza as assinaturas já criadas.** Só a troca de plano ou de ciclo de um tenant ajusta a cobrança dele. Um reajuste de preço do plano não chega aos tenants que já estão nele.
3. **O gateway de um tenant não pode ser trocado** (RN49). Migrar um tenant de gateway exige intervenção manual.
4. **Só três tipos de evento têm efeito.** Estorno, chargeback e reativação de assinatura no gateway são gravados e ignorados.
5. **O primeiro vencimento é fixo em relação ao cadastro** (7 dias por padrão). Não há período de teste nem data de vencimento escolhida por tenant.
6. **Não há aviso ao tenant** sobre vencimento, carência, suspensão ou reativação por parte da plataforma. Os avisos de cobrança são os do próprio gateway.
7. **Tenants cadastrados antes da cobrança não têm assinatura** e não são afetados pela carência. Não afeta instalações novas.

### Plataforma

8. **Ambientes criados antes da versão 1.0 podem ter tenants incompletos.** Tenants cadastrados antes da fatia 1 não têm documento, plano nem contato, e alguns têm uma tabela `legacy_users`, da época do Jetstream, que pode ser removida. Não afeta instalações novas.
9. **Upload de arquivo em painel de tenant não foi tratado.** As rotas de upload e de pré-visualização do Livewire não resolvem o tenant.
10. **"Esqueci minha senha" do tenant está habilitado, mas pouco exercitado.** A tela abre e é coberta por teste; o envio do e-mail e a troca pelo link não foram testados de ponta a ponta.
11. **Não há como desfazer a verificação de um domínio.**
12. **RNF02 e RNF03 não foram medidos em condição real.** Só há números de teste automatizado, sem carga.
13. **Desativar uma pessoa não derruba a sessão dela**; a próxima requisição recebe "acesso negado".
14. **O Redis deste projeto disputa a porta 6379** com outros projetos na mesma máquina.
15. **Permissões não estão ligadas a funcionalidades.**
16. **Um trait sem uso no código de produção** (`RequiresFeature`), com exceção em `phpstan.neon` até um módulo usá-lo.
17. **Tenant sem domínio para o painel de cliente** ainda permite cadastrar cliente, que recebe um link que não serve para ele.
18. **Usuário desativado continua responsável pelos clientes dele** até o admin trocar os responsáveis.
19. **Cliente usa sempre o perfil de sistema Cliente**; não há tela para atribuir um perfil customizado de tipo cliente.
20. **Não há aviso ao tenant quando ele é suspenso ou reativado manualmente.**

## Divergências em relação ao padrão técnico

| Regra do padrão | Código hoje |
|---|---|
| Fluxo em camadas | Atendido em todas as fatias. O webhook entra por Controller → DTO → Action → Service → Repository |
| `strict_types`, `final`, `pint.json`, PHPStan nível 8 | Atendido |
| Jobs disparados só por Listeners | Atendido |
| Integração externa assíncrona | Atendido para os gateways: criação e alteração de assinatura rodam em job. Exceções deliberadas e comentadas no código: a notificação de senha provisória é enviada de dentro de um job sem ser enfileirada, e o teste de DNS é síncrono |
| Exceção de infraestrutura traduzida na borda | Atendido nos gateways: toda falha vira `BillingException`, com a mensagem legível do gateway |
| Job em contexto de tenant inicializa a tenancy | Atendido: os jobs de cobrança e de provisionamento rodam no contexto central; o de senha provisória é executado pelo pacote no tenant de origem e confere isso |
| Resolução de tenant por middleware | Atendido, com middleware próprio (ADR-0008) |
| Cache com tag do tenant | Não se aplica: o único cache é o de resolução de domínio, dado do central |
| Observer só para efeito técnico | Atendido |
| Exceções de domínio | Atendido |
| FormRequest na entrada HTTP | Não se aplica ao webhook: o corpo é conferido pela assinatura do gateway e interpretado pelo tradutor de cada um, não por regras de validação de formulário |
| Cobertura ≥ 80% em Services e Actions | Atendido: Actions em 100% e Services entre 88% e 100%; total do projeto em 94,6% (221 testes). O adaptador de DNS do sistema (`SystemDnsLookup`) não tem teste, porque consulta DNS de verdade. Os gateways são testados contra respostas simuladas |
