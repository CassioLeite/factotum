# Plano 003 — Modelos de mensagens

| | |
| --- | --- |
| Spec | [docs/specs/003-message-template-management.md](../specs/003-message-template-management.md) |
| Estado | Plano para revisão; código ainda não iniciado |
| Data | 2026-11-23 |

Este plano descreve como implementar a spec 003 sobre as Fases 1 e 2 já existentes. Não instala dependências, não cria histórico de mensagens e não integra com a Meta.

## 1. Base atual

A aplicação já tem:

- `Contact`, `Obligation`, seus repositories, services, controllers, Form Requests e views.
- `ContactService::delete(): bool` recusa o contato que possui obrigação. O flash de erro já é renderizado em `partials/flash.blade.php`.
- `routes/web.php` registra `contacts`, `obligations` e `GET /modules/{module}` com `messages`, `templates` e `settings`.
- A navegação e o cartão da home apontam Modelos para `modules.show` com o slug `templates`. A página diz que o módulo será implementado numa próxima etapa.
- `tests/Feature/NavigationTest.php` espera que `messages`, `templates` e `settings` abram essa página. `/modules/obligations` e `/modules/billing` já respondem 404.
- `DatabaseSeeder` só grava em `local` e `testing`: usuário `test@example.com`, `ContactSeeder` e `ObligationSeeder`.
- `phpunit.xml` usa SQLite em memória. Os testes de feature usam `RefreshDatabase`.
- Não existe model, migration nem rota de modelo. Não existe tabela de mensagens.

A implementação usa só o que já está instalado. Nenhum `composer require` e nenhum `npm install` de pacote novo.

## 2. Decisões de implementação

- Fluxo: `MessageTemplateController` → `MessageTemplateService` → `MessageTemplateRepository` → `MessageTemplate`. Classes concretas. Sem interface, binding novo, DTO, Action ou Clean Architecture.
- A URL do resource é `/templates`, com parâmetro `{template}` e nomes `templates.*`. A classe não se chama `Template` para não colidir com o vocabulário de views do Laravel.
- O conteúdo é a coluna `body`. Na tela o rótulo é Conteúdo. `string()` do Laravel limita a 255 caracteres, então `body` é `text`. O máximo de 1024 fica na validação.
- `status` é o backed enum `MessageTemplateStatus`, no mesmo estilo de `ContactStatus`. Não reutilizar `ContactStatus`.
- `is_default` não é fillable. A migração insere o único padrão. Create e update não escrevem essa coluna nem `meta_template_name`.
- A proteção de exclusão fica em `MessageTemplateService::delete(): bool`. `false` significa que era o padrão e nada foi apagado. O repository continua capaz de apagar; o service recusa antes. Não há foreign key nesta fase.
- A lista de variáveis e a pré-visualização ficam no service. O Form Request chama esse service. O controller não reimplementa a regra.
- Pré-visualizar reutiliza o Form Request do formulário atual. O botão envia `intent=preview`. `store` e `update`, quando recebem esse valor, validam, redirecionam de volta com `withInput()` e gravam o texto em `session('preview')`, sem persistir. Não há rota extra: na edição o parâmetro `{template}` continua disponível para a unicidade ignorar o próprio registro, e o botão não envia `_method`. Não usa o flash `success`.
- Busca e paginação repetem o desenho atual: 10 por página, `q` preservado, escape de `%`, `_` e `\` com `ESCAPE '\'` no SQLite. Ordenação: `name`, depois `id`.
- O padrão é dado de aplicação e entra na migração. O seeder só cria personalizados e só em `local` e `testing`.
- Não criar `Message`, rota de envio, job, cliente HTTP da Meta nem coluna de template em `obligations`.

## 3. Arquivos

### Criar

