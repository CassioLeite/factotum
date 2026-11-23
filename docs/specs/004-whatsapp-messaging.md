# Spec 004 — WhatsApp e envio manual

| | |
| --- | --- |
| Identificador | `004-whatsapp-messaging` |
| Produto | Factotum |
| Estado | Revisada |
| Plano | [docs/plans/004-whatsapp-messaging.md](../plans/004-whatsapp-messaging.md) |
| Depende de | [Spec 001](001-contact-management.md), [Spec 002](002-obligation-management.md), [Spec 003](003-message-template-management.md) |
| Data | 2026-11-23 |

Esta etapa entrega a especificação. Nenhum código de aplicação faz parte deste documento. Nenhum token, telefone ou segredo aparece aqui.

A revisão de 2026-11-23 mantém o escopo: o webhook usa o campo `messages`, as telas ficam locais, o mês dos indicadores é `America/Sao_Paulo` e o teste de conexão não grava histórico.

## 1. Contexto

O Factotum já cadastra contatos, obrigações e modelos de mensagem. Mensagens ainda abre a página de espera em `/modules/messages`. As colunas Mensagem e Envios no mês das obrigações mostram o texto fixo "Sem envios". Não existe tabela de mensagens, cliente HTTP da Meta nem webhook.

O ambiente de desenvolvimento usa a WhatsApp Cloud API oficial. O Sail do repositório já aponta para a imagem PHP 8.5. As credenciais já podem existir no `.env` local, que não entra no repositório. O código só lê `config('services.whatsapp')`.

Um modelo do Factotum continua sendo um texto interno. Ele não fica aprovado na Meta só porque foi cadastrado. O `hello_world` é o template de amostra da Meta para o teste de conexão. Ele não substitui o modelo padrão "Lembrete de vencimento".

## 2. Objetivo

Permitir, sem login e sem scheduler, que o operador:

- confirme que a conta de testes fala com a Meta, usando só o destinatário autorizado;
- envie manualmente um template aprovado, ou um texto livre quando a janela de 24 horas estiver aberta;
- veja o histórico, o aceite da API e, quando o webhook chegar, a entrega;
- reenvie, gerando outra tentativa;
- leia nas obrigações a situação real da notificação e a quantidade de envios do contato no mês.

## 3. Fora desta fase

- Autenticação e autorização. Continuam previstas para a Fase 6. A tela de configurações também fica de fora.
- Scheduler, lembretes `before_due`, `on_due` e `overdue`, e qualquer envio automático.
- Recorrência de obrigações.
- Sincronização, submissão ou consulta de aprovação de templates na Meta.
- Direct Send, ou qualquer atalho de texto livre fora da janela oficial de 24 horas.
- Mapa fixo e universal `{{nome}}` → `{{1}}`, `{{obrigacao}}` → `{{2}}`, `{{vencimento}}` → `{{3}}`.
- Caixa de entrada completa, mídia, botões e catálogo.
- Jobs, filas e workers. Cada requisição de envio dispara no máximo uma mensagem, então a fila não é requisito desta fase.
- Envio em lote.
- Publicar a aplicação inteira, ou versionar túnel e proxy no repositório. O proxy restrito ao webhook, quando existir, é só operação local.
- Pacotes novos e infraestrutura nova no repositório.
- Interfaces, DTOs, Actions, DDD e Clean Architecture.

## 4. Configuração

As chaves abaixo são lidas em `config/services.php`. O valor real fica no ambiente. O `.env.example` ganha as chaves vazias, com padrão só onde o padrão não é segredo.

