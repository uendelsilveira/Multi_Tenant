---
status: accepted
date: 2026-10-04
---

# ADR-0007 — Painéis em Filament v5, com a branch `filament` como base

Todos os painéis (central, admin, usuário e cliente) são construídos em Filament v5 sobre Livewire v4, e a branch `filament` passa a ser a base do projeto. O trabalho feito na `main` com Jetstream e Livewire, depois da remoção do Filament, não é levado adiante. Decidimos assim porque a plataforma precisa de quatro painéis com tabelas, formulários, navegação e controle de acesso por painel, e o Filament entrega isso pronto, enquanto em Livewire puro cada uma dessas peças seria construída à mão.

## Opções consideradas

- **Seguir na `main` com Livewire + Jetstream.** Aproveitaria autenticação em dois fatores, passkeys e os layouts já feitos, ao custo de construir manualmente toda a infraestrutura de painel.
- **Filament só no central, Livewire nos painéis de tenant.** Dois jeitos de construir tela no mesmo projeto.
- **Filament v5 em todos os painéis (escolhida).**

## Consequências

- Filament é camada de apresentação: resources, pages e actions delegam a Actions de domínio. Nenhuma regra de negócio mora neles (`laravel-filament-specialist`).
- Recursos que o Jetstream trazia (dois fatores, passkeys, tokens de API) não existem nesta base. Se forem necessários, entram como requisito e são feitos com os mecanismos do Filament.
- A atualização de v3 para v5 trouxe o Livewire v4 e exigiu migrar o resource de tenants para a API e o layout de diretórios do v5.
- A branch `main` permanece no repositório. Renomear ou arquivar é decisão à parte.