| Arquivo | Responsabilidade |
| --- | --- |
| `database/migrations/*_create_message_templates_table.php` | tabela e modelo padrão |
| `app/Enums/MessageTemplateStatus.php` | `active` e `inactive`, com `label()` |
| `app/Models/MessageTemplate.php` | Eloquent, fillable, casts |
| `database/factories/MessageTemplateFactory.php` | personalizados de teste e seed |
| `database/seeders/MessageTemplateSeeder.php` | exemplos só em `local` e `testing` |
| `app/Repositories/MessageTemplateRepository.php` | paginação, create, update, delete |
| `app/Services/MessageTemplateService.php` | normalização, variáveis, pré-visualização, exclusão do padrão |
| `app/Http/Requests/StoreMessageTemplateRequest.php` | validação de cadastro e de pré-visualização |
| `app/Http/Requests/UpdateMessageTemplateRequest.php` | validação de edição, herdando o store |
| `app/Http/Controllers/MessageTemplateController.php` | CRUD; pré-visualização dentro de `store` e `update` |
| `resources/views/templates/index.blade.php` | lista |
| `resources/views/templates/create.blade.php` | cadastro |
| `resources/views/templates/edit.blade.php` | edição |
| `resources/views/templates/show.blade.php` | detalhe e pré-visualização gravada |
| `resources/views/templates/_form.blade.php` | campos, ajuda e pré-visualização da sessão |
| `tests/Feature/MessageTemplateTest.php` | CRUD, variáveis, pré-visualização, padrão e seeder |

### Alterar

| Arquivo | Mudança |
| --- | --- |
| `resources/views/partials/nav.blade.php` | Modelos aponta para `templates.index` e ativa em `templates.*` |
| `resources/views/home.blade.php` | cartão Modelos funcional; Mensagens e Configurações seguem na espera |
| `app/Http/Controllers/ModulePlaceholderController.php` | remove `templates` do mapa |
| `routes/web.php` | resource `templates`; `whereIn` sem `templates` |
| `database/seeders/DatabaseSeeder.php` | chama `MessageTemplateSeeder` depois de `ObligationSeeder`, ainda só em `local` e `testing` |
| `tests/Feature/NavigationTest.php` | Modelos deixa de ser página de espera |

### Não criar

`app/Models/Message.php`, migration de `messages`, controller de envio, jobs, cliente da Meta, coluna `message_template_id` e campo editável de template da Meta.

### Não mexer

`phpunit.xml`, `config/database.php`, `vite.config.js`, `resources/css/app.css`, `resources/js/app.js`, a regra de telefone, o bloqueio de exclusão de contato e as colunas provisórias de obrigação.

## 4. Migration

Gerar com:

```bash
php artisan make:migration create_message_templates_table
```

`up()` cria a tabela e insere o padrão com `DB::table`, sem usar o model:

```php
Schema::create('message_templates', function (Blueprint $table) {
    $table->id();
    $table->string('name')->unique();
    $table->text('body');
    $table->string('status')->default('active')->index();
    $table->boolean('is_default')->default(false);
    $table->string('meta_template_name')->nullable();
    $table->timestamps();
});

DB::table('message_templates')->insert([
    'name' => 'Lembrete de vencimento',
    'body' => 'Olá, {{nome}}. Lembramos que {{obrigacao}} vence em {{vencimento}}.',
    'status' => 'active',
    'is_default' => true,
    'meta_template_name' => null,
    'created_at' => now(),
    'updated_at' => now(),
]);
```

`down()` remove `message_templates`.

Não acrescentar status de aprovação, idioma, categoria da Meta, contador de uso nem chave para mensagens. Sem soft delete. Não criar índice único parcial em `is_default`: a migração insere um padrão e o fillable não permite outro.

## 5. Enum, model e factory

`MessageTemplateStatus`:

```php
enum MessageTemplateStatus: string
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

`MessageTemplate`:

```php
#[Fillable(['name', 'body', 'status'])]
class MessageTemplate extends Model
{
    /** @use HasFactory<MessageTemplateFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => MessageTemplateStatus::class,
            'is_default' => 'boolean',
        ];
    }
}
```

`is_default` e `meta_template_name` ficam de fora do fillable. Não há relação com `Contact` nem com `Obligation`.

Factory, sempre personalizada:

- `name`: palavras curtas do faker, únicas
- `body`: frase que inclui `{{nome}}`
- `status`: `MessageTemplateStatus::Active`
- `is_default`: `false`
- `meta_template_name`: `null`

Estado `inactive()` altera só `status`. Não criar estado que grave outro padrão.

`MessageTemplateSeeder::run()` retorna imediatamente fora de `local` e `testing`. Nos ambientes permitidos, cria 3 personalizados pela factory e não altera o registro em que `is_default` é verdadeiro. `DatabaseSeeder` chama esse seeder depois de `ObligationSeeder`, dentro do mesmo `if` que já protege o usuário de teste.

## 6. Repository e service

`MessageTemplateRepository`, sem classe base.

```php
public function paginate(?string $search): LengthAwarePaginator
public function create(array $attributes): MessageTemplate
public function update(MessageTemplate $template, array $attributes): MessageTemplate
public function delete(MessageTemplate $template): void
```

Constante `PER_PAGE = 10`.

`paginate` agrupa a busca quando `$search` não é vazio. O grupo usa `whereRaw` com `escape '\\'` em `name` e `body`, no mesmo método privado de escape dos repositories atuais. `orderBy('name')->orderBy('id')->paginate(self::PER_PAGE)->withQueryString()`.

`MessageTemplateService` recebe só o repository.

```php
public const VARIABLES = ['nome', 'obrigacao', 'vencimento'];

public const SAMPLE = [
    '{{nome}}' => 'Ana Lima',
    '{{obrigacao}}' => 'IPTU',
    '{{vencimento}}' => '15/01/2026',
];
```

`create` e `update` passam por `attributes()`, que devolve apenas `name`, `body` e `status`. Conteúdo vazio vira o que a validação já recusou; o service ainda normaliza string vazia para não gravar espaço. Nenhuma chave `is_default` ou `meta_template_name` entra nesse array.

```php
public function delete(MessageTemplate $template): bool
{
    if ($template->is_default) {
        return false;
    }

    $this->templates->delete($template);

    return true;
}
```

`preview(string $body): string` devolve `strtr($body, self::SAMPLE)`. `strtr` troca só o token que existe no texto: um conteúdo só com `{{nome}}` não recebe IPTU nem a data. Não consulta o banco e não converte variáveis para placeholders da Meta.

`bodyErrors(string $body): array` devolve zero, uma ou duas mensagens:

1. Extrai os tokens com `/\{\{([^{}]*)\}\}/`. Para cada miolo que não seja exatamente um item de `VARIABLES`, guarda o token completo, na ordem, sem repetir. Um resultado vira "A variável TOKEN não é suportada. Use {{nome}}, {{obrigacao}} ou {{vencimento}}." Dois ou mais viram "As variáveis TOKEN, TOKEN e TOKEN não são suportadas. Use {{nome}}, {{obrigacao}} ou {{vencimento}}.", com vírgulas e "e" antes do último.
2. Remove os tokens fechados. Se sobrar `{{` ou `}}`, acrescenta "Feche todas as variáveis com {{ e }}."

`{{nome}}`, `{{obrigacao}}` e `{{vencimento}}` não entram na lista. `{{ nome }}`, `{{Nome}}`, `{{telefone}}` e `{{}}` entram. `{nome}` não é token e não gera erro.

## 7. Form Requests

`StoreMessageTemplateRequest`:

- `authorize(): true`
- `prepareForValidation()` faz trim de `name` e das pontas de `body`. Não altera `status`. Não lê `is_default` nem `meta_template_name`.
- Regras:

```php
return [
    'name' => [
        'required',
        'string',
        'max:120',
        Rule::unique('message_templates', 'name')->ignore($this->route('template')),
    ],
    'body' => [
        'required',
        'string',
        'max:1024',
        function (string $attribute, mixed $value, Closure $fail): void {
            foreach (app(MessageTemplateService::class)->bodyErrors((string) $value) as $message) {
                $fail($message);
            }
        },
    ],
    'status' => ['required', Rule::enum(MessageTemplateStatus::class)],
];
```

`attributes()`: nome, conteúdo, status.

`messages()` em português. A chave do enum é `status.` concatenada com `Illuminate\Validation\Rules\Enum`. O texto é "Selecione um status válido." Nome repetido: "Este nome já está cadastrado." Máximo do conteúdo: "O conteúdo deve ter no máximo :max caracteres."

`UpdateMessageTemplateRequest extends StoreMessageTemplateRequest` e não duplica regras. O `ignore` usa o parâmetro `template` da rota de edição. Na pré-visualização do cadastro esse parâmetro não existe, então a unicidade considera todos os nomes. Falha de validação redireciona de volta com erros e input. O teste não espera 422.

## 8. Rotas e controllers

```php
Route::resource('templates', MessageTemplateController::class);

Route::get('/modules/{module}', ModulePlaceholderController::class)
    ->whereIn('module', ['messages', 'settings'])
    ->name('modules.show');
```

Nomes do resource: `templates.index`, `create`, `store`, `show`, `edit`, `update`, `destroy`. Não registrar `templates.preview`.

`ModulePlaceholderController` fica só com Mensagens e Configurações. Slug ausente do mapa continua em 404.

`MessageTemplateController` recebe `MessageTemplateService`.

| Método | Comportamento |
| --- | --- |
| `index` | normaliza `q` como em contatos, no máximo 100 caracteres; string vazia vira `null`; devolve a página |
| `create` | formulário vazio |
| `store` | se `intent` for `preview`, `back()->withInput()->with('preview', ...)` e não cria; senão `create($request->validated())` e redirect com "Modelo cadastrado." |
| `show` | detalhe com `preview($template->body)` |
| `edit` | formulário |
| `update` | se `intent` for `preview`, volta com o texto resolvido e não atualiza; senão redirect com "Modelo atualizado." |
| `destroy` | se `delete()` for falso, `back()->with('error', 'O modelo padrão não pode ser excluído.')`; senão redirect com "Modelo excluído." |

Redirects de sucesso usam `to_route('templates.index')`. Nenhum `validate()` no controller. `validated()` não inclui `is_default` nem `meta_template_name` porque eles não estão nas regras; um POST com esses campos não os grava.

## 9. Views

Navegação: o link Modelos usa `route('templates.index')`. A condição ativa é `request()->routeIs('templates.*')`.

Home: o cartão Modelos sai do loop de espera. Título "Modelos", texto "Escreva os textos usados nos lembretes." e botão "Gerenciar modelos" para `templates.index`. Mensagens e Configurações permanecem no loop atual, com a frase de espera e `modules.show`.

`templates/index.blade.php`:

- Cabeçalho "Modelos" e link "Novo modelo".
- Busca `GET` com `q`, rótulo "Buscar" e botão "Buscar".
- Nota única: "Estes modelos ainda não são enviados nem sincronizados com a Meta."
- Vazio sem busca: "Nenhum modelo cadastrado." e link para criar.
- Vazio com busca: "Nenhum modelo encontrado."
- Tabela a partir de `md` e cartões abaixo de `md`. A wrapper usa `overflow-x-auto`.
- Colunas: Nome, Conteúdo, Status, Ações.
- Nome com badge "Padrão" quando `is_default` for verdadeiro. Conteúdo com `Str::limit($template->body, 80)`. Status com as classes de Ativo e Inativo já usadas em contatos.
- Ações: Ver, Editar e, só quando não for padrão, formulário `DELETE` com a confirmação "Excluir este modelo? Esta ação não pode ser desfeita."
- `{{ $templates->links() }}` quando `hasPages()`.

`_form.blade.php`:

- Nome, conteúdo e status, nesta ordem. Status percorre `MessageTemplateStatus::cases()`. No cadastro, o selecionado inicial é `active`.
- Ajuda do conteúdo: "Variáveis disponíveis: {{nome}}, {{obrigacao}} e {{vencimento}}."
- Erros, resumo "Revise os campos destacados.", botão `Cadastrar` ou `Salvar`, botão "Pré-visualizar" e link Cancelar para a listagem.
- O formulário continua sendo o de cadastro ou o de edição. Pré-visualizar é `<button name="intent" value="preview">`. Esse valor não faz parte das regras e não é gravado. Na edição, o formulário usa `@method('PUT')`; o botão de pré-visualização envia o mesmo PUT e o controller desvia antes de atualizar. Não usar `formaction` numa rota sem o `{template}`, porque a unicidade deixaria de ignorar o próprio modelo.
- Quando `session('preview')` existir, um bloco mostra "Pré-visualização", o texto escapado com quebras preservadas e a nota "Dados demonstrativos: Ana Lima, IPTU e 15/01/2026."
- O formulário usa `novalidate`. Não há campo de padrão nem de Meta.

`show.blade.php` mostra nome, conteúdo escapado com quebras, pré-visualização recebida do controller, status pelo rótulo, badge Padrão quando couber, "Template da Meta" com "Não vinculado", `updated_at` em `d/m/Y H:i` e, no padrão, "Este é o modelo padrão e não pode ser excluído." Ações: Editar, excluir só se não for padrão, e voltar à lista. Não incluir Enviar, Reenviar, Sincronizar nem Aprovar.

## 10. Contrato futuro que esta fase não implementa

Quando o módulo de mensagens for implementado:

- uma mensagem pode apontar para `message_templates.id` e também pode existir sem modelo;
- o texto enviado sai de `body`, com `{{nome}}` vindo do contato, `{{obrigacao}}` do título da obrigação e `{{vencimento}}` de `due_on` em `d/m/Y`;
- modelo inativo não entra na escolha do envio;
- fora da janela de 24 horas, o envio iniciado pela empresa só ocorre se o modelo interno estiver ligado a um template aprovado pela Meta;
- a ligação preenche `meta_template_name`. Os parâmetros posicionais da Meta são definidos pelo conteúdo e pela ordem daquele template aprovado, não por um mapa fixo das três variáveis do Factotum. Uma edição posterior do modelo interno precisa ser conferida com o texto aprovado. Esta fase não converte variáveis, não bloqueia a edição e não sincroniza;
- modelo com histórico não é excluído.

Nada disso vira migration, classe ou botão agora. As colunas "Sem envios" das obrigações permanecem literais.

## 11. Ordem de implementação

1. Migration, enum, model e factory. Rodar `php artisan migrate`.
2. Repository e service, incluindo variáveis, pré-visualização e o `bool` do delete.
3. Form Requests.
4. Controller, rotas e placeholder. A pré-visualização entra em `store` e `update`, sem rota nova.
5. Views, navegação e home.
6. Seeder.
7. Testes da seção 12 e ajuste de `NavigationTest`.
8. `vendor/bin/pint --dirty`.
9. `php artisan test --compact`.

Comandos de geração:

```bash
php artisan make:migration create_message_templates_table
php artisan make:enum MessageTemplateStatus
php artisan make:model MessageTemplate -f
php artisan make:seeder MessageTemplateSeeder
php artisan make:class Repositories/MessageTemplateRepository
php artisan make:class Services/MessageTemplateService
php artisan make:request StoreMessageTemplateRequest
php artisan make:request UpdateMessageTemplateRequest
php artisan make:controller MessageTemplateController --resource
php artisan make:test MessageTemplateTest
```

Não acrescentar método `preview` ao resource. Não gerar model de mensagem.

## 12. Testes

Os testes novos usam `RefreshDatabase`. A migração deixa o padrão na base de cada teste.

### `tests/Feature/MessageTemplateTest.php`

| Teste | Asserções |
| --- | --- |
| Padrão da migração | um registro; nome "Lembrete de vencimento"; conteúdo da RN-10; `active`; `is_default` verdadeiro; `meta_template_name` nulo |
| Lista, colunas e padrão | a lista vê Nome, Conteúdo, Status, Ações, "Padrão" e a nota da Meta; o padrão não vê o formulário de exclusão; não vê "Enviar" |
| Inativo visível | factory `inactive()`; a lista vê "Inativo" e o nome |
| Paginação | apaga a tabela só dentro do teste e vê "Nenhum modelo cadastrado."; cria 11 personalizados com nomes controlados; a página 1 não mostra o nome exclusivo da página 2; a página 2 existe; a ordem é o nome |
| Busca | nome e conteúdo filtram; o outro registro não aparece |
| Vazio de busca | com `q` sem resultado, "Nenhum modelo encontrado." |
| Curinga | `q=100%` responde 200 e não lista o personalizado cujo nome e conteúdo não contêm esse texto |
| Cadastro válido | nome "Cobranca amigavel", conteúdo com as três variáveis, status `active`, e também `is_default=1` e `meta_template_name=aprovado` no POST; redirect; flash "Modelo cadastrado."; banco com `is_default` falso e `meta_template_name` nulo |
| Subconjunto de variáveis | texto sem variável, só `{{nome}}`, só `{{vencimento}}` e `{{nome}}` repetido são aceitos e gravados como personalizados |
| Cadastro inválido | `from(route('templates.create'))`; nome vazio, conteúdo `Olá, {{telefone}}`, status `archived`; redirect; erros em `name`, `body` e `status`; input preservado; a mensagem cita `{{telefone}}` e as três variáveis; só o padrão existe |
| Chaves e espaços | `{{ nome }}` e `Olá, {{nome` geram erro em `body`; o segundo caso vê "Feche todas as variáveis com {{ e }}." |
| Nome repetido | o nome do padrão é recusado com "Este nome já está cadastrado." |
| Conteúdo longo | 1025 caracteres são recusados; 1024 com variáveis válidas são aceitos |
| Pré-visualização sem gravar | POST em `templates.store` com `intent=preview` e o conteúdo do padrão; redirect de volta; sessão `preview` igual a "Olá, Ana Lima. Lembramos que IPTU vence em 15/01/2026."; a quantidade de modelos não muda |
| Pré-visualização parcial | POST com `intent=preview` e conteúdo "Olá, {{nome}}."; a sessão é "Olá, Ana Lima."; o texto não contém "IPTU" nem "15/01/2026"; nenhum registro é criado |
| Pré-visualização na edição | PUT do padrão com `intent=preview` e o nome atual; não altera `updated_at` nem o conteúdo; a sessão traz o texto resolvido. O mesmo nome não dispara "Este nome já está cadastrado." |
| Pré-visualização inválida | POST com `intent=preview` e `{{telefone}}` redireciona com erro em `body`, sem `preview` e sem criar ou atualizar registro |
| Detalhe | vê o conteúdo, a pré-visualização, "Não vinculado" e a frase de proteção; não vê "Enviar"; id inexistente é 404 |
| Edição do padrão | PUT muda o conteúdo; `is_default` continua verdadeiro; flash "Modelo atualizado." |
| Exclusão do padrão | a página não contém a confirmação de exclusão; `delete` redireciona de volta; flash "O modelo padrão não pode ser excluído."; o registro permanece |
| Exclusão do personalizado | a página contém "Excluir este modelo? Esta ação não pode ser desfeita."; `delete` redireciona; flash "Modelo excluído."; some só o personalizado; contato e obrigação de exemplo, se criados no teste, permanecem |
| Atualização e ausência | editar personalizado grava nome, conteúdo e status; GET, PUT e DELETE de id inexistente respondem 404 |
| Seeder | em `production`, `MessageTemplateSeeder` e `DatabaseSeeder` não criam personalizado nem usuário; o padrão continua único; em `local` e em `testing`, depois de apagar só `is_default = false`, o seeder deixa 3 personalizados e 1 padrão |

O fluxo inválido usa `assertRedirect`, `assertSessionHasErrors` e `assertSessionHasInput`. Não usar `assertStatus(422)`.

O teste de paginação que esvazia a tabela precisa recriar os 11 registros pela factory. Os outros testes não apagam o padrão, salvo esse caso isolado.

### Ajustes da Fase 1 e da Fase 2

- `NavigationTest`: a home vê "Gerenciar modelos". Mensagens e Configurações continuam vendo a frase de espera. `/modules/templates` passa a ser 404, junto com `/modules/obligations` e `/modules/billing`. A listagem de modelos não vê a frase de espera.
- `ContactTest` e `ObligationTest` permanecem. O seeder novo roda dentro do mesmo `if` e não muda a contagem de 12 contatos, 8 obrigações nem 1 usuário de teste.
- Não duplicar o bloqueio de contato com obrigação.

### Comando

```bash
php artisan test --compact
```

A suíte inteira, inclusive `ExampleTest`, `ContactTest` e `ObligationTest`, precisa passar.

## 13. Verificação manual

Com a migration aplicada e o frontend compilado, se as views usarem classe nova do Tailwind:

1. Abrir a home e a navegação no desktop e abaixo de 768px. Modelos abre a lista. Mensagens continua na espera.
2. Confirmar o padrão, o badge, a ausência de Excluir e a nota sobre a Meta.
3. Cadastrar um personalizado com as três variáveis. Confirmar o flash e que o detalhe mostra "Não vinculado" e a pré-visualização com Ana Lima, IPTU e 15/01/2026.
4. Pré-visualizar "Olá, {{nome}}." sem salvar. O texto mostra Ana Lima e não mostra IPTU nem a data. A lista não ganha registro.
5. Enviar `{{telefone}}` e uma chave aberta. Confirmar o retorno, o resumo, as bordas e os valores preservados.
6. Marcar um personalizado como Inativo. Ele continua na lista. Não deve aparecer Enviar.
7. Tentar excluir o padrão e ler o flash de erro. Excluir o personalizado e confirmar que o padrão, os contatos e as obrigações permanecem.
8. Abrir `/modules/templates` e um id de modelo inexistente.
9. Em `local`, `php artisan db:seed` pode criar os três exemplos. Em `production`, o mesmo comando não cria personalizado nem o usuário de teste; o padrão da migração permanece.

## 14. Critério de pronto

- A spec 003 está coberta por CA-01 a CA-15.
- O padrão existe em todo ambiente, pode ser editado e desativado, e não pode ser excluído. A edição não é bloqueada por vínculo com a Meta.
- Personalizado tem CRUD, status e variáveis validadas, em qualquer subconjunto das três suportadas.
- A pré-visualização substitui só as variáveis presentes, recusa variável inválida e não grava. Não há conversão para placeholders da Meta.
- `meta_template_name` existe, nasce nulo e não tem formulário.
- Não existe tabela `messages` nem endpoint de envio ou de sincronização.
- `php artisan test --compact` passa.
- Pint passa nos PHP tocados.
- Nenhum pacote novo foi adicionado.
