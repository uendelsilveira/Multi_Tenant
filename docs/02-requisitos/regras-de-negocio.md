# Regras de negócio

| ID | Regra |
|---|---|
| RN01 | Um **Domínio** pertence a exatamente um **Tenant** e aponta para exatamente um **Painel**. Um **Tenant** pode ter vários domínios. |
| RN02 | Só **Domínio** verificado resolve. Pendente se comporta como inexistente. |
| RN03 | **Domínios** são cadastrados apenas pelo central. |
| RN04 | Uma **Funcionalidade** está ativa para um tenant quando está no **Plano** dele **e** está ligada pelo **Admin**. |
| RN05 | Desligar uma **Funcionalidade**, por escolha do admin ou por troca de plano, nunca apaga dados nem a preferência de ligado/desligado. |
| RN06 | Funcionalidades são liberadas exclusivamente pelo **Plano**. Não existe exceção por tenant. |
| RN07 | Na troca para um plano menor, as funcionalidades que saíram são desligadas imediatamente. |
| RN08 | Existem três **Tipos Base**: admin, usuário e cliente. Cada um corresponde a um **Painel**. |
| RN09 | Os três **Perfis de Sistema** existem em todo tenant e não podem ser editados nem excluídos. |
| RN10 | Um **Perfil Customizado** herda um **Tipo Base**, e é o tipo base que define o painel. |
| RN11 | Permissões formam um catálogo fixo. O tenant combina permissões, não cria novas. |
| RN12 | Uma pessoa tem exatamente um **Perfil** dentro do tenant. |
| RN13 | O central nunca acessa o ambiente nem os dados de um **Tenant**. |
| RN14 | **Cliente** é cadastrado apenas por **Usuário** do tenant. Não há autocadastro. |
| RN15 | O **Admin** inicial é criado pelo central com senha provisória, de uso único, que obriga a troca no primeiro acesso. |
| RN16 | **Tenant** suspenso opera em somente leitura: consulta sim, alteração não. |
| RN17 | Enquanto houver trava manual vigente, eventos de cobrança são registrados mas não alteram a situação do **Tenant**. |
| RN18 | Cada evento de cobrança produz efeito no máximo uma vez, mesmo se reenviado. |
| RN19 | Toda mudança manual de situação exige motivo. |
| RN20 | O **Slug** tem de 3 a 50 caracteres, só letras minúsculas, números e hífen, começa por letra, é único e não muda depois de criado. Ele dá nome ao banco do tenant. |
| RN21 | Todo **Tenant** tem ao menos um **Domínio**, e ao menos um deles aponta para o **Painel** admin. O domínio é o endereço completo, não pode ser um domínio do central nem repetir o de outro tenant. |
| RN22 | Um **Plano** oferece de um a três **Ciclos** (mensal, semestral, anual), cada um com seu preço. O **Tenant** contrata o plano em um ciclo que ele ofereça. |
| RN23 | **Plano** em uso por algum tenant não é excluído, apenas inativado. Plano inativo não é contratado por novos tenants, mas quem já está nele permanece. Um ciclo com tenants contratados não pode ser retirado do plano. |
| RN24 | A **Exclusão** de um tenant é lógica: o banco dele é mantido, o slug e o documento continuam reservados, seus domínios deixam de responder e ele pode ser restaurado. Não existe exclusão definitiva pela aplicação. |
| RN25 | O documento do tenant é um CPF ou CNPJ válido (inclusive CNPJ alfanumérico) e único entre os tenants. |
| RN26 | No central, super admin e admin gerenciam planos e tenants; manager e operator apenas consultam. |

## Pontos em aberto

Não foram decididos na entrevista. Cada um vira RN (ou item de "fora do escopo") quando houver resposta.

1. **Carência antes de suspender.** Quantos dias entre a falha de pagamento e a suspensão? Recomendação: 3 a 5 dias, com aviso no painel admin.
2. **Admin cadastra cliente?** A regra dita foi "cliente é cadastrado por usuário do tenant". O admin vê todos os clientes, mas não ficou dito se ele também cadastra e vincula.
3. **Tipo base de perfil customizado pode mudar depois de criado?** Recomendação: não, pois mudaria o painel de todos os usuários daquele perfil.
4. **Valor pago no downgrade.** Com o desligamento imediato, o tenant perde o que pagou até o fim do ciclo. Definir política (crédito, proporcional ou sem devolução).
5. **Cancelamento de assinatura.** Cancelado é igual a suspenso (somente leitura) ou há um estado final com prazo para retirada dos dados?
