# ServiConnect — diagnóstico e plano de evolução

Análise em 09/11/2026. Referência: branch `main`, commit `023fe9a`.

> Este documento registra o diagnóstico inicial. A etapa de correções críticas e criação do banco local foi implementada posteriormente; consulte [DESENVOLVIMENTO.md](DESENVOLVIMENTO.md) para o estado atual e as verificações executadas.

## 1. Objetivo e alcance

Transformar o ServiConnect em uma plataforma para encontrar e contratar serviços de MEIs e empresas terceirizadas. Empresas e pessoas físicas poderão contratar, conforme definição do responsável pelo projeto nesta conversa.

Esta entrega é um diagnóstico do código e uma proposta de implementação por etapas. Nenhum fluxo da aplicação foi alterado. Não houve publicação, alteração de banco ou teste contra produção.

Foram examinados os arquivos PHP, JavaScript, CSS, README e workflows. Os 24 arquivos PHP passaram em `php -l` com PHP 8.0.30 instalado no XAMPP. Os cinco arquivos JavaScript passaram na análise de sintaxe com o parser do Node. Foi reproduzida, em ambiente simulado, a falha do menu de `js/script.js`; também foram conferidas referências estáticas locais.

**Limites:** o repositório não contém um esquema SQL completo ou migrações. Não foi validado o conteúdo do banco, seu esquema real, a configuração do servidor, o envio de e-mails, a execução dos workflows no GitHub ou a aparência renderizada em navegador. Observações de layout abaixo derivam do HTML e CSS. Não houve exploração das falhas no site publicado.

## 2. Base atual que pode ser aproveitada

- PHP com PDO e consultas parametrizadas nos principais acessos ao banco.
- Senhas cadastradas com `password_hash` e verificadas com `password_verify`.
- Cadastro, login, recuperação de senha, perfis e painéis separados para contratante e terceirizada.
- Solicitação de orçamento e resposta com valor ou recusa.
- Consulta da resposta limitada ao prestador destinatário, tanto na tela quanto no UPDATE por proprietário.
- Uso de transações no cadastro e nas operações de arquivamento.
- Integrações de CEP e CNPJ e biblioteca de mapa já presentes.
- Identidade visual com azul e amarelo que pode orientar a padronização.

É possível evoluir essa base de forma incremental. A prioridade é fechar o fluxo de contratação e corrigir os problemas que comprometem seu uso.

## 3. Problemas encontrados, impacto e correção

### 3.1 Busca e descoberta — prioridade da primeira entrega funcional

| Achado | Evidência | Impacto | Correção proposta |
| --- | --- | --- | --- |
| Busca da página inicial sem funcionamento | `index.php:37`: input e botão dentro de uma `div`, sem formulário, destino, nome de parâmetro ou evento de pesquisa | Digitar e clicar não produz resultados | Formulário GET levando à página de resultados; permitir Enter e compartilhar a URL |
| Painel lista todos os prestadores | `dashboard_contratante.php:33`: consulta sem parâmetros de pesquisa, categoria, cidade, paginação ou ordenação | Não há descoberta por serviço nem por região | Centralizar uma consulta filtrada e reaproveitá-la no catálogo e painel |
| Resultados incluem apenas terceirizadas | `dashboard_contratante.php:38` | MEIs não têm participação no fluxo | Consultar prestadores ativos, com filtro por MEI ou terceirizada |
| Mensagem promete filtro regional que não existe | `dashboard_contratante.php:134` | Usuário recebe informação incorreta sobre os resultados | Mostrar filtros aplicados e quantidade real de resultados |
| Mapa inventa coordenadas ausentes | `dashboard_contratante.php:358` | Prestador aparece em uma posição falsa | Exibir marcador somente com localização válida; manter o prestador sem coordenadas na lista |
| Endereço é salvo sem atualizar coordenadas | `PerfilTercerizadaController.php`, bloco de UPDATE | Mapa pode ficar ausente ou desatualizado após edição | Definir processo de geocodificação ou confirmação da localização e invalidar coordenadas antigas ao mudar endereço |
| Serviços e área atendida são texto livre | `dashboard_tercerizada.php:135` e controller do perfil | Termos inconsistentes dificultam filtros e relevância | Categorias estruturadas, serviços específicos e regiões atendidas separadas do endereço base |