| Chave | Uso |
| --- | --- |
| `WHATSAPP_ACCESS_TOKEN` | Bearer da Cloud API. Nunca é gravado em mensagem, log de erro visível ou teste commitado |
| `WHATSAPP_PHONE_NUMBER_ID` | identificador do número remetente na URL da Graph API |
| `WHATSAPP_BUSINESS_ACCOUNT_ID` | identificador da conta. Esta fase não chama endpoint de gestão da conta |
| `WHATSAPP_API_VERSION` | versão da Graph API. O desenvolvimento usa `v25.0` |
| `WHATSAPP_TEST_RECIPIENT` | único destino permitido no modo de teste, só dígitos, com DDI. O número canadense autorizado permanece no ambiente |
| `WHATSAPP_DEFAULT_TEMPLATE` | nome do template usado no teste de conexão. O desenvolvimento usa `hello_world` |
| `WHATSAPP_TEMPLATE_LANGUAGE` | idioma desse teste. O desenvolvimento usa `en_US` |
| `WHATSAPP_TEST_MODE` | quando verdadeiro, bloqueia qualquer outro destino. O padrão desta fase é verdadeiro |
| `WHATSAPP_WEBHOOK_VERIFY_TOKEN` | token que a Meta devolve na verificação do webhook |
| `WHATSAPP_APP_SECRET` | segredo usado para validar `X-Hub-Signature-256` |

O teste automatizado usa valores fictícios no `phpunit.xml`. Ele não copia o token nem o telefone real.

## 5. Limites da Meta que esta fase respeita

- Mensagem iniciada pela empresa, fora de uma janela de atendimento, só pode sair como template aprovado. O Factotum não envia o corpo interno como se ele fosse esse template.
- Texto livre só é enviado se o contato tiver uma mensagem de entrada registrada há menos de 24 horas. A comparação é entre instantes. Sem esse registro, a interface bloqueia e explica. Direct Send não é oferecido.
- HTTP 200 com `messages[0].id` significa requisição aceita, não mensagem entregue. O Factotum grava o identificador e o status `accepted`. `sent`, `delivered` e `read` só nascem de `value.statuses[]`. `failed` também nasce aí, ou na hora, quando a própria chamada não é aceita. Antes da confirmação de envio, a tela mostra o status real e não simula envio nem entrega.
- A inscrição do webhook na Cloud API usa somente o campo `messages`. Esse campo traz mensagens recebidas em `value.messages[]` e atualizações de entrega em `value.statuses[]`. Esta fase não exige uma inscrição separada chamada `message_status`.
- No modo de desenvolvimento da conta, a Meta só entrega a números autorizados. O código 130497 é tratado como recusa de destinatário: o número fica gravado e a tela explica a recusa, sem expor o token.
- Token inválido ou expirado chega como falha de autenticação da Graph API, em geral código 190. A tela diz que o token é inválido ou expirou. O token em si não aparece.
- A ordem dos parâmetros posicionais é a do template aprovado daquela mensagem, configurada no modelo interno. Ela não é deduzida por uma tabela fixa das três variáveis.
- As telas administrativas permanecem no ambiente local. A aplicação inteira não é publicada por um túnel HTTPS. Se um túnel for usado para a Meta alcançar o webhook, ele aponta para um proxy que encaminha somente `GET` e `POST` de `/webhooks/whatsapp`. Sem esse proxy, o processamento se prova com testes e payloads simulados.
- Webhook e telas não são expostos publicamente enquanto `APP_DEBUG=true`.
- A isenção de CSRF vale só para `/webhooks/whatsapp`. Ela preserva o verify token no GET e a validação de `X-Hub-Signature-256` no POST.

## 6. Requisitos funcionais

### 6.1 Conexão

- **RF-01.** `WhatsAppService` envia template e, quando a janela permitir, texto, usando o HTTP Client do Laravel contra `https://graph.facebook.com/{versão}/{phone-number-id}/messages`.
- **RF-02.** O teste de conexão envia o template oficial `hello_world`, idioma `en_US`, sem parâmetros, somente para `WHATSAPP_TEST_RECIPIENT`. A ação é o comando `whatsapp:test`, disponível apenas com ambiente `local` e modo de teste ligado. Ela não usa um `MessageTemplate`, não aceita outro destinatário e não grava linha em `messages`. A saída mostra o `wamid` ou a descrição segura, nunca o token, o segredo ou o telefone.
- **RF-03.** Timeout, falha de conexão, HTTP de erro e o corpo `error` da Meta viram um resultado fracassado, com código e descrição quando a Meta os enviar. Nenhuma exceção bruta de token chega à tela.
- **RF-04.** Resposta aceita guarda o `wamid`. Isso não preenche a data de envio efetivo.
- **RF-05.** Com o modo de teste ligado, qualquer destino diferente do número autorizado é recusado antes da chamada HTTP. O comando e o envio manual obedecem a mesma trava.

