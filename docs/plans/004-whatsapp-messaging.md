# Plano 004 — WhatsApp e envio manual

| | |
| --- | --- |
| Spec | [docs/specs/004-whatsapp-messaging.md](../specs/004-whatsapp-messaging.md) |
| Estado | Revisado; código ainda não iniciado |
| Data | 2026-11-23 |

Este plano implementa a spec 004 sobre as Fases 1, 2 e 3. Não instala dependência, não cria scheduler, não versiona proxy e não coloca segredo no código.

A revisão de 2026-11-23 acompanha a spec: campo de webhook `messages`, telas só locais, mês em `America/Sao_Paulo` e comando de conexão sem gravar histórico.

## 1. Base atual

- Contatos, obrigações e modelos já seguem Controller → Service → Repository → Model, com Form Request e SQLite.
- `ContactService::delete()` recusa contato com obrigação e devolve `bool`. O flash de erro já existe.
- `message_templates.meta_template_name` existe, é nulo e não entra no formulário. O padrão "Lembrete de vencimento" não está ligado a um template da Meta.
- A pré-visualização dos modelos usa Ana Lima, IPTU e 15/01/2026. A do envio não reutiliza esses literais.
- `/modules/messages` e `/modules/settings` ainda são páginas de espera. Obrigações mostram "Sem envios" e a frase "Os envios ainda não são registrados."
- `config/services.php` não tem bloco WhatsApp. O `.env` local pode ter as chaves; ele não é commitado. O `.env.example` ainda não as lista.
- `bootstrap/app.php` não exclui rota de CSRF. `routes/console.php` só tem o comando `inspire`.
- `QUEUE_CONNECTION` é `database` no exemplo e `sync` no PHPUnit. Esta fase não cria job.
- O Sail usa a imagem `sail-8.5/app`. O `composer.json` permanece em `php: ^8.3`. Não alterar a versão do PHP.
- `phpunit.xml` usa SQLite em memória. Não há `Http::fake()` de WhatsApp ainda.

## 2. Decisões de implementação

- Fluxo de mensagem: `MessageController` → `MessageService` → `MessageRepository` → `Message`. O HTTP fica só em `WhatsAppService`. O webhook fica em `WhatsAppWebhookController` e `WhatsAppWebhookService`.
- Classes concretas. Sem interface, DTO, Action ou binding novo. O resultado da API é um array associativo, não um objeto de transporte.
- Sem job e sem lote. Cada `POST /messages` faz no máximo uma chamada à Meta. O operador vê o aceite no redirect, e o webhook só atualiza uma linha. A Meta recebe 200 depressa. O limite seguro é uma mensagem por requisição, com `timeout` de 15 segundos.
- A URL é `POST https://graph.facebook.com/{version}/{phone_number_id}/messages`, com `Authorization: Bearer {token}` e `timeout` de 15 segundos, `connectTimeout` de 5. Versão, token e phone number id vêm de `config('services.whatsapp')`.
- Modo de teste padrão verdadeiro. A comparação usa só dígitos. O número real não aparece em PHP, Blade, factory ou teste.
- `hello_world` / `en_US` / `v25.0` são defaults de configuração do teste de conexão, não do modelo interno padrão.
- A ordem dos parâmetros é a coluna `meta_parameter_order`. Vazio omite `components`. Não há conversão fixa para `{{1}}`, `{{2}}` e `{{3}}`.
- Texto livre só com `whatsapp_last_inbound_at` nas últimas 24 horas. Sem Direct Send.
- `sent_at` não é preenchido no aceite. HTTP 200 da Meta grava `accepted`. O primeiro webhook `sent`, `delivered` ou `read` preenche `sent_at` se ainda for nulo. Evento repetido ou anterior não rebaixa. Depois de `failed` ou `read`, nada reabre a linha.
- Indicadores contam linhas de `messages`, não de `whatsapp_status_events`. Entram status `sent`, `delivered` e `read`, com `is_test` falso e `sent_at` no mês de `America/Sao_Paulo`. `accepted` e `failed` ficam de fora, mesmo quando a falha já tem `sent_at`. A coluna Mensagem mostra o status real da tentativa não teste mais recente. Aceite continua "Aceita pela Meta".
- Exclusão: obrigação continua a ser a primeira checagem do contato, para o flash da Fase 2 não mudar. Mensagem é a segunda. Obrigação e modelo também recusam exclusão quando há tentativa. Foreign keys `restrictOnDelete`.
- CSRF: excluir só `webhooks/whatsapp`. Essa exclusão não dispensa o verify token nem o `X-Hub-Signature-256`. Não criar login. A autenticação permanece para a Fase 6.
- As telas ficam no uso local. Não apontar túnel para a porta do Sail nem do `artisan serve`. Um webhook real, se for exercitado fora dos testes, passa por um proxy local que encaminha somente `/webhooks/whatsapp`, e somente com `APP_DEBUG=false`. Sem esse proxy, a entrega 4.3 se prova com payloads assinados no PHPUnit. Não adicionar serviço ao `compose.yaml`.

