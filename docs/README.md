# Documentação — Multi_Tenant

Base genérica e reutilizável para produtos por assinatura que atendem várias empresas, cada uma com seus dados isolados.

Versão documentada: `v1.0.0-beta.1`. As oito fatias do plano de construção estão implementadas.

## Por onde começar

| Você quer | Leia |
|---|---|
| Rodar o projeto | [`../README.md`](../README.md) |
| Entender os termos | [`../CONTEXT.md`](../CONTEXT.md) |
| Construir o seu produto em cima | [`05-guia/criando-um-modulo.md`](05-guia/criando-um-modulo.md) |
| Colocar no ar e operar | [`06-entrega/runbook.md`](06-entrega/runbook.md) |
| Saber o que falta e o que tem risco | [`04-arquitetura/estado-atual.md`](04-arquitetura/estado-atual.md) |

## Mapa

| Fase | Arquivo | Conteúdo |
|---|---|---|
| Problema | [01-problema/contexto.md](01-problema/contexto.md) | O problema, quem sofre e como medir o sucesso |
| Problema | [01-problema/escopo.md](01-problema/escopo.md) | O que está dentro e o que está fora |
| Requisitos | [02-requisitos/requisitos-funcionais.md](02-requisitos/requisitos-funcionais.md) | RF01 a RF30 |
| Requisitos | [02-requisitos/regras-de-negocio.md](02-requisitos/regras-de-negocio.md) | RN01 a RN53 |
| Requisitos | [02-requisitos/requisitos-nao-funcionais.md](02-requisitos/requisitos-nao-funcionais.md) | RNF01 a RNF09 |
| Requisitos | [02-requisitos/rastreabilidade.md](02-requisitos/rastreabilidade.md) | De cada requisito até as classes e os testes |
| Modelagem | [03-modelagem/modelo-de-dados.md](03-modelagem/modelo-de-dados.md) | Os dois bancos e os ciclos de vida |
| Arquitetura | [04-arquitetura/visao-geral.md](04-arquitetura/visao-geral.md) | Componentes, fluxos e resposta a cada RNF |
| Arquitetura | [04-arquitetura/estado-atual.md](04-arquitetura/estado-atual.md) | O que existe, o que diverge e as pendências |
| Guia | [05-guia/criando-um-modulo.md](05-guia/criando-um-modulo.md) | Passo a passo de um módulo novo |
| Entrega | [06-entrega/runbook.md](06-entrega/runbook.md) | Implantação e operação |
| Decisões | [adr/](adr/) | 10 ADRs aceitas |
| Histórico | [../CHANGELOG.md](../CHANGELOG.md) | O que cada versão trouxe |

## O que ficou em aberto

1. **Validar a cobrança no sandbox do Asaas e do Stripe.** É o que separa a versão beta da versão final. O roteiro está em `estado-atual.md`.
2. **As premissas de `contexto.md`.** Quem sofre a dor e as métricas de sucesso foram propostas, não confirmadas, e estão marcadas com **[PREMISSA]**.
3. **Os valores dos requisitos não funcionais.** São propostos. Dois deles (RNF02 e RNF03) só têm medição de teste automatizado, sem carga.
4. **A lista de "fora do escopo".** Metade dela decorre de decisões tomadas; a outra metade é proposta.
5. **Certificado automático para domínios próprios.** Ponto técnico ainda por validar em `visao-geral.md`.
6. **Política de retenção de dados.** O que acontece com os dados de um tenant cancelado, e em que prazo, não foi decidido.

## Convenções

- IDs estáveis: `RF01`, `RN01`, `RNF01`, `ADR-0001`. Número aposentado não é reutilizado.
- Diagramas em Mermaid dentro do `.md`.
- ADR não é editada. Mudou a decisão, cria-se outra com `Substitui ADR-XXXX`.
- Termo novo de domínio entra no `CONTEXT.md` no mesmo PR que o introduziu.
- Um requisito só está pronto quando a linha dele em `rastreabilidade.md` aponta para as classes e para os testes.
