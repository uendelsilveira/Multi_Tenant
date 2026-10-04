---
status: accepted
date: 2026-10-04
---

# ADR-0006 — Dois gateways de cobrança atrás de uma interface, com ajuste manual travável

A cobrança usa Asaas e Stripe. Cada assinatura pertence a um único gateway. Os dois ficam atrás de uma mesma interface, e cada um tem um mapper que converte seus eventos em eventos internos (`PaymentConfirmed`, `PaymentOverdue`, `SubscriptionCanceled`). A situação do tenant muda automaticamente por webhook, e o central pode alterá-la manualmente, com motivo e com uma trava de prazo durante a qual os webhooks não alteram a situação. Decidimos assim porque a plataforma é genérica e precisa atender cobrança local (Pix, boleto) e por cartão, sem que a regra de situação do tenant conheça nenhum gateway.

## Opções consideradas

- **Um único gateway.** Metade do trabalho de integração, mas limita os meios de pagamento.
- **Situação apenas manual.** Sem integração, mas não escala e depende de alguém conferir pagamentos.
- **Dois gateways com automático + manual (escolhida).**

## Consequências

- A autenticidade do webhook é validada de forma diferente em cada gateway (assinatura no Stripe, token no Asaas). Não se unifica a validação, só o que vem depois dela.
- Eventos são gravados antes de processados e têm unicidade por `(gateway, id do evento)`, porque gateways reenviam e entregam fora de ordem.
- Sem a trava, uma reativação manual seria desfeita pelo próximo evento de falha de cobrança.
- `SubscriptionService` é o único lugar que altera a situação do tenant, seja qual for a origem.