## 3. Arquivos

### Criar

| Arquivo | Entrega | Responsabilidade |
| --- | --- | --- |
| `database/migrations/*_create_messages_table.php` | 4.1 | `messages`, evento de status, coluna de entrada no contato e colunas Meta do modelo |
| `app/Enums/MessageStatus.php` | 4.1 | `accepted`, `sent`, `delivered`, `read`, `failed`, com `label()` |
| `app/Models/Message.php` | 4.1 | fillable, casts, relações |
| `app/Models/WhatsAppStatusEvent.php` | 4.3 | idempotência do webhook |
| `database/factories/MessageFactory.php` | 4.1 | tentativas de teste |
| `app/Services/WhatsAppService.php` | 4.1 | HTTP de template e de texto |
| `app/Console/Commands/WhatsAppTestCommand.php` | 4.1 | `whatsapp:test` |
| `app/Repositories/MessageRepository.php` | 4.2 | histórico, existência, indicadores |
| `app/Services/MessageService.php` | 4.2 | pré-visualização, envio, reenvio e regras |
| `app/Http/Requests/StoreMessageRequest.php` | 4.2 | validação do formulário |
| `app/Http/Controllers/MessageController.php` | 4.2 | listagem, formulário, envio, detalhe e reenvio |
| `resources/views/messages/index.blade.php` | 4.2 | histórico |
| `resources/views/messages/create.blade.php` | 4.2 | formulário |
| `resources/views/messages/show.blade.php` | 4.2 | detalhe |
| `resources/views/messages/_form.blade.php` | 4.2 | campos e pré-visualização |
| `app/Services/WhatsAppWebhookService.php` | 4.3 | verificação, assinatura, status e entrada |
| `app/Http/Controllers/WhatsAppWebhookController.php` | 4.3 | GET e POST do webhook |
| `tests/Feature/WhatsAppServiceTest.php` | 4.1 | HTTP simulado |
| `tests/Feature/MessageTest.php` | 4.2 | envio manual, reenvio e bloqueios |
| `tests/Feature/WhatsAppWebhookTest.php` | 4.3 | assinatura, idempotência e indicadores |

### Alterar