### 6.2 Envio manual

- **RF-06.** Mensagens deixa a página de espera. A navegação e o cartão da home abrem `/messages`. `/modules/messages` responde 404. Configurações continua em espera.
- **RF-07.** A listagem pagina de 10 em 10, da tentativa mais recente para a mais antiga. Cada linha mostra data de criação, contato, modelo ou "Texto livre", obrigação ou "—", status e, quando for teste, o badge "Teste".
- **RF-08.** O operador escolhe um contato, uma obrigação opcional e um modelo ativo que tenha nome de template da Meta, ou escolhe texto livre.
- **RF-09.** A obrigação, quando informada, pertence ao contato escolhido. Se o texto do modelo ou o texto livre contiver `{{obrigacao}}` ou `{{vencimento}}`, a obrigação é obrigatória.
- **RF-10.** Pré-visualizar resolve as variáveis com o contato e a obrigação escolhidos, não grava tentativa e não chama a Meta. Os dados demonstrativos do módulo de modelos não são reutilizados aqui.
- **RF-11.** Enviar pede a confirmação "Enviar esta mensagem pelo WhatsApp?". Só depois disso a aplicação chama a Meta e grava a tentativa.
- **RF-12.** Reenviar abre de novo o formulário com os mesmos contato, obrigação e modelo ou texto. A confirmação cria outra linha. A linha anterior permanece.
- **RF-13.** Contato inativo ou sem consentimento não gera chamada nem linha de histórico. A tela explica o motivo.
- **RF-14.** Modelo inativo, modelo sem `meta_template_name` ou texto livre sem janela aberta não são enviados. A tela explica o motivo.
- **RF-15.** Não há edição nem exclusão de uma tentativa já registrada.

### 6.3 Histórico, webhook e indicadores

- **RF-16.** Cada tentativa guarda contato, obrigação opcional, modelo opcional, cópia do texto resolvido, origem `manual`, `wamid` quando houver, status, datas de aceite, envio, entrega, leitura e falha, código e descrição de erro, e a marca de teste.
- **RF-17.** O webhook GET confirma a inscrição quando `hub.mode` é `subscribe` e `hub.verify_token` coincide com `WHATSAPP_WEBHOOK_VERIFY_TOKEN`. A resposta é o `hub.challenge` em texto puro. Token ausente ou diferente responde 403. O POST só é processado se `X-Hub-Signature-256` for o HMAC-SHA256 do corpo cru com `WHATSAPP_APP_SECRET`. Segredo ausente ou assinatura diferente responde 403, sem gravar. A isenção de CSRF desse caminho não substitui essa verificação.
- **RF-18.** Dentro de uma alteração cujo `field` é `messages`, cada item de `value.statuses[]` atualiza a tentativa do `wamid`. Os status reconhecidos são `sent`, `delivered`, `read` e `failed`. O mesmo par `wamid` + status não é aplicado duas vezes. Evento repetido ou fora de ordem não rebaixa o status nem reabre uma falha. `failed` não apaga o histórico. Um campo diferente de `messages`, inclusive um campo chamado `message_status`, é ignorado. A resposta de um POST assinado continua 200.
- **RF-19.** No mesmo campo `messages`, cada item de `value.messages[]` de um contato conhecido atualiza a data da última entrada, usada na janela de 24 horas. Número desconhecido não cria contato. A resposta continua 200 para a Meta não reenviar indefinidamente por um número que o Factotum não cadastrou.
- **RF-20.** Na obrigação, a coluna Mensagem passa a ser a situação da tentativa não teste mais recente daquela obrigação. Sem tentativa, o texto continua "Sem envios".
- **RF-21.** Envios no mês conta mensagens únicas do contato, não eventos de webhook. Entra a linha não teste cujo status efetivo é `sent`, `delivered` ou `read` e que não terminou em `failed`. O mês é o calendário de `America/Sao_Paulo`, aplicado a `sent_at`. Aceite sem confirmação de envio não entra. Teste não entra. Várias notificações da mesma mensagem contam uma vez. Duas tentativas manuais contam duas, se cada uma cumprir a regra. O número não é coluna de `obligations`. Sem confirmação, a coluna Mensagem mostra o status real, inclusive "Aceita pela Meta", e não um sucesso simulado.
- **RF-22.** A frase "Os envios ainda não são registrados." sai da listagem de obrigações.
- **RF-23.** Contato com qualquer tentativa, inclusive falha ou teste, não é excluído. O bloqueio por obrigação da Fase 2 permanece. Obrigação ou modelo com tentativa também não é excluído.
- **RF-24.** Cada requisição de envio manual dispara no máximo uma mensagem. Não há ação de lote e não há job.
- **RF-25.** As telas de contatos, obrigações, modelos e mensagens permanecem de uso local. A autenticação continua prevista para a Fase 6 e não é implementada agora. O único caminho que pode ser alcançado pela Meta é `/webhooks/whatsapp`, atrás do proxy restrito da seção 5, com `APP_DEBUG` desligado. Sem esse ambiente, os payloads simulados dos testes cobrem o processamento.

