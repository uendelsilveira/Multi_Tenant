# Requisitos não funcionais

> Gate desta fase: todo RNF tem valor mensurável e método de medição.
> **Todos os valores abaixo são proposta.** Nenhum foi definido na entrevista; ajustar antes de fechar a Onda 0.
> A resposta arquitetural de cada um está em `04-arquitetura/visao-geral.md`.

| ID | Requisito | Valor | Como medir |
|---|---|---|---|
| RNF01 | Isolamento entre tenants | 0 leituras ou escritas cruzadas | Suíte automatizada que executa cada caso de uso com dois tenants e verifica que nenhum dado de um aparece no outro |
| RNF02 | Custo da resolução de domínio | p95 < 20 ms por requisição, com cache aquecido | Medição do middleware de resolução em teste de carga |
| RNF03 | Tempo de provisionamento | Tenant acessível em < 60 s (p95) após o cadastro | Tempo entre o cadastro no central e a conclusão do job de provisionamento |
| RNF04 | Recebimento de evento de cobrança | Resposta ao gateway em < 2 s (p95); efeito aplicado em < 60 s | Log do endpoint e do job de processamento |
| RNF05 | Propagação de mudança de plano ou de funcionalidade | Efeito visível em < 5 s | Teste automatizado: altera e consulta em seguida |
| RNF06 | Auditoria de situação | 100% das mudanças de situação com origem, autor e motivo | Teste automatizado sobre o histórico |
| RNF07 | Qualidade de código | Cobertura ≥ 80% em Services e Actions; análise estática no nível 8 sem erros | Pipeline de CI |
| RNF08 | Reuso | Produto novo iniciado sobre a base em ≤ 1 dia, sem alterar código da base | Medição no primeiro produto construído sobre ela |
| RNF09 | Segurança da senha provisória | Nunca armazenada em texto; válida para um único acesso | Revisão de código e teste automatizado |