| Arquivo | Mudança |
| --- | --- |
| `config/services.php` | bloco `whatsapp` lendo o ambiente |
| `.env.example` | chaves vazias e defaults não secretos |
| `phpunit.xml` | valores fictícios da API e modo de teste |
| `bootstrap/app.php` | CSRF excluído só em `webhooks/whatsapp`; a assinatura continua obrigatória no POST |
| `routes/web.php` | mensagens, reenvio e webhook; `messages` sai do placeholder |
| `routes/console.php` | não é o lugar do comando; o comando fica em `app/Console/Commands` e o Laravel 13 o registra sozinho |
| `app/Models/Contact.php` | `messages()` e cast de `whatsapp_last_inbound_at` |
| `app/Models/Obligation.php` | `messages()` |
| `app/Models/MessageTemplate.php` | `messages()` e os novos campos no fillable |
| `app/Services/ContactService.php` | também recusa histórico de mensagens |
| `app/Http/Controllers/ContactController.php` | flash específico quando a recusa for só por mensagem |
| `app/Services/ObligationService.php` | recusa exclusão com mensagens |
| `app/Http/Controllers/ObligationController.php` | flash e dados reais das duas colunas |
| `app/Services/MessageTemplateService.php` | recusa exclusão com mensagens; não muda a pré-visualização demonstrativa |
| `app/Http/Controllers/MessageTemplateController.php` | flash de histórico |
| `app/Http/Requests/StoreMessageTemplateRequest.php` | campos opcionais da Meta |
| `resources/views/templates/_form.blade.php` | nome, idioma e ordem |
| `resources/views/templates/show.blade.php` | mostra o vínculo ou "Não vinculado" |
| `resources/views/obligations/index.blade.php` | situação e contagem; remove o aviso provisório |
| `resources/views/obligations/show.blade.php` | os mesmos indicadores |
| `resources/views/partials/nav.blade.php` | Mensagens aponta para `messages.index` |
| `resources/views/home.blade.php` | cartão Mensagens funcional |
| `app/Http/Controllers/ModulePlaceholderController.php` | remove `messages` |
| `tests/Feature/NavigationTest.php` | Mensagens deixa de ser espera |
| `tests/Feature/ObligationTest.php` | deixa de exigir a frase provisória; "Sem envios" continua válido sem histórico |
| `tests/Feature/ContactTest.php` | exclusão sem mensagem continua passando |
| `tests/Feature/MessageTemplateTest.php` | exclusão sem mensagem continua passando |

### Não criar

Job, scheduler, endpoint de lote, model de template da Meta separado, tela de configurações, login, proxy ou túnel versionado, cliente Guzzle fora do HTTP Client do Laravel, e coluna de contagem em `obligations`.

### Não mexer

`vite.config.js`, `resources/css/app.css`, `resources/js/app.js`, a regra de telefone, a marca `is_default` e a frase de proteção do modelo padrão.

## 4. Configuração

Em `config/services.php`:

```php
'whatsapp' => [
    'token' => env('WHATSAPP_ACCESS_TOKEN'),
    'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
    'business_account_id' => env('WHATSAPP_BUSINESS_ACCOUNT_ID'),
    'version' => env('WHATSAPP_API_VERSION', 'v25.0'),
    'test_recipient' => env('WHATSAPP_TEST_RECIPIENT'),
    'default_template' => env('WHATSAPP_DEFAULT_TEMPLATE', 'hello_world'),
    'template_language' => env('WHATSAPP_TEMPLATE_LANGUAGE', 'en_US'),
    'test_mode' => env('WHATSAPP_TEST_MODE', true),
    'webhook_verify_token' => env('WHATSAPP_WEBHOOK_VERIFY_TOKEN'),
    'app_secret' => env('WHATSAPP_APP_SECRET'),
],
```

`env()` só nesse arquivo. Services usam `config()`.

`.env.example` lista as mesmas chaves. Token, ids, destinatário, verify token e app secret ficam vazios. Versão `v25.0`, template `hello_world`, idioma `en_US` e modo de teste `true`.

`phpunit.xml` define token `test-token`, phone number id `1001`, destinatário fictício `15551230000`, os mesmos defaults públicos e segredo `test-secret`. Não usar o número canadense real.

## 5. Migration

Uma migration só, na entrega 4.1, para as entregas seguintes não precisarem alterar tabela.

```php
Schema::create('messages', function (Blueprint $table) {
    $table->id();
    $table->foreignId('contact_id')->constrained()->restrictOnDelete();
    $table->foreignId('obligation_id')->nullable()->constrained()->restrictOnDelete();
    $table->foreignId('message_template_id')->nullable()->constrained('message_templates')->restrictOnDelete();
    $table->text('body');
    $table->string('origin')->default('manual');
    $table->string('wamid')->nullable()->index();
    $table->string('status')->index();
    $table->timestamp('accepted_at')->nullable();
    $table->timestamp('sent_at')->nullable()->index();
    $table->timestamp('delivered_at')->nullable();
    $table->timestamp('read_at')->nullable();
    $table->timestamp('failed_at')->nullable();
    $table->string('error_code')->nullable();
    $table->text('error_message')->nullable();
    $table->boolean('is_test')->default(false)->index();
    $table->timestamps();
});

Schema::create('whatsapp_status_events', function (Blueprint $table) {
    $table->id();
    $table->string('wamid');
    $table->string('status');
    $table->timestamps();
    $table->unique(['wamid', 'status']);
});

Schema::table('contacts', function (Blueprint $table) {
    $table->timestamp('whatsapp_last_inbound_at')->nullable();
});

Schema::table('message_templates', function (Blueprint $table) {
    $table->string('meta_template_language')->nullable();
    $table->string('meta_parameter_order')->nullable();
});
```

