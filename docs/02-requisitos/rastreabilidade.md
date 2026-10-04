# Matriz de rastreabilidade

Cadeia: `RF → RN → CDU → artefato técnico → teste`.

Na Onda 0 a matriz vai até o artefato técnico **planejado**. As colunas CDU e Teste são preenchidas quando a fatia vertical correspondente for detalhada e construída. Os nomes de artefato são intenção de projeto e podem mudar na fatia.

| RF | RNs | CDU | Artefato técnico planejado | Teste |
|---|---|---|---|---|
| RF01 | RN13, RN15 | — | `CreateTenantAction` → `TenantProvisioningService` | — |
| RF02 | RN01, RN03 | — | `RegisterDomainAction` → `DomainService` | — |
| RF03 | RN02 | — | `VerifyDomainAction` → `DomainService` | — |
| RF04 | RN06 | — | `PlanService`, catálogo de funcionalidades | — |
| RF05 | RN07 | — | `ChangeTenantPlanAction` → evento `TenantPlanChanged` | — |
| RF06 | RN17, RN19 | — | `ChangeTenantStatusAction` → `SubscriptionService` | — |
| RF07 | RN19 | — | `tenant_status_logs` | — |
| RF08 | RN01, RN02 | — | Middleware de resolução de domínio | — |
| RF09 | RN08, RN10 | — | `canAccessPanel` por tipo base | — |
| RF10 | RN15 | — | Middleware de troca obrigatória de senha | — |
| RF11 | RN09, RN15 | — | `ProvisionTenantJob` | — |
| RF12 | RN12 | — | `UserService` | — |
| RF13 | RN09, RN10, RN11 | — | `RoleService` | — |
| RF14 | RN04, RN05 | — | `FeatureService`, `feature_settings` | — |
| RF15 | RN04 | — | `FeatureService` (menu, rota e jobs) | — |
| RF16 | RN14 | — | `CreateCustomerAction` → `CustomerService` | — |
| RF17 | RN14 | — | Pivot `customer_user`, escopo de consulta por vínculo | — |
| RF18 | RN17, RN18 | — | Endpoints de webhook, `ProcessWebhookEventJob`, `SubscriptionService` | — |
| RF19 | RN16 | — | Negação central de escrita (`Gate::before`) | — |
| RF20 | RN05, RN07 | — | Listener de `TenantPlanChanged` limpa cache de funcionalidades | — |

## RNs ainda sem RF

Nenhuma. Toda RN de `regras-de-negocio.md` aparece em pelo menos uma linha acima.

## ADRs por requisito

| ADR | RFs afetados |
|---|---|
| ADR-0001 | RF11 |
| ADR-0002 | RF02, RF08, RF09 |
| ADR-0003 | RF12, RF16, RF17 |
| ADR-0004 | RF01, RF11 |
| ADR-0005 | RF04, RF14, RF15, RF20 |
| ADR-0006 | RF18 |