## 7. Interface

A interface continua em português, no layout claro já usado. Sem biblioteca nova. A confirmação usa `confirm()`.

- **UI-01.** A lista de mensagens, a partir de `md`, é tabela. Abaixo disso, cartões. A wrapper usa `overflow-x-auto`.
- **UI-02.** O formulário mostra contato, obrigação, a escolha entre modelo e texto livre, o modelo elegível, o texto livre e os botões Pré-visualizar, Enviar e Cancelar. Enviar é o único que dispara.
- **UI-03.** Modelo elegível é o ativo que tem nome de template da Meta. Os demais não aparecem no select.
- **UI-04.** Texto livre permanece visível, e fica impedido, com a frase "O texto livre só fica disponível nas 24 horas seguintes a uma mensagem recebida deste contato. Não há janela de atendimento aberta.", quando a janela não existe.
- **UI-05.** A pré-visualização mostra o texto resolvido e, no envio por template, os parâmetros na ordem configurada. Ela deixa claro que a Meta recebe o template aprovado e esses parâmetros, não o corpo interno como mensagem solta.
- **UI-06.** Erro de formulário usa o resumo "Revise os campos destacados." e a borda do campo. O fluxo Blade redireciona com erros e valores, e não responde 422.
- **UI-07.** Aceite mostra o flash "Mensagem aceita pela Meta." Falha de API mostra "A Meta não aceitou a mensagem." e a descrição segura. Nenhum dos dois diz que a mensagem foi entregue.
- **UI-08.** O detalhe lista os campos do histórico, o `wamid` quando existir, e oferece Reenviar. Não oferece apagar.
- **UI-09.** Nas obrigações, Mensagem e Envios no mês continuam antes de Pagamento. Mensagem usa o rótulo do status real. "Aceita pela Meta" permanece assim até o webhook confirmar o envio. Envios no mês usa o inteiro, inclusive zero. Some o aviso de que os envios ainda não são registrados. A data e a hora exibidas no histórico de mensagens usam `America/Sao_Paulo`.
- **UI-10.** O cartão da home diz "Acompanhe e envie mensagens manuais." e aponta "Gerenciar mensagens" para a listagem.
- **UI-11.** O módulo de modelos ganha três campos opcionais: nome do template na Meta, idioma e ordem dos parâmetros. Vazio continua "Não vinculado". Não há botão de sincronizar.

