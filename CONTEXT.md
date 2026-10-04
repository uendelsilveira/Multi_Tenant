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

**Trava Manual**:
Prazo definido pelo Usuário Central durante o qual a cobrança automática não altera a Situação.
_Avoid_: Override, congelamento

**Provisionamento**:
Criação do ambiente de um Tenant novo: banco, estrutura, Perfis de Sistema e Admin inicial.
_Avoid_: Setup, onboarding, instalação

## Relationships

- Um **Tenant** contrata exatamente um **Plano** e tem um ou mais **Domínios**
- Um **Domínio** aponta para exatamente um **Tenant** e um **Painel**
- Um **Plano** contém várias **Funcionalidades**
- Uma **Funcionalidade** está ativa quando está no **Plano** e ligada pelo **Admin**
- Uma pessoa do **Tenant** tem exatamente um **Perfil**
- Um **Perfil** pertence a exatamente um **Tipo Base**, e o **Tipo Base** determina o **Painel**
- Um **Usuário** atende vários **Clientes**, e um **Cliente** é atendido por vários **Usuários**
- Um **Tenant** tem no máximo uma **Assinatura** vigente

## Domain → Technical Mapping

Nomes planejados; confirmados fatia a fatia.

| Ação de domínio | Action | Service | Evento |
|---|---|---|---|
| Cadastrar Tenant | `CreateTenantAction` | `TenantProvisioningService` | `TenantCreated` |
| Provisionar Tenant | (job) `ProvisionTenantJob` | `TenantProvisioningService` | `TenantProvisioned` |
| Cadastrar Domínio | `RegisterDomainAction` | `DomainService` | `DomainRegistered` |
| Verificar Domínio | `VerifyDomainAction` | `DomainService` | `DomainVerified` |
| Trocar Plano | `ChangeTenantPlanAction` | `PlanService` | `TenantPlanChanged` |
| Alterar Situação manualmente | `ChangeTenantStatusAction` | `SubscriptionService` | `TenantStatusChanged` |
| Processar evento de cobrança | (job) `ProcessWebhookEventJob` | `SubscriptionService` | `TenantStatusChanged` |
| Ligar/desligar Funcionalidade | `ToggleFeatureAction` | `FeatureService` | `FeatureToggled` |
| Criar Perfil Customizado | `CreateRoleAction` | `RoleService` | — |
| Cadastrar Cliente | `CreateCustomerAction` | `CustomerService` | `CustomerCreated` |

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