MVP da busca: termo por serviço/nome/descrição, categoria, cidade/UF e tipo de prestador; filtros combináveis, paginação, ordenação estável, estado vazio e ação de limpar filtros. A pesquisa deve considerar diferenças de caixa e acentuação conforme a configuração confirmada do banco. Sinônimos, como “jardineiro” e “jardinagem”, exigem termos associados ou regra explícita; um `LIKE` isolado não resolve essa equivalência.

Busca pública e visualização de perfil são propostas para facilitar a descoberta. Solicitar orçamento exigirá autenticação, preservando o destino e a pesquisa após o login. A exposição pública deve limitar os campos retornados; CPF, endereço residencial completo e informações internas não devem compor os resultados.

### 3.2 MEI e pessoas físicas — mudança do modelo de produto

O cadastro ativo de `cadastro_empresa.php` oferece somente `terceirizada` e `contratante`; cadastro e login exigem CNPJ. Não foi encontrada uma tela ativa de currículo ou vagas. `js/perfil_cadastro.js` contém lógica de formação e experiência profissional sem referência nas páginas PHP/HTML atuais; `js/cep_api.js` também preserva nomes antigos de empregado.

Portanto, nesta versão a mudança envolve criar o fluxo de MEI e adaptar o de contratante. A existência de registros antigos de profissionais no banco precisa ser verificada antes de planejar qualquer migração desses registros.

Modelo proposto:

| Dimensão | Valores | Finalidade |
| --- | --- | --- |
| Papel de acesso | Contratante, prestador, administrador | Controlar o que a conta pode fazer |
| Documento do contratante | CPF ou CNPJ | Permitir pessoa física e empresa contratante |
| Modalidade do prestador | MEI ou empresa terceirizada | Adaptar apresentação e campos do perfil sem duplicar todo o fluxo |
| Oferta | Categorias, serviços e regiões atendidas | Fazer o prestador ser encontrado pela atividade oferecida |

O termo “MEI” precisa aparecer no cadastro, painel, perfil, busca e orçamento. Apenas trocar o texto de um botão não garante isso: as regras atuais de sessão e autorização reconhecem `terceirizada` em vários arquivos.

O perfil do prestador deve permitir nome de exibição, apresentação, categoria principal, serviços oferecidos, cidades/bairros atendidos, disponibilidade e canal de contato. Portfólio e faixa de preço podem entrar depois. Fotos de serviços devem passar pela mesma validação segura dos demais uploads.

No orçamento para MEI, o centro do formulário deve ser **o serviço solicitado**: descrição, local, data desejada e frequência. A quantidade de funcionários hoje é obrigatória em `solicitacao_orcamento.php` e `SolicitacaoController.php`; esse campo deve ser opcional ou específico de demandas com equipe. Exemplos de oferta: jardinagem, corte de grama e manutenção de jardins.

### 3.3 Segurança e integridade — corrigir antes de ampliar o uso

| Prioridade | Achado e evidência | Ação |
| --- | --- | --- |
| Imediata | `backend/conexao.php:12` contém configuração de produção e senha não vazia gravada no repositório público | Trocar a credencial no provedor, retirar segredos do código e configurar credenciais fora do versionamento. Apagar do arquivo atual não invalida cópias anteriores |
| Alta | `AuthController.php:46` não consulta nem verifica `usuarios.status` | Recusar login de contas bloqueadas e revalidar a situação em ações protegidas; aplicar a mesma restrição à busca e aos perfis |
| Alta | `SolicitacaoController.php:19` recebe `contratante_id` do POST | Usar exclusivamente o ID autenticado da sessão; validar também se o destinatário existe, está ativo e oferece serviços |
| Alta | Controllers dos dois perfis movem uploads para `foto/` usando a extensão/nome recebido, sem validar conteúdo ou tamanho | Aceitar formatos definidos, verificar MIME e imagem real, limitar tamanho, gerar nome seguro e impedir execução de scripts no diretório de uploads |
| Alta | `admin.php:43`, `adm.php:15` e `arquivados.php:15` alteram dados por GET; não foi encontrada proteção CSRF nos fluxos de alteração | Usar POST e token CSRF, validar permissão e alvo no servidor |
| Alta | `verificacao_status.html` insere mensagem da URL em `innerHTML`; o popup de `dashboard_contratante.php:363` interpola dados do banco como HTML | Renderizar texto com `textContent`/elementos DOM e tratar URLs e atributos; padronizar escape conforme o contexto |
| Alta | `CadastroEmpresaController.php` verifica presença dos campos, mas não impõe no servidor a validação completa de documento, e-mail, senha e tipo permitido | Validar e limitar valores no backend, independentemente do JavaScript. Não aceitar papel ou modalidade arbitrários |
| Média | Login não renova o ID de sessão e não há limitação de tentativas visível no código | Renovar a sessão ao autenticar; definir cookies e limites compatíveis com HTTPS e o ambiente real |
| Média | `backend/teste_conexao.php` e mensagens de exceção expõem informações de banco/infraestrutura se publicados e acessíveis | Restringir/remover diagnóstico em produção e registrar erros internamente, com mensagem genérica ao usuário |

