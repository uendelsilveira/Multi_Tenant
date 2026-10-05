# Requisitos funcionais

> Gate desta fase: todo requisito é verificável.
> Os termos em negrito estão definidos em `CONTEXT.md`.

## Central

| ID | Requisito |
|---|---|
| RF01 | O **Usuário Central** cadastra um **Tenant** informando **Slug**, dados cadastrais (razão social, nome fantasia, tipo de pessoa, CNPJ ou CPF, inscrição estadual, responsável, e-mail, telefone, endereço, observações internas), **Plano** e **Ciclo**. O e-mail do **Admin** inicial entra na fatia de provisionamento. |
| RF02 | No cadastro e na edição do **Tenant**, o **Usuário Central** informa um ou mais **Domínios**, indicando a qual **Painel** cada um aponta. |
| RF03 | Todo **Domínio** nasce pendente. O **Usuário Central** consulta os domínios em uma listagem própria, pode testar para onde cada um aponta e o marca como verificado; o sistema registra quem verificou e quando. |
| RF04 | O **Usuário Central** cadastra **Planos** com nome, descrição, situação (ativo ou inativo), preço para cada **Ciclo** oferecido e as **Funcionalidades** incluídas. O catálogo de funcionalidades é declarado em configuração e sincronizado por comando. |
| RF05 | O **Usuário Central** altera o **Plano** de um **Tenant**. |
| RF06 | O **Usuário Central** altera manualmente a situação de um **Tenant**, informando motivo e, opcionalmente, uma data até a qual a cobrança automática não altera essa situação. |
| RF07 | O sistema mantém histórico de toda mudança de situação do **Tenant**, com origem (gateway ou manual), autor e motivo. |
| RF21 | O **Usuário Central** exclui um **Tenant** (exclusão lógica) e pode restaurá-lo. Tenants excluídos são consultados por um filtro na listagem. |
| RF22 | Usuários centrais com papel de consulta listam planos e tenants, mas não cadastram, alteram, excluem nem restauram. |

## Resolução e acesso

| ID | Requisito |
|---|---|
| RF08 | A cada requisição, o sistema identifica o **Tenant** e o **Painel** a partir do domínio. Domínio desconhecido, pendente ou de tenant excluído recebe "não encontrado", assim como o caminho de um painel acessado pelo domínio de outro. |
| RF09 | O acesso a um **Painel** só é permitido a quem tem o **Tipo Base** correspondente àquele painel. Usuário central só acessa o painel central. |
| RF10 | No primeiro acesso com **Senha Provisória**, o sistema leva o usuário para a troca da senha e não abre nenhuma outra tela antes disso. Com a senha provisória vencida, a sessão é encerrada. |

## Provisionamento

| ID | Requisito |
|---|---|
| RF11 | Ao cadastrar um **Tenant**, o sistema enfileira o **Provisionamento**: cria o banco isolado, a estrutura de dados e o **Admin** inicial, a partir do responsável e do e-mail de contato, com **Senha Provisória** enviada por e-mail. Os **Perfis de Sistema** entram na fatia de perfis. |
| RF23 | O **Usuário Central** vê, na listagem, a situação do ambiente de cada **Tenant** (aguardando, provisionando, pronto, falhou) e o motivo da falha, e pode mandar provisionar novamente o que não está pronto. |
| RF24 | O **Usuário Central** manda reenviar a **Senha Provisória** do **Admin** inicial. |
| RF25 | Quem acessa o domínio de um **Tenant** cujo ambiente ainda não está pronto vê uma página de espera, que se atualiza sozinha. |

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