Rótulos de status na tela: Aceita pela Meta, Enviada, Entregue, Lida e Falhou. No banco: `accepted`, `sent`, `delivered`, `read` e `failed`.

## 8. Regras de negócio

- **RN-01.** Toda tentativa de mensagem tem `contact_id`. `obligation_id` e `message_template_id` podem ser nulos. Texto livre não tem modelo. Template não tem corpo enviado como tipo `text`.
- **RN-02.** A origem desta fase é sempre `manual`. Não se grava `automatic` nem tipo de lembrete. Reenvio manual não consulta histórico para se recusar.
- **RN-03.** O destino é o telefone já normalizado do contato. O sistema não acrescenta DDI.
- **RN-04.** Contato inativo ou sem consentimento não é destino, mesmo no modo de teste.
- **RN-05.** Modo de teste ligado compara o telefone do contato, só dígitos, com `WHATSAPP_TEST_RECIPIENT`. Diferente, a chamada não sai. O comando de conexão nem chega a escolher outro número.
- **RN-06.** Uma tentativa nasce `accepted` só quando a Meta devolve `wamid`. Nesse momento `accepted_at` é preenchido e `sent_at` fica nulo. Falha de rede ou HTTP nasce `failed`, sem `wamid`, com `failed_at`.
- **RN-07.** `sent_at` passa a existir no primeiro webhook `sent`, `delivered` ou `read`. Aceite não o preenche. Se a notificação `sent` não chegar e a seguinte for `delivered` ou `read`, `sent_at` usa o horário desse primeiro evento, para o mês não ficar zerado por uma notificação perdida.
- **RN-08.** A progressão é aceite, enviada, entregue, lida. O mesmo status repetido não muda datas. Um status anterior, recebido depois, não rebaixa a tentativa. `failed` pode encerrar uma tentativa que ainda não foi lida e preenche código e descrição. Depois de `failed` ou `read`, um evento atrasado não altera o status. Falha não apaga a linha. A idempotência é a unicidade de `wamid` + status. Evento de webhook não é uma mensagem contável.
- **RN-09.** `is_test` é verdadeiro quando o modo de teste está ligado no momento do envio manual. Indicadores de obrigação ignoram essas linhas. O histórico continua mostrando-as, com o badge "Teste". O comando de conexão não usa esta marca, porque não grava linha.
- **RN-10.** Envios no mês conta linhas de `messages`, nunca linhas de `whatsapp_status_events`. A linha entra quando `is_test` é falso, o status é `sent`, `delivered` ou `read`, e `sent_at` cai no mês corrente de `America/Sao_Paulo`. Status `failed` fica de fora, mesmo que `sent_at` já tenha sido preenchido. Status `accepted` fica de fora. O fuso da aplicação permanece UTC e não define esse mês. A data exibida no histórico de mensagens usa `America/Sao_Paulo`, para o dia visto na tela coincidir com a conta.
- **RN-11.** A coluna Mensagem da obrigação olha só linhas não teste com aquele `obligation_id` e mostra o rótulo da mais recente. Sem linha, "Sem envios".
- **RN-12.** O corpo gravado é o texto já com as variáveis trocadas: `{{nome}}` pelo nome do contato, `{{obrigacao}}` pelo título e `{{vencimento}}` por `due_on` em `d/m/Y`. A substituição não é recursiva.
- **RN-13.** No envio por template, o JSON leva o nome e o idioma do template. Os parâmetros seguem `meta_parameter_order`, uma lista ordenada de `nome`, `obrigacao` e `vencimento`. Lista vazia significa template sem parâmetro, como `hello_world`. A ordem é a configurada, não a posição em que a variável aparece no texto interno.
- **RN-14.** Idioma vazio no modelo usa `WHATSAPP_TEMPLATE_LANGUAGE` só no comando de conexão. No envio manual, idioma vazio do modelo também cai nesse padrão. O nome do template da Meta não tem padrão: sem nome, o modelo não é elegível.
- **RN-15.** A janela de texto livre está aberta quando `whatsapp_last_inbound_at` tem menos de 24 horas em relação ao instante atual. A coluna só é escrita por item de `value.messages[]`, com assinatura válida, de um contato já cadastrado.
- **RN-16.** Excluir contato continua recusado se houver obrigação. Também passa a ser recusado se houver tentativa. A foreign key segura os dois casos. A mensagem de obrigação da Fase 2 não muda. Só histórico, sem obrigação, usa "Este contato possui mensagens e não pode ser excluído."
- **RN-17.** Excluir obrigação ou modelo personalizado que tenha tentativa é recusado. O padrão continua não excluível pela regra da Fase 3, mesmo sem tentativa.
- **RN-18.** O comando `whatsapp:test` só roda com ambiente `local` e modo de teste ligado. O destino é exclusivamente `WHATSAPP_TEST_RECIPIENT`. O template e o idioma saem da configuração, no desenvolvimento `hello_world` e `en_US`, sem parâmetros e sem `MessageTemplate`. O comando não cria contato e não grava tentativa, então não entra no histórico nem nos indicadores. Fora de `local`, com o modo de teste desligado, ou sem token, identificador do número ou destinatário, termina sem chamar a Meta. A saída não contém token, segredo nem telefone.
- **RN-19.** Uma requisição HTTP de envio contém um único contato e produz no máximo uma chamada à Meta. Não há lista de destinatários nem repetição dentro do pedido.
- **RN-20.** A exceção de CSRF cobre apenas `webhooks/whatsapp`. O GET depende do verify token. O POST depende da assinatura com o App Secret. As demais rotas continuam com CSRF. Nenhum túnel aponta para a porta da aplicação inteira. Com `APP_DEBUG=true`, nem o webhook é publicado.