Essas observações confirmam lacunas no código; o impacto final de um upload ou de uma exposição depende também da configuração do servidor. Não foram realizados ataques nem tentativas de acesso a dados reais.

### 3.4 Banco, perfis e orçamento

1. **Falta um esquema reproduzível.** Não há arquivos `.sql`. `admin.php:26` cria `solicitacoes` sem `data_solicitacao` e `valor_orcamento`, embora outros arquivos utilizem essas colunas. Isso demonstra incompatibilidade do instalador embutido, sem comprovar que o banco online esteja sem as colunas. Criar migrações e instruções de instalação após obter a estrutura real.
2. **Estrutura alterada durante acesso a páginas.** `admin.php` e `esqueci_senha.php` executam CREATE/ALTER. Mover alterações para migrações versionadas e executar a aplicação com permissões adequadas às operações normais.
3. **Arquivar apaga o perfil.** `admin.php:59` e `adm.php:34` removem a empresa e o usuário. `arquivados.php:29` restaura apenas a conta, com um novo ID, sem restaurar perfil ou vínculos. Adotar inativação preservando identidade e histórico, ou um mecanismo completo de arquivo/restauração. Conferir chaves estrangeiras e registros existentes antes de migrar.
4. **Bloqueio, exclusão e preservação de dados precisam de regras próprias.** Os textos atuais chamam o processo de “LGPD”, mas o código de arquivo não preserva o perfil como o README anuncia. Documentar o comportamento real e a política de dados; esta análise não é uma avaliação jurídica.
5. **Perfil do prestador incompleto.** O painel ativo não oferece edição de nome de exibição, telefone, responsável e horário, embora esses dados apareçam em outras telas; o controller recebe campos que a tela nem envia. Definir quais são obrigatórios, permitir editá-los e evitar publicar “Empresa em Configuração”.
6. **CNPJ depende de validação no navegador.** Em `js/validacao.js:56`, uma resposta HTTP bem-sucedida é classificada como “ativo” sem conferir no código a situação retornada. Uma falha da API também impede o cadastro. Separar documento válido, consulta disponível e verificação de situação/modalidade; confirmar o contrato da API na implementação.
7. **Histórico do prestador incompleto.** O painel mostra só solicitações abertas. Após responder, o pedido desaparece dessa lista. Adicionar abas de pendentes, respondidos e recusados.
8. **Estados do orçamento não têm transição protegida.** `RespostaOrcamentoController.php:25` limita por proprietário, mas não por estado aberto. A tela bloqueia a resposta repetida visualmente, porém o UPDATE não impõe a mesma regra. Definir transições, condição de estado e retorno quando nenhuma linha é alterada.
9. **Mensagens de resultado pouco claras.** Controllers redirecionam com parâmetros de status que nem sempre são apresentados. Mostrar confirmação e erro junto à ação, preservando dados quando houver falha.
10. **Frequência do serviço embutida em texto.** O formulário concatena frequência e detalhes numa descrição via JavaScript. Salvar campos estruturados quando precisarem ser filtrados ou acompanhados e validar o formulário no servidor.

