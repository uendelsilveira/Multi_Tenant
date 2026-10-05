# Escopo

## Dentro do escopo (primeira versão)

- Cadastro de tenants, planos e domínios pelo painel central.
- Resolução de tenant e painel pelo domínio da requisição.
- Validação manual de domínio pelo central.
- Provisionamento do tenant com admin inicial e senha provisória.
- Quatro painéis: central, admin do tenant, usuário do tenant, cliente.
- Perfis base fixos e perfis customizados pelo tenant.
- Catálogo de funcionalidades, liberação por plano e tela de liga/desliga no painel admin.
- Cadastro de clientes pelo usuário do tenant, com vínculo N:N.
- Assinatura com cobrança automática por Asaas e Stripe, e ajuste manual pelo central.
- Suspensão com bloqueio total do tenant.

## Fora do escopo

> Gate desta fase: esta lista não pode ficar vazia.
> Os itens 1 a 5 decorrem de decisões já tomadas. Os itens 6 a 10 são **proposta**, a confirmar.

| # | Item | Origem |
|---|---|---|
| 1 | Cadastro self-service de tenant | Decidido: o central cria o tenant e o admin |
| 2 | Acesso do central ao ambiente do tenant (impersonação) | Decidido: ADR-0004 |
| 3 | Autocadastro de cliente | Decidido: só o usuário do tenant cadastra |
| 4 | Validação automática de DNS | Decidido: validação manual |
| 5 | Liberação de funcionalidade fora do plano (exceção por tenant) | Decidido: ADR-0005 |
| 6 | Módulos de negócio (ex.: monitoramento, helpdesk) | Proposta: a plataforma é a base genérica; módulos são projetos sobre ela |
| 7 | Usuário com mais de um perfil | Proposta |
| 8 | API pública para terceiros | Proposta |
| 9 | Aplicativo mobile | Proposta |
| 10 | Multi-idioma | Proposta |