`meta_template_name` já existe. `down()` remove as tabelas novas e as colunas novas. Sem `reminder_type`. Sem soft delete.

## 6. Entrega 4.1 — Integração

### WhatsAppService

```php
public function sendTemplate(string $to, string $name, string $language, array $parameters = []): array
public function sendText(string $to, string $body): array
```

O array de retorno tem `accepted` (bool), `wamid` (?string), `error_code` (?string) e `error_message` (?string).

`sendTemplate` monta `messaging_product`, `to`, `type=template` e `template.name` / `template.language.code`. Se `$parameters` não for vazio, inclui `components` com um bloco `body` e um item `type=text` por valor, na ordem recebida. `hello_world` chama esse método com array vazio.

`sendText` usa `type=text` e `text.body`. Só o `MessageService` decide se a janela permite chamar esse método.

Erros:

- `ConnectionException` ou timeout: `accepted=false`, código nulo, mensagem "Não foi possível conectar à Meta."
- JSON com `error.code` 190, ou HTTP 401: mensagem "O token de acesso da Meta é inválido ou expirou." Código `190` quando vier no JSON.
- JSON com `error.code` 130497: mensagem "A Meta recusou este destinatário (130497)." Código `130497`.
- Outro `error`: código em string e `error.message` da Meta, cortada em 500 caracteres. Se a Meta não mandar mensagem, "A Meta não aceitou a mensagem."
- HTTP 200 com `messages.0.id`: `accepted=true` e esse id.

O token não entra em exceção, log nem no array além do header da requisição. O teste confirma que a tabela e a sessão não contêm `test-token`.

`assertTestRecipient(string $phone): bool` compara dígitos com `config('services.whatsapp.test_recipient')` quando `test_mode` é verdadeiro. Fora do modo de teste, devolve verdadeiro. Telefone vazio no modo de teste é falso.

### Comando

`php artisan whatsapp:test`

Ação técnica de desenvolvimento, sem tela e sem argumento de destinatário.

- Só executa a chamada quando `app()->environment('local')` e `test_mode` estão ligados. Nos demais ambientes, inclusive `testing` e `production`, termina com erro e não chama a Meta. O teste automatizado que precisa vê-lo passar troca o ambiente para `local` só naquele caso.
- Se faltar token, phone number id ou destinatário, termina com erro e não chama a Meta.
- Chama `sendTemplate` com `WHATSAPP_TEST_RECIPIENT`, `default_template` e `template_language`, sem parâmetros. No desenvolvimento esses valores são `hello_world` e `en_US`. Não consulta `message_templates`.
- Não cria contato e não grava `messages`. Um contato já existente com esse telefone não recebe linha de teste por causa do comando. O histórico comercial e os indicadores permanecem iguais.
- Escreve na saída o `wamid` ou a descrição segura. Não imprime token, app secret, cabeçalho `Authorization` nem telefone. `WhatsAppService` também não registra isso em log e não relança a exceção do HTTP Client, para a página de debug não exibir a credencial.

### Testes da 4.1

`Http::fake()` e `Http::assertSent` / `Http::assertNothingSent`.

| Teste | Asserção |
| --- | --- |
| Template aceito | URL da versão e do phone number id; JSON sem `components`; `wamid` presente; `accepted` verdadeiro; status ainda não é entrega |
| `hello_world` em `local` | nome e idioma saem da config; não há parâmetro; não nasce contato nem linha em `messages`; a saída não contém `test-token` |
| Comando fora de `local` | `Http::assertNothingSent` e nenhuma linha, mesmo com contato daquele telefone |
| Timeout | `ConnectionException` vira falha de conexão, sem exceção do cliente escapando |
| HTTP 500 | falha genérica |
| Código 190 | frase de token; o token não está no resultado nem no log |
| Código 130497 | código e frase de destinatário |
| Modo de teste | outro telefone não gera HTTP |

