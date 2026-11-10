# Etapa 3 — Busca de serviços

A busca da página inicial envia o texto para `busca.php`, disponível sem login. O painel do contratante usa a mesma consulta, com acesso ao perfil, orçamento e mapa dos resultados da página atual. Esta etapa usa as tabelas existentes: **não exige importar SQL nem substituir o banco de produção**.

## Como pesquisar

- Serviço ou nome: procura cada palavra no nome, serviços, apresentação, cidade e regiões atendidas. Desconsidera diferenças de caixa e acentuação conforme a colação `utf8mb4_general_ci` do banco atual.
- Cidade ou região atendida: consulta tanto a cidade da sede quanto as regiões declaradas no perfil. Não usa geolocalização do visitante.
- Estado da sede: filtra a UF cadastrada no endereço principal do prestador.
- Tipo: MEI, empresa terceirizada ou ambos.
- Ordenação: relevância para os termos informados, nome de A a Z ou de Z a A. A relevância prioriza serviços e depois nome; sem texto, a ordem é alfabética.

Cada página contém até 12 prestadores. A contagem usa todos os resultados dos mesmos filtros; os links preservam os filtros e a ordenação. Uma nova pesquisa começa na primeira página. Páginas fora do intervalo são normalizadas, e parâmetros inválidos recebem uma mensagem na tela, sem erros de banco expostos.

Os resultados exibem identificação comercial, tipo, serviços, apresentação resumida, cidade/UF e regiões atendidas. Os prestadores precisam preencher esses campos no próprio perfil para que seus serviços e regiões sejam encontrados. A lista não inventa ofertas, avaliações, preços ou disponibilidade.

O visitante pode pesquisar sem conta. Ao escolher um perfil, entra pelo login e retorna ao prestador escolhido. Contatos e detalhes completos continuam sujeitos às permissões do perfil. Contratantes autenticados podem solicitar orçamento diretamente pelo resultado.

## Segurança e mapa

- Somente contas ativas de prestadores aparecem; contratantes, administradores, bloqueados e arquivados ficam de fora.
- Consultas preparadas e ordenação definida por uma lista fixa. `%` e `_` digitados são tratados como texto, sem ampliar artificialmente a busca.
- Conteúdo dos campos e da pesquisa é escapado no HTML.
- Documento, e-mail, telefone e endereço residencial não são selecionados pela busca.
- Coordenadas de MEI permanecem ocultas, inclusive no JSON do mapa. O mapa do painel mostra somente terceirizadas da página atual com coordenadas informadas.
- A continuação do login aceita apenas um ID numérico de prestador e constrói a rota interna no servidor; não aceita destinos externos.

## Verificação local

Com o servidor e o banco separados de desenvolvimento ativos:

```powershell
& 'C:\xampp\php\php.exe' tests/search.php
& 'C:\xampp\php\php.exe' tests/accounts.php
& 'C:\xampp\php\php.exe' tests/security.php
```

Os testes de busca criam registros temporários apenas em `serviconnect_dev`, validam consultas e páginas HTTP reais e removem os registros ao terminar. Cobrem combinações de filtros, acentos, caracteres especiais, injeção, contagem, paginação, privacidade, exclusão de contas indisponíveis e continuação do login.

## Publicação

O workflow existente publica os arquivos do commit na `main` em `serviconnect.online/htdocs/`. A configuração privada já existente é preservada, assim como as fotos enviadas pelos usuários. Após o deploy, conferir a busca na página inicial e em `https://serviconnect.online/busca.php` com os dados reais da hospedagem.
