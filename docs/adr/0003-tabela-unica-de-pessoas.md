---
status: accepted
date: 2026-10-04
---

# ADR-0003 — Tabela única de pessoas no tenant, com pivot auto-referenciado para clientes

Admin, usuário e cliente ficam na mesma tabela `users` do banco do tenant, diferenciados pelo tipo base do perfil. O vínculo N:N entre usuário e cliente é um pivot `customer_user` em que as duas colunas apontam para `users`. Decidimos assim porque a regra dita foi "o perfil define o painel", e um único cadastro de pessoas com um único mecanismo de login e de perfis é mais simples de manter do que três.

## Opções consideradas

- **`users` e `customers` em tabelas e guards separados.** Separa bem os dados específicos de cliente e torna impossível vincular a pessoa errada, ao custo de dois fluxos de autenticação e de perfis.
- **Três tabelas, uma por painel.** Mesma vantagem, com ainda mais duplicação.
- **Tabela única (escolhida).**

## Consequências

- O banco não impede que `customer_user` vincule duas pessoas de tipos errados. Essa validação é responsabilidade do serviço e precisa de teste.
- Dados que só clientes têm (documento, endereço, empresa) não devem inflar `users`. Quando surgirem, vão para uma tabela complementar 1:1.
- A listagem de clientes de um usuário é sempre filtrada pelo pivot; a do admin não.
