# Plataforma Multi-Tenant

Base genérica para produtos por assinatura que atendem várias empresas contratantes, cada uma com dados isolados, endereços próprios, painéis por tipo de pessoa e funcionalidades liberadas por plano.

## Language

### Plataforma

**Central**:
O ambiente da operadora da plataforma, onde se administram tenants, domínios, planos e assinaturas.
_Avoid_: Admin geral, backoffice, landlord

**Usuário Central**:
Pessoa que opera o Central.
_Avoid_: Super admin, admin (reservado ao tenant)

**Tenant**:
Empresa contratante da plataforma, com banco de dados próprio.
_Avoid_: Cliente, conta, empresa, inquilino

**Slug**:
Identificador permanente do Tenant, digitado pelo Central no cadastro, que também dá nome ao banco dele.
_Avoid_: Código, ID, subdomínio

**Domínio**:
Endereço cadastrado no Central que identifica um Tenant e um Painel.
_Avoid_: URL, host, subdomínio (subdomínio é só um tipo de Domínio)

**Painel**:
Ambiente de uso destinado a um tipo de pessoa: central, admin, usuário ou cliente.
_Avoid_: Área, portal, dashboard

### Pessoas do tenant

**Admin**:
Pessoa do Tenant que administra usuários, perfis e funcionalidades.
_Avoid_: Administrador geral, gestor, dono

**Usuário**:
Pessoa do Tenant que opera o produto e atende Clientes.
_Avoid_: Operador, atendente, funcionário

**Cliente**:
Pessoa atendida por um ou mais Usuários do Tenant, com painel próprio.
_Avoid_: Cliente final, consumidor, contato

### Acesso

**Tipo Base**:
Uma das três categorias fixas de perfil (admin, usuário, cliente), que determina o Painel.
_Avoid_: Tipo de usuário, papel, nível

**Perfil**:
Conjunto nomeado de permissões atribuído a uma pessoa do Tenant, sempre ligado a um Tipo Base.
_Avoid_: Papel, role, grupo

**Perfil de Sistema**:
Perfil padrão que existe em todo Tenant, um por Tipo Base, e não pode ser alterado.
_Avoid_: Perfil padrão, perfil fixo

**Perfil Customizado**:
Perfil criado pelo Admin a partir de um Tipo Base.
_Avoid_: Perfil personalizado, perfil do tenant

### Comercial

**Plano**:
Pacote de assinatura que define quais Funcionalidades um Tenant pode usar.
_Avoid_: Pacote, tier, licença

**Ciclo**:
Periodicidade de cobrança de um Plano: mensal, semestral ou anual, cada uma com seu preço.
_Avoid_: Periodicidade, recorrência, vigência

**Funcionalidade**:
Recurso do produto que pode ser liberado por Plano e ligado ou desligado pelo Admin.
_Avoid_: Feature, módulo, recurso

**Assinatura**:
Vínculo de cobrança recorrente entre um Tenant e um Plano, em um gateway.
_Avoid_: Contrato, mensalidade

**Situação**:
Estado do Tenant perante a plataforma: ativo ou suspenso.
_Avoid_: Status de pagamento, bloqueio

**Suspensão**:
Situação em que o Tenant acessa e consulta, mas não altera nada.
_Avoid_: Bloqueio, cancelamento, inadimplência

**Exclusão**:
Retirada lógica de um Tenant: ele some da listagem e deixa de ser acessível, mas seu banco é mantido e ele pode ser restaurado.
_Avoid_: Remoção, cancelamento, arquivamento

**Trava Manual**:
Prazo definido pelo Usuário Central durante o qual a cobrança automática não altera a Situação.
_Avoid_: Override, congelamento

**Provisionamento**:
Criação do ambiente de um Tenant novo: banco, estrutura, Perfis de Sistema e Admin inicial.
_Avoid_: Setup, onboarding, instalação

**Senha Provisória**:
Senha gerada pela plataforma para o primeiro acesso do Admin inicial, com validade curta e troca obrigatória.
_Avoid_: Senha temporária, senha inicial, senha padrão

## Relationships

- Um **Tenant** contrata exatamente um **Plano** e tem um ou mais **Domínios**
- Um **Domínio** aponta para exatamente um **Tenant** e um **Painel**
- Um **Plano** contém várias **Funcionalidades** e oferece de um a três **Ciclos**
- Um **Tenant** contrata um **Plano** em um dos **Ciclos** que ele oferece
- Um **Tenant** tem ao menos um **Domínio** apontando para o **Painel** admin
- Uma **Funcionalidade** está ativa quando está no **Plano** e ligada pelo **Admin**
- Uma pessoa do **Tenant** tem exatamente um **Perfil**
- Um **Perfil** pertence a exatamente um **Tipo Base**, e o **Tipo Base** determina o **Painel**
- Enquanto os **Perfis** não existem, cada pessoa do **Tenant** carrega diretamente o seu **Tipo Base**
- Só **Domínio** verificado responde; cada **Domínio** serve um único **Painel**, no caminho próprio dele
- Um **Usuário** atende vários **Clientes**, e um **Cliente** é atendido por vários **Usuários**
- Um **Tenant** tem no máximo uma **Assinatura** vigente
- O **Provisionamento** de um **Tenant** cria seu **Admin** inicial a partir do responsável e do e-mail de contato, com uma **Senha Provisória**

