---
status: accepted
date: 2026-10-04
---

# ADR-0005 — Funcionalidades liberadas só pelo plano, com liga/desliga do tenant e downgrade imediato

Uma funcionalidade está ativa para um tenant quando está contida no plano dele **e** está ligada na tela de configurações do admin. O central não libera funcionalidade avulsa para um tenant específico. Na troca para um plano menor, o que saiu é desligado na mesma hora. Decidimos assim para manter uma única fonte de verdade sobre o que cada tenant pode usar e eliminar qualquer agendamento de desligamento.

## Opções consideradas

- **Liberação manual, tenant por tenant.** Flexível, mas cada tenant vira um caso particular.
- **Plano com exceções por tenant.** Atende negociações comerciais pontuais, ao custo de uma segunda fonte de verdade.
- **Desligamento só no fim do ciclo pago.** Mais justo com o tenant, mas exige guardar o plano anterior e uma rotina agendada.
- **Só plano, corte imediato (escolhida).**

## Consequências

- Uma negociação comercial fora dos planos existentes exige criar um plano novo.
- No downgrade, o tenant perde acesso ao que já pagou até o fim do ciclo. A política de valor (crédito, proporcional ou sem devolução) está em aberto em `02-requisitos/regras-de-negocio.md`.
- A mudança precisa limpar o cache de funcionalidades do tenant, e jobs de módulo conferem a funcionalidade no `handle()`.
- Dados e preferências são preservados: ao voltar para o plano maior, tudo reaparece.
