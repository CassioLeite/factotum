# Plano 002 — Gestão de obrigações

| | |
| --- | --- |
| Spec | [docs/specs/002-obligation-management.md](../specs/002-obligation-management.md) |
| Estado | Plano de implementação; código ainda não iniciado |
| Data | 2026-11-23 |

Este plano descreve como implementar a spec 002 sobre a Fase 1 já existente. Não instala dependências e não cria histórico de mensagens.

## 1. Base atual

A Fase 1 já entregou:

- `Contact`, `ContactRepository`, `ContactService`, `ContactController` e as views de contatos.
- `ContactService::delete()` apaga o contato sem consultar obrigações. `ContactRepository::delete()` chama `$contact->delete()`.
- `routes/web.php` registra `contacts` e `GET /modules/{module}` com `obligations`, `messages`, `templates` e `settings`.
- A navegação e o cartão da home apontam Obrigações para `modules.show` com o slug `obligations`. A página diz que o módulo será implementado numa próxima etapa.
- `partials/flash.blade.php` só renderiza `session('success')`.
- `phpunit.xml` já usa SQLite em memória. Os testes de feature usam `RefreshDatabase`.
- Não existe model, migration nem rota de obrigação. Não existe tabela de mensagens.

A implementação usa só o que já está instalado. Nenhum `composer require` e nenhum `npm install` de pacote novo.

## 2. Decisões de implementação

- Fluxo das obrigações: `ObligationController` → `ObligationService` → `ObligationRepository` → `Obligation`. Classes concretas. Sem interface, binding novo, DTO, Action ou Clean Architecture.
- O bloqueio de exclusão do contato fica em `ContactService`. O service passa a receber também `ObligationRepository` e consulta `existsFor(Contact)` antes de apagar. O controller só traduz o retorno em redirect e flash.
- `payment_status` é um backed enum, no mesmo estilo de `ContactStatus`. Entra no formulário e numa coluna discreta da lista. Não há soma, filtro financeiro nem data de pagamento.
- Mensagem e Envios no mês são texto literal nas views. Nenhum accessor, coluna, cache ou consulta a uma tabela inexistente. As duas exibem "Sem envios".
- Não criar `Message`, migration `messages`, enum de tipo de lembrete, job, fila ou cliente de WhatsApp. O contrato futuro fica só nesta spec e na seção 10 deste plano.
- A foreign key usa `restrictOnDelete()`. O service recusa antes; a chave cobre uma exclusão que fuja do service.
- Busca e paginação repetem o desenho de contatos: 10 por página, `q` preservado, escape de `%`, `_` e `\` com `ESCAPE '\'` no SQLite.
- Ordenação da lista: `due_on` crescente, depois `id`. Vencimento passado permanece visível.
- Seeders de obrigação, contato e usuário de teste retornam sem gravar fora de `local` e `testing`.
- `ContactService::delete()` passa a devolver `bool`. `true` significa que apagou. `false` significa que havia obrigação e nada foi apagado.

## 3. Arquivos

### Criar

| Arquivo | Responsabilidade |
| --- | --- |
| `database/migrations/*_create_obligations_table.php` | tabela `obligations` |
| `app/Enums/ObligationPaymentStatus.php` | `pending`, `paid`, `cancelled`, com `label()` |
| `app/Models/Obligation.php` | Eloquent, fillable, casts, `contact()` |
| `database/factories/ObligationFactory.php` | dados de teste e seed |
| `database/seeders/ObligationSeeder.php` | exemplos só em `local` e `testing` |
| `app/Repositories/ObligationRepository.php` | paginação, existência por contato, create, update, delete |
| `app/Services/ObligationService.php` | delegação e normalização de texto |
| `app/Http/Requests/StoreObligationRequest.php` | validação de cadastro |
| `app/Http/Requests/UpdateObligationRequest.php` | validação de edição, herdando o store |
| `app/Http/Controllers/ObligationController.php` | CRUD HTTP |
| `resources/views/obligations/index.blade.php` | lista, busca, colunas provisórias |
| `resources/views/obligations/create.blade.php` | cadastro |
| `resources/views/obligations/edit.blade.php` | edição |
| `resources/views/obligations/show.blade.php` | detalhe |
| `resources/views/obligations/_form.blade.php` | campos compartilhados |
| `tests/Feature/ObligationTest.php` | CRUD, indicadores, bloqueio de contato, seeder |

### Alterar

