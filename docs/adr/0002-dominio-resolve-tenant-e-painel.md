---
status: accepted
date: 2026-10-04
---

# ADR-0002 — O domínio resolve tenant e painel ao mesmo tempo

Cada registro de domínio no banco central carrega o tenant e o painel (`admin`, `user` ou `customer`) a que aponta. Um tenant com base `foo.com` pode ter `aaa.foo.com` no painel admin e `bbb.foo.com` no painel de clientes, e também domínios totalmente diferentes entre si. Decidimos assim porque o tenant quer endereços distintos para públicos distintos, e não um endereço único com caminhos.

## Opções consideradas

- **Painel por caminho** (`/admin`, `/app`, `/portal` no mesmo domínio). É o caminho natural do Filament e o mais simples, mas não permite que o tenant dê um endereço próprio a cada público.
- **Painel único com menus por perfil.** Menos código, mas mistura no mesmo ambiente pessoas com papéis muito diferentes, inclusive clientes externos.
- **Painel por domínio (escolhida).**

## Consequências

- O domínio escolhe o painel, mas não autoriza a entrada: o perfil precisa ter o tipo base correspondente (RF09).
- Como os domínios são dinâmicos, os painéis não podem ter domínio fixo em configuração. A forma de roteamento está descrita como ponto a validar em `04-arquitetura/visao-geral.md`.
- Sessão e cookies são por domínio: uma pessoa logada em um domínio do tenant não está logada em outro.
- Cada domínio próprio precisa de certificado, o que exige emissão automática.
