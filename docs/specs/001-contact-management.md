# Spec 001 — Gestão de contatos

| | |
| --- | --- |
| Identificador | `001-contact-management` |
| Produto | Factotum |
| Estado | Pronta para implementação |
| Plano | [docs/plans/001-contact-management.md](../plans/001-contact-management.md) |
| Data | 2026-11-23 |

Esta etapa entrega apenas a especificação. Nenhum código de aplicação faz parte deste documento.

## 1. Contexto

O Factotum automatiza mensagens de WhatsApp. O repositório hoje é o skeleton do Laravel 13: SQLite como banco padrão, Laravel Sail, Blade, Tailwind CSS 4 e Vite. A única rota web renderiza a página inicial do framework. Não há módulo de contatos, camada de serviço, repositório, Form Request nem autenticação.

O contato é a base dos módulos futuros. Esta spec cobre o cadastro, a consulta e a organização desses contatos, incluindo o registro explícito de consentimento para WhatsApp. O envio de mensagens fica para uma spec posterior.

A interface é em português. O banco e o código usam identificadores em inglês, no padrão do Laravel já presente no model `User`.

## 2. Objetivo

Permitir que um operador, sem login, gerencie contatos em uma interface web simples, moderna e responsiva, com:

- navegação estável para a home, contatos e módulos futuros;
- listagem com busca e paginação;
- criação, visualização, edição e exclusão;
- campos nome, empresa, telefone, e-mail, status e consentimento WhatsApp;
- validação no servidor e feedback visual de sucesso e de erro.

## 3. Fora de escopo

- Autenticação, autorização, perfis e multiusuário.
- API JSON, filas, eventos e integração com o WhatsApp.
- Envio, agendamento ou bloqueio real de mensagens.
- Importação, exportação, etiquetas, grupos e mesclagem de duplicados.
- Auditoria além da data de consentimento.
- Clean Architecture, DDD, interfaces, DTOs, Actions e contratos de repositório.
- Bloqueio de exclusão por obrigações ou histórico de mensagens. A restrição fica registrada para uma fase futura e não é aplicada agora.
- Pacotes novos, inclusive Laravel Boost e bibliotecas de telefone ou máscara.
- Tradução global da aplicação (`lang/`). Os textos desta tela ficam nas views e nas mensagens dos Form Requests.
- Modo escuro.

## 4. Ator

Há um único ator: o operador que abre o sistema. Qualquer visitante da URL alcança o módulo. A ausência de autenticação é uma limitação conhecida e aceita nesta fase.

## 5. Requisitos funcionais

### Navegação

- **RF-01.** A raiz `/` deixa de exibir a página de boas-vindas do Laravel e passa a exibir a home do Factotum, dentro do layout da aplicação.
- **RF-02.** O layout expõe estes destinos:
  - Início
  - Contatos
  - Obrigações
  - Mensagens
  - Modelos
  - Configurações
- **RF-03.** Início e Contatos levam a telas funcionais. Os outros quatro destinos existem como navegação e abrem uma página própria informando que o módulo ainda será implementado. Um identificador desconhecido responde 404.
- **RF-04.** A home resume o produto e oferece os mesmos destinos, com Contatos como ação principal.
- **RF-05.** O nome exibido no layout é o de `config('app.name')`. O valor esperado do produto é `Factotum`.

### Contatos

- **RF-06.** O operador lista os contatos em páginas de 10 registros, ordenados por nome e, em empate, por `id`.
- **RF-07.** A busca é opcional, via query `q`, e considera nome, empresa, e-mail e telefone. A paginação preserva o termo. Uma nova busca volta à primeira página.
- **RF-08.** O termo é ignorado quando, depois do trim, fica vazio. Acima de 100 caracteres, a aplicação usa só os 100 primeiros.
- **RF-09.** Lista vazia e busca sem resultado têm mensagens diferentes. A lista vazia oferece o caminho para cadastrar o primeiro contato.
- **RF-10.** O operador cria um contato com nome, empresa, telefone, e-mail, status e consentimento WhatsApp.
- **RF-11.** O operador abre um contato, edita os mesmos campos e o exclui.
- **RF-12.** Criar, atualizar e excluir redirecionam de volta à listagem com uma mensagem de sucesso.
- **RF-13.** Dados inválidos num formulário Blade redirecionam de volta à mesma tela. Os erros e os valores informados ficam na sessão, e o formulário é reapresentado com esses valores e com a mensagem de cada campo. A resposta desse fluxo é o redirecionamento, não HTTP 422.
- **RF-14.** Um contato inexistente na visualização, edição, atualização ou exclusão responde 404.
- **RF-15.** A exclusão é definitiva, depois de confirmação explícita no navegador.

