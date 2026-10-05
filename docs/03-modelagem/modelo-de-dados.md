# Modelo de dados

> Gate desta fase: toda entidade existe no glossário (`CONTEXT.md`).

São dois bancos. O **central** é único. O **banco do tenant** existe um por tenant, com a mesma estrutura. Não há chave estrangeira entre os dois: o vínculo é feito em tempo de execução pela resolução do domínio.

## Banco central

```mermaid
erDiagram
    PLANS ||--o{ TENANTS : "contratado por"
    PLANS ||--o{ FEATURE_PLAN : contem
    PLANS ||--|{ PLAN_PRICES : oferece
    FEATURES ||--o{ FEATURE_PLAN : "liberada em"
    TENANTS ||--o{ DOMAINS : possui
    TENANTS ||--o| SUBSCRIPTIONS : tem
    TENANTS ||--o{ TENANT_STATUS_LOGS : registra
    CENTRAL_USERS ||--o{ TENANT_STATUS_LOGS : "autor de"

    CENTRAL_USERS {
        id id
        string name
        string email
        string password
    }
    PLANS {
        id id
        string name
        string description
        bool is_active
    }
    PLAN_PRICES {
        id id
        id plan_id
        string billing_cycle
        decimal price
    }
    FEATURES {
        id id
        string key
        string name
        string module
    }
    FEATURE_PLAN {
        id plan_id
        id feature_id
    }
    TENANTS {
        string id
        string legal_name
        string trade_name
        string person_type
        string document
        string state_registration
        string contact_name
        string contact_email
        string contact_phone
        string zip_code
        string street
        string number
        string complement
        string district
        string city
        string state
        string notes
        id plan_id
        string billing_cycle
        string status
        string provisioning_status
        string provisioning_error
        datetime provisioned_at
        datetime status_locked_until
        datetime deleted_at
    }
    DOMAINS {
        id id
        string tenant_id
        string domain
        string panel
        string status
        datetime verified_at
        id verified_by
    }
    SUBSCRIPTIONS {
        id id
        string tenant_id
        id plan_id
        string gateway
        string gateway_subscription_id
        string status
        datetime current_period_end
    }
    WEBHOOK_EVENTS {
        id id
        string gateway
        string gateway_event_id
        string type
        json payload
        datetime processed_at
    }
    TENANT_STATUS_LOGS {
        id id
        string tenant_id
        string from
        string to
        string source
        id central_user_id
        string reason
    }
```

Restrições:

- `tenants.id` é o slug (RN20). `tenants.document` é único. `tenants.deleted_at` marca a exclusão lógica (RN24).
- `plan_prices` tem unicidade em `(plan_id, billing_cycle)`. `billing_cycle` ∈ `monthly | semiannual | annual`, em `plan_prices` e em `tenants`.
- `tenants.status_locked_until` ainda não existe no banco: entra com a fatia de situação.
- `tenants.provisioning_status` ∈ `pending | provisioning | ready | failed`. É independente de `tenants.status`: um diz se o ambiente existe, o outro se o tenant pode operar.
- `domains.domain` é único.
- `domains.panel` ∈ `admin | user | customer`. `domains.status` ∈ `pending | active`.
- `tenants.status` ∈ `active | suspended`.
- `webhook_events` tem unicidade em `(gateway, gateway_event_id)`.
- `tenant_status_logs.source` ∈ `gateway | manual`. `central_user_id` e `reason` são obrigatórios quando `manual`.

## Banco do tenant

```mermaid
erDiagram
    ROLES ||--o{ USERS : "atribuido a"
    USERS ||--o{ CUSTOMER_USER : "atende (user_id)"
    USERS ||--o{ CUSTOMER_USER : "e atendido (customer_id)"
    USERS ||--o| CUSTOMER_PROFILES : "dados de cliente"

    USERS {
        id id
        string name
        string email
        string password
        id role_id
        bool is_active
        bool must_change_password
        datetime password_expires_at
    }
    ROLES {
        id id
        string name
        string base_type
        bool is_system
        json permissions
    }
    CUSTOMER_USER {
        id user_id
        id customer_id
    }
    CUSTOMER_PROFILES {
        id user_id
        string phone
        string document
        string notes
    }
    FEATURE_SETTINGS {
        string feature_key
        bool enabled
    }
```

Restrições:

- `roles.base_type` ∈ `admin | user | customer`.
- `customer_user` aponta duas vezes para `users`: `user_id` é uma pessoa de tipo base `user`, `customer_id` é uma pessoa de tipo base `customer`. A validação desses tipos é regra de serviço, não do banco. Ver ADR-0003.
- `feature_settings.feature_key` referencia `features.key` do central por valor, sem chave estrangeira.
- As permissões não têm tabela: o catálogo fica em `config/permissions.php` e `roles.permissions` guarda as chaves marcadas em um perfil customizado (ADR-0009). Perfil de sistema não usa a coluna.
- Os três perfis de sistema são criados pela própria migration, em todo tenant.
- `users.must_change_password` e `users.password_expires_at` controlam a senha provisória; `users.is_active` a desativação.
- `customer_profiles` guarda o que só cliente tem, em relação 1:1 com a pessoa, para não inflar `users` (ADR-0003).
- No código, a mesma linha de `users` é lida por dois models: `TenantUser`, que autentica, e `Customer`, que é a visão de cadastro, restrita a perfis de tipo cliente.

## Ciclo de vida do tenant

```mermaid
stateDiagram-v2
    [*] --> active : provisionado
    active --> suspended : pagamento falhou / cancelamento / ação manual
    suspended --> active : pagamento confirmado / ação manual
    note right of suspended
        Somente leitura (RN16).
        Com trava manual vigente,
        eventos de cobrança não
        mudam o estado (RN17).
    end note
```

## Ciclo de vida do domínio

```mermaid
stateDiagram-v2
    [*] --> pending : cadastrado pelo central
    pending --> active : verificado manualmente
    active --> [*] : removido
    pending --> [*] : removido
```
