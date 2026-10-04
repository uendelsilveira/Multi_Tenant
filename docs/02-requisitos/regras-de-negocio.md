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

## Pontos em aberto

Não foram decididos na entrevista. Cada um vira RN (ou item de "fora do escopo") quando houver resposta.

1. **Carência antes de suspender.** Quantos dias entre a falha de pagamento e a suspensão? Recomendação: 3 a 5 dias, com aviso no painel admin.
2. **Admin cadastra cliente?** A regra dita foi "cliente é cadastrado por usuário do tenant". O admin vê todos os clientes, mas não ficou dito se ele também cadastra e vincula.
3. **Tipo base de perfil customizado pode mudar depois de criado?** Recomendação: não, pois mudaria o painel de todos os usuários daquele perfil.
4. **Valor pago no downgrade.** Com o desligamento imediato, o tenant perde o que pagou até o fim do ciclo. Definir política (crédito, proporcional ou sem devolução).
5. **Cancelamento de assinatura.** Cancelado é igual a suspenso (somente leitura) ou há um estado final com prazo para retirada dos dados?
