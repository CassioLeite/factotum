# Plano 001 — Gestão de contatos

| | |
| --- | --- |
| Spec | [docs/specs/001-contact-management.md](../specs/001-contact-management.md) |
| Estado | Plano de implementação; código ainda não iniciado |
| Data | 2026-11-23 |

Este plano descreve como implementar a spec 001. Não instala dependências e não altera código de aplicação.

## 1. Base atual

Inspeção do repositório em 2026-11-23:

- `composer.json`: PHP `^8.3`, `laravel/framework` `^13.17`, Sail `^1.71`, PHPUnit `^12.5`, Pint. Sem Breeze, Fortify, Sanctum ou Boost.
- `package.json`: Tailwind CSS `^4` via `@tailwindcss/vite`, Vite `^8`, `laravel-vite-plugin`. Entradas em `vite.config.js`: `resources/css/app.css` e `resources/js/app.js`. A fonte Instrument Sans já está no tema do CSS.
- `config/database.php`: conexão padrão `sqlite`, arquivo `database/database.sqlite`.
- `compose.yaml`: serviço único `laravel.test` (runtime Sail 8.5). Sem MySQL. SQLite dispensa serviço de banco.
- `routes/web.php`: apenas `GET /` retornando `welcome`.
- `app/`: `Controller` vazio, `User` com `#[Fillable]` e `casts()`, `AppServiceProvider` vazio. Não existem `Services`, `Repositories`, `Http/Requests` nem `Enums`.
- `resources/views/welcome.blade.php`: página do skeleton. `resources/js/app.js` está vazio.
- `tests/Feature/ExampleTest.php`: `GET /` responde 200. `tests/Unit/ExampleTest.php` não sobe a aplicação.
- `phpunit.xml`: `DB_DATABASE=testing` e `DB_URL` vazio. Não fixa `DB_CONNECTION`. Com o `.env` em `sqlite`, o nome `testing` vira um arquivo SQLite no diretório de trabalho, não um banco em memória.
- `APP_LOCALE=en` e não há diretório `lang/`. `SESSION_DRIVER=database` no `.env`; nos testes, `SESSION_DRIVER=array`.
- `.env.example`: `APP_NAME=Laravel`.

A implementação segue o que já está instalado. Nenhum `composer require` e nenhum `npm install`.

## 2. Decisões de implementação

- Fluxo obrigatório: Controller → Service → Repository → Model. Classes concretas. O container resolve pelo construtor. Sem interface, binding no provider, DTO, Action ou Clean Architecture.
- `ContactController` orquestra HTTP. `ContactService` aplica a regra de consentimento, reduz o telefone a dígitos e monta os atributos graváveis. `ContactRepository` é o único lugar com query Eloquent de listagem.
- Form Requests validam a entrada e normalizam trim, e-mail e checkbox. O telefone é validado como foi digitado. A redução a dígitos acontece no service, antes da persistência. A checagem de formato, tamanho e unicidade fica num closure do próprio request, sem classe `Rule` nova.
- A exclusão desta fase continua sendo remoção definitiva. Não há consulta a obrigações nem a histórico de mensagens, porque essas tabelas não existem. A RN-16 permanece só documentada.
- `ContactSeeder` grava contatos apenas em `local` e `testing`. `production` e os demais ambientes saem sem inserir.
- Enum `ContactStatus` é um backed enum do PHP, usado no cast do model e em `Rule::enum`. Não é uma camada de domínio.
- Rotas web com `Route::resource`. Sem middleware de autenticação. `authorize()` dos Form Requests retorna `true`.
- Textos de tela e mensagens de validação em português, escritos na view e no Form Request. Locale global permanece `en`.
- Tema claro, utilitários Tailwind nas views. Sem alteração funcional em `app.css`, salvo se o build exigir. Sem Alpine, Livewire ou máscara.
- Paginação de 10 itens com as views Tailwind que o Laravel 13 já usa por padrão. `->links()` na listagem. Sem `Paginator::useBootstrap()`.
- `welcome.blade.php` sai do fluxo e é removido, para não restarem duas homes.
- Testes de feature e o teste do service usam `RefreshDatabase` sobre SQLite em memória. O `phpunit.xml` passa a declarar essa conexão, para a suíte não criar o arquivo `testing` nem tocar em `database/database.sqlite`.
- `APP_NAME=Factotum` em `.env.example` e no `.env` local, para o layout ler `config('app.name')`.

