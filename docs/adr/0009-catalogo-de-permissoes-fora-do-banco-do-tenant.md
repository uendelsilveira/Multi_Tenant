---
status: accepted
date: 2026-10-05
---

# ADR-0009 — Catálogo de permissões em configuração, chaves gravadas no perfil

O catálogo de permissões fica em `config/permissions.php`, e cada perfil customizado guarda, em uma coluna JSON, as chaves das permissões marcadas. Não existem as tabelas `permissions` e `role_permission` previstas na modelagem inicial. Um perfil de sistema não guarda nada: tem, por regra, todas as permissões do seu tipo base. Decidimos assim porque há um banco por tenant: um catálogo em tabela precisaria ser semeado e mantido igual em todos eles a cada permissão nova, e qualquer tenant fora de sincronia teria um conjunto de permissões diferente dos demais.

## Opções consideradas

- **Tabelas `permissions` e `role_permission` em cada banco de tenant.** É o desenho clássico e permite consultar por SQL quem tem qual permissão, ao custo de sincronizar o catálogo em todos os bancos.
- **Um pacote pronto de papéis e permissões.** Traz mais do que o necessário (vários perfis por pessoa, permissão direta na pessoa) e também guarda o catálogo no banco.
- **Catálogo em configuração e chaves no perfil (escolhida).**

## Consequências

- Uma permissão nova vale para todos os tenants no deploy, sem migração de dados.
- Uma chave que sai do catálogo simplesmente deixa de valer: o que estiver gravado em perfis é ignorado na leitura.
- "Quem tem a permissão X" é respondido em código, percorrendo os perfis do tenant. Com poucos perfis por tenant isso é barato; não serve para relatórios entre tenants.
- Cada permissão declara os tipos base em que vale. Ao trocar o tipo de um perfil, o que não vale para o novo tipo é descartado.
