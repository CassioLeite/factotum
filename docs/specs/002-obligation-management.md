# Spec 002 — Gestão de obrigações

| | |
| --- | --- |
| Identificador | `002-obligation-management` |
| Produto | Factotum |
| Estado | Pronta para implementação |
| Plano | [docs/plans/002-obligation-management.md](../plans/002-obligation-management.md) |
| Depende de | [Spec 001 — Gestão de contatos](001-contact-management.md) |
| Data | 2026-11-23 |

Esta etapa entrega a especificação. Nenhum código de aplicação faz parte deste documento.

## 1. Contexto

O Factotum automatiza mensagens de WhatsApp. A obrigação existe para que, numa fase seguinte, o sistema saiba a quem lembrar e sobre qual vencimento lembrar. O pagamento é um dado auxiliar do cadastro. Esta fase não cobra, não concilia e não resume valores.

A Fase 1 entregou o CRUD de contatos e deixou Obrigações como página de espera em `/modules/obligations`. A regra RN-16 da spec 001 previa bloquear a exclusão de um contato com obrigações ou com histórico de mensagens. Esta fase cumpre a parte das obrigações. A parte do histórico continua futura, porque o histórico ainda não existe.

A informação que a lista deve destacar é o vencimento e o estado da comunicação. O status de pagamento permanece no cadastro e aparece na lista em posição secundária.

## 2. Objetivo

Permitir que o operador, sem login, gerencie obrigações ligadas a contatos já cadastrados, com:

- CRUD, busca e paginação;
- título, observação, vencimento, contato e status de pagamento;
- coluna Mensagem e coluna Envios no mês, ambas provisórias, com o texto fixo "Sem envios";
- recusa da exclusão de um contato que possua ao menos uma obrigação.

O envio, o reenvio e a contagem real ficam especificados aqui e só serão executados no módulo de mensagens.

## 3. Fora desta fase

- Integração com WhatsApp, filas, jobs e disparo automático ou manual.
- Tabela, model ou histórico de mensagens.
- Colunas, contadores ou flags de envio na tabela `obligations`.
- Botão ou ação de enviar ou reenviar.
- Valor, moeda, juros, desconto, forma de pagamento, data de pagamento e totais financeiros.
- Recorrência de obrigações.
- Autenticação.
- Clean Architecture, DDD, interfaces, DTOs e Actions.
- Pacotes novos.

## 4. Reservado ao módulo de mensagens

Estas regras ficam contratadas para a fase de mensagens. A Fase 2 não as executa e não cria estrutura para simulá-las.

- Uma mensagem pertence sempre a um contato. O vínculo com uma obrigação é opcional. Mensagem só de contato e mensagem de obrigação são as duas formas válidas.
- A coluna Mensagem de cada obrigação será calculada pelas mensagens cujo `obligation_id` é o daquela obrigação. Sem mensagens, o texto continua "Sem envios".
- A coluna Envios no mês será a quantidade de mensagens daquele contato com `sent_at` dentro do mês corrente, no fuso de `config('app.timezone')`. A conta inclui as mensagens de todas as obrigações do contato e as mensagens manuais ligadas só ao contato. O número não será gravado na obrigação.
- O reenvio manual insere outra mensagem mesmo quando já existe envio anterior, automático ou manual, para a mesma obrigação e o mesmo tipo de lembrete. O histórico anterior não impede o gesto do operador.
- O envio automático não repete, para a mesma obrigação e o mesmo tipo de lembrete, um automático que já tenha sido registrado. Os tipos previstos são `before_due`, `on_due` e `overdue`. Essa trava não usa índice único que também alcance o envio manual. O automático exige obrigação e tipo de lembrete. O manual pode omitir os dois.
- A exclusão de contato com histórico de mensagens continua proibida quando esse histórico existir. Esta fase não a aplica.

## 5. Requisitos funcionais

### Navegação

- **RF-01.** Obrigações deixa de abrir a página de espera e passa a abrir a listagem do módulo.
- **RF-02.** O item Obrigações da navegação aponta para a listagem e fica ativo em todas as rotas de obrigação.
- **RF-03.** O cartão Obrigações da home leva à listagem. Mensagens, Modelos e Configurações continuam como página de espera.
- **RF-04.** `/modules/obligations` deixa de ser uma rota válida. Mensagens, Modelos e Configurações seguem em `/modules/{module}`. Outro slug continua respondendo 404.