## 3. Arquivos

### Criar

| Arquivo | Responsabilidade |
| --- | --- |
| `database/migrations/*_create_contacts_table.php` | tabela `contacts` |
| `app/Enums/ContactStatus.php` | `active` e `inactive`, com `label()` |
| `app/Models/Contact.php` | Eloquent, fillable, casts |
| `database/factories/ContactFactory.php` | dados de teste e seed |
| `database/seeders/ContactSeeder.php` | 12 contatos só em `local` e `testing` |
| `app/Repositories/ContactRepository.php` | paginação, create, update, delete |
| `app/Services/ContactService.php` | consentimento e delegação ao repositório |
| `app/Http/Requests/StoreContactRequest.php` | validação de cadastro |
| `app/Http/Requests/UpdateContactRequest.php` | validação de edição |
| `app/Http/Controllers/HomeController.php` | home |
| `app/Http/Controllers/ModulePlaceholderController.php` | módulos futuros |
| `app/Http/Controllers/ContactController.php` | CRUD HTTP |
| `resources/views/layouts/app.blade.php` | casca, título, Vite, flash |
| `resources/views/partials/nav.blade.php` | navegação responsiva |
| `resources/views/partials/flash.blade.php` | flash de sucesso |
| `resources/views/home.blade.php` | home |
| `resources/views/modules/placeholder.blade.php` | módulo pendente |
| `resources/views/contacts/index.blade.php` | lista, busca, paginação |
| `resources/views/contacts/create.blade.php` | cadastro |
| `resources/views/contacts/edit.blade.php` | edição |
| `resources/views/contacts/show.blade.php` | detalhe |
| `resources/views/contacts/_form.blade.php` | campos compartilhados |
| `tests/Feature/ContactTest.php` | CRUD, validação, busca, paginação |
| `tests/Feature/NavigationTest.php` | home e módulos |
| `tests/Unit/ContactServiceTest.php` | timestamp de consentimento |

### Alterar

| Arquivo | Mudança |
| --- | --- |
| `routes/web.php` | home, resource de contatos, módulos |
| `database/seeders/DatabaseSeeder.php` | chama `ContactSeeder`; mantém o usuário de exemplo |
| `phpunit.xml` | SQLite em memória |
| `.env.example` | `APP_NAME=Factotum` |
| `.env` | `APP_NAME=Factotum` |

### Remover

| Arquivo | Motivo |
| --- | --- |
| `resources/views/welcome.blade.php` | substituída pela home |

### Não mexer

`User`, migrations de users/cache/jobs, `bootstrap/app.php`, `AppServiceProvider`, `config/database.php`, `compose.yaml`, `vite.config.js`, `resources/css/app.css`, `resources/js/app.js`, `tests/Feature/ExampleTest.php` e `tests/Unit/ExampleTest.php`.

O teste de exemplo de feature continua válido porque `/` segue respondendo 200.

## 4. Migration

Gerar com:

```bash
php artisan make:migration create_contacts_table
```

`up()`:

```php
Schema::create('contacts', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('company')->nullable();
    $table->string('phone')->unique();
    $table->string('email')->nullable();
    $table->string('status')->default('active')->index();
    $table->boolean('whatsapp_consent')->default(false);
    $table->timestamp('whatsapp_consent_at')->nullable();
    $table->timestamps();

    $table->index('name');
});
```

`down()` remove `contacts`.

Coluna `status` é `string` para permanecer portátil no SQLite. A restrição dos dois valores fica no enum e no Form Request. E-mail não é único: o SQLite aceita vários `NULL` numa coluna única, mas a spec não pede unicidade de e-mail. Telefone é único porque a comparação acontece depois da normalização.

Sem foreign key e sem soft delete.

## 5. Enum, model e factory

`app/Enums/ContactStatus.php`:

```php
enum ContactStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Ativo',
            self::Inactive => 'Inativo',
        };
    }
}
```

`app/Models/Contact.php`, no mesmo estilo do `User`:

```php
#[Fillable([
    'name',
    'company',
    'phone',
    'email',
    'status',
    'whatsapp_consent',
    'whatsapp_consent_at',
])]
class Contact extends Model
{
    /** @use HasFactory<ContactFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => ContactStatus::class,
            'whatsapp_consent' => 'boolean',
            'whatsapp_consent_at' => 'datetime',
        ];
    }
}
```

`whatsapp_consent_at` fica fillable porque o service grava o model por array. O Form Request não declara esse campo, então `validated()` não o traz do HTTP.

Factory:

- `name`: `fake()->name()`
- `company`: `fake()->optional()->company()`
- `phone`: `'55'.fake()->unique()->numerify('119########')` (13 dígitos)
- `email`: `fake()->optional()->safeEmail()`
- `status`: `ContactStatus::Active`
- `whatsapp_consent`: `false`
- `whatsapp_consent_at`: `null`

Estados:

- `inactive()`: `status = ContactStatus::Inactive`
- `consented()`: `whatsapp_consent = true` e `whatsapp_consent_at = now()`

`ContactSeeder` cria 12 contatos pela factory somente quando `app()->environment(['local', 'testing'])`. Em `production` e em qualquer outro ambiente, `run()` retorna sem gravar. `DatabaseSeeder` chama esse seeder e continua criando o usuário `test@example.com`. Os testes de CRUD usam a factory direto, com `RefreshDatabase`, e não dependem do seed para existir. O `php artisan db:seed` em desenvolvimento serve para enxergar a paginação; o mesmo comando em produção não popula contatos.

## 6. Repository

`ContactRepository`, sem classe base.

```php
public function paginate(?string $search): LengthAwarePaginator
public function create(array $attributes): Contact
public function update(Contact $contact, array $attributes): Contact
public function delete(Contact $contact): void
```

Constante `PER_PAGE = 10`.

`paginate`:

1. Começa em `Contact::query()`.
2. Se `$search` não for nulo nem `''`, agrupa um `where` com `orWhere` em `name`, `company` e `email`, usando `like` e o termo escapado (`\`, `%`, `_`).
3. Se o termo tiver ao menos um dígito, acrescenta `orWhere` em `phone` com `like` desses dígitos, também escapados. Sem dígitos, não filtra telefone — um `like '%%'` devolveria a tabela inteira.
4. `orderBy('name')->orderBy('id')`.
5. `paginate(self::PER_PAGE)->withQueryString()`.

`create` usa `Contact::query()->create($attributes)`. `update` usa `$contact->update($attributes)` e devolve `$contact->refresh()`. `delete` usa `$contact->delete()`. Não há guarda por obrigações ou mensagens: a RN-16 não vira código nesta fase, e o status ativo/inativo segue sendo o recurso para desativar o contato.

A busca do contato por id na rota fica com o route model binding, não com um método `find` do repositório.

## 7. Service

`ContactService` recebe `ContactRepository` no construtor.

```php
public function paginate(?string $search): LengthAwarePaginator
public function create(array $data): Contact
public function update(Contact $contact, array $data): Contact
public function delete(Contact $contact): void
```

`paginate` e `delete` só delegam. `create` e `update` passam por `attributes(array $data, ?Contact $current = null): array`, que devolve exatamente:

| Chave | Origem |
| --- | --- |
| `name`, `company`, `email`, `status` | dados já validados |
| `phone` | somente os dígitos de `$data['phone']`, via `preg_replace('/\D+/', '', ...)` |
| `whatsapp_consent` | `(bool) $data['whatsapp_consent']` |
| `whatsapp_consent_at` | `now()` quando o consentimento fica verdadeiro e o registro atual não era consentido; o timestamp atual quando já era consentido e continua; `null` quando o consentimento fica falso |

`company` e `email` ausentes ou string vazia viram `null` aqui também, como rede de segurança da RN-06.

Nenhuma query nova nasce no service.

## 8. Form Requests

`StoreContactRequest`:

- `authorize(): true`
- `prepareForValidation()` não altera `phone`. O valor digitado precisa voltar intacto no `old()` quando o redirect de validação ocorrer.
  - `name`: trim quando preenchido
  - `company`: trim, ou `null` se vazio
  - `email`: trim, `mb_strtolower`, ou `null` se vazio
  - `whatsapp_consent`: `$this->boolean('whatsapp_consent')`
- `rules()`:

```php
return [
    'name' => ['required', 'string', 'max:120'],
    'company' => ['nullable', 'string', 'max:120'],
    'phone' => [
        'bail',
        'required',
        'string',
        'regex:/^[0-9+() \-]+$/',
        function (string $attribute, mixed $value, Closure $fail): void {
            $digits = preg_replace('/\D+/', '', (string) $value) ?? '';

            if (! preg_match('/^\d{10,15}$/', $digits)) {
                $fail('Informe o telefone com DDI e DDD, usando de 10 a 15 dígitos.');

                return;
            }

            $current = $this->route('contact');

            $exists = Contact::query()
                ->where('phone', $digits)
                ->when(
                    $current instanceof Contact,
                    fn ($query) => $query->whereKeyNot($current->getKey()),
                )
                ->exists();

            if ($exists) {
                $fail('Este telefone já está cadastrado.');
            }
        },
    ],
    'email' => ['nullable', 'string', 'email', 'max:255'],
    'status' => ['required', Rule::enum(ContactStatus::class)],
    'whatsapp_consent' => ['required', 'boolean'],
];
```

O `regex` aceita apenas dígitos, espaço comum, `+`, `(` , `)` e `-`. Letras, pontos e outros símbolos falham nessa regra. O `bail` impede que a mesma entrada também dispare a mensagem de tamanho. O closure conta os dígitos e consulta a unicidade pelo valor normalizado, sem substituir o input. A query desse closure é a checagem de unicidade que o `Rule::unique` faria; não é listagem e não justifica outro repositório.

`attributes()` em português: nome, empresa, telefone, e-mail, status, consentimento WhatsApp.

`messages()` cobre o regex do telefone ("Use apenas dígitos, espaços, parênteses, hífens e o sinal de +."). Os demais textos podem usar as chaves padrão do Laravel com os atributos traduzidos; onde a frase padrão sair em inglês, substituir por uma frase curta em português no próprio request.

`UpdateContactRequest extends StoreContactRequest` e não duplica regras. No cadastro, `$this->route('contact')` é nulo. Na edição, o model da rota é ignorado na unicidade. Falha de validação de store ou update lança `ValidationException`: redirect de volta (302) com erros e input na sessão. O teste não espera 422, porque estes formulários não pedem JSON.

O request não inclui `whatsapp_consent_at`, `id` nem timestamps.

## 9. Rotas e controllers

Substituir o conteúdo de `routes/web.php` por:

```php
use App\Http\Controllers\ContactController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ModulePlaceholderController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::resource('contacts', ContactController::class);

Route::get('/modules/{module}', ModulePlaceholderController::class)
    ->whereIn('module', ['obligations', 'messages', 'templates', 'settings'])
    ->name('modules.show');
```

`Route::resource` registra as sete rotas REST, com `create` antes de `{contact}`. Nomes: `contacts.index`, `contacts.create`, `contacts.store`, `contacts.show`, `contacts.edit`, `contacts.update`, `contacts.destroy`.

`HomeController` é invokável e retorna `view('home')`.

`ModulePlaceholderController` é invokável. Mapa interno:

| Slug | Título |
| --- | --- |
| `obligations` | Obrigações |
| `messages` | Mensagens |
| `templates` | Modelos |
| `settings` | Configurações |

`whereIn` já impede slug fora da lista. Ainda assim, slug ausente do mapa responde 404. A view recebe `title` e o texto "Este módulo será implementado em uma próxima etapa."

`ContactController` recebe `ContactService` no construtor. Métodos enxutos:

| Método | Comportamento |
| --- | --- |
| `index(Request $request)` | `$search = trim((string) $request->query('q', ''))`; se `mb_strlen($search) > 100`, fica com `mb_substr($search, 0, 100)`; string vazia vira `null`. Retorna `contacts.index` com `contacts` e `search`. |
| `create()` | `contacts.create` |
| `store(StoreContactRequest $request)` | `$this->contacts->create($request->validated())`, redirect `contacts.index` com flash "Contato cadastrado." |
| `show(Contact $contact)` | `contacts.show` |
| `edit(Contact $contact)` | `contacts.edit` |
| `update(UpdateContactRequest $request, Contact $contact)` | `$this->contacts->update($contact, $request->validated())`, redirect `contacts.index` com flash "Contato atualizado." |
| `destroy(Contact $contact)` | `$this->contacts->delete($contact)`, redirect `contacts.index` com flash "Contato excluído." |

Redirects usam `to_route(...)`. Nenhum `validate()` no controller.

## 10. Views

Layout `layouts/app.blade.php`:

- HTML com `lang="pt-BR"`, viewport e título `@yield('title') — {{ config('app.name') }}`.
- `@vite(['resources/css/app.css', 'resources/js/app.js'])`.
- Corpo `bg-slate-100 text-slate-900 antialiased`.
- Em `lg`, grade com a sidebar `w-64` e o miolo. Abaixo de `lg`, cabeçalho com o nome da aplicação e a navegação recolhível via `<details>`, sem CSS extra.
- Miolo `max-w-5xl mx-auto p-4 sm:p-6 lg:p-8`, inclui o partial de flash e `@yield('content')`.

`partials/nav.blade.php` lista os seis destinos com `route()`. O item ativo usa `request()->routeIs(...)`: home em `home`, contatos em `contacts.*`, cada módulo em `modules.show` com o slug correspondente. Classe ativa: fundo `bg-slate-900 text-white`. Demais links: `text-slate-700 hover:bg-slate-100`.

`partials/flash.blade.php`: se `session('success')`, uma faixa `rounded-lg bg-emerald-50 text-emerald-800 border border-emerald-200 px-4 py-3`.

`home.blade.php`: título "Factotum", uma frase sobre automação de mensagens WhatsApp e cartões para os seis destinos. O cartão Contatos é o único com botão preenchido "Gerenciar contatos". Os outros quatro cartões repetem que o módulo vem depois e apontam para `modules.show`.

`modules/placeholder.blade.php`: título do módulo e a frase de espera. Link "Voltar ao início".

`contacts/index.blade.php`:

- Cabeçalho com "Contatos" e link "Novo contato" para `contacts.create`.
- Formulário `GET` para `contacts.index`: input `name="q"` com `value="{{ $search }}"`, label "Buscar", botão "Buscar". Sem o campo, a listagem volta ao conjunto completo.
- Se não houver registros e não houver busca: "Nenhum contato cadastrado." e link para criar.
- Se não houver registros e houver busca: "Nenhum contato encontrado."
- Tabela visível em `md` com colunas Nome, Empresa, Telefone, E-mail, Status, Consentimento e Ações. Empresa e e-mail nulos aparecem como "—".
- Abaixo de `md`, a tabela fica `hidden` e os mesmos dados saem em cartões `md:hidden`.
- Status: `$contact->status->label()` com badge esmeralda para ativo e cinza para inativo.
- Consentimento: "Consentido" ou "Sem consentimento".
- Ações: links Ver e Editar; formulário `DELETE` com `@csrf`, `@method('DELETE')` e `onsubmit="return confirm('Excluir este contato? Esta ação não pode ser desfeita.')"`.
- `{{ $contacts->links() }}` quando `hasPages()`.

`contacts/_form.blade.php` recebe `$contact` anulável:

- `name`, `company`, `phone`, `email` como inputs texto. E-mail com `type="email"`.
- Telefone com `inputmode="tel"`, `autocomplete="tel"` e ajuda "DDI + DDD + número. São aceitos dígitos, espaço, parênteses, hífen e +. Ex.: +55 (11) 98888-7777".
- `status` como `<select>` percorrendo `ContactStatus::cases()`. Cadastro pré-seleciona `active`. Edição pré-seleciona `$contact->status`.
- `whatsapp_consent` como checkbox. Marcado quando `old('whatsapp_consent', $contact?->whatsapp_consent)`.
- Valores com `old($field, $contact?->$field)`.
- Cada campo: `@error` em `text-sm text-red-600` e borda `border-red-500` quando `$errors->has`.
- Se `$errors->any()`, resumo "Revise os campos destacados."
- Botão submit com o rótulo recebido (`Cadastrar` ou `Salvar`) e link "Cancelar" de volta a `contacts.index`.

`create.blade.php` abre o formulário em `contacts.store` com `@csrf` e inclui o partial.

`edit.blade.php` abre o formulário em `contacts.update` com `@csrf` e `@method('PUT')`.

`show.blade.php` lista nome, empresa, telefone, e-mail, status, consentimento e, se houver, `whatsapp_consent_at` formatado com `translatedFormat` ou `format('d/m/Y H:i')`. Também mostra `updated_at`. Ações: Editar e o mesmo formulário de exclusão da lista. Link para a listagem.

Formulários usam `novalidate` para o feedback visual ser o do servidor, não o balão nativo do navegador. A validação continua obrigatória no Form Request.

## 11. Ordem de implementação

1. Ajustar `phpunit.xml` e `APP_NAME` antes dos testes.
2. Migration, enum, model e factory. Rodar `php artisan migrate`.
3. Seeder e a chamada no `DatabaseSeeder`.
4. Repository e service.
5. Form Requests.
6. Controllers e `routes/web.php`.
7. Layout, partials, home, placeholder e views de contato.
8. Remover `welcome.blade.php`.
9. Testes da seção 12.
10. `vendor/bin/pint --dirty` nos PHP alterados.
11. `php artisan test --compact`.

Comandos de geração, a partir da raiz do projeto, no host ou no Sail se o container já estiver de pé:

```bash
php artisan make:migration create_contacts_table
php artisan make:enum ContactStatus
php artisan make:model Contact -f
php artisan make:seeder ContactSeeder
php artisan make:class Repositories/ContactRepository
php artisan make:class Services/ContactService
php artisan make:request StoreContactRequest
php artisan make:request UpdateContactRequest
php artisan make:controller HomeController --invokable
php artisan make:controller ModulePlaceholderController --invokable
php artisan make:controller ContactController --resource
php artisan make:test ContactTest
php artisan make:test NavigationTest
php artisan make:test --unit ContactServiceTest
```

O `--resource` cria os sete métodos. O teste unitário gerado estende `PHPUnit\Framework\TestCase`; trocar para `Tests\TestCase` para ter aplicação e banco.

### Isolamento do banco de teste

Em `phpunit.xml`, no bloco `<php>`:

```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

Manter `DB_URL` vazio, para a URL não recolocar outra conexão por cima.

## 12. Testes

Todos os testes novos de aplicação usam `Illuminate\Foundation\Testing\RefreshDatabase`. Nomes de método no padrão já usado: `test_descrição_em_snake_case`.

`ContactServiceTest` estende `Tests\TestCase`. Os de feature também. Não usar o `ExampleTest` unitário como base, porque ele não sobe o Laravel.

Congelar o relógio com `Carbon::setTestNow()` nos casos de timestamp e limpar com `Carbon::setTestNow()` no `tearDown` ou no fim do teste.

### `tests/Feature/NavigationTest.php`

| Teste | Asserções |
| --- | --- |
| Home do Factotum | `get(route('home'))` é 200, vê "Contatos", "Obrigações", "Mensagens", "Modelos" e "Configurações"; não vê "Let's get started" nem "Campanhas" |
| Módulos previstos | cada slug de `modules.show` é 200 e vê o título em português e "Este módulo será implementado em uma próxima etapa." |
| Módulo desconhecido | `GET /modules/billing` é 404 |

### `tests/Feature/ContactTest.php`

| Teste | Asserções |
| --- | --- |
| Lista paginada | factory de 11 contatos com nomes controlados; `get(route('contacts.index'))` é 200; a página 2 existe; um nome exclusivo da página 2 não aparece na página 1 |
| Busca por nome, empresa, e-mail e telefone | dois contatos; cada `q` deixa só o esperado; `assertDontSee` o outro nome |
| Busca sem resultado e lista vazia | com dados e `q` inexistente, vê "Nenhum contato encontrado"; sem dados e sem `q`, vê "Nenhum contato cadastrado" |
| Termo com curinga | contato cujo nome não contém `%`; `q=100%` não lista esse contato e responde 200 |
| Cadastro válido | `post` com telefone mascarado `+55 (11) 98888-7777`, e-mail `Ana@Empresa.com`, consentimento `"1"`; redirect para `contacts.index`; flash "Contato cadastrado."; banco tem `phone = 5511988887777`, `email = ana@empresa.com`, `whatsapp_consent = true`, `whatsapp_consent_at` não nulo |
| Cadastro sem consentimento | checkbox omitido; banco tem consentimento falso e timestamp nulo |
| Cadastro inválido | `from(route('contacts.create'))` e `post` sem nome, telefone `abc`, e-mail `nao-e-email`, status `archived`; `assertRedirect` de volta ao formulário; `assertSessionHasErrors` em `name`, `phone`, `email`, `status`; `assertSessionHasInput('phone', 'abc')`; `assertDatabaseCount('contacts', 0)`. Não afirmar HTTP 422 |
| Telefone com caractere proibido | `11.988887777` e `11988887777a` geram erro em `phone`, redirecionam de volta e preservam o valor digitado na sessão. Nenhum registro é criado |
| Telefone curto e longo | 9 dígitos e 16 dígitos, só com caracteres permitidos, geram erro em `phone` |
| Telefone duplicado | segundo `post` com outra máscara do mesmo número; erro em `phone`; continua um único registro |
| Edição preserva o próprio telefone | `put` no mesmo número; redirect; sem erro |
| Edição não toma o telefone alheio | `put` com o telefone do outro; erro em `phone`; o primeiro registro permanece |
| Detalhe e 404 | `get` do contato vê o nome; `get('/contacts/9999')` é 404 |
| Exclusão | `delete`; redirect; flash "Contato excluído."; `assertDatabaseMissing` |
| Seeder por ambiente | em `production`, `ContactSeeder` não cria contatos; em `local` e em `testing`, cria 12 |

O `from(route('contacts.create'))` ou `from(route('contacts.edit', $contact))` precede os posts inválidos, para o redirect de volta ser o formulário. A asserção desse fluxo é `assertRedirect` mais `assertSessionHasErrors` e `assertSessionHasInput`. A página seguinte, no uso real, é um GET 200 com os erros da sessão. O teste da requisição inválida não usa `assertStatus(422)`.

### `tests/Unit/ContactServiceTest.php`

Resolver o service com `app(ContactService::class)`.

| Teste | Asserções |
| --- | --- |
| Consentimento novo | `create` com `whatsapp_consent => true` grava timestamp igual ao `setTestNow` |
| Consentimento mantido | criar consentido em T1; avançar o relógio para T2; `update` ainda consentido; `whatsapp_consent_at` continua T1 |
| Consentimento removido | `update` com falso; timestamp fica `null` |
| Consentimento reativado | depois de removido, `update` com verdadeiro em T3; timestamp passa a ser T3 |

`ContactTest` cobre a normalização do telefone pelo HTTP. O teste de service não repete a máscara.

### Comando

```bash
php artisan test --compact
```

A suíte inclui `ExampleTest`. Critério: tudo verde, inclusive os testes que já existiam.

## 13. Verificação manual

Com o Vite em execução (`npm run dev` ou `composer run dev`) e a migration aplicada:

1. Abrir `/` e percorrer os seis destinos no desktop e numa largura abaixo de 768px.
2. Cadastrar um contato válido com telefone mascarado e consentimento marcado. Confirmar o flash, os dígitos na lista e o detalhe com data de consentimento.
3. Enviar o formulário vazio, um telefone com letra e um telefone com ponto. Confirmar o retorno ao formulário, o resumo, as mensagens por campo, a borda vermelha e os valores digitados preservados.
4. Tentar repetir o telefone.
5. Editar sem mexer no consentimento e conferir que a data de consentimento não muda. Desmarcar, salvar, marcar de novo e conferir a data nova.
6. Buscar por parte do nome e por dígitos do telefone. Confirmar o estado vazio da busca.
7. Em ambiente `local`, com mais de 10 contatos (`php artisan db:seed`), confirmar a segunda página e que a busca permanece no link da paginação. O mesmo seed em `production` não insere contatos.
8. Excluir e cancelar o `confirm`. Excluir de novo e aceitar. O contato não volta.
9. Abrir um id inexistente e `/modules/billing`.

Não há ferramenta de browser obrigatória neste plano porque a implementação ainda não começou. Na etapa de código, a verificação manual acima precisa ser executada na aplicação rodando.

## 14. Critério de pronto

- A spec 001 está coberta pelos critérios CA-01 a CA-18.
- A listagem, o formulário e a navegação funcionam no tema claro, em largura grande e estreita.
- `php artisan test --compact` passa.
- Pint passa nos arquivos PHP tocados.
- Nenhum pacote novo foi adicionado.
- Nenhum endpoint exige autenticação.