## 7. Entrega 4.2 — Mensagens

### Regras no MessageService

`preview(array $data): string` troca só as variáveis presentes. `nome` vem do contato. `obrigacao` e `vencimento` só existem com obrigação. Não chama `WhatsAppService`.

`send(array $data): Message` só roda depois da validação. Confere de novo a trava de teste. Se falhar, lança `ValidationException` no campo `contact_id` com a frase do modo de teste, sem HTTP e sem linha. Monta o corpo resolvido. No tipo template, lê a ordem e resolve os textos; valor vazio impede o envio. Grava a linha só depois da resposta: aceite ou falha. `is_test` copia o modo de teste. `accepted_at` ou `failed_at` usa `now()`.

`resend(Message $message): array` devolve os campos para o formulário. Não envia sozinho.

Parâmetros do template: `explode` de `meta_parameter_order` por vírgula, nomes válidos apenas `nome`, `obrigacao` e `vencimento`. Idioma do modelo ou, se vazio, `template_language` da config.

Texto livre: se a janela estiver fechada, o Form Request já recusou. O service não chama `sendTemplate`.

### Form Request

`composition`: `template` ou `text`.

`contact_id`: required, integer, exists. Closure: contato ativo e consentido; no modo de teste, telefone igual ao configurado.

`obligation_id`: nullable, integer, exists. Closure: se vier, o `contact_id` da obrigação é o escolhido. Se o modelo ou o texto livre contiver `{{obrigacao}}` ou `{{vencimento}}`, o campo é obrigatório.

`message_template_id`: required se `composition=template`, exists, status `active`, `meta_template_name` não vazio.

`body`: required se `composition=text`, string, max 4096. Reutiliza `MessageTemplateService::bodyErrors()`. Closure da janela: `whatsapp_last_inbound_at` não nulo e posterior a `now()->subHours(24)`.

`prepareForValidation` faz trim do texto livre. Não mexe no telefone do contato.

Mensagens em português, as frases da spec. Falha redireciona. Sem 422.

### Controller e rotas

```php
Route::get('/messages/{message}/resend', [MessageController::class, 'resend'])->name('messages.resend');
Route::resource('messages', MessageController::class)->only(['index', 'create', 'store', 'show']);
```

O reenvio fica antes do resource. `whereIn` do placeholder fica só `settings`.

| Método | Comportamento |
| --- | --- |
| `index` | página de 10, `latest('id')` depois da ordenação `created_at desc`, `with` contato, obrigação e modelo |
| `create` | contatos por nome, obrigações por contato e vencimento, modelos elegíveis por nome |
| `store` | `intent=preview` volta com `session('preview')` e `session('preview_parameters')`; senão envia e redireciona ao detalhe |
| `show` | detalhe |
| `resend` | a view de criação com o modelo da mensagem anterior nos `old` iniciais |

Não há `validate()` no controller. Sucesso do aceite: `to_route('messages.show', $message)`.

`contact_id` é um inteiro. Uma lista de contatos não passa da validação e não inicia um laço. O controller chama `MessageService::send()` uma vez. Com o timeout de 15 segundos, a requisição fica limitada a uma ida à Meta. Não há rota de lote e não há job.

### Views

Lista: "Mensagens", "Nova mensagem", nota "Uma aceitação da Meta ainda não é uma entrega." Colunas: Data (`d/m/Y H:i` em `America/Sao_Paulo`), Contato, Modelo, Obrigação, Status, Ações. Modelo vazio aparece "Texto livre". Obrigação vazia, "—". Badge "Teste" quando `is_test`. Status pelo `label()`, sem promover `accepted` a enviada ou entregue. Ação Ver. Vazio: "Nenhuma mensagem registrada." O fuso da aplicação continua UTC; só esta formatação e a conta do mês usam `America/Sao_Paulo`.