## Domain → Technical Mapping

Linhas sem "(planejado)" já estão implementadas. As demais são intenção de projeto, confirmada fatia a fatia.

| Ação de domínio | Action | Service | Evento |
|---|---|---|---|
| Cadastrar Plano | `CreatePlanAction` | `PlanService` | — |
| Alterar Plano | `UpdatePlanAction` | `PlanService` | — |
| Excluir Plano | `DeletePlanAction` | `PlanService` | — |
| Sincronizar catálogo de Funcionalidades | `SyncFeatureCatalogAction` | `FeatureService` | — |
| Cadastrar Tenant (com Domínios) | `CreateTenantAction` | `TenantService` | `TenantRegistered` |
| Alterar Tenant (dados, Plano, Ciclo, Domínios) | `UpdateTenantAction` | `TenantService` | — |
| Excluir Tenant | `SoftDeleteTenantAction` | `TenantService` | `TenantSoftDeleted` |
| Restaurar Tenant | `RestoreTenantAction` | `TenantService` | `TenantRestored` |
| Provisionar Tenant | (job) `ProvisionTenantJob` → `ProvisionTenantAction` | `TenantProvisioningService` | `TenantProvisioned` |
| Provisionar novamente | `RetryTenantProvisioningAction` | `TenantProvisioningService` | `TenantProvisioningRetryRequested` |
| Reenviar Senha Provisória | `RequestProvisionalPasswordResendAction` → (job) `ResendProvisionalPasswordJob` | `TenantProvisioningService` | `ProvisionalPasswordResendRequested` |
| Trocar Senha Provisória | `ChangeProvisionalPasswordAction` | `TenantUserService` | — |
| Verificar Domínio | `VerifyTenantDomainAction` | `TenantDomainService` | `TenantDomainVerified` |
| Testar DNS de um Domínio | `CheckTenantDomainDnsAction` | `TenantDomainService` | — |
| Resolver Domínio da requisição | (middleware) `InitializeTenancyForTenantDomain` | `TenantDomainService` | — |
| Alterar Situação manualmente (planejado) | `ChangeTenantStatusAction` | `SubscriptionService` | `TenantStatusChanged` |
| Processar evento de cobrança (planejado) | (job) `ProcessWebhookEventJob` | `SubscriptionService` | `TenantStatusChanged` |
| Ligar/desligar Funcionalidade (planejado) | `ToggleFeatureAction` | `FeatureService` | `FeatureToggled` |
| Criar Perfil Customizado (planejado) | `CreateRoleAction` | `RoleService` | — |
| Cadastrar Cliente (planejado) | `CreateCustomerAction` | `CustomerService` | `CustomerCreated` |

## Example dialogue

> **Dev:** "Quando um **Tenant** troca para um **Plano** menor, o que acontece com a **Funcionalidade** que saiu?"
> **Especialista:** "Fica inativa na hora, mas os dados continuam lá. Se ele voltar ao **Plano** maior, reaparece como estava."
>
> **Dev:** "E se um **Cliente** tentar entrar pelo **Domínio** do **Painel** admin?"
> **Especialista:** "É barrado. O **Domínio** escolhe o **Painel**, mas quem autoriza é o **Tipo Base** do **Perfil**."

## Flagged ambiguities

- **"Cliente"** foi usado para a empresa que contrata a plataforma e para a pessoa atendida pelo usuário do tenant. Resolvido: a empresa contratante é **Tenant**; **Cliente** é sempre a pessoa atendida por um **Usuário**.
- **"Usuário"** foi usado para qualquer pessoa que faz login e para o tipo específico que opera o produto. Resolvido: **Usuário** é o tipo base intermediário. Para falar de qualquer pessoa, usa-se "pessoa do tenant". Quem opera o Central é **Usuário Central**.
- **"Perfil"** foi usado para o tipo fixo (admin, usuário, cliente) e para o conjunto de permissões. Resolvido: o tipo fixo é **Tipo Base**; **Perfil** é o conjunto de permissões.
- **"Painel"** e **"Domínio"** não são sinônimos: vários **Domínios** de um mesmo **Tenant** podem apontar para o mesmo **Painel**.