| Arquivo | Mudança |
| --- | --- |
| `app/Models/Contact.php` | relação `obligations()` |
| `app/Services/ContactService.php` | recebe `ObligationRepository` e recusa o delete quando houver obrigação |
| `app/Http/Controllers/ContactController.php` | flash de erro quando `delete()` devolve `false` |
| `resources/views/partials/flash.blade.php` | faixa para `session('error')` |
| `resources/views/partials/nav.blade.php` | Obrigações aponta para `obligations.index` e ativa em `obligations.*` |
| `resources/views/home.blade.php` | cartão Obrigações funcional; os outros três módulos seguem na espera |
| `app/Http/Controllers/ModulePlaceholderController.php` | remove `obligations` do mapa |
| `routes/web.php` | resource `obligations`; `whereIn` sem `obligations` |
| `database/seeders/DatabaseSeeder.php` | chama `ObligationSeeder` depois de `ContactSeeder`, ainda só em `local` e `testing` |
| `tests/Feature/NavigationTest.php` | Obrigações deixa de ser página de espera |
| `tests/Feature/ContactTest.php` | cobre a exclusão bloqueada e a exclusão liberada depois da última obrigação |
| `tests/Unit/ContactServiceTest.php` | cobre o `false` do delete quando existe obrigação |

### Não criar

`app/Models/Message.php`, migration de `messages`, enum de lembrete, controller de envio, jobs, eventos e rotas de disparo.

### Não mexer

`phpunit.xml`, `config/database.php`, `vite.config.js`, `resources/css/app.css`, `resources/js/app.js`, autenticação e a regra de telefone dos contatos.

## 4. Migration

Gerar com:

```bash
php artisan make:migration create_obligations_table
```

`up()`:

```php
Schema::create('obligations', function (Blueprint $table) {
    $table->id();
    $table->foreignId('contact_id')->constrained()->restrictOnDelete();
    $table->string('title');
    $table->text('notes')->nullable();
    $table->date('due_on');
    $table->string('payment_status')->default('pending')->index();
    $table->timestamps();

    $table->index('due_on');
});
```

`down()` remove `obligations`.

Não acrescentar `message_sent`, `messages_count`, `last_sent_at`, `monthly_send_count` nem qualquer coluna equivalente. `payment_status` é `string` para permanecer simples no SQLite. A restrição dos três valores fica no enum e no Form Request. Sem soft delete.

## 5. Enum, model e factory

`ObligationPaymentStatus`:

```php
enum ObligationPaymentStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendente',
            self::Paid => 'Pago',
            self::Cancelled => 'Cancelada',
        };
    }
}
```

`Obligation`, no estilo de `Contact`:

```php
#[Fillable(['contact_id', 'title', 'notes', 'due_on', 'payment_status'])]
class Obligation extends Model
{
    /** @use HasFactory<ObligationFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'due_on' => 'date',
            'payment_status' => ObligationPaymentStatus::class,
        ];
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}
```

`Contact::obligations()` devolve `hasMany(Obligation::class)`. A relação serve ao Eloquent e ao `existsFor`. A listagem de obrigações continua no `ObligationRepository`.

Factory:

- `contact_id`: `Contact::factory()`
- `title`: frase curta do faker
- `notes`: `fake()->optional()->sentence()`
- `due_on`: `fake()->date()`
- `payment_status`: `ObligationPaymentStatus::Pending`

Estados `paid()` e `cancelled()` alteram só `payment_status`.

`ObligationSeeder::run()` retorna imediatamente quando o ambiente não é `local` nem `testing`. Nos ambientes permitidos, cria 8 obrigações pela factory. `DatabaseSeeder` chama `ContactSeeder` e depois `ObligationSeeder` somente dentro do mesmo `if` de `local`/`testing` que já protege o usuário de teste. Cada seeder mantém a própria trava.

## 6. Repository e service

`ObligationRepository`, sem classe base.

```php
public function paginate(?string $search): LengthAwarePaginator
public function existsFor(Contact $contact): bool
public function create(array $attributes): Obligation
public function update(Obligation $obligation, array $attributes): Obligation
public function delete(Obligation $obligation): void
```

Constante `PER_PAGE = 10`.

`paginate` faz `with('contact')`, agrupa a busca quando `$search` não é vazio e escapa o termo como o repositório de contatos. O grupo busca `title`, `notes` e, por `whereHas('contact')`, o `name` do contato. `orderBy('due_on')->orderBy('id')->paginate(self::PER_PAGE)->withQueryString()`.