Formulário: select de contato; select de obrigação com opção "Nenhuma" e o rótulo `contato — título`; radios Modelo e Texto livre; select de modelo; textarea; Pré-visualizar com `name=intent value=preview`; Enviar com `confirm('Enviar esta mensagem pelo WhatsApp?')`. Pré-visualização mostra o texto e, se houver, a lista ordenada de parâmetros. Aviso fixo quando não existe modelo elegível. O bloco de texto livre inclui a frase da janela; o servidor é quem impede o envio.

Detalhe: os campos do histórico, `wamid` ou "—", descrição de erro quando houver, e link Reenviar. Sem Excluir.

Home: cartão "Mensagens", texto "Acompanhe e envie mensagens manuais.", botão "Gerenciar mensagens". Configurações permanece no loop de espera.

Navegação: `route('messages.index')` e `routeIs('messages.*')`.

### Modelos

O Form Request de modelo ganha:

- `meta_template_name`: nullable, string, max 512
- `meta_template_language`: nullable, string, max 16
- `meta_parameter_order`: nullable, string, regex `/^(nome|obrigacao|vencimento)(,(nome|obrigacao|vencimento))*$/`

Vazio vira nulo. Esses campos passam a ser fillable. `is_default` continua de fora. O show mostra o nome do template ou "Não vinculado", o idioma ou "—", e a ordem ou "Sem parâmetros".

### Exclusões

`ContactService::delete()`: se houver obrigação, `false` como hoje. Senão, se `MessageRepository::existsForContact`, `false`. O controller, quando for falso, distingue: obrigação mantém o flash atual; só mensagem usa o flash novo. A forma mais simples é o service devolver `false` e o controller olhar de novo a existência, ou o service devolver a razão em string. Para não criar DTO, o controller chama dois métodos já existentes: o delete atual continua `bool`, e o controller só troca o flash se não houver obrigação e o delete deu falso. Isso distingue os dois casos sem mudar o retorno da Fase 2.

`ObligationService::delete(): bool` passa a consultar `existsForObligation`. Flash novo. O repository de obrigação continua apagando; o service recusa antes.

`MessageTemplateService::delete()`: se for padrão, `false` e o flash atual. Se houver mensagens, `false` e o flash de histórico. O controller precisa separar padrão e histórico do mesmo jeito: padrão primeiro.

### Testes da 4.2

Usam `RefreshDatabase` e `Http::fake()`.

| Teste | Asserção |
| --- | --- |
| Listagem | 11 linhas; a página 1 não mostra a mais antiga; a página 2 mostra |
| Envio de template | contato autorizado, ativo e consentido; modelo com `meta_template_name`; redirect; flash de aceite; `sent_at` nulo; `is_test` verdadeiro |
| Pré-visualização | `intent=preview`; sessão com o nome real do contato; contagem de mensagens não muda; HTTP não sai |
| Obrigação opcional | sem obrigação e sem as duas variáveis, aceita |
| Obrigação obrigatória | corpo com `{{vencimento}}` sem obrigação volta com erro; obrigação de outro contato volta com erro |
| Parâmetros | ordem `nome,vencimento` gera dois textos, nome e data `d/m/Y`, e não inventa um terceiro |
| Inativo e sem consentimento | erro do campo; `Http::assertNothingSent`; contagem zero |
| Modelo inelegível | inativo ou sem nome Meta; sem HTTP |
| Modo de teste | telefone diferente do `15551230000`; sem HTTP e sem linha |
| Reenvio | segunda linha; a primeira conserva o `wamid` |
| Texto livre fechado | erro da janela; sem HTTP |
| Texto livre aberto | `whatsapp_last_inbound_at` há uma hora; JSON `type=text` |
| Falha 130497 no manual | linha `failed`, código `130497`, flash de não aceite |
| Exclusões | contato só com mensagem; obrigação com mensagem; modelo com mensagem; contato com obrigação mantém o flash antigo |
| Navegação | home vê "Gerenciar mensagens"; `/modules/messages` é 404; a lista não vê a frase de módulo pendente |

## 8. Entrega 4.3 — Webhook e indicadores

### Webhook