No banco, confirmar tipos, restrições únicas para documentos/e-mails, relacionamentos e índices antes de propor o SQL definitivo. A implantação de MEI/CPF deve preservar IDs e orçamentos existentes, com backup e validação em banco de desenvolvimento.

### 3.5 Interface, conteúdo e manutenção

- **Erro JavaScript confirmado:** `js/script.js:77` chama `addEventListener` em `.mobile-nav-toggle`, ausente no HTML da home. O cenário foi reproduzido com DOM simulado. No CSS, o menu normal é ocultado abaixo de 768px; é necessário implementar o controle e a navegação móvel acessível.
- **Três referências estáticas quebradas:** `dashboard_tercerizada.php` pede `css/terceirizada.css`, mas o arquivo é `css/tercerizada.css`; `dashboard.php` envia o perfil para `PerfilTerceirizadaController.php`, mas o arquivo é `PerfilTercerizadaController.php`; `perfil_empresa.php` usa `href="text"` como fallback de Voltar.
- **Layout da busca sem adaptação específica para celular:** mapa e lista ficam na mesma linha, com lateral fixa de 380px. Criar visualização em coluna ou alternância Lista/Mapa; validar em larguras pequenas. O perfil também precisa adaptar o cabeçalho e a grade de duas colunas.
- **Inconsistência visual:** CSS global, CSS de painel e estilos inline redefinem botões, cores e tipografia. Consolidar componentes e espaçamentos, preservando a identidade azul/amarela.
- **Acessibilidade:** diversos campos de perfil não têm associação `for`/`id`; cards com `onclick` não oferecem a mesma interação por teclado. Priorizar labels corretos, foco visível, botões/links semânticos e mensagens de formulário acessíveis.
- **Conteúdo promete recursos não encontrados:** a home anuncia intermediação de pagamentos, contratos e avaliações, mas não foram encontrados os respectivos fluxos no repositório. Ajustar o texto à entrega disponível. O modelo atual é orçamento direcionado, apesar da mensagem de publicação de demandas.
- **SEO e descoberta:** propor páginas de categorias e perfis públicos com título e descrição úteis depois de definir a política de exibição dos dados. Não publicar documentos e endereço completo por padrão.
- **Código duplicado:** `adm.php`/`admin.php` e `dashboard.php`/painéis específicos mantêm implementações paralelas. Definir rotas oficiais e consolidar validação/autorização, sem excluir arquivos antes de conferir seus usos.
- **Consulta antiga inconsistente:** `UsuarioModel::buscarTerceirizadas()` filtra `usuarios.tipo_conta = 'terceirizada'`, enquanto o cadastro grava `empresa` e o painel usa `empresas.tipo_empresa`. Centralizar a regra de prestador para a nova busca.
- **Dados de demonstração isolados:** `js/mapa_servicos.js` contém empresas fixas e um alerta no lugar do pedido de orçamento; não é referenciado nas páginas atuais. Remover ou separar demonstrações do código de uso real.
- **Integração contínua:** o lint usa PHP 8.0 e o deploy configura PHP 8.2. O comando de lint contém quatro espaços não separáveis U+00A0, que precisam ser substituídos por espaços normais. A execução no GitHub não foi verificada. Arquivos repetidos diretamente em `.github/` também devem ser consolidados em `.github/workflows/`.
- **Publicação antes da validação:** o deploy e o lint rodam independentemente em push; não há dependência explícita que faça o deploy aguardar a aprovação dos checks. Vincular a publicação às verificações e definir exclusões para documentos internos, testes e configurações locais.
- **Falta documentação operacional:** registrar requisitos do PHP, extensões, instalação do banco, configuração local e passos de verificação. Criar `.gitignore` e configuração de exemplo sem segredos.

## 4. Primeira entrega proposta: busca e MEI com contratação por CPF/CNPJ

### Etapa 0 — preparar a base

- Trocar as credenciais expostas e separar configuração de código.
- Obter somente a estrutura das tabelas, sem exigir dados pessoais ou senha de produção, e preparar banco de desenvolvimento com dados fictícios.
- Registrar migrações compatíveis com os registros atuais.
- Corrigir autorização do pedido, bloqueio, uploads e proteção das alterações envolvidas no fluxo.

### Etapa 1 — cadastro e perfil compatíveis