## 6. Interface

A interface usa o Tailwind já configurado em `resources/css/app.css` e a fonte Instrument Sans. Não há JavaScript de componente: `resources/js/app.js` permanece o ponto de entrada do Vite, e a confirmação de exclusão usa `confirm()` nativo.

- **UI-01.** Em largura grande, a navegação fica numa coluna lateral fixa. Em largura menor, ela cabe no cabeçalho e pode ser aberta sem biblioteca adicional.
- **UI-02.** O item da seção atual fica visualmente destacado.
- **UI-03.** A listagem é uma tabela a partir do breakpoint `md`. Abaixo disso, cada contato vira um cartão com os mesmos dados e as mesmas ações.
- **UI-04.** Ações de cada contato: Ver, Editar e Excluir. Há um botão Novo contato.
- **UI-05.** Status aparece como texto legível: Ativo ou Inativo. Consentimento aparece como Consentido ou Sem consentimento.
- **UI-06.** Todo campo de formulário tem rótulo associado. Campos obrigatórios são identificáveis no rótulo. O foco do teclado permanece visível.
- **UI-07.** Erro de validação colore a borda do campo e mostra a mensagem junto dele. Um resumo no topo do formulário informa que há correções pendentes.
- **UI-08.** Mensagem de sucesso aparece no topo do conteúdo e some na navegação seguinte (flash de sessão).
- **UI-09.** O telefone é exibido como foi gravado: somente dígitos. O formulário aceita dígitos e os caracteres `+`, espaço, parênteses e hífen, com o exemplo `+55 (11) 98888-7777`.
- **UI-10.** O checkbox de consentimento traz o texto de ajuda: o contato autorizou receber mensagens pelo WhatsApp. Desmarcado significa ausência de consentimento.
- **UI-11.** O status inicial sugerido no cadastro é Ativo.

## 7. Regras de negócio

