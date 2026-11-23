# Spec 003 — Modelos de mensagens

| | |
| --- | --- |
| Identificador | `003-message-template-management` |
| Produto | Factotum |
| Estado | Aguardando revisão |
| Plano | [docs/plans/003-message-template-management.md](../plans/003-message-template-management.md) |
| Depende de | [Spec 001 — Gestão de contatos](001-contact-management.md), [Spec 002 — Gestão de obrigações](002-obligation-management.md) |
| Data | 2026-11-23 |

Esta etapa entrega a especificação. Nenhum código de aplicação faz parte deste documento.

## 1. Contexto

O Factotum cadastra contatos e obrigações para, numa fase seguinte, lembrar vencimentos por WhatsApp. As Fases 1 e 2 já entregaram esses dois cadastros. Modelos continua como página de espera em `/modules/templates`. Mensagens e Configurações continuam em espera. Não existe tabela de mensagens nem de modelos.

O modelo é o texto que o operador pretende usar num lembrete. Ele ainda não é enviado, não entra na fila e não é submetido à Meta. A pré-visualização usa dados demonstrativos fixos, para o operador ler o texto com as variáveis resolvidas antes de existir um envio real.

A spec 002 já contratou o histórico futuro de mensagens: toda mensagem terá `contact_id`, `obligation_id` opcional, origem, tipo de lembrete e `sent_at`. Esta fase não cria essa tabela e não altera as colunas provisórias da obrigação, que continuam com o texto "Sem envios".

## 2. Objetivo

Permitir que o operador, sem login, gerencie modelos de mensagem, com:

- um modelo padrão de lembrete de vencimento, protegido contra exclusão;
- modelos personalizados, que podem ser criados, editados e excluídos;
- nome, conteúdo e status ativo ou inativo;
- as variáveis `{{nome}}`, `{{obrigacao}}` e `{{vencimento}}`;
- validação que nomeia a variável inválida;
- pré-visualização com dados demonstrativos, sem gravar e sem enviar.

## 3. Fora desta fase

- Envio, reenvio, fila, job e qualquer chamada à API do WhatsApp ou da Meta.
- Submissão, sincronização, consulta de status ou aprovação de template na Meta.
- Tabela, model ou histórico de mensagens.
- Escolha do modelo na obrigação, na tela de contato ou num disparo.
- Substituição das colunas "Sem envios" por contagem real.
- Recorrência, janela de 24 horas executada em código e bloqueio de contato por histórico de mensagens.
- Autenticação.
- Clean Architecture, DDD, interfaces, DTOs e Actions.
- Pacotes novos.
- Editor rico, markdown, anexos e botões de resposta.

## 4. Reservado à integração com a Meta

Estas regras ficam contratadas para uma fase posterior. A Fase 3 não as executa.

- Mensagem iniciada pela empresa fora da janela de atendimento de 24 horas precisa usar um template aprovado pela Meta. Dentro dessa janela, uma mensagem de sessão pode ser texto livre. O Factotum ainda não distingue as duas situações em código.
- O modelo interno não é um template da Meta. Aprovar, consultar o status de aprovação e definir os placeholders numerados são passos de uma fase futura.
- Na integração futura, os parâmetros posicionais da Meta serão definidos conforme o conteúdo e a ordem do template aprovado. Não há mapa obrigatório de `{{nome}}`, `{{obrigacao}}` e `{{vencimento}}` para `{{1}}`, `{{2}}` e `{{3}}` em todos os modelos. Esta fase não converte variáveis.
- Quando um modelo interno estiver vinculado a um template aprovado, uma alteração desse modelo precisará ser conferida com o texto aprovado. Esta fase não bloqueia a edição nem sincroniza essa correspondência.
- Quando o envio passar a existir, a mensagem poderá guardar o modelo interno usado. `message_template_id` será opcional, porque uma mensagem de sessão pode não usar modelo. Esta fase não cria a chave.
- Um modelo com `{{obrigacao}}` ou `{{vencimento}}` só terá dados reais quando o envio estiver ligado a uma obrigação. O cadastro do modelo não exige obrigação.
- Modelo inativo não deverá ser oferecido no envio futuro. A lista desta fase continua mostrando os inativos.
- A exclusão de um modelo que já tenha histórico de mensagens será decidida nessa fase. O desenho previsto é recusar a exclusão, no mesmo espírito da obrigação que segura o contato. Sem a tabela de mensagens, esta fase não aplica essa trava.

## 5. Requisitos funcionais

### Navegação