- Oferecer “Quero contratar” e “Quero prestar serviços”.
- No contratante, aceitar pessoa física com CPF ou empresa com CNPJ.
- No prestador, identificar MEI ou terceirizada e pedir os dados necessários ao seu perfil de serviços.
- Unificar permissões de prestador e manter compatibilidade com as contas já existentes.
- Permitir informar serviços e regiões atendidas de forma consistente.
- Concluir dados essenciais antes de tornar o perfil encontrável.

### Etapa 2 — pesquisa de ponta a ponta

- Ligar a busca da home a resultados reais do banco.
- Reutilizar filtros no painel autenticado, com URL contendo os parâmetros de busca.
- Pesquisar por serviço e nome, com filtros por categoria, cidade/UF e modalidade de prestador.
- Exibir cards com nome, modalidade, serviços e região; abrir perfil e pedir orçamento.
- Excluir perfis bloqueados/inativos e impedir exibição de dados privados.
- Paginar, manter filtros na navegação e mostrar estados vazios/erros claros.
- Fazer a lista funcionar mesmo que o mapa ou seus recursos externos falhem.
- Usar apenas coordenadas reais para marcadores; apresentar claramente quando a localização não estiver disponível.

### Etapa 3 — orçamento adequado a serviços

- Pedir descrição, local, data desejada e frequência; adaptar a necessidade de equipe.
- Associar contratante pela sessão e conferir destinatário ativo.
- Permitir MEI e terceirizada responderem a seus próprios pedidos.
- Exibir confirmação de envio, resposta e histórico para ambas as partes.
- Proteger transições e evitar envio duplicado por clique repetido.

Essas etapas formam a primeira entrega funcional. A implementação pode ser dividida em mudanças pequenas, começando pela estrutura de conta/perfil que a busca precisa consultar.

## 5. Critérios de aceite e validação da primeira entrega

Usar dados fictícios para estes cenários:

1. Cadastrar pessoa física contratante, empresa contratante, MEI de jardinagem e empresa terceirizada; validar no servidor documentos, e-mails, senhas e tipos permitidos.
2. Manter login, perfil e orçamento das contas anteriores funcionando após a migração.
3. Buscar “jardinagem” e localizar somente ofertas compatíveis. Buscar “jardineiro” deverá funcionar por associação de termos definida e testada.
4. Combinar cidade, categoria e MEI/terceirizada; conferir que resultados e total respeitam todos os filtros.
5. Confirmar o comportamento com caixa/acentos, termo vazio, caracteres especiais, consultas longas, nenhum resultado e múltiplas páginas. Definir e aplicar limites de entrada.
6. Manter filtros ao abrir perfil, voltar e passar pelo login para solicitar orçamento.
7. Não mostrar prestador bloqueado/inativo; impedir login e ações protegidas de conta bloqueada, inclusive com sessão antiga.
8. Não inventar posição para prestador sem coordenadas. Buscar e abrir perfil mesmo quando o mapa falhar.
9. Enviar orçamento para MEI sem exigir uma equipe; conferir autoria pelo usuário autenticado, ignorando eventual ID de contratante enviado pelo cliente.
10. Permitir resposta apenas do destinatário, proteger o estado do pedido e manter histórico visível para as duas partes.
11. Rejeitar arquivos inválidos e requisições sem autorização/proteção aplicável; exibir mensagens recebidas como texto, sem interpretá-las como HTML.
12. Verificar busca, cadastro, perfil e orçamento no desktop e em 360/390px, além da navegação por teclado.

Esses cenários são o plano de validação da implementação futura. Nesta entrega foram executados somente os checks de código descritos na seção 1.

## 6. Evoluções seguintes

Depois do fluxo principal funcionando: padronização visual e navegação móvel; portfólio; favoritos; notificações; ciclo de conclusão/cancelamento do serviço; avaliações vinculadas a serviços efetivamente concluídos; moderação; métricas de busca e conversão. Pagamentos e contratos devem ter escopo próprio, depois de definido o funcionamento operacional.

A primeira meta de produto é permitir que uma pessoa encontre um prestador adequado e consiga obter uma resposta ao pedido de orçamento. Esse percurso deve orientar a ordem das entregas.
