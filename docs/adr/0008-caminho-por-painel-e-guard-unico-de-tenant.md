---
status: accepted
date: 2026-10-04
---

# ADR-0008 — Um caminho por painel de tenant, guard único e Livewire ciente do tenant

Os três painéis de tenant têm caminhos fixos e diferentes: admin em `/admin`, usuário em `/app` e cliente em `/portal`. O domínio da requisição diz qual painel vale; o caminho de outro painel, naquele domínio, responde 404. Os três painéis autenticam pelo mesmo guard, `tenant`, e quem decide a entrada é o tipo base da pessoa. A rota de atualização do Livewire passa a resolver o tenant antes de abrir a sessão. Decidimos assim porque os domínios vêm do banco e mudam a qualquer momento, então não é possível prender um domínio a um painel na configuração do Filament, que é o caminho que o framework oferece.

## Opções consideradas

- **Domínio fixo por painel na configuração.** É o recurso nativo do Filament, mas exige conhecer os domínios ao subir a aplicação.
- **Um único painel com o mesmo caminho para todos**, trocando menus conforme o tipo de pessoa. Contraria a ADR-0002 e mistura públicos no mesmo ambiente.
- **Um guard por painel.** Daria três sessões independentes, ao custo de três providers sobre a mesma tabela. Como cada domínio já tem sua própria sessão e uma pessoa só tem um tipo, não acrescenta isolamento.
- **Caminhos distintos, guard único (escolhida).**

## Consequências

- O endereço que o usuário vê inclui o caminho do painel (`cliente.com.br/portal`). A raiz redireciona para ele.
- A troca de model de autenticação em tempo de execução, que existia no `TenancyServiceProvider`, foi removida. O central usa o guard `web`; o tenant, o guard `tenant`.
- A sessão do tenant mora no banco do tenant. Por isso a resolução do domínio e a espera pelo provisionamento têm prioridade sobre o middleware de sessão, inclusive na rota do Livewire. Qualquer rota nova que rode em domínio de tenant precisa do mesmo cuidado; as de upload e pré-visualização de arquivo do Livewire ainda não têm.
- A resolução de domínio fica em cache por cinco minutos. Observers de domínio e de tenant limpam o cache quando algo muda, e por isso remoções passam pelo model, não por exclusão em massa.
