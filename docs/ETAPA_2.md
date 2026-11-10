# Etapa 2 — Cadastro e perfis

Implementada na pasta `C:\xampp\htdocs\serviconnect`.

## Fluxos disponíveis

| Conta | Documento | Uso |
| --- | --- | --- |
| Contratante pessoa física | CPF | Buscar prestadores, solicitar e acompanhar orçamentos |
| Contratante empresa | CNPJ | Buscar prestadores, solicitar e acompanhar orçamentos |
| MEI | CNPJ | Apresentar serviços e responder a pedidos |
| Empresa terceirizada | CNPJ | Apresentar serviços, informar equipe e responder a pedidos |

O cadastro pede nome de apresentação, documento, e-mail, senha e confirmação. Os dados de serviços e atendimento são preenchidos em Meu perfil após o login. A consulta externa de CNPJ deixou de ser obrigatória; a validação local verifica formato e dígitos, não comprova situação cadastral, identidade do titular ou enquadramento como MEI. A modalidade é informada pelo usuário.

CPF é exclusivamente numérico. CNPJ pode ser numérico ou alfanumérico; letras são normalizadas para maiúsculas. São removidos somente espaços e separadores de máscara. A validação de CNPJ usa módulo 11 com o valor ASCII menos 48, conforme o [manual da Receita Federal](https://www.gov.br/receitafederal/pt-br/centrais-de-conteudo/publicacoes/documentos-tecnicos/cnpj/manual-dv-cnpj.pdf). O CPF usa seus dois dígitos verificadores. Documentos repetidos e duplicados são recusados no servidor.

## Perfis

- Nome, telefone, apresentação e endereço são editáveis pelo titular.
- MEI e terceirizada também editam serviços, regiões atendidas, horários e responsável pelo atendimento. Quantidade de colaboradores é opcional e aparece somente para terceirizadas.
- CPF/CNPJ, tipo de pessoa e modalidade permanecem somente para leitura no perfil; o servidor ignora tentativas de alterá-los por POST.
- Outro usuário não recebe CPF, e-mail ou endereço residencial completo de um contratante pessoa física. Seu telefone fica restrito ao titular, à administração e aos prestadores destinatários de seus pedidos.
- O endereço base e as coordenadas exatas de MEIs não são enviados ao mapa/lista de outros usuários; a localização exibida é cidade/UF. O telefone informado pelo prestador é apresentado no perfil de serviços.
- A listagem existente passa a incluir MEIs e terceirizadas. A implementação da pesquisa com filtros continua sendo a etapa seguinte.

## Compatibilidade

A migração 004 acrescenta `usuarios.tipo_pessoa`, com padrão `juridica` para as contas antigas, e a opção `mei` em `empresas.tipo_empresa`. Os documentos já cabem no VARCHAR(14), inclusive CPF de 11 posições e CNPJ alfanumérico de 14.

O valor legado `usuarios.tipo_conta='empresa'` permanece por compatibilidade interna. Os papéis de acesso são calculados pelo perfil: contratante, prestador (MEI/terceirizada) ou administrador. Sessões antigas de terceirizadas são reconhecidas. As rotas existentes dos painéis e pedidos continuam válidas e IDs, senhas e vínculos não são recriados.

## Atualização no InfinityFree

O painel confirmou o diretório do domínio como `serviconnect.online/htdocs`. O workflow FTP publica em `./serviconnect.online/htdocs/`, e a configuração privada fica em `serviconnect.online/htdocs/backend/config.local.php`. A pasta `htdocs` na raiz da conta não é o destino deste domínio.

Como a etapa 1 já foi importada, usar **`database/infinityfree/ATUALIZAR_ETAPA_2_CADASTRO_PERFIS.sql`** no banco atual pelo phpMyAdmin, após exportar um backup. É uma atualização incremental, reaplicável, sem excluir registros. O arquivo consolidado `ATUALIZAR_BANCO_ATUAL.sql` também contém as duas etapas; `BANCO_NOVO_VAZIO.sql` continua exclusivo de instalação vazia.

O pacote **`ServiConnect_Etapa_2_Site_e_SQL.zip`** contém:

- `htdocs/`: código e recursos do site, incluindo as proteções da etapa 1.
- `SQL/ATUALIZAR_ETAPA_2_CADASTRO_PERFIS.sql`: atualização do banco, para importar pelo phpMyAdmin.
- `LEIA-ME.md`: passos de publicação e exemplo da configuração privada.

O pacote não inclui credenciais locais, `.git`, arquivos do MySQL, testes, contas fictícias ou fotos de usuários. Enviar os arquivos da pasta `htdocs/` para a pasta `htdocs/` existente da hospedagem, preservando fotos e a configuração privada de produção. Importar SQL sozinho não publica as proteções e os novos fluxos PHP.

Antes de usar o código corrigido, configurar `htdocs/backend/config.local.php` com `environment='production'`, URL real e os dados MySQL do painel do InfinityFree, incluindo a credencial substituída após a exposição anterior. A configuração local de desenvolvimento não deve ir para a hospedagem. Verificar a aplicação das regras `.htaccess` e testar o fluxo de cadastro/login/perfil/orçamento online após o envio. A publicação e essas verificações online não foram executadas nesta etapa.

## Testes locais

```powershell
& 'C:\xampp\php\php.exe' tests/security.php
& 'C:\xampp\php\php.exe' tests/accounts.php
```

Foram aprovadas 90 verificações de contas e perfis e 47 de segurança. Os testes de contas cobrem CPF, CNPJ, MEI, terceirizada, CNPJ alfanumérico, confirmação, duplicidade, validação no servidor, edição, autorização, privacidade, compatibilidade e pedido CPF→MEI→resposta. O teste de exportação SQL cobre atualização a partir do banco original, atualização somente da etapa 2 após a etapa 1 e instalação vazia, usando apenas dados fictícios.

Também houve conferência no navegador do cadastro CPF/MEI, login e perfil de prestador/MEI, incluindo largura móvel de 390px sem rolagem horizontal. As capturas locais estão em `docs/qa/`. A imagem padrão anterior estava vazia e foi substituída por um avatar SVG local; uploads de SVG continuam recusados.

## Demonstração local

Senha inicial das contas fictícias: `ServiDev!2026`.

| Conta | Documento |
| --- | --- |
| Contratante pessoa física | `90000000680` |
| MEI de jardinagem | `90000005000107` |
| Empresa contratante anterior | `90000001000129` |
| Terceirizada anterior | `90000002000173` |

Essas contas são apenas do banco de desenvolvimento e não estão nos SQLs para a hospedagem.