## 9. Validação e feedback

A validação do formulário fica no Form Request. O controller não revalida o payload. Pré-visualizar usa as mesmas regras e não chama a Meta.

| Campo | Entrada | Regra |
| --- | --- | --- |
| Contato | `contact_id` | obrigatório, existente, ativo e com consentimento |
| Obrigação | `obligation_id` | opcional, existente, do mesmo contato; obrigatória se o texto usar `{{obrigacao}}` ou `{{vencimento}}` |
| Tipo | `composition` | `template` ou `text` |
| Modelo | `message_template_id` | obrigatório no tipo template; ativo e com nome de template da Meta |
| Texto livre | `body` | obrigatório no tipo text; até 4096 caracteres; variáveis só as três suportadas; janela aberta |
| Confirmação de envio | botão Enviar | não é campo persistido |

| Situação | Resultado |
| --- | --- |
| Pré-visualização válida | volta ao formulário com o texto resolvido; sem linha nova e sem HTTP |
| Envio aceito | redireciona ao detalhe; flash "Mensagem aceita pela Meta." |
| Envio recusado pela API ou pela rede | redireciona ao detalhe da tentativa falha; flash "A Meta não aceitou a mensagem." |
| Contato inativo | "Este contato está inativo e não pode receber mensagens." Nenhuma linha |
| Sem consentimento | "Este contato não autorizou o WhatsApp." Nenhuma linha |
| Destino fora do modo de teste | "No modo de teste, só é possível enviar para o número autorizado." Nenhuma chamada |
| Modelo inelegível | "Selecione um modelo ativo vinculado a um template da Meta." |
| Obrigação ausente | "Selecione a obrigação usada neste modelo." |
| Obrigação de outro contato | "A obrigação não pertence ao contato selecionado." |
| Texto livre sem janela | a frase da UI-04 |
| Código 130497 | a tentativa fica `failed`, com o código, e a descrição visível "A Meta recusou este destinatário (130497)." |
| Token inválido ou expirado | a tentativa fica `failed` e a descrição visível "O token de acesso da Meta é inválido ou expirou." |
| Comando fora de `local` ou sem modo de teste | não chama a Meta e não grava mensagem |
| Comando de conexão aceito | sem linha em `messages`; a saída não traz credenciais |
| Exclusão de personalizado | a confirmação da Fase 3 continua. Com histórico, o modelo permanece e o flash é "Este modelo possui mensagens e não pode ser excluído." |
| Exclusão de obrigação com histórico | a obrigação permanece; flash "Esta obrigação possui mensagens e não pode ser excluída." |