- **RN-01.** Nome é obrigatório. Empresa e e-mail são opcionais.
- **RN-02.** O telefone identifica o contato no WhatsApp. Dois contatos não podem ter o mesmo telefone normalizado.
- **RN-03.** A entrada do telefone só pode conter dígitos e os caracteres de formatação `+`, espaço, parênteses e hífen. Letras, pontos e qualquer outro caractere invalidam o campo. Esses caracteres não são removidos em silêncio.
- **RN-04.** Antes da gravação, o telefone aceito é normalizado para somente dígitos. O valor gravado tem de 10 a 15 dígitos. Quantidade fora desse intervalo, ou entrada que não deixe dígitos, invalida o campo. O sistema não acrescenta DDI automaticamente.
- **RN-05.** O e-mail, quando informado, é gravado em minúsculas e sem espaços nas pontas. Vários contatos podem compartilhar o mesmo e-mail. Vários e-mails vazios são permitidos.
- **RN-06.** Nome e empresa são gravados sem espaços nas pontas. Empresa em branco é gravada como nula. E-mail em branco é gravado como nulo.
- **RN-07.** O status só pode ser `active` ou `inactive`. No banco o valor é esse identificador; na tela, Ativo ou Inativo. O padrão de um contato novo, se o campo não for alterado, é `active`.
- **RN-08.** Status e consentimento são independentes. Um contato inativo pode manter consentimento registrado, e um contato ativo pode estar sem consentimento.
- **RN-09.** O consentimento nasce desmarcado. Só é verdadeiro quando o operador marca o campo.
- **RN-10.** Ao passar a consentido, a aplicação grava `whatsapp_consent_at` com a data e hora atuais. Ao remover o consentimento, esse timestamp volta a nulo. Se o contato já era consentido e continua consentido, o timestamp original permanece.
- **RN-11.** O operador não informa `whatsapp_consent_at`. Esse campo é consequência da regra RN-10.
- **RN-12.** Contato inativo continua visível na lista e na busca. Esta fase não filtra por status.
- **RN-13.** A busca por telefone usa os dígitos do termo. Nome, empresa e e-mail usam correspondência parcial, sem diferenciar o uso de curingas informados pelo operador: `%`, `_` e `\` do termo são tratados como texto.
- **RN-14.** Módulos futuros de mensagem só poderão enviar para contato ativo e consentido. Esta spec registra a regra e não a executa, porque o envio não existe ainda.
- **RN-15.** Nesta fase, excluir remove o registro em definitivo, depois da confirmação. Não há lixeira nem restauração. O status ativo/inativo continua disponível para tirar o contato de uso sem apagá-lo.
- **RN-16.** No futuro, um contato com obrigações ou com histórico de mensagens não poderá ser excluído. Esta fase não cria essas relações e não aplica esse bloqueio.
- **RN-17.** Contatos criados pelo seeder servem apenas aos ambientes `local` e `testing`. Em produção, e em qualquer outro ambiente, esse seeder não insere registros.

## 8. Validação e feedback

A validação é exclusiva dos Form Requests. O controller não revalida o payload.

| Campo na tela | Entrada | Regra |
| --- | --- | --- |
| Nome | `name` | obrigatório, texto, no máximo 120 caracteres |
| Empresa | `company` | opcional, texto, no máximo 120 caracteres |
| Telefone | `phone` | obrigatório; só dígitos, `+`, espaço, parênteses e hífen; 10 a 15 dígitos depois da normalização; único em `contacts.phone` |
| E-mail | `email` | opcional, e-mail, no máximo 255 caracteres |
| Status | `status` | obrigatório, `active` ou `inactive` |
| Consentimento WhatsApp | `whatsapp_consent` | booleano; checkbox ausente vale falso |

Na edição, a unicidade do telefone ignora o próprio contato. Mensagens e nomes dos atributos são em português.

Feedback esperado:

| Situação | Resultado |
| --- | --- |
| Cadastro válido | redireciona para a listagem; flash "Contato cadastrado." |
| Atualização válida | redireciona para a listagem; flash "Contato atualizado." |
| Exclusão confirmada | redireciona para a listagem; flash "Contato excluído." |
| Dados inválidos | redireciona de volta ao formulário; a sessão guarda os erros e os valores informados; a tela reapresenta esses valores e o erro de cada campo |
| Exclusão | o navegador pede confirmação com o texto "Excluir este contato? Esta ação não pode ser desfeita." |

## 9. Modelo conceitual

Entidade `Contact`.

| Campo | Obrigatório | Observação |
| --- | --- | --- |
| `name` | sim | nome do contato |
| `company` | não | empresa |
| `phone` | sim | dígitos, único |
| `email` | não | minúsculas |
| `status` | sim | `active` ou `inactive` |
| `whatsapp_consent` | sim | booleano, padrão falso |
| `whatsapp_consent_at` | não | preenchido só pela RN-10 |
| `created_at`, `updated_at` | sim | timestamps do Laravel |

A tela de detalhe mostra também a data de consentimento, quando existir, e a data de atualização. O formulário não edita essas datas.

URLs:

| Método | Caminho | Uso |
| --- | --- | --- |
| GET | `/` | home |
| GET | `/contacts` | listagem |
| GET | `/contacts/create` | formulário de cadastro |
| POST | `/contacts` | gravar |
| GET | `/contacts/{contact}` | detalhe |
| GET | `/contacts/{contact}/edit` | formulário de edição |
| PUT/PATCH | `/contacts/{contact}` | atualizar |
| DELETE | `/contacts/{contact}` | excluir |
| GET | `/modules/{module}` | módulo futuro; `module` é `obligations`, `messages`, `templates` ou `settings` |

## 10. Critérios de aceite

- **CA-01.** Abrir `/` mostra a home do Factotum com os seis destinos e não mostra o conteúdo da welcome do Laravel.
- **CA-02.** Em viewport larga, a navegação lateral está visível. Em viewport estreita, os mesmos destinos continuam acessíveis pelo cabeçalho.
- **CA-03.** Obrigações, Mensagens, Modelos e Configurações abrem a página de módulo pendente. Outro slug em `/modules/{module}` responde 404.
- **CA-04.** Cadastrar nome "Ana Lima", telefone "+55 (11) 98888-7777", empresa "Oficina", e-mail "Ana@Empresa.com", status Ativo e consentimento marcado grava `phone = 5511988887777`, `email = ana@empresa.com`, `status = active`, `whatsapp_consent = true` e `whatsapp_consent_at` preenchido.
- **CA-05.** O mesmo cadastro sem a marcação de consentimento grava consentimento falso e `whatsapp_consent_at` nulo.
- **CA-06.** Nome vazio, telefone com letras, telefone com caractere fora da formatação permitida (como o ponto), telefone com menos de 10 dígitos, telefone com mais de 15 dígitos, e-mail malformado ou status fora da lista redirecionam de volta ao formulário. A sessão guarda os erros e os valores informados, e a tela mostra o erro no campo correspondente com o que o operador digitou.
- **CA-07.** Dois cadastros cujo telefone normalizado coincide são recusados, inclusive quando a máscara digitada é diferente.
- **CA-08.** Editar o telefone de um contato para outro valor livre funciona. Editá-lo para o telefone de outro contato é recusado. Salvar o próprio telefone sem mudança é aceito.
- **CA-09.** Manter o consentimento marcado numa edição não altera `whatsapp_consent_at`. Desmarcar zera o timestamp. Marcar de novo grava um timestamp novo.
- **CA-10.** A listagem mostra os campos previstos, os rótulos Ativo/Inativo e Consentido/Sem consentimento, e pagina de 10 em 10. Com 11 contatos, a segunda página existe e a primeira não repete os itens da segunda.
- **CA-11.** Buscar por parte do nome, da empresa, do e-mail ou dos dígitos do telefone reduz a lista ao conjunto correspondente. Busca sem resultado mostra "Nenhum contato encontrado". Base vazia mostra "Nenhum contato cadastrado" e o atalho de cadastro.
- **CA-12.** O termo `100%` não é interpretado como curinga e não provoca erro.
- **CA-13.** Ver um contato exibe os dados gravados, inclusive a data de consentimento quando houver. O id inexistente responde 404.
- **CA-14.** Excluir pede confirmação. Depois da confirmação o contato some da lista e não volta a ser encontrado.
- **CA-15.** Cada operação bem-sucedida de criar, atualizar e excluir exibe o flash correspondente.
- **CA-16.** As páginas do módulo funcionam sem autenticação.
- **CA-17.** A suíte automatizada descrita no plano cobre estes critérios e a suíte já existente continua passando.
- **CA-18.** O seeder de contatos cria os registros de exemplo em `local` e em `testing`. Em `production` não cria nenhum contato.

## 11. Decisões desta spec

- Contatos são um recurso web clássico, com página de detalhe, e não apenas um formulário sobre a lista.
- Os módulos futuros entram agora só como navegação, com página de espera, para o layout não depender de links mortos. Obrigações usa o slug `obligations`.
- Telefone único e normalizado é a chave operacional do WhatsApp. A entrada aceita só dígitos e a formatação `+`, espaço, parênteses e hífen; letras e outros símbolos são recusados. E-mail não é único.
- A exclusão desta fase remove o contato. Quando existirem obrigações ou histórico de mensagens, a exclusão desses contatos deixará de ser permitida. O status ativo/inativo permanece.
- O seeder de contatos só popula `local` e `testing`.
- O DDI não é inferido. O operador informa o número completo.
- Consentimento é opt-in, com timestamp estável enquanto o opt-in permanecer.
- Status inativo não esconde o contato e não apaga o consentimento.
- A interface desta fase é clara e só em tema claro, sobre o Tailwind 4 já instalado.
