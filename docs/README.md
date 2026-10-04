# Documentação — Plataforma Multi-Tenant

Base genérica e reutilizável para produtos por assinatura que atendem várias empresas, cada uma com seus dados isolados.

Esta é a documentação da **Onda 0**: esqueleto do problema, requisitos macro, modelo e arquitetura base. O detalhe de cada caso de uso chega por fatia vertical, nas ondas seguintes.

## Mapa

| Fase | Arquivo | Situação |
|---|---|---|
| Problema | [01-problema/contexto.md](01-problema/contexto.md) | Rascunho com premissas a confirmar |
| Problema | [01-problema/escopo.md](01-problema/escopo.md) | "Fora do escopo" é proposta, a confirmar |
| Requisitos | [02-requisitos/requisitos-funcionais.md](02-requisitos/requisitos-funcionais.md) | Extraído da entrevista |
| Requisitos | [02-requisitos/regras-de-negocio.md](02-requisitos/regras-de-negocio.md) | Extraído da entrevista |
| Requisitos | [02-requisitos/requisitos-nao-funcionais.md](02-requisitos/requisitos-nao-funcionais.md) | Valores propostos, a ajustar |
| Requisitos | [02-requisitos/rastreabilidade.md](02-requisitos/rastreabilidade.md) | Matriz inicial, testes entram por fatia |
| Modelagem | [03-modelagem/modelo-de-dados.md](03-modelagem/modelo-de-dados.md) | Modelo alvo; o código ainda não o implementa |
| Arquitetura | [04-arquitetura/visao-geral.md](04-arquitetura/visao-geral.md) | Arquitetura alvo, com 2 pontos a validar |
| Arquitetura | [04-arquitetura/estado-atual.md](04-arquitetura/estado-atual.md) | Retrato do código × documentação e padrão técnico |
| Decisões | [adr/](adr/) | 7 ADRs aceitas |
| Glossário | [../CONTEXT.md](../CONTEXT.md) | Decidido |

## Pendências antes de fechar a Onda 0

1. Adequar o código ao padrão técnico e à documentação, na ordem de estado-atual.md.
2. Confirmar as premissas de `contexto.md` (quem sofre a dor e a métrica de sucesso).
3. Confirmar ou ajustar a lista de "Fora do escopo".
4. Ajustar os valores dos RNFs.
5. Decidir os pontos em aberto listados no fim de `regras-de-negocio.md`.
6. Validar os dois pontos técnicos de `visao-geral.md` (painéis por domínio dinâmico e certificado automático).
7. O `README.md` da raiz descreve o projeto antes destas decisões e precisa ser revisto. Runbook e changelog ainda não existem.

## Convenções

- IDs estáveis: `RF01`, `RN01`, `RNF01`, `CDU01`, `ADR-0001`. Número aposentado não é reutilizado.
- Diagramas em Mermaid dentro do `.md`.
- ADR não é editada. Mudou a decisão, cria-se outra com `Substitui ADR-XXXX`.
- Termo novo de domínio entra no `CONTEXT.md` no mesmo PR que o introduziu.