Dados inválidos redirecionam ao formulário. O teste não espera 422.

## 10. Modelo conceitual

Tentativa `Message`, criada nesta fase.

| Campo | Obrigatório | Observação |
| --- | --- | --- |
| `contact_id` | sim | restrição na exclusão do contato |
| `obligation_id` | não | restrição na exclusão da obrigação |
| `message_template_id` | não | restrição na exclusão do modelo |
| `body` | sim | texto resolvido ou texto livre |
| `origin` | sim | sempre `manual` nesta fase |
| `wamid` | não | identificador devolvido pela Meta |
| `status` | sim | `accepted`, `sent`, `delivered`, `read` ou `failed` |
| `accepted_at` | não | hora do aceite HTTP |
| `sent_at` | não | primeiro webhook de envio efetivo, entrega ou leitura |
| `delivered_at` | não | webhook `delivered` |
| `read_at` | não | webhook `read` |
| `failed_at` | não | falha local ou webhook `failed` |
| `error_code` | não | código numérico da Meta, em texto |
| `error_message` | não | descrição segura, sem segredo |
| `is_test` | sim | verdadeiro no modo de teste |
| `created_at`, `updated_at` | sim | timestamps do Laravel |

`contacts` ganha `whatsapp_last_inbound_at`, nulo até uma mensagem de entrada assinada de um contato já cadastrado.

`message_templates` passa a gravar, todos opcionais:

| Campo | Observação |
| --- | --- |
| `meta_template_name` | já existe; continua nulo até o operador informar o template aprovado |
| `meta_template_language` | exemplo `pt_BR` ou `en_US`; vazio usa o idioma padrão da configuração no envio |
| `meta_parameter_order` | lista ordenada, como `nome,vencimento`; vazia significa sem parâmetros |

Evento de webhook `WhatsAppStatusEvent`, para a idempotência:

| Campo | Observação |
| --- | --- |
| `wamid` | identificador da tentativa |
| `status` | `sent`, `delivered`, `read` ou `failed` |
| `payload_hash` | conferência opcional; a unicidade prática é `wamid` + `status` |

Não há coluna nova em `obligations`.

URLs:

| Método | Caminho | Uso |
| --- | --- | --- |
| GET | `/messages` | histórico |
| GET | `/messages/create` | formulário |
| POST | `/messages` | pré-visualizar ou enviar |
| GET | `/messages/{message}` | detalhe |
| GET | `/messages/{message}/resend` | formulário preenchido para nova tentativa |
| GET | `/webhooks/whatsapp` | verificação da Meta |
| POST | `/webhooks/whatsapp` | campo `messages`: `value.messages[]` e `value.statuses[]` |

Não há `PUT` nem `DELETE` de mensagem.

## 11. Critérios de aceite

