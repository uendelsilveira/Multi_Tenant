# Runbook

Como implantar e operar a plataforma. Escrito para quem vai manter um ambiente no ar.

## O ambiente precisa de

| Componente | Observação |
|---|---|
| PHP 8.3 ou superior | Com as extensões usuais do Laravel e `redis` |
| MySQL 8 | O usuário da aplicação precisa poder criar bancos: cada tenant ganha um |
| Redis | Fila e cache. O cache precisa de um driver com suporte a tags |
| Worker de fila | `php artisan queue:work`, mantido por supervisor, ou Horizon |
| Agendador | `* * * * * php artisan schedule:run` no cron |
| Serviço de e-mail | `MAIL_MAILER` real. Nunca `log` em produção: o log guardaria senhas provisórias |
| Servidor web com certificado | Inclusive para os domínios próprios dos tenants |

## Variáveis de ambiente

Além das do Laravel:

| Variável | Para quê |
|---|---|
| `TENANT_DB_PREFIX`, `TENANT_DB_SUFFIX` | Nome dos bancos de tenant: prefixo + slug + sufixo |
| `BILLING_GRACE_DAYS` | Dias de carência antes da suspensão (padrão 10) |
| `BILLING_FIRST_DUE_IN_DAYS` | Prazo do primeiro vencimento (padrão 7) |
| `ASAAS_BASE_URL` | Em produção, `https://api.asaas.com/v3` |
| `ASAAS_API_KEY`, `ASAAS_WEBHOOK_TOKEN` | Credenciais do Asaas |
| `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET` | Credenciais do Stripe |

Os domínios do painel central ficam em `config/tenancy.php`, em `central_domains`. Um domínio central nunca pode ser cadastrado para um tenant.

## Implantação

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force            # banco central
php artisan tenants:migrate --force    # todos os bancos de tenant
php artisan features:sync              # catálogo de funcionalidades
php artisan optimize
php artisan queue:restart
```

`tenants:migrate` percorre todos os tenants; o tempo cresce com a quantidade deles. Se uma migration de tenant falhar no meio, o MySQL não desfaz o que já foi criado: a migration precisa poder rodar de novo.

Na primeira implantação, crie o primeiro usuário central. O seeder do projeto cria usuários com senha conhecida e é só para desenvolvimento.

## Webhooks dos gateways

| Gateway | URL | Autenticação |
|---|---|---|
| Asaas | `https://SEU-DOMINIO-CENTRAL/webhooks/asaas` | Token no cadastro do webhook, igual a `ASAAS_WEBHOOK_TOKEN` |
| Stripe | `https://SEU-DOMINIO-CENTRAL/webhooks/stripe` | Segredo de assinatura do endpoint em `STRIPE_WEBHOOK_SECRET` |

Eventos que têm efeito: no Asaas, `PAYMENT_CONFIRMED`, `PAYMENT_RECEIVED`, `PAYMENT_OVERDUE`, `SUBSCRIPTION_DELETED` e `SUBSCRIPTION_INACTIVATED`; no Stripe, `invoice.paid`, `invoice.overdue`, `invoice.payment_failed` e `customer.subscription.deleted`. Os demais são gravados e ignorados.

Antes de cobrar clientes reais, siga o roteiro de validação em sandbox de `docs/04-arquitetura/estado-atual.md`.

## Operações do dia a dia

Todas no painel central.

| Preciso de | Onde |
|---|---|
| Cadastrar um cliente da plataforma | Tenants → Novo. Depois, Domínios → Marcar como verificado |
| Saber por que um tenant não abre | Tenants: colunas Ambiente e Situação. Domínios: se está verificado |
| Suspender ou reativar | Tenants → Alterar situação. O motivo é obrigatório e interno |
| Dar um prazo a um inadimplente | Alterar situação → Ativo, com "Travar até" na data combinada |
| Ver o que aconteceu com a situação de um tenant | Tenants → Editar → Histórico de situação |
| Conferir se um pagamento chegou | Eventos de cobrança |
| Trocar o plano | Tenants → Editar. Vale na hora; o novo valor entra na próxima cobrança |

## Quando algo dá errado

**O tenant fica em "Aguardando" e não sai.**
A fila não está rodando. Suba o worker; o job pendente é processado.

**O ambiente aparece como "Falhou".**
Passe o mouse sobre o selo para ver o motivo. Corrigida a causa, use "Provisionar novamente": a preparação continua de onde parou e nada é duplicado.

**A cobrança aparece como "Falhou ao criar".**
O motivo está no selo. Os mais comuns são credencial do gateway ausente e documento recusado. Corrija e use "Criar assinatura novamente". O tenant funciona normalmente enquanto isso.

**O domínio do tenant responde "não encontrado".**
Em ordem: o domínio está cadastrado no tenant? Está verificado? O tenant foi excluído? O DNS aponta para a plataforma?

**O tenant vê "Conta suspensa".**
Veja o histórico de situação: a origem diz se foi o central ou a cobrança, e o motivo explica.

**O admin do tenant não recebeu a senha provisória, ou ela venceu.**
Tenants → Reenviar senha provisória. Só funciona enquanto ele não fez o primeiro acesso; depois disso a recuperação é pelo "esqueci minha senha" do próprio painel.

**Ninguém é suspenso por falta de pagamento.**
O agendador não está rodando, ou os webhooks de vencimento não estão chegando. Confira `php artisan schedule:list` e a tela de eventos de cobrança.

**Um webhook é recusado com 401.**
O token do Asaas ou o segredo do Stripe no `.env` não bate com o cadastrado no gateway.

## Backup

Há um banco central e um banco por tenant. O backup precisa cobrir todos; restaurar só o central deixa os tenants sem os dados deles, e restaurar só um tenant é possível sem tocar nos demais.

## Logs que valem acompanhar

Os eventos de negócio são gravados com chave fixa e contexto:

| Chave | Significa |
|---|---|
| `tenant.registered`, `tenant.provisioned` | Cadastro e ambiente pronto. O segundo traz a duração |
| `tenant.provisioning_failed` | O provisionamento desistiu depois das tentativas |
| `tenant.status_changed` | Mudança manual de situação |
| `billing.grace_enforced` | Suspensões por fim de carência |
| `webhook.rejected` | Requisição recusada na conferência de origem |
| `webhook.processed` | Evento aplicado, e para qual tenant a situação mudou |
| `subscription.started` | Assinatura criada no gateway |
