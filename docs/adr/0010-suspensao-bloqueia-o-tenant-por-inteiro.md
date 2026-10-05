---
status: accepted
date: 2026-10-05
---

# ADR-0010 — Tenant suspenso fica bloqueado por inteiro

Um tenant suspenso não é acessado por ninguém: qualquer requisição aos painéis dele, inclusive de quem já estava com a tela aberta, recebe uma página que informa a suspensão e pede contato com o administrador. Esta decisão substitui a escolha inicial do projeto, de manter o tenant suspenso em modo somente leitura. Decidimos assim porque o bloqueio total é o que efetivamente leva a empresa a regularizar a situação, e porque é uma regra simples de explicar ao cliente e de garantir no sistema.

## Opções consideradas

- **Somente leitura (decisão anterior).** As pessoas entram e consultam, mas não alteram nada. Preserva o acesso à informação, ao custo de cada tela e cada ação de cada módulo ter de respeitar a regra, e de um esquecimento virar brecha.
- **Só o painel admin continua acessível.** Meio-termo que mantém o contato com quem decide o pagamento.
- **Bloqueio total (escolhida).**

## Consequências

- A regra é garantida em um único lugar, antes da sessão e do login: um middleware nos três painéis, na raiz do domínio e na rota de atualização do Livewire. Nenhum módulo precisa fazer nada para respeitá-la nas telas.
- Jobs de módulo não passam por esse middleware. Para eles há um middleware de job próprio, que cada job de módulo declara.
- Enquanto suspensa, a empresa não consulta os próprios dados. Pedidos de exportação ou de acesso pontual durante a suspensão precisam ser tratados pelo central, reativando com trava por prazo curto.
- Os dados não são tocados: reativar devolve tudo como estava, na hora.
- O motivo da suspensão é interno e não aparece na página de bloqueio.
