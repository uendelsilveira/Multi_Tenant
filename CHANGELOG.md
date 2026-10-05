# Changelog

Todas as mudanças relevantes deste projeto ficam registradas aqui.
O formato segue o [Keep a Changelog](https://keepachangelog.com/pt-BR/1.1.0/) e o projeto usa [versionamento semântico](https://semver.org/lang/pt-BR/).

## [1.0.0-beta.1] - 2026-10-05

Primeira versão pública da base. As oito fatias do plano de construção estão implementadas, com 221 testes e 94,6% de cobertura. É beta porque a cobrança ainda não foi validada contra os gateways reais.

### Adicionado

- **Central:** cadastro de planos com preço por ciclo (mensal, semestral, anual) e funcionalidades; cadastro de tenants com slug imutável, dados cadastrais, plano, ciclo, gateway e domínios; exclusão lógica e restauração de tenant.
- **Provisionamento:** criação do banco do tenant em fila, com acompanhamento da situação do ambiente e nova tentativa; admin inicial com senha provisória de 24 horas e troca obrigatória no primeiro acesso.
- **Domínios e painéis:** resolução de tenant e painel pelo domínio; três painéis de tenant (admin, usuário, cliente), cada um no seu caminho; verificação manual de domínio com teste de DNS; guard de autenticação próprio do tenant.
- **Pessoas e perfis:** cadastro, desativação e reativação de pessoas; perfis de sistema e perfis customizados por tipo base; catálogo de permissões em configuração; garantia de que o tenant nunca fica sem gestor de pessoas.
- **Funcionalidades:** liberação por plano e liga/desliga pelo admin do tenant; troca de plano com efeito imediato; middleware de rota, trait para o Filament e middleware de job para os módulos respeitarem a funcionalidade.
- **Clientes:** cadastro pelo usuário, vínculo N:N com os usuários responsáveis, gestão dos vínculos pelo admin e acesso ao portal por senha provisória.
- **Situação:** alteração manual com motivo e trava por prazo; histórico de mudanças; bloqueio total do tenant suspenso.
- **Cobrança:** criação da assinatura no Asaas ou no Stripe no cadastro; webhooks com conferência de origem e efeito único; carência de 10 dias; suspensão e reativação automáticas; listagem dos eventos recebidos.
- **Documentação:** requisitos, regras de negócio, matriz de rastreabilidade, modelagem, arquitetura, dez ADRs, glossário, guia de criação de módulo e runbook.

### Limitações conhecidas

- As integrações com Asaas e Stripe foram testadas apenas contra respostas simuladas.
- Não há módulos de negócio; o catálogo de funcionalidades vem vazio.
- A emissão automática de certificado para domínios próprios não está configurada.
- A lista completa está em `docs/04-arquitetura/estado-atual.md`.

[1.0.0-beta.1]: https://github.com/uendelsilveira/Multi_Tenant/releases/tag/v1.0.0-beta.1
