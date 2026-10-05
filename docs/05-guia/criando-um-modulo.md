# Como criar um módulo sobre a base

Um módulo é uma parte do seu produto: chamados, agenda, estoque, relatórios. A base não traz nenhum; ela traz os encaixes para que um módulo novo já nasça isolado por tenant, liberado por plano, protegido por permissão e bloqueado na suspensão.

Este guia usa como exemplo um módulo de chamados, com a funcionalidade `helpdesk.tickets`.

## Antes de escrever código

Um módulo é uma fatia vertical nova, e segue as mesmas fases do restante do projeto:

1. Escreva os requisitos em `docs/02-requisitos/requisitos-funcionais.md` (próximo `RF` livre) e as regras em `regras-de-negocio.md` (próximo `RN` livre). IDs nunca são reutilizados.
2. Acrescente os termos novos ao `CONTEXT.md`. O nome que estiver lá é o nome que vai para o código.
3. Se a decisão for difícil de reverter, registre uma ADR em `docs/adr`.

## 1. Declare a funcionalidade

Em `config/features.php`:

```php
'catalog' => [
    ['key' => 'helpdesk.tickets', 'name' => 'Chamados', 'module' => 'Helpdesk'],
],
```

Sincronize com o banco central e inclua a funcionalidade em um plano, pelo painel central:

```bash
./vendor/bin/sail artisan features:sync
```

A partir daí ela aparece, desligada, na tela Funcionalidades do admin de cada tenant daquele plano.

## 2. Declare as permissões

Em `config/permissions.php`. Cada permissão diz em quais tipos de perfil ela existe:

```php
[
    'key' => 'tickets.manage',
    'name' => 'Gerenciar chamados',
    'group' => 'Helpdesk',
    'base_types' => ['admin', 'user'],
],
[
    'key' => 'tickets.open',
    'name' => 'Abrir chamados',
    'group' => 'Helpdesk',
    'base_types' => ['customer'],
],
```

Não há migração: a permissão passa a valer em todos os tenants no deploy. Os perfis de sistema ganham na hora todas as permissões do seu tipo; os customizados, só as que o admin marcar.

## 3. Crie as tabelas no banco do tenant

Migrations de módulo ficam em `database/migrations/tenant`, e rodam em todos os tenants:

```bash
./vendor/bin/sail artisan make:migration create_tickets_table --path=database/migrations/tenant
./vendor/bin/sail artisan tenants:migrate
```

O model é um model comum. Dentro de um tenant a conexão padrão já é a do banco dele:

```php
final class Ticket extends Model
{
    protected $fillable = ['subject', 'customer_id', 'status'];
}
```

Só models do banco central usam o trait `CentralConnection`.

## 4. Escreva o caso de uso em camadas

O fluxo é sempre o mesmo, e cada camada tem um papel só:

| Camada | Papel | Não faz |
|---|---|---|
| DTO (`app/DTOs`) | Carrega os dados de entrada | Regra |
| Action (`app/Actions`) | Orquestra: chama o Service, emite evento, registra log | Regra, acesso a dados |
| Service (`app/Services`) | Regra de negócio; lança exceção de domínio | Consulta direta ao banco |
| Repository (`app/Repositories`) | Acesso a dados, atrás de interface; dono da transação | Regra |

```php
final class OpenTicketAction extends BaseAction
{
    public function __construct(private readonly TicketService $service) {}

    public function execute(OpenTicketDTO $dto, TenantUser $actor): Ticket
    {
        $ticket = $this->service->open($dto, $actor);

        event(new TicketOpened($ticket->id));

        return $ticket;
    }
}
```

Registre a interface do repositório em `AppServiceProvider::$bindings`. Exceções de regra estendem `App\Exceptions\DomainException`; a mensagem delas é mostrada ao usuário, então escreva-a para ele.

## 5. Crie as telas no painel certo

Cada painel de tenant descobre sozinho o que estiver na pasta dele:

| Painel | Pasta | Caminho |
|---|---|---|
| Admin | `app/Filament/Tenant/Admin` | `/admin` |
| Usuário | `app/Filament/Tenant/User` | `/app` |
| Cliente | `app/Filament/Tenant/Customer` | `/portal` |

Use o trait `RequiresFeature`: com a funcionalidade desligada ou fora do plano, a tela some do menu e não abre.

```php
final class TicketResource extends Resource
{
    use RequiresFeature;

    protected static ?string $model = Ticket::class;

    protected static function requiredFeature(): string
    {
        return 'helpdesk.tickets';
    }
}
```

A autorização vai em uma Policy, que pergunta ao `TenantAccessService`:

```php
final class TicketPolicy
{
    public function __construct(private readonly TenantAccessService $access) {}

    public function viewAny(TenantUser $actor): bool
    {
        return $this->access->allows($actor, 'tickets.manage');
    }
}
```

As páginas não gravam nada sozinhas. Sobrescreva `handleRecordCreation` e `handleRecordUpdate` para montar o DTO e chamar a Action, como fazem `CreateCustomer` e `EditCustomer`. Exceção de domínio vira notificação na tela.

## 6. Rotas fora do Filament

Uma rota de tenant precisa resolver o tenant antes de tudo, e pode exigir a funcionalidade:

```php
Route::middleware([
    'web',
    InitializeTenancyForTenantDomain::class,
    PreventAccessFromCentralDomains::class,
    EnsureTenantIsProvisioned::class,
    EnsureTenantIsNotSuspended::class,
    EnsureFeatureIsActive::class.':helpdesk.tickets',
])->group(function () {
    // ...
});
```

## 7. Jobs

Job é disparado por Listener, nunca direto pela Action. Um job despachado de dentro de um tenant roda no mesmo tenant. Declare os dois middlewares para que ele não execute com a funcionalidade inativa ou o tenant suspenso, inclusive se já estava na fila quando isso mudou:

```php
public function middleware(): array
{
    return [
        new SkipWhenTenantIsSuspended,
        new SkipWhenFeatureIsInactive('helpdesk.tickets'),
    ];
}
```

## 8. Testes

- **Unitários** para Service e Action, com os repositórios em mock. Veja `tests/Unit/Services/CustomerServiceTest.php`.
- **Com banco real** para telas e repositórios de tenant: o teste cadastra um tenant, que é provisionado de verdade, e remove tudo no fim. Veja `tests/Feature/Tenancy/TenantCustomersTest.php` e marque a classe com `#[Group('real-database')]`.
- Nenhum teste chama um gateway: a base `tests/TestCase.php` bloqueia requisições externas.

A meta do projeto é 80% de cobertura em Services e Actions.

## 9. Feche a fatia

- Preencha a linha do requisito em `docs/02-requisitos/rastreabilidade.md`, com as classes e os testes.
- Rode `./vendor/bin/sail pint`, `./vendor/bin/sail composer phpstan` e a suíte de testes.
- Quando este for o primeiro módulo a usar `RequiresFeature`, remova a exceção `trait.unused` de `phpstan.neon`.
- Acrescente a entrega ao `CHANGELOG.md`.

## O que você não precisa fazer

- **Isolar dados por tenant.** Cada tenant tem o próprio banco; não existe coluna `tenant_id` nem filtro a lembrar.
- **Bloquear telas na suspensão.** O bloqueio acontece antes do login, para todos os painéis.
- **Controlar o plano.** Basta usar o trait, o middleware de rota e o middleware de job; a troca de plano vale na hora.
