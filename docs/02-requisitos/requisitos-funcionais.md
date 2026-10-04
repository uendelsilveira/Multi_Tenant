# Requisitos funcionais

> Gate desta fase: todo requisito é verificável.
> Os termos em negrito estão definidos em `CONTEXT.md`.

## Central

| ID | Requisito |
|---|---|
| RF01 | O **Usuário Central** cadastra um **Tenant** informando nome, **Plano** e e-mail do **Admin** inicial. |
| RF02 | O **Usuário Central** cadastra um ou mais **Domínios** para um **Tenant**, indicando a qual **Painel** cada um aponta. |
| RF03 | O **Domínio** nasce pendente. O **Usuário Central** o marca como verificado, e o sistema registra quem verificou e quando. A tela oferece um teste que mostra para onde o domínio aponta. |
| RF04 | O **Usuário Central** cadastra **Planos** e define quais **Funcionalidades** cada plano contém. |
| RF05 | O **Usuário Central** altera o **Plano** de um **Tenant**. |
| RF06 | O **Usuário Central** altera manualmente a situação de um **Tenant**, informando motivo e, opcionalmente, uma data até a qual a cobrança automática não altera essa situação. |
| RF07 | O sistema mantém histórico de toda mudança de situação do **Tenant**, com origem (gateway ou manual), autor e motivo. |

## Resolução e acesso

| ID | Requisito |
|---|---|
| RF08 | A cada requisição, o sistema identifica o **Tenant** e o **Painel** a partir do domínio. Domínio desconhecido ou pendente recebe "não encontrado". |
| RF09 | O acesso a um **Painel** só é permitido a quem tem **Perfil** do **Tipo Base** correspondente àquele painel. |
| RF10 | No primeiro acesso com senha provisória, o sistema exige a troca da senha antes de liberar qualquer tela. |

## Provisionamento

| ID | Requisito |
|---|---|
| RF11 | Ao cadastrar um **Tenant**, o sistema cria seu banco isolado, a estrutura de dados, os três **Perfis de Sistema** e o **Admin** inicial com senha provisória enviada por e-mail. |

## Dentro do tenant

| ID | Requisito |
|---|---|
| RF12 | O **Admin** cadastra, edita e desativa usuários do tenant e atribui a cada um exatamente um **Perfil**. |
| RF13 | O **Admin** cria **Perfis Customizados** escolhendo um **Tipo Base** e marcando permissões de um catálogo fixo. |
| RF14 | O **Admin** liga e desliga, na tela de configurações, as **Funcionalidades** contidas no **Plano** do tenant. |
| RF15 | Uma **Funcionalidade** inativa não aparece no menu, tem suas telas bloqueadas e não executa processamento em segundo plano. |
| RF16 | O **Usuário** cadastra um **Cliente**, que fica vinculado a ele. |
| RF17 | Um **Cliente** pode estar vinculado a vários **Usuários** e um **Usuário** a vários **Clientes**. O **Usuário** vê apenas os clientes vinculados a ele; o **Admin** vê todos. |

## Assinatura e cobrança

| ID | Requisito |
|---|---|
| RF18 | O sistema recebe eventos de cobrança do Asaas e do Stripe e atualiza a situação do **Tenant** conforme o evento. |
| RF19 | Com o **Tenant** suspenso, todos os painéis dele permitem login e consulta, e negam criação, edição e exclusão. |
| RF20 | A troca de **Plano** vale imediatamente: funcionalidades que saíram do plano ficam inativas na mesma hora. |