A Cloud API é inscrita no campo `messages`. Não há inscrição em `message_status`. O mesmo campo entrega os dois arranjos.

`GET /webhooks/whatsapp`: se `hub_mode` é `subscribe` e `hub_verify_token` coincide com a config, responde o `hub_challenge` em texto puro. Senão, 403. Esse GET não usa o App Secret.

`POST`: lê o corpo cru. Calcula `hash_hmac('sha256', $raw, $secret)`. Compara com o header `X-Hub-Signature-256` sem o prefixo `sha256=`, em `hash_equals`. Segredo vazio ou diferença: 403, sem gravar. A verificação acontece no controller antes de qualquer escrita. A isenção de CSRF não a substitui.

Para cada item de `entry.*.changes[]` cujo `field` seja `messages`:

- em `value.statuses[]`, `firstOrCreate` em `whatsapp_status_events` por `wamid` e `status`. Se o par já existia, ignora. Acha `Message` pelo `wamid`. Se não achar, segue. Aplica a RN-08: a ordem é `accepted` < `sent` < `delivered` < `read`; status igual ou anterior não muda datas; `failed` só encerra quem ainda não está `read`; depois de `failed` ou `read`, o evento é descartado. O instante gravado vem de `Carbon::createFromTimestampUTC($timestamp)`. A conta do mês e a tela convertem esse instante para `America/Sao_Paulo`. `failed` grava `error_code` e a descrição do primeiro item de `errors`, se vierem.
- em `value.messages[]`, normaliza `from` para dígitos, acha o contato por `phone` e, se achar, grava `whatsapp_last_inbound_at`. Se não achar, não cria contato.

Qualquer `field` diferente de `messages` é ignorado. A resposta de um POST assinado é 200, mesmo sem trabalho.

`bootstrap/app.php` fica assim, sem alargar a exceção:

```php
$middleware->validateCsrfTokens(except: [
    'webhooks/whatsapp',
]);
```

As rotas de contatos, obrigações, modelos e mensagens continuam protegidas por CSRF.

### Exposição

Não alterar `compose.yaml` e não instalar túnel. O operador usa a aplicação em `127.0.0.1` ou na rede local do Sail.

Se for preciso receber um webhook real da Meta, o túnel aponta para um proxy local que encaminha apenas `GET` e `POST` `/webhooks/whatsapp` ao aplicativo. O túnel não aponta para a porta pública do Sail nem do `artisan serve`. Essa prova manual só vale com `APP_DEBUG=false`. O proxy não entra no repositório.

Sem esse arranjo, a entrega 4.3 não fica bloqueada: os testes assinam payloads e cobrem verificação, status, entrada, idempotência e ordem. O PHPUnit não abre túnel.

### Indicadores

`MessageRepository`:

```php
public function latestForObligation(Obligation $obligation): ?Message
public function sentThisMonth(Contact $contact): int
public function existsForContact(Contact $contact): bool
public function existsForObligation(Obligation $obligation): bool
public function existsForTemplate(MessageTemplate $template): bool
```

`latestForObligation` filtra `is_test=false`, ordena `id desc`, e devolve o status real. `accepted` não é exibido como enviada nem entregue.

`sentThisMonth` conta linhas de `messages` com `is_test=false`, status em `sent`, `delivered` ou `read`, e `sent_at` entre o início e o fim do mês corrente em `America/Sao_Paulo`. Não lê `whatsapp_status_events`. Não usa `config('app.timezone')`, que permanece `UTC` em `config/app.php`. Uma mensagem `failed` com `sent_at` preenchido não entra. Vários eventos da mesma linha contam uma vez.

`ObligationController@index` e `show` passam o rótulo e a contagem calculados. A view troca o literal "Sem envios" por esse rótulo, e o segundo "Sem envios" pelo inteiro. Remove a frase provisória. Não adiciona botão Enviar na obrigação.

Rótulos do enum:

| Valor | Tela |
| --- | --- |
| `accepted` | Aceita pela Meta |
| `sent` | Enviada |
| `delivered` | Entregue |
| `read` | Lida |
| `failed` | Falhou |

Sem registro não teste: a view mostra "Sem envios". Zero envios no mês mostra `0`.

