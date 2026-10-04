# Contexto do problema

> Gate desta fase: nenhuma palavra de tecnologia neste arquivo.
> Trechos marcados com **[PREMISSA]** não foram ditos na entrevista. Confirmar ou corrigir.

## O problema

Todo produto novo vendido por assinatura para várias empresas exige reconstruir a mesma fundação antes de entregar qualquer valor de negócio: separar os dados de cada empresa contratante, dar a cada uma seu próprio endereço de acesso, controlar quem enxerga o quê, definir o que cada uma contratou e cobrar por isso.

Esse trabalho se repete a cada projeto, consome as primeiras semanas e, quando refeito às pressas, é onde aparecem os erros mais graves: uma empresa enxergando dados de outra.

## Quem sofre

- **Quem constrói os produtos.** **[PREMISSA]** Uma equipe pequena, com tempo limitado, que não pode gastar o início de cada projeto refazendo a fundação.
- **A empresa contratante.** Precisa confiar que seus dados não se misturam com os de outras, quer usar o próprio endereço e decidir quais recursos ficam ativos.
- **As pessoas dentro da empresa contratante.** Administradores, usuários e os clientes desses usuários precisam, cada um, de um ambiente próprio com apenas o que lhes cabe.

## Como é hoje

**[PREMISSA]** Cada projeto começa do zero ou de uma cópia adaptada do projeto anterior, levando junto decisões e defeitos antigos.

## Resultado esperado

Uma base única, pronta, sobre a qual qualquer produto novo começa já com isolamento entre empresas, endereços próprios, ambientes por tipo de pessoa, recursos ativáveis por plano e cobrança recorrente.

## Como saber que deu certo

**[PREMISSA]** Valores a confirmar:

| Métrica | Alvo |
|---|---|
| Tempo para iniciar um produto novo sobre a base, até a primeira empresa contratante acessando | até 1 dia de trabalho |
| Ocorrências de uma empresa acessando dados de outra | zero |
| Tempo entre cadastrar uma empresa contratante e ela conseguir acessar | menos de 1 minuto |
| Alterações na base necessárias para acomodar um produto novo | nenhuma |

## Restrições conhecidas

- A operadora da plataforma não acessa o ambiente das empresas contratantes.
- A liberação de recursos segue exclusivamente o plano contratado.