`existsFor` é `Obligation::query()->where('contact_id', $contact->id)->exists()`. Não conta mensagens.

`ObligationService` recebe só `ObligationRepository`. `create` e `update` passam por `attributes()`, que devolve `contact_id`, `title`, `notes` nulo quando vazio, `due_on` e `payment_status`. Nenhuma chave de envio entra nesse array.

`ContactService` fica assim no delete:

```php
public function __construct(
    private ContactRepository $contacts,
    private ObligationRepository $obligations,
) {}

public function delete(Contact $contact): bool
{
    if ($this->obligations->existsFor($contact)) {
        return false;
    }

    $this->contacts->delete($contact);

    return true;
}
```

O restante de `ContactService` não muda. Não há exceção de domínio nem DTO de resultado.

## 7. Form Requests

`StoreObligationRequest`:

- `authorize(): true`
- `prepareForValidation()` faz trim de `title`. `notes` vazio vira `null`. Não altera `payment_status` nem `due_on`.
- Regras:

```php
return [
    'contact_id' => ['required', 'integer', Rule::exists('contacts', 'id')],
    'title' => ['required', 'string', 'max:160'],
    'notes' => ['nullable', 'string', 'max:2000'],
    'due_on' => ['required', 'date'],
    'payment_status' => ['required', Rule::enum(ObligationPaymentStatus::class)],
];
```

`attributes()`: contato, título, observação, vencimento, pagamento.

`messages()` em português, no mesmo critério da Fase 1. A mensagem do enum usa a chave da classe `Illuminate\Validation\Rules\Enum`. Texto sugerido do pagamento: "Selecione um pagamento válido." O contato inexistente usa "Selecione um contato válido."

`UpdateObligationRequest extends StoreObligationRequest` e não duplica regras. Falha de validação redireciona de volta com erros e input na sessão. O teste não espera 422.

O request não declara campo de mensagem, contagem ou data de envio.

## 8. Rotas e controllers

Acrescentar o resource e tirar `obligations` do placeholder:

```php
Route::resource('obligations', ObligationController::class);

Route::get('/modules/{module}', ModulePlaceholderController::class)
    ->whereIn('module', ['messages', 'templates', 'settings'])
    ->name('modules.show');
```

O resource fica antes da rota curinga. Nomes: `obligations.index`, `create`, `store`, `show`, `edit`, `update`, `destroy`.

`ModulePlaceholderController` fica só com Mensagens, Modelos e Configurações. Slug ausente do mapa continua em 404.

`ObligationController` recebe `ObligationService`.

| Método | Comportamento |
| --- | --- |
| `index` | normaliza `q` como em contatos, no máximo 100 caracteres; string vazia vira `null`; devolve a página |
| `create` | view com os contatos ordenados por nome, para o `<select>` |
| `store` | `create($request->validated())`, redirect com "Obrigação cadastrada." |
| `show` | detalhe |
| `edit` | formulário com os contatos ordenados por nome |
| `update` | redirect com "Obrigação atualizada." |
| `destroy` | redirect com "Obrigação excluída." |

A lista de contatos do formulário vem de `Contact::query()->orderBy('name')->orderBy('id')->get(['id', 'name'])` no controller. É uma leitura auxiliar do formulário, não uma segunda listagem paginada. Não criar método de página no `ContactRepository` para isso.

`ContactController::destroy` fica:

```php
if (! $this->contacts->delete($contact)) {
    return back()->with('error', 'Este contato possui obrigações e não pode ser excluído.');
}

return to_route('contacts.index')->with('success', 'Contato excluído.');
```

Redirects de sucesso usam `to_route(...)`. Nenhum `validate()` no controller.

## 9. Views

Navegação: o link Obrigações usa `route('obligations.index')`. A condição ativa é `request()->routeIs('obligations.*')`.

Home: o cartão Obrigações sai do loop de espera. Título "Obrigações", texto "Cadastre vencimentos ligados aos contatos." e botão "Gerenciar obrigações" para `obligations.index`. Mensagens, Modelos e Configurações permanecem no loop atual, com a frase de espera e `modules.show`.

Flash: manter a faixa verde de sucesso e acrescentar, quando houver `session('error')`, uma faixa `rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800` com `role="alert"`.

`obligations/index.blade.php`:

- Cabeçalho "Obrigações" e link "Nova obrigação".
- Busca `GET` com `q`, rótulo "Buscar" e botão "Buscar".
- Nota única, visível com ou sem registros: "Os envios ainda não são registrados."
- Vazio sem busca: "Nenhuma obrigação cadastrada." e link para criar.
- Vazio com busca: "Nenhuma obrigação encontrada."
- Tabela a partir de `md` e cartões abaixo de `md`.
- Colunas, nesta ordem: Contato, Obrigação, Vencimento, Mensagem, Envios no mês, Pagamento, Ações.
- Contato: `$obligation->contact->name`. Obrigação: `title`. Vencimento: `$obligation->due_on->format('d/m/Y')`.
- Mensagem e Envios no mês: o literal `Sem envios` nas duas células. Não usar número e não consultar relação de mensagens.
- Pagamento: `$obligation->payment_status->label()` num badge `bg-slate-100 text-slate-600`, igual para os três status.
- A wrapper da tabela usa `overflow-x-auto` para as colunas não serem cortadas.
- Ações: Ver, Editar e formulário `DELETE` com a confirmação "Excluir esta obrigação? Esta ação não pode ser desfeita."
- `{{ $obligations->links() }}` quando `hasPages()`.

`_form.blade.php`:

- `<select name="contact_id">` com opção vazia "Selecione" e um `option` por contato. A seleção usa `old('contact_id', $obligation?->contact_id)`.
- Título, observação (`textarea`), vencimento (`input type="date"`) e pagamento.
- O pagamento percorre `ObligationPaymentStatus::cases()`. No cadastro, o selecionado inicial é `pending`.
- O pagamento vem depois do vencimento.
- Erros, resumo, botão `Cadastrar` ou `Salvar` e link Cancelar de volta à listagem.
- O formulário usa `novalidate`.

`show.blade.php` lista os mesmos dados, com "Sem envios" em Mensagem e em Envios no mês, a observação ou "—", o pagamento pelo rótulo e `updated_at` em `d/m/Y H:i`. Ações: Editar, excluir e voltar à lista. Não incluir botão Reenviar nem Enviar.

## 10. Contrato futuro que esta fase não implementa

Quando o módulo de mensagens for implementado, a lista deixa de usar o literal e passa a calcular:

- Mensagem: mensagens com `obligation_id` da linha. Conjunto vazio continua "Sem envios". Outro rótulo só nasce numa spec de mensagens.
- Envios no mês: `count` das mensagens do `contact_id` com `sent_at` no mês corrente do timezone da aplicação. Entram mensagens com `obligation_id` preenchido e mensagens com `obligation_id` nulo.

O reenvio manual cria outra linha de origem `manual`, mesmo que já exista automático ou manual daquela obrigação e daquele `reminder_type`. O automático, antes de gravar, consulta se já existe origem `automatic` para a mesma obrigação e o mesmo tipo (`before_due`, `on_due`, `overdue`). Se existir, não grava outro automático. Não criar índice único em `(obligation_id, reminder_type)`, porque ele também bloquearia o reenvio manual.

Nada disso vira migration, classe ou botão agora.

## 11. Ordem de implementação

1. Migration, enum, model, relação em `Contact` e factory. Rodar `php artisan migrate`.
2. Repository e `ObligationService`.
3. Ajuste de `ContactService::delete()` e do `ContactController`.
4. Form Requests.
5. Controller, rotas e placeholder.
6. Views, navegação, home e flash.
7. Seeder.
8. Testes da seção 12 e ajuste dos testes da Fase 1 que mudam de expectativa.
9. `vendor/bin/pint --dirty`.
10. `php artisan test --compact`.

Comandos de geração:

```bash
php artisan make:migration create_obligations_table
php artisan make:enum ObligationPaymentStatus
php artisan make:model Obligation -f
php artisan make:seeder ObligationSeeder
php artisan make:class Repositories/ObligationRepository
php artisan make:class Services/ObligationService
php artisan make:request StoreObligationRequest
php artisan make:request UpdateObligationRequest
php artisan make:controller ObligationController --resource
php artisan make:test ObligationTest
```

## 12. Testes

Os testes novos usam `RefreshDatabase`. O `ContactServiceTest` continua estendendo `Tests\TestCase`.

### `tests/Feature/ObligationTest.php`