### Testes da 4.3

| Teste | Asserção |
| --- | --- |
| Verificação | challenge correto volta o texto; token errado é 403 |
| Assinatura | corpo sem `X-Hub-Signature-256` válido é 403 e não muda a mensagem; a isenção de CSRF não é tratada como autorização |
| Campo `messages` | `value.statuses[]` e `value.messages[]` no campo `messages` são processados |
| Campo ignorado | um payload cujo `field` é `message_status` responde 200 e não altera a tentativa |
| `sent` | preenche `sent_at` e status; HTTP 200 anterior da Meta não tinha feito isso |
| Repetido | o mesmo POST duas vezes deixa um evento e a mesma data; a conta do mês continua 1 |
| Fora de ordem | `delivered` preenche `sent_at`; um `sent` posterior não rebaixa; um `delivered` depois de `read` também não |
| `failed` | código e descrição; a linha continua; um `sent` posterior não reabre |
| Entrada | contato conhecido ganha `whatsapp_last_inbound_at`; desconhecido não é criado |
| Mês | duas mensagens não teste com status `sent` ou superior neste mês contam 2; três eventos da mesma mensagem contam 1; aceite, falha com `sent_at` e teste ficam de fora |
| Virada do mês | `sent_at` em `2026-12-01 02:30:00` UTC cai em 30/11 às 23:30 em `America/Sao_Paulo` e conta em novembro, não em dezembro |
| Obrigação | sem mensagem vê "Sem envios"; aceita vê "Aceita pela Meta"; entregue vê "Entregue"; não vê a frase provisória nem coluna nova em `obligations` |
| `wamid` desconhecido | 200 e nenhuma linha nova |
| CSRF | a lista `except` de `validateCsrfTokens` contém `webhooks/whatsapp` e não contém as rotas administrativas |

## 9. Ordem

### 4.1

1. Configuração, `.env.example` e `phpunit.xml`.
2. Migration, enum, model e factory.
3. `WhatsAppService`, comando e testes de HTTP.
4. `vendor/bin/pint --dirty` nos arquivos novos e `php artisan test --compact --filter=WhatsApp`.

### 4.2

1. Repository e `MessageService`.
2. Ajustes de exclusão em contato, obrigação e modelo.
3. Form Request, controller, rotas e placeholder.
4. Views de mensagens, home, navegação e campos Meta do modelo.
5. `MessageTest`, `NavigationTest` e os flashes novos.
6. Pint e a suíte inteira.

### 4.3

1. Model do evento, webhook service e controller, exceção de CSRF.
2. Consultas dos indicadores e views de obrigação.
3. `WhatsAppWebhookTest` e o ajuste de `ObligationTest`.
4. Pint, `php artisan test --compact`, `php artisan migrate` e `npm run build` se a view usar classe nova.
5. Conferir rotas e a tela em desktop e abaixo de 768px, no host local. O comando real `whatsapp:test` só é rodado pelo operador, no ambiente `local`, com o `.env` local. Ele não faz parte da prova automática e não exige túnel. A prova do webhook é o `WhatsAppWebhookTest`.

Não há fase 4.4 neste plano.

## 10. Critério de pronto

- CA-01 a CA-15 cobertos, sem túnel.
- Nenhum segredo no Git. O número autorizado só existe no ambiente. Comando, log e tela não o exibem, nem o token.
- HTTP 200 da Meta fica `accepted`. O mês conta mensagem única `sent`, `delivered` ou `read`, fora de teste e fora de `failed`, no calendário `America/Sao_Paulo`.
- Texto livre sem janela não sai. Direct Send não existe.
- Reenvio cria outra linha e não é bloqueado pelo histórico. Cada requisição envia no máximo uma.
- Scheduler, lote, job e lembretes automáticos não existem.
- O webhook lê o campo `messages`, valida a assinatura mesmo sem CSRF, e não exige `message_status`.
- As telas continuam locais. Nenhum proxy foi adicionado ao repositório.
- Contato, obrigação e modelo com histórico não são excluídos.
- `php artisan test --compact` passa, inclusive as fases anteriores.
- Pint passa. Nenhum pacote novo.