- **CA-01.** Com HTTP simulado, um template sem parâmetro para o número autorizado grava `accepted`, o `wamid` e `accepted_at`, deixa `sent_at` nulo e não chama a palavra entregue no flash.
- **CA-02.** Timeout ou falha de conexão grava `failed` sem `wamid`. HTTP 500 grava `failed` com a descrição segura.
- **CA-03.** Resposta com código 190 grava falha e a frase de token inválido ou expirado. O token não aparece na resposta nem no banco.
- **CA-04.** Resposta com código 130497 grava esse código e a frase de destinatário recusado.
- **CA-05.** Contato inativo, contato sem consentimento e telefone diferente do autorizado, no modo de teste, não disparam HTTP e não criam linha.
- **CA-06.** Modelo inativo ou sem nome de template da Meta é recusado. Modelo ativo vinculado, sem obrigação, é aceito quando o texto não usa `{{obrigacao}}` nem `{{vencimento}}`.
- **CA-07.** Texto com `{{obrigacao}}` ou `{{vencimento}}` exige obrigação do mesmo contato. A pré-visualização mostra o nome, o título e a data reais e não cria linha.
- **CA-08.** Reenviar depois de uma tentativa aceita cria uma segunda linha, com outro envio simulado, e conserva a primeira.
- **CA-09.** Com ambiente `local` e HTTP simulado, o comando `whatsapp:test` envia `hello_world` em `en_US`, sem parâmetros, só ao número configurado. Não cria contato, não exige `MessageTemplate` e não grava `messages`. Fora de `local`, não chama a Meta. A saída não contém o token.
- **CA-10.** O GET com verify token devolve o challenge. O POST sem `X-Hub-Signature-256` válido responde 403 e não grava. Um payload do campo `messages` com `value.statuses[]` atualiza a tentativa. O mesmo status, recebido duas vezes, deixa uma única transição. `delivered` antes de `sent` preenche `sent_at` e não volta atrás quando `sent` chega depois. `read` não volta para `delivered`. Um evento depois de `failed` não reabre a tentativa. `value.messages[]` atualiza a entrada do contato conhecido. Um campo `message_status` não é necessário nem processado.
- **CA-11.** Envios no mês, no calendário de `America/Sao_Paulo`, conta uma vez cada mensagem não teste com status `sent`, `delivered` ou `read`. Três webhooks da mesma mensagem contam 1. Duas tentativas distintas no mês contam 2. Aceite, falha e teste ficam de fora, inclusive a falha que já tinha `sent_at`. Uma mensagem aceita mostra "Aceita pela Meta", não "Enviada". A obrigação sem histórico continua "Sem envios". A frase de envios ainda não registrados não aparece. Nada é gravado em `obligations`.
- **CA-12.** Contato só com mensagem não é excluído. Contato com obrigação continua com o flash da Fase 2. Obrigação e modelo com mensagem não são excluídos.
- **CA-13.** Texto livre com janela fechada não chama a Meta. Com `whatsapp_last_inbound_at` dentro de 24 horas, o texto é enviado como tipo `text`.
- **CA-14.** Mensagens abre pelo menu e pela home, sem a frase de módulo pendente. `/modules/messages` responde 404. Configurações continua pendente.
- **CA-15.** A suíte inteira, inclusive Fases 1, 2 e 3, passa sem túnel e sem proxy. Não há scheduler, job de envio, envio em lote nem endpoint de sincronização de template.

## 12. Decisões

- O produto desta fase é a tentativa manual e o retorno da Meta, não o lembrete automático.
- HTTP 200 da Meta é aceite da requisição. O mês conta a mensagem única com status `sent`, `delivered` ou `read` que não terminou em `failed`. Teste e evento de webhook não entram. O calendário é `America/Sao_Paulo`.
- O modo de teste nasce ligado. O número canadense autorizado não entra no código.
- `hello_world` serve ao comando local de conexão. Esse comando não grava histórico. O modelo interno padrão só é enviável depois que o operador informar o template aprovado e a ordem dos parâmetros.
- Texto livre depende de uma entrada real do contato, recebida em `value.messages[]`. Sem essa prova, a tela bloqueia. Direct Send fica de fora.
- A autenticação fica para a Fase 6. Até lá as telas administrativas só são usadas localmente. A aplicação inteira não vai para um túnel. O webhook só pode ser publicado por um proxy restrito a `/webhooks/whatsapp`, com `APP_DEBUG` desligado e com a assinatura preservada apesar da isenção de CSRF. Sem esse proxy, a prova do webhook é o teste com payload simulado.
- O campo de webhook é `messages`, para entrada e para entrega. `message_status` não é exigido.
- O envio manual continua síncrono, limitado a uma mensagem por requisição.
- A trava do envio automático por tipo de lembrete continua contratada e não é implementada. Não há índice único que impediria o reenvio manual.