### Obrigações

- **RF-05.** O operador lista as obrigações em páginas de 10, ordenadas pelo vencimento crescente e, em empate, por `id`.
- **RF-06.** A busca é opcional, via `q`, e considera título, observação e nome do contato. A paginação preserva o termo. Uma nova busca volta à primeira página.
- **RF-07.** O termo vazio, depois do trim, é ignorado. Acima de 100 caracteres, valem os 100 primeiros. `%`, `_` e `\` do termo são texto, não curinga.
- **RF-08.** Lista vazia e busca sem resultado têm mensagens diferentes. A lista vazia oferece o caminho para cadastrar a primeira obrigação.
- **RF-09.** O operador cria, abre, edita e exclui uma obrigação. A exclusão da obrigação pede confirmação e remove só aquele registro.
- **RF-10.** Cada obrigação tem contato, título, observação, vencimento e status de pagamento. O contato é um registro já existente. A observação é opcional. Vencimento no passado é aceito.
- **RF-11.** Criar, atualizar e excluir uma obrigação redirecionam à listagem com flash de sucesso.
- **RF-12.** Dados inválidos redirecionam de volta ao formulário. A sessão guarda os erros e os valores informados. A resposta desse fluxo de Blade é o redirecionamento, não HTTP 422.
- **RF-13.** Obrigação inexistente na visualização, edição, atualização ou exclusão responde 404.

### Indicadores provisórios

- **RF-14.** A listagem e o detalhe mostram a coluna ou o campo Mensagem com o texto exato "Sem envios".
- **RF-15.** A listagem e o detalhe mostram Envios no mês com o texto exato "Sem envios".
- **RF-16.** Esses dois textos são fixos. A tela não exibe zero, "Não enviado", "Enviada" nem data de envio.
- **RF-17.** A listagem informa, uma vez, que o registro de envios ainda não existe. Essa frase não substitui o texto "Sem envios" de cada linha.

### Contatos

- **RF-18.** Excluir um contato sem obrigações continua removendo o contato, com a confirmação e o flash já definidos na spec 001.
- **RF-19.** Excluir um contato com ao menos uma obrigação é recusado. O contato e as obrigações permanecem. A lista de contatos mostra o flash "Este contato possui obrigações e não pode ser excluído."
- **RF-20.** O status de pagamento da obrigação não altera o bloqueio. Pendente, paga ou cancelada, a obrigação impede a exclusão do contato.

## 6. Interface

A interface continua em português, no layout, na fonte e no Tailwind da Fase 1. Tema claro. Sem biblioteca nova de componente.

- **UI-01.** A ordem das colunas da lista é: Contato, Obrigação, Vencimento, Mensagem, Envios no mês, Pagamento e Ações.
- **UI-02.** Mensagem e Envios no mês aparecem antes de Pagamento. O badge de pagamento usa o mesmo peso visual discreto nas três situações, sem ser o título da página nem a primeira coluna.
- **UI-03.** No formulário, o status de pagamento fica depois do vencimento. O valor sugerido no cadastro é Pendente.
- **UI-04.** Abaixo do breakpoint `md`, cada obrigação vira um cartão com os mesmos dados, na mesma ordem de importância.
- **UI-05.** Ações de cada obrigação: Ver, Editar e Excluir. Há o botão Nova obrigação. Não há ação Enviar nem Reenviar.
- **UI-06.** Vencimento aparece como `dd/mm/aaaa`. Contato aparece pelo nome.
- **UI-07.** Campos obrigatórios têm rótulo associado e indicação visível. Erro de validação colore a borda, mostra a mensagem do campo e o resumo "Revise os campos destacados."
- **UI-08.** O flash de sucesso e o flash de erro da exclusão de contato usam faixas distintas no topo do conteúdo.
- **UI-09.** O detalhe repete contato, título, observação, vencimento, Mensagem, Envios no mês e pagamento, e mostra a data de atualização.

Rótulos de pagamento na tela: Pendente, Pago e Cancelada. No banco, os valores são `pending`, `paid` e `cancelled`.

## 7. Regras de negócio desta fase

- **RN-01.** Toda obrigação pertence a um contato. Um contato pode ter várias obrigações. O título não é único.
- **RN-02.** Título e vencimento são obrigatórios. Observação vazia é gravada como nula. Título e observação são gravados sem espaços nas pontas.
- **RN-03.** O vencimento é uma data de calendário. Datas passadas, de hoje e futuras são válidas.
- **RN-04.** O status de pagamento só pode ser `pending`, `paid` ou `cancelled`. O padrão do cadastro é `pending`.
- **RN-05.** A tabela `obligations` não recebe campo de último envio, quantidade de envios, flag de mensagem enviada nem contagem mensal.
- **RN-06.** Enquanto não existe histórico, Mensagem e Envios no mês são o texto "Sem envios" em todas as obrigações, inclusive as recém-cadastradas e as de status pago ou cancelado.
- **RN-07.** Excluir a obrigação não exclui o contato.
- **RN-08.** Excluir o contato é permitido só quando ele não tem obrigação. A verificação acontece antes do `delete`. A foreign key recusa a exclusão se a aplicação tentar apagar o contato mesmo assim.
- **RN-09.** Depois que a última obrigação de um contato é excluída, esse contato volta a poder ser excluído.
- **RN-10.** Obrigações de exemplo do seeder existem só em `local` e `testing`. Em produção, e em qualquer outro ambiente, esse seeder não insere registros. O usuário de teste continua sujeito à mesma trava da Fase 1.

## 8. Regras futuras de mensagem

Estas regras não têm critério de aceite executável nesta fase. Estão aqui para a fase de mensagens não recolocar contador na obrigação nem impedir o reenvio manual.

- **RN-11.** Mensagem sempre tem `contact_id`. `obligation_id` é nulo quando o envio é só para o contato.
- **RN-12.** O reenvio manual grava uma nova mensagem com origem `manual`. A existência de mensagens anteriores da mesma obrigação e do mesmo tipo de lembrete não recusa o reenvio.
- **RN-13.** O envio automático grava origem `automatic` e um tipo de lembrete. Se já houver mensagem automática daquela obrigação com aquele tipo, o novo automático é recusado. A recusa é uma consulta, não um índice único compartilhado com o envio manual.
- **RN-14.** Envios no mês conta mensagens do contato no mês corrente, com ou sem obrigação. Mensagem da obrigação olha só as mensagens daquela obrigação. Nenhuma das duas contas é coluna de `obligations`.

## 9. Validação e feedback

A validação de obrigação fica nos Form Requests. O controller não revalida o payload.

| Campo na tela | Entrada | Regra |
| --- | --- | --- |
| Contato | `contact_id` | obrigatório, inteiro, existente em `contacts` |
| Título | `title` | obrigatório, texto, no máximo 160 caracteres |
| Observação | `notes` | opcional, texto, no máximo 2000 caracteres |
| Vencimento | `due_on` | obrigatório, data |
| Pagamento | `payment_status` | obrigatório, `pending`, `paid` ou `cancelled` |

Mensagens e nomes dos atributos são em português.

| Situação | Resultado |
| --- | --- |
| Cadastro válido | redireciona à listagem; flash "Obrigação cadastrada." |
| Atualização válida | redireciona à listagem; flash "Obrigação atualizada." |
| Exclusão confirmada da obrigação | redireciona à listagem; flash "Obrigação excluída." |
| Dados inválidos | redireciona ao formulário com erros e valores na sessão |
| Exclusão da obrigação | confirmação "Excluir esta obrigação? Esta ação não pode ser desfeita." |
| Exclusão de contato com obrigação | o contato permanece; flash "Este contato possui obrigações e não pode ser excluído." |

## 10. Modelo conceitual

Entidade `Obligation`, criada nesta fase.

| Campo | Obrigatório | Observação |
| --- | --- | --- |
| `contact_id` | sim | chave para `contacts`, restrição na exclusão do contato |
| `title` | sim | título da obrigação |
| `notes` | não | observação |
| `due_on` | sim | vencimento |
| `payment_status` | sim | `pending`, `paid` ou `cancelled` |
| `created_at`, `updated_at` | sim | timestamps do Laravel |

Contrato futuro da entidade `Message`, não criada nesta fase:

| Campo | Obrigatório | Observação |
| --- | --- | --- |
| `contact_id` | sim | destinatário |
| `obligation_id` | não | nulo no envio só para o contato |
| `origin` | sim | `automatic` ou `manual` |
| `reminder_type` | no automático | `before_due`, `on_due` ou `overdue`; opcional no manual |
| `sent_at` | sim | base do mês corrente e do histórico |

URLs desta fase:

| Método | Caminho | Uso |
| --- | --- | --- |
| GET | `/obligations` | listagem |
| GET | `/obligations/create` | formulário |
| POST | `/obligations` | gravar |
| GET | `/obligations/{obligation}` | detalhe |
| GET | `/obligations/{obligation}/edit` | edição |
| PUT/PATCH | `/obligations/{obligation}` | atualizar |
| DELETE | `/obligations/{obligation}` | excluir |

A exclusão de contato continua em `DELETE /contacts/{contact}`.

## 11. Critérios de aceite

- **CA-01.** Abrir Obrigações pela navegação e pela home mostra a listagem, sem o texto "Este módulo será implementado em uma próxima etapa."
- **CA-02.** Mensagens, Modelos e Configurações continuam na página de espera. `/modules/billing` responde 404. `/modules/obligations` também responde 404.
- **CA-03.** Cadastrar título "IPTU", contato existente, vencimento passado, observação vazia e pagamento Pendente grava `payment_status = pending`, `notes` nulo e o `contact_id` escolhido.
- **CA-04.** Título vazio, contato ausente ou inexistente, vencimento inválido ou pagamento fora da lista redirecionam ao formulário, com erro no campo e o valor informado preservado.
- **CA-05.** A lista mostra Contato, Obrigação, Vencimento, Mensagem, Envios no mês e Pagamento, nesta ordem. Mensagem e Envios no mês exibem "Sem envios". A página não exibe "0", "Não enviado" nem "Enviada".
- **CA-06.** Uma obrigação paga e uma cancelada também exibem "Sem envios" nas duas colunas. O pagamento aparece como Pago ou Cancelada, depois das colunas de mensagem.
- **CA-07.** Com 11 obrigações, a segunda página existe e a primeira não repete o item exclusivo da segunda. A ordenação é vencimento crescente.
- **CA-08.** Buscar por parte do título, da observação ou do nome do contato reduz a lista. Busca sem resultado mostra "Nenhuma obrigação encontrada". Base vazia mostra "Nenhuma obrigação cadastrada" e o atalho de cadastro.
- **CA-09.** O termo `100%` não é curinga e não provoca erro.
- **CA-10.** O detalhe mostra os dados gravados, "Sem envios" nos dois indicadores e não oferece Reenviar.
- **CA-11.** Excluir uma obrigação pede a confirmação prevista, remove só a obrigação e exibe "Obrigação excluída."
- **CA-12.** Excluir um contato sem obrigação continua funcionando como na Fase 1.
- **CA-13.** Excluir um contato com obrigação, em qualquer status de pagamento, mantém o contato e as obrigações e exibe "Este contato possui obrigações e não pode ser excluído."
- **CA-14.** Depois de excluir a última obrigação do contato, a exclusão desse contato volta a ser aceita.
- **CA-15.** O seeder de obrigações cria exemplos em `local` e em `testing`. Em `production` não cria obrigação nem usuário de teste.
- **CA-16.** A suíte desta fase passa junto com a suíte já existente. Nenhuma migration de `messages` é criada.

## 12. Decisões

- O produto desta fase é o cadastro do que poderá gerar um lembrete, não um controle de pagamento.
- O status de pagamento fica no registro e na interface, sempre depois do vencimento e dos indicadores de mensagem.
- Mensagem e Envios no mês entram na lista agora para fixar a prioridade visual, com o texto "Sem envios". O cálculo real espera o histórico.
- Reenviar é uma ação futura e não tem botão nesta fase.
- A trava do automático é por obrigação e tipo de lembrete. A trava não se aplica ao manual. Por isso a contagem e a flag não nascem em `obligations`.
- A exclusão de contato com obrigação passa a ser regra executada. A exclusão por histórico de mensagens permanece adiada.
