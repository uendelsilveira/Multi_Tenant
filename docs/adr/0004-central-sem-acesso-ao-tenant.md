---
status: accepted
date: 2026-10-04
---

# ADR-0004 — O central nunca entra no ambiente do tenant

Não existe impersonação nem qualquer tela do painel central que leia dados de um tenant. O único ponto em que o lado central escreve no banco de um tenant é o job de provisionamento, que cria a estrutura e o admin inicial. Decidimos assim para que a promessa de isolamento valha também contra a operadora da plataforma.

## Opções consideradas

- **Impersonação com log de auditoria.** Padrão em produtos por assinatura; facilita muito o suporte.
- **Impersonação apenas com autorização do tenant.** Meio-termo, com mais telas e fluxo de consentimento.
- **Sem acesso (escolhida).**

## Consequências

- O suporte não consegue ver o que o tenant está vendo. O diagnóstico depende de logs e métricas técnicas identificadas por tenant, sem dados de negócio, e isso precisa existir desde o início.
- O admin inicial nasce com senha provisória criada pelo central (RN15). Se o admin perder o acesso, a recuperação é por redefinição de senha via e-mail, não por intervenção do central.
- Correções de dados em um tenant exigem migration ou comando versionado, nunca edição manual por tela.