| Teste | Asserções |
| --- | --- |
| Lista ordenada e paginada | 11 obrigações com vencimentos controlados; a página 1 não mostra o vencimento exclusivo da página 2; a página 2 existe |
| Colunas provisórias | a lista vê "Mensagem", "Envios no mês" e "Sem envios"; não vê "Reenviar", "Não enviado" nem "Enviada" |
| Paga e cancelada | as duas linhas também vêem "Sem envios"; vêem "Pago" e "Cancelada" |
| Busca | título, observação e nome do contato filtram; o outro registro não aparece |
| Vazios | sem dados, "Nenhuma obrigação cadastrada"; com `q` sem resultado, "Nenhuma obrigação encontrada" |
| Curinga | nome e título sem `%`; `q=100%` responde 200 e não lista o registro |
| Cadastro válido | contato, título "IPTU", vencimento passado, observação vazia, `pending`; redirect; flash "Obrigação cadastrada."; banco com `notes` nulo e `payment_status = pending` |
| Cadastro inválido | `from(route('obligations.create'))`; sem título, `contact_id` inexistente, data inválida, status `overdue`; redirect de volta; erros em `title`, `contact_id`, `due_on`, `payment_status`; input preservado; nenhum registro |
| Detalhe | vê o título e "Sem envios"; não vê "Reenviar"; id inexistente é 404 |
| Exclusão da obrigação | confirmado pelo texto na página; `delete` redireciona; flash "Obrigação excluída."; some só a obrigação |
| Contato bloqueado | contato com obrigação `paid` ou `cancelled`; `delete` do contato redireciona de volta; flash de erro exato; contato e obrigação permanecem |
| Contato liberado | apagar a última obrigação e então apagar o contato; o contato sai do banco |
| Seeder | em `production`, `ObligationSeeder` e `DatabaseSeeder` não criam obrigação nem usuário; em `local` e em `testing`, `ObligationSeeder` cria 8 |

O fluxo inválido usa `assertRedirect`, `assertSessionHasErrors` e `assertSessionHasInput`. Não usar `assertStatus(422)`.

### Ajustes da Fase 1

- `NavigationTest`: a home vê "Gerenciar obrigações" e não associa Obrigações à frase de espera. Mensagens, Modelos e Configurações continuam vendo essa frase. `/modules/obligations` passa a ser 404, junto com `/modules/billing`.
- `ContactTest`: o teste atual de exclusão de contato sem obrigação permanece válido. Acrescentar o caso com obrigação se ele não ficar só em `ObligationTest`. Um dos dois lugares basta, sem duplicar a mesma asserção nos dois arquivos. Preferir `ObligationTest` para o bloqueio.
- `ContactServiceTest`: criar uma obrigação para o contato e esperar `delete()` igual a `false` e o contato ainda presente. Sem obrigação, `delete()` continua `true`.

### Comando

```bash
php artisan test --compact
```

A suíte inteira, inclusive `ExampleTest`, precisa passar.

## 13. Verificação manual

Com a migration aplicada e o frontend já compilado:

1. Abrir a home e a navegação no desktop e abaixo de 768px. Obrigações abre a lista. Mensagens continua na espera.
2. Cadastrar uma obrigação com vencimento passado e pagamento Pendente. Confirmar o flash, a data na lista e "Sem envios" nas duas colunas.
3. Enviar o formulário vazio e um contato inválido. Confirmar o retorno, o resumo, as bordas e os valores preservados.
4. Marcar uma obrigação como Paga. A coluna de pagamento muda; Mensagem e Envios no mês continuam "Sem envios". Não deve aparecer Reenviar.
5. Buscar pelo nome do contato e por um termo sem resultado.
6. Excluir o contato dessa obrigação e ler o flash de erro. Excluir a obrigação, depois excluir o contato, e confirmar que o contato sai.
7. Abrir `/modules/obligations` e um id de obrigação inexistente.
8. Em `local`, `php artisan db:seed` pode criar exemplos. Em `production`, o mesmo comando não cria obrigação nem o usuário de teste.

## 14. Critério de pronto

- A spec 002 está coberta por CA-01 a CA-16.
- A lista mostra vencimento e os indicadores provisórios antes do pagamento.
- Contato com obrigação não é excluído. Contato sem obrigação continua sendo excluído.
- Não existe tabela `messages` nem coluna de contagem em `obligations`.
- `php artisan test --compact` passa.
- Pint passa nos PHP tocados.
- Nenhum pacote novo foi adicionado.
- Nenhum endpoint de envio foi criado.
