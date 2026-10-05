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
| RN10 | Um **Perfil Customizado** tem um **Tipo Base**, e é o tipo base que define o painel. O tipo pode ser trocado depois: as pessoas do perfil passam a entrar no outro painel e as permissões que não valem para o novo tipo são descartadas. |
| RN11 | **Permissões** formam um catálogo fixo, declarado na plataforma, e cada uma vale para um ou mais tipos base. O tenant combina permissões, não cria novas. |
| RN12 | Uma pessoa tem exatamente um **Perfil** dentro do tenant. |
| RN13 | O central nunca acessa o ambiente nem os dados de um **Tenant**. |
| RN14 | **Cliente** é cadastrado apenas por **Usuário** do tenant. O admin não cadastra, e não há autocadastro. |
| RN15 | O **Admin** inicial é criado pelo **Provisionamento**, com uma **Senha Provisória** gerada pela plataforma, gravada apenas como hash e enviada por e-mail. Ela obriga a troca no primeiro acesso. |
| RN16 | **Tenant** suspenso fica inteiramente bloqueado: ninguém entra, consulta ou altera nada. Os dados são preservados e o acesso volta na hora em que ele é reativado. Substitui a decisão inicial de somente leitura (ADR-0010). |
| RN17 | Enquanto houver **Trava Manual** vigente, a cobrança automática não altera a situação do **Tenant**. O central continua podendo alterá-la, e a trava deixa de valer sozinha quando a data passa. |
| RN18 | Cada evento de cobrança produz efeito no máximo uma vez, mesmo se reenviado. |
| RN19 | Toda mudança manual de situação exige motivo. O motivo é interno: fica no histórico e não é mostrado ao tenant, que vê apenas um aviso genérico. |
| RN20 | O **Slug** tem de 3 a 50 caracteres, só letras minúsculas, números e hífen, começa por letra, é único e não muda depois de criado. Ele dá nome ao banco do tenant. |
| RN21 | Todo **Tenant** tem ao menos um **Domínio**, e ao menos um deles aponta para o **Painel** admin. O domínio é o endereço completo, não pode ser um domínio do central nem repetir o de outro tenant. |
| RN22 | Um **Plano** oferece de um a três **Ciclos** (mensal, semestral, anual), cada um com seu preço. O **Tenant** contrata o plano em um ciclo que ele ofereça. |
| RN23 | **Plano** em uso por algum tenant não é excluído, apenas inativado. Plano inativo não é contratado por novos tenants, mas quem já está nele permanece. Um ciclo com tenants contratados não pode ser retirado do plano. |
| RN24 | A **Exclusão** de um tenant é lógica: o banco dele é mantido, o slug e o documento continuam reservados, seus domínios deixam de responder e ele pode ser restaurado. Não existe exclusão definitiva pela aplicação. |
| RN25 | O documento do tenant é um CPF ou CNPJ válido (inclusive CNPJ alfanumérico) e único entre os tenants. |
| RN26 | No central, super admin e admin gerenciam planos e tenants; manager e operator apenas consultam. |
| RN27 | O **Admin** inicial usa o nome do responsável e o e-mail de contato cadastrados no **Tenant**. Sem esses dois dados não há provisionamento. |
| RN28 | A **Senha Provisória** vale 24 horas. O central pode mandar reenviar apenas enquanto o admin não fez o primeiro acesso; a nova senha invalida a anterior. Depois do primeiro acesso, o central não altera mais a conta. |
| RN29 | O **Provisionamento** pode ser repetido sem efeito colateral: cada etapa confere se já foi feita, não cria um segundo admin nem envia outra senha. Qualquer tenant que não esteja pronto pode ser provisionado novamente. |
| RN30 | Enquanto o ambiente do **Tenant** não está pronto, seus domínios respondem com uma página de espera, e não com erro. |
| RN31 | Todo **Domínio** passa pela verificação manual, inclusive subdomínio da própria plataforma. Trocar o endereço de um domínio cria um domínio novo, pendente. Trocar só o painel mantém a verificação. |
| RN32 | Cada **Painel** de tenant tem um caminho fixo dentro do domínio que aponta para ele: admin em `/admin`, usuário em `/app`, cliente em `/portal`. A raiz do domínio leva a esse caminho. |
| RN33 | O **Tipo Base** de uma pessoa do tenant é o do perfil dela. Pessoa desativada, sem perfil ou com perfil de tipo não reconhecido não entra em painel nenhum. |
| RN34 | Excluir um tenant, remover um domínio ou trocar seu endereço fecha o acesso na hora, sem esperar a expiração de cache. |
| RN35 | Um **Perfil de Sistema** tem sempre todas as permissões do seu tipo base. Um **Perfil Customizado** tem só as marcadas. |
| RN36 | O tenant nunca fica sem uma pessoa ativa que possa gerenciar pessoas. Toda alteração que levaria a isso é recusada: desativar a pessoa, trocar o perfil dela, tirar a permissão do perfil ou trocar o tipo do perfil. Ninguém desativa a própria conta. |
| RN37 | Pessoa não é excluída, só desativada, e pode ser reativada. Perfil com pessoas vinculadas não é excluído. |
| RN38 | Pessoa nova nasce sem senha utilizável. O acesso vem por **Senha Provisória** de 24 horas, enviada ao e-mail dela, com troca obrigatória. O e-mail é único entre as pessoas do tenant. |
| RN39 | Uma **Funcionalidade** incluída no plano nasce desligada: o admin liga o que a empresa vai usar. Uma funcionalidade fora do plano não aparece para o tenant nem pode ser ligada. |
| RN40 | Ligar e desligar funcionalidades é uma **Permissão** do tipo admin. O perfil de sistema Admin a tem; um perfil customizado pode não ter. |
| RN41 | Todo **Cliente** recebe acesso ao portal: no cadastro sai uma **Senha Provisória** para o e-mail dele, com as mesmas regras das demais pessoas. |
| RN42 | O e-mail é único entre todas as pessoas do tenant, clientes inclusive. Cadastrar um e-mail que já existe é recusado, com a orientação de pedir o vínculo ao admin. |
| RN43 | Todo **Cliente** tem pelo menos um **Usuário** responsável, e só usuário ativo pode ser responsável. O cliente nasce vinculado a quem o cadastrou; depois, só quem gerencia todos os clientes altera os vínculos. |
| RN44 | Cliente não é excluído, só desativado, por quem gerencia todos os clientes. Cliente fica fora da gestão de pessoas: não aparece nela nem é alcançado por suas ações. |
| RN45 | A situação do tenant só é alterada por um único ponto do sistema, que sempre registra a mudança no histórico. O histórico só cresce: nenhuma linha é editada ou apagada. |
| RN46 | A suspensão não afeta o painel central, e é independente da situação do ambiente: um tenant pode estar provisionado e suspenso. |

## Pontos em aberto

Não foram decididos na entrevista. Cada um vira RN (ou item de "fora do escopo") quando houver resposta.

1. **Carência antes de suspender.** Quantos dias entre a falha de pagamento e a suspensão? Recomendação: 3 a 5 dias, com aviso no painel admin.
2. ~~Admin cadastra cliente?~~ Decidido: não. Só usuário cadastra; o admin vê todos, gerencia os vínculos e desativa (RN14, RN43, RN44).
3. ~~Tipo base de perfil customizado pode mudar depois de criado?~~ Decidido: pode mudar sempre (RN10), respeitada a RN36.
4. **Valor pago no downgrade.** Com o desligamento imediato, o tenant perde o que pagou até o fim do ciclo. Definir política (crédito, proporcional ou sem devolução).
5. **Cancelamento de assinatura.** Cancelado é igual a suspenso (somente leitura) ou há um estado final com prazo para retirada dos dados?
