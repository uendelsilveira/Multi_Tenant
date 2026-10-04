---
status: accepted
date: 2026-10-04
---

# ADR-0001 — Um banco de dados por tenant

Cada tenant tem seu próprio banco, e um banco central guarda apenas tenants, domínios, planos, funcionalidades e assinaturas. A conexão é trocada em tempo de execução pelo `stancl/tenancy`. Decidimos assim porque o isolamento entre empresas contratantes é o requisito mais crítico da plataforma (RNF01), e um banco por tenant torna o vazamento entre tenants um erro de conexão, não um `where` esquecido.

## Opções consideradas

- **Banco único com coluna `tenant_id`.** Mais simples de operar e de migrar, mas todo acesso a dados depende de um escopo aplicado corretamente. Um único esquecimento expõe dados de outro tenant.
- **Banco por tenant (escolhida).** Isolamento físico, backup e restauração por tenant, possibilidade de mover um tenant de servidor.

## Consequências

- Migrations de tenant ficam em diretório separado e rodam em todos os bancos a cada deploy. O tempo de deploy cresce com o número de tenants.
- Não existem consultas que cruzem tenants. Relatórios consolidados, se um dia forem necessários, exigem outro mecanismo.
- Todo job que opera em contexto de tenant precisa inicializar a tenancy no `handle()`.