- **RF-01.** Modelos deixa de abrir a página de espera e passa a abrir a listagem do módulo.
- **RF-02.** O item Modelos da navegação aponta para a listagem e fica ativo em todas as rotas de modelo.
- **RF-03.** O cartão Modelos da home leva à listagem. Mensagens e Configurações continuam como página de espera.
- **RF-04.** `/modules/templates` deixa de ser uma rota válida. Mensagens e Configurações seguem em `/modules/{module}`. `/modules/obligations`, `/modules/billing` e qualquer outro slug continuam em 404.

### Modelos

- **RF-05.** O operador lista os modelos em páginas de 10, ordenados pelo nome e, em empate, por `id`.
- **RF-06.** A busca é opcional, via `q`, e considera nome e conteúdo. A paginação preserva o termo. Uma nova busca volta à primeira página.
- **RF-07.** O termo vazio, depois do trim, é ignorado. Acima de 100 caracteres, valem os 100 primeiros. `%`, `_` e `\` do termo são texto, não curinga.
- **RF-08.** Lista vazia e busca sem resultado têm mensagens diferentes. A lista vazia oferece o caminho para cadastrar o primeiro modelo. Com o modelo padrão presente, a lista operacional não fica vazia; o texto da lista vazia permanece para o caso em que a tabela não tenha registros.
- **RF-09.** O operador cria, abre, edita e exclui um modelo personalizado. A exclusão pede confirmação e remove só aquele registro.
- **RF-10.** Cada modelo tem nome, conteúdo e status. O nome é único depois do trim. O conteúdo pode ocupar várias linhas.
- **RF-11.** Criar, atualizar e excluir um modelo personalizado redirecionam à listagem com flash de sucesso.
- **RF-12.** Dados inválidos redirecionam de volta ao formulário. A sessão guarda os erros e os valores informados. A resposta desse fluxo de Blade é o redirecionamento, não HTTP 422.
- **RF-13.** Modelo inexistente na visualização, edição, atualização ou exclusão responde 404.
- **RF-14.** A listagem e o detalhe informam, de forma visível, que os modelos ainda não são enviados nem sincronizados com a Meta.

### Modelo padrão

- **RF-15.** A migração entrega um único modelo padrão de lembrete de vencimento, ativo, em qualquer ambiente, inclusive produção.
- **RF-16.** O operador pode editar nome, conteúdo e status do modelo padrão, inclusive desativá-lo. A edição não remove a marca de padrão e esta fase não a bloqueia por causa da Meta.
- **RF-17.** O operador não exclui o modelo padrão. A lista e o detalhe não oferecem Excluir nesse registro. Um `DELETE` direto é recusado, o registro permanece e a lista mostra o flash de erro.
- **RF-18.** O formulário não permite marcar outro modelo como padrão nem preencher o nome do template da Meta.

### Variáveis e pré-visualização

- **RF-19.** O conteúdo pode usar qualquer subconjunto de `{{nome}}`, `{{obrigacao}}` e `{{vencimento}}`: nenhuma, uma, duas ou as três, repetidas ou não. Essas três são as únicas variáveis suportadas.
- **RF-20.** Variável desconhecida, com espaço, com maiúsculas diferentes ou com chave sem fechar invalida o conteúdo. A mensagem diz qual variável é inválida, quando houver um token fechado. Esse conteúdo não é gravado.
- **RF-21.** A pré-visualização substitui somente as variáveis suportadas que estão no texto, pelos dados demonstrativos fixos. Variável ausente não é inserida. O resultado não é gravado.
- **RF-22.** O detalhe sempre mostra o conteúdo gravado e a pré-visualização. O botão Pré-visualizar reenvia o formulário de cadastro ou de edição, valida e devolve o texto resolvido sem criar nem atualizar registro.
- **RF-23.** Conteúdo inválido não produz pré-visualização. O formulário volta com o erro e com o que foi digitado. Nenhum registro é criado ou atualizado.

### Status

- **RF-24.** O status só pode ser ativo ou inativo. O valor sugerido no cadastro é Ativo.
- **RF-25.** Modelo inativo continua na lista e na busca. Esta fase não filtra por status e não esconde o padrão inativo.

## 6. Interface

A interface continua em português, no layout, na fonte e no Tailwind das fases anteriores. Tema claro. Sem biblioteca nova de componente. A confirmação de exclusão usa `confirm()` nativo.

- **UI-01.** A ordem das colunas da lista é: Nome, Conteúdo, Status e Ações.
- **UI-02.** O modelo padrão exibe o badge "Padrão" junto do nome. O badge usa o mesmo peso visual discreto do pagamento na lista de obrigações. Personalizado não recebe badge.
- **UI-03.** O conteúdo na lista aparece truncado. O detalhe mostra o conteúdo integral, com as quebras de linha gravadas.
- **UI-04.** Status aparece como Ativo ou Inativo, com o mesmo tratamento visual já usado em contatos.
- **UI-05.** Abaixo do breakpoint `md`, cada modelo vira um cartão com os mesmos dados, na mesma ordem.
- **UI-06.** Ações de um modelo personalizado: Ver, Editar e Excluir. No padrão, Ver e Editar. Há o botão Novo modelo. Não há Enviar, Reenviar, Sincronizar nem Aprovar.
- **UI-07.** A listagem informa, uma vez: "Estes modelos ainda não são enviados nem sincronizados com a Meta."
- **UI-08.** O formulário pede nome, conteúdo e status, nesta ordem. O conteúdo é um `textarea`. Abaixo dele, o texto de ajuda: "Variáveis disponíveis: {{nome}}, {{obrigacao}} e {{vencimento}}."
- **UI-09.** Campos obrigatórios têm rótulo associado e indicação visível. Erro de validação colore a borda, mostra a mensagem do campo e o resumo "Revise os campos destacados." O formulário usa `novalidate`.
- **UI-10.** Pré-visualizar fica ao lado de Cadastrar ou Salvar e não grava. O bloco de pré-visualização mostra o texto resolvido e a nota "Dados demonstrativos: Ana Lima, IPTU e 15/01/2026."
- **UI-11.** O detalhe mostra nome, conteúdo, pré-visualização, status, a marca Padrão quando for o caso, "Template da Meta" com o valor "Não vinculado" e a data de atualização. No padrão, uma frase informa: "Este é o modelo padrão e não pode ser excluído."
- **UI-12.** O cartão da home sai do grupo de espera. Título "Modelos", texto "Escreva os textos usados nos lembretes." e botão "Gerenciar modelos".

Rótulos de status na tela: Ativo e Inativo. No banco, `active` e `inactive`.

## 7. Regras de negócio

- **RN-01.** Um modelo não pertence a um contato nem a uma obrigação. Vários modelos personalizados podem coexistir com o padrão.
- **RN-02.** Nome e conteúdo são obrigatórios e são gravados sem espaços nas pontas. Quebras de linha internas do conteúdo permanecem. Nome vazio ou conteúdo vazio depois do trim não são gravados.
- **RN-03.** O nome é único entre os modelos, já com o trim aplicado. Na edição, o próprio registro não conflita consigo. A comparação é a do valor gravado, sem converter maiúsculas.
- **RN-04.** O conteúdo tem no máximo 1024 caracteres, contando o texto armazenado, inclusive as variáveis ainda não substituídas. O limite acompanha o tamanho de corpo aceito num template da Meta e não dispara nenhuma API.
- **RN-05.** As únicas variáveis são os tokens exatos `{{nome}}`, `{{obrigacao}}` e `{{vencimento}}`. Elas fazem parte do texto que o operador escreve e permanecem em português dentro do campo.
- **RN-06.** Uma chave simples, como `{nome}`, é texto literal, não variável. Espaço, acento ou diferença de maiúsculas impedem o reconhecimento: `{{ nome }}`, `{{Nome}}` e `{{obrigação}}` são inválidos.
- **RN-07.** Todo `{{` precisa fechar com `}}` no mesmo token, e todo `}}` precisa pertencer a um token. Token vazio, `{{}}`, é inválido.
- **RN-08.** A pré-visualização substitui `{{nome}}` por Ana Lima, `{{obrigacao}}` por IPTU e `{{vencimento}}` por 15/01/2026, apenas onde o token aparece. Um conteúdo só com `{{nome}}` não recebe IPTU nem a data. Esses valores não são lidos do banco. Apagar o contato ou a obrigação de exemplo não muda a pré-visualização.
- **RN-09.** A substituição não é recursiva. O resultado demonstrativo não é gravado no modelo.
- **RN-10.** Existe um único modelo padrão. Ele nasce na migração com o nome "Lembrete de vencimento", status ativo e o conteúdo "Olá, {{nome}}. Lembramos que {{obrigacao}} vence em {{vencimento}}."
- **RN-11.** A marca de padrão não entra no formulário e não é alterada por create nem por update. Um campo `is_default` ou `meta_template_name` enviado no POST é ignorado.
- **RN-12.** Excluir é permitido só para modelo personalizado. A verificação acontece antes do `delete`. Status ativo ou inativo não altera essa proteção.
- **RN-13.** Excluir um modelo personalizado não exclui contatos nem obrigações.
- **RN-14.** `meta_template_name` nasce nulo e esta fase não tem tela para preenchê-lo. Enquanto estiver nulo, o detalhe mostra "Não vinculado".
- **RN-15.** O modelo padrão existe em todos os ambientes porque faz parte do esquema. Modelos personalizados de exemplo do seeder existem só em `local` e `testing`. Em produção, esse seeder não insere modelos nem o usuário de teste.

## 8. Validação e feedback

A validação fica nos Form Requests. O controller não revalida o payload. Pré-visualizar usa as mesmas regras do cadastro.

| Campo na tela | Entrada | Regra |
| --- | --- | --- |
| Nome | `name` | obrigatório, texto, no máximo 120 caracteres, único em `message_templates.name` |
| Conteúdo | `body` | obrigatório, texto, no máximo 1024 caracteres, só variáveis suportadas e chaves equilibradas |
| Status | `status` | obrigatório, `active` ou `inactive` |

Mensagens e nomes dos atributos são em português.

| Situação | Mensagem |
| --- | --- |
| Uma variável fechada e inválida | "A variável {{telefone}} não é suportada. Use {{nome}}, {{obrigacao}} ou {{vencimento}}." O token citado é o que foi escrito. |
| Duas ou mais | "As variáveis {{telefone}} e {{empresa}} não são suportadas. Use {{nome}}, {{obrigacao}} ou {{vencimento}}." A ordem é a da primeira ocorrência de cada token. Três ou mais separam por vírgula e usam "e" antes da última. |
| Chaves desequilibradas | "Feche todas as variáveis com {{ e }}." |
| Nome repetido | "Este nome já está cadastrado." |
| Status fora da lista | "Selecione um status válido." |

Se houver variável inválida e chave desequilibrada no mesmo conteúdo, as duas mensagens aparecem no campo.

| Situação | Resultado |
| --- | --- |
| Cadastro válido | redireciona à listagem; flash "Modelo cadastrado." |
| Atualização válida | redireciona à listagem; flash "Modelo atualizado." |
| Exclusão confirmada de personalizado | redireciona à listagem; flash "Modelo excluído." |
| Pré-visualização válida | permanece no formulário, com os valores digitados e o texto resolvido; nada é gravado |
| Dados inválidos | redireciona ao formulário com erros e valores na sessão; sem pré-visualização |
| Exclusão de personalizado | confirmação "Excluir este modelo? Esta ação não pode ser desfeita." |
| Exclusão do padrão | o modelo permanece; flash "O modelo padrão não pode ser excluído." |

## 9. Modelo conceitual

Entidade `MessageTemplate`, criada nesta fase. A URL do recurso permanece `/templates`, que já é o slug de Modelos na navegação.

| Campo | Obrigatório | Observação |
| --- | --- | --- |
| `name` | sim | único, rótulo Nome |
| `body` | sim | rótulo Conteúdo; texto com as variáveis |
| `status` | sim | `active` ou `inactive` |
| `is_default` | sim | verdadeiro só no modelo entregue pela migração |
| `meta_template_name` | não | gancho futuro; nulo nesta fase |
| `created_at`, `updated_at` | sim | timestamps do Laravel |

Contrato futuro, não criado nesta fase:

| Campo | Obrigatório | Observação |
| --- | --- | --- |
| `message_template_id` em `messages` | não | modelo usado no envio; nulo na mensagem de sessão |
| status de aprovação da Meta | não | só quando existir integração |
| idioma do template Meta | não | previsto `pt_BR`, sem coluna agora |

URLs desta fase:

| Método | Caminho | Uso |
| --- | --- | --- |
| GET | `/templates` | listagem |
| GET | `/templates/create` | formulário |
| POST | `/templates` | gravar, ou pré-visualizar quando o botão for Pré-visualizar |
| GET | `/templates/{template}` | detalhe |
| GET | `/templates/{template}/edit` | edição |
| PUT/PATCH | `/templates/{template}` | atualizar, ou pré-visualizar quando o botão for Pré-visualizar |
| DELETE | `/templates/{template}` | excluir personalizado ou recusar o padrão |

Pré-visualizar usa a mesma URL do formulário em que o operador está. Não há rota separada de pré-visualização: assim a edição reconhece o próprio modelo na validação do nome e o botão não dispara a atualização.

## 10. Critérios de aceite

- **CA-01.** Abrir Modelos pela navegação e pela home mostra a listagem, sem o texto "Este módulo será implementado em uma próxima etapa."
- **CA-02.** Mensagens e Configurações continuam na página de espera. `/modules/templates`, `/modules/obligations` e `/modules/billing` respondem 404.
- **CA-03.** Depois das migrações existe um modelo padrão ativo, de nome "Lembrete de vencimento", com o conteúdo definido na RN-10, `is_default` verdadeiro e `meta_template_name` nulo.
- **CA-04.** Cadastrar nome "Cobranca amigavel", conteúdo "Olá, {{nome}}. Segue {{obrigacao}} com vencimento em {{vencimento}}.", status Ativo e observação vazia de Meta grava o personalizado com `is_default` falso e `meta_template_name` nulo. Enviar `is_default` ou `meta_template_name` no POST não altera esses dois campos.
- **CA-05.** Nome vazio, nome repetido, conteúdo vazio, conteúdo acima de 1024 caracteres, status fora da lista, `{{telefone}}`, `{{ nome }}` ou `{{nome` sem fechar redirecionam ao formulário, com erro no campo e o valor informado preservado. Nenhum registro novo é criado.
- **CA-06.** A mensagem de `{{telefone}}` cita esse token e lembra as três variáveis suportadas. São aceitos o texto sem variável e qualquer subconjunto das três, inclusive uma variável repetida.
- **CA-07.** A lista mostra Nome, Conteúdo, Status e Ações, nesta ordem. O padrão tem o badge "Padrão" e não oferece Excluir. Um inativo aparece como Inativo e continua na lista.
- **CA-08.** Com 11 modelos no total, a segunda página existe e a primeira não repete o item exclusivo da segunda. A ordenação é nome crescente.
- **CA-09.** Buscar por parte do nome ou do conteúdo reduz a lista. Busca sem resultado mostra "Nenhum modelo encontrado." O termo `100%` não é curinga e não provoca erro.
- **CA-10.** O detalhe do padrão mostra o conteúdo gravado, a pré-visualização "Olá, Ana Lima. Lembramos que IPTU vence em 15/01/2026.", "Não vinculado" e não oferece Excluir nem Enviar.
- **CA-11.** Pré-visualizar "Olá, {{nome}}." mostra "Olá, Ana Lima." e não mostra IPTU nem 15/01/2026. O número de modelos não muda. Pré-visualizar com `{{telefone}}` volta com erro de validação, sem texto resolvido e sem criar ou atualizar registro. O conteúdo do padrão, com as três variáveis, continua resolvendo para "Olá, Ana Lima. Lembramos que IPTU vence em 15/01/2026."
- **CA-12.** Editar o padrão muda o conteúdo e mantém `is_default` verdadeiro. O `DELETE` seguinte mantém o registro e exibe "O modelo padrão não pode ser excluído."
- **CA-13.** Excluir um personalizado pede a confirmação prevista, remove só esse modelo e exibe "Modelo excluído." Contatos e obrigações permanecem.
- **CA-14.** O seeder de exemplos cria modelos personalizados em `local` e em `testing`. Em `production` não cria personalizado nem usuário de teste. O padrão da migração continua presente.
- **CA-15.** A suíte desta fase passa junto com a suíte já existente. Nenhuma migration de `messages` é criada e nenhuma rota de envio é registrada.

## 11. Decisões

- O produto desta fase é o texto do lembrete, não o envio e não a aprovação na Meta.
- A URL segue o slug já usado na navegação, `/templates`. A classe e a tabela levam `MessageTemplate` e `message_templates` para não se confundirem com template de view.
- O padrão nasce na migração porque produção também precisa dele. Os exemplos personalizados continuam restritos a `local` e `testing`.
- O padrão pode ser reescrito e desativado, e não pode ser apagado. A marca de padrão não depende do nome. Nesta fase a edição não é bloqueada. Quando houver vínculo com a Meta, a alteração precisará ser conferida com o template aprovado.
- As variáveis ficam em português porque são o que o operador digita no conteúdo. A pré-visualização troca só as que estão no texto, pelos literais Ana Lima, IPTU e 15/01/2026, sem consultar o banco e sem converter para placeholders da Meta.
- O único gancho da Meta nesta fase é `meta_template_name`, nulo e fora do formulário. Os parâmetros posicionais ficarão a cargo do template aprovado. O limite de 1024 caracteres deixa o corpo compatível com um template futuro, sem implementar a API nem a conversão.
- Inativo continua visível, como o contato inativo. O envio futuro é que deverá ignorá-lo.
