# Ambiente de desenvolvimento e correções críticas

Preparado em 09/11/2026 a partir da estrutura do SQL fornecido. O arquivo original, com dados do site, permanece em Downloads e não foi importado nem adicionado ao repositório.

## Ambientes separados

| Ambiente | Banco | Acesso |
| --- | --- | --- |
| Desenvolvimento | `serviconnect_dev` | MySQL separado em `127.0.0.1:3307` |
| Site publicado | Banco existente na hospedagem | Mantido sem alterações nesta entrega |

O MySQL de desenvolvimento usa um diretório próprio fora do repositório, em `%LOCALAPPDATA%/ServiConnect/dev-<identificador>/mysql`. A configuração do XAMPP e seus bancos existentes não são substituídos. O servidor web local escuta apenas em `127.0.0.1:8080`.

Requisitos: Windows, PowerShell 7.2 ou superior e XAMPP em `C:\xampp`. Foi validado com PHP 8.0.30 e MariaDB 10.4.32 instalados neste computador. Os workflows de sintaxe e publicação usam PHP 8.2.

## Iniciar e parar

Na pasta do projeto:

```powershell
pwsh -File .\tools\start-dev.ps1
```

Abrir [site local](http://127.0.0.1:8080). O script é reutilizável: mantém o banco e seus dados, aplica apenas migrações pendentes e não redefine as senhas de contas já existentes. Não instala um serviço do Windows nem modifica o MySQL de produção.

Para encerrar, preservando os dados:

```powershell
pwsh -File .\tools\stop-dev.ps1
```

Se o XAMPP estiver em outro diretório, passar `-XamppPath` aos dois scripts. A porta do banco pode ser escolhida na primeira inicialização com `-DatabasePort`; reutilizar a mesma porta nas seguintes. A porta web desta configuração é 8080. Um processo diferente ocupando uma das portas causa interrupção, em vez de ser encerrado pelo script.

## Contas fictícias

Todos os dados abaixo são exclusivos dos testes locais. Senha inicial: **`ServiDev!2026`**.

| Conta | Documento de demonstração |
| --- | --- |
| Contratante | `90000001000129` |
| Prestador de jardinagem | `90000002000173` |
| Prestador de limpeza | `90000003000118` |
| Administrador | `90000004000162` |
| MEI de jardinagem | `90000005000107` |
| Contratante pessoa física | `90000000680` |

Os cadastros CPF/CNPJ e os perfis MEI/terceirizada estão implementados. Consulte [Etapa 2](ETAPA_2.md) para os novos fluxos e a atualização incremental do InfinityFree.

A [Etapa 3](ETAPA_3.md) acrescenta busca pública e no painel, filtros e paginação com os dados existentes, sem nova importação de banco. A suíte específica é `tests/search.php`.

As [etapas 4 e 5](ETAPAS_4_E_5.md) acrescentam propostas e histórico completo, perfis com seleção de serviços/cidades, mapas e visual responsivo. A migração incremental 005 foi preparada para preservar os registros. A suíte específica é `tests/workflow.php`.

## Estrutura e configurações

- `database/migrations/001_base.sql`: estrutura do banco atual, sem INSERTs de produção, sem nome do banco de produção e sem contadores de IDs reais.
- `database/migrations/002_integridade.sql`: estados de orçamento compatíveis com a aplicação, data de arquivamento, índices e tabela de limitação de tentativas.
- `database/migrations/003_preservar_status_legado.sql`: preserva os estados vazios e nulos existentes no dump original, sem inferir se um pedido foi respondido ou recusado.
- `database/migrations/004_cadastro_perfis.sql`: tipos de pessoa CPF/CNPJ e modalidade MEI, preservando as contas anteriores.
- `database/infinityfree/`: arquivos de atualização e instalação preparados para importação pelo phpMyAdmin, com instruções em `LEIA-ME.md`.
- `schema_migrations`: registra as migrações já aplicadas no ambiente novo.
- `tools/setup-dev.php`: cria exclusivamente `serviconnect_dev`, aplica as migrações e configura a conta local da aplicação. Usa as credenciais privadas geradas pelo script de inicialização.
- `backend/config.example.php`: exemplo sem segredos.
- `backend/config.local.php`: configuração gerada localmente e ignorada pelo Git. Contém uma senha aleatória da conta de desenvolvimento.
- `%LOCALAPPDATA%/ServiConnect/dev-<identificador>/credentials.json`: credenciais administrativas do servidor local, fora do projeto. Nesse diretório ficam também PIDs e logs.

A conta da aplicação possui somente SELECT, INSERT, UPDATE e DELETE em `serviconnect_dev`. CREATE/ALTER ficam restritos à preparação por CLI. É possível usar variáveis de ambiente `APP_ENV`, `APP_URL`, `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER` e `DB_PASSWORD`; elas têm preferência sobre o arquivo local. `APP_URL` deve representar o endereço confiável da aplicação, sem depender do cabeçalho Host recebido.

## Correções entregues

- Conexão sem senha de produção no código; erro público de conexão genérico.
- Sessão com cookie HttpOnly/SameSite e renovação do identificador no login; Secure quando servido por HTTPS.
- Contas bloqueadas ou arquivadas impedidas de entrar e de usar sessões anteriores; prestadores indisponíveis excluídos da listagem e dos perfis.
- Limitação de tentativas de login e de recuperação de senha no servidor.
- Proteção CSRF nos formulários, incluindo ações administrativas e logout; alterações administrativas passam a usar POST.
- Contratante do pedido determinado pela sessão, com validação do prestador destinatário e dos campos no servidor.
- Resposta de orçamento restrita ao destinatário e ao estado aberto, com valores monetários limitados e estados aceitos no banco.
- Upload limitado a JPG/PNG/WebP reais de até 2 MB e 6000 pixels por lado, com nome/extensão gerados pelo servidor. O caminho da foto atual enviado pelo navegador é ignorado.
- Arquivamento por status, preservando o mesmo ID, perfil e pedidos. Restauração reativa a mesma conta. Registros do sistema antigo de arquivo permanecem separados: dados já excluídos exigem recuperação por backup.
- Mensagem da URL tratada como texto; popups do mapa construídos com elementos DOM, sem interpolação de HTML dos prestadores.
- Mapa sem coordenadas inventadas; edição de endereço do prestador invalida coordenadas anteriores.
- Recuperação de senha com token armazenado como hash, expiração e uso único. Exibição do link de teste restrita ao modo development com acesso de loopback. Envio real de e-mail depende da configuração da hospedagem.
- Removidas alterações de estrutura durante navegação em páginas; diagnóstico de banco desativado publicamente.
- Corrigidos o erro JavaScript causado pelo botão de menu ausente e referências quebradas. O painel antigo redireciona aos painéis oficiais.
- Servidor PHP local usa roteador que bloqueia arquivos internos. Apache tem regras equivalentes em `.htaccess`, incluindo proteção do diretório de upload.
- Lint com espaços normais e verificação de sintaxe dentro do job de publicação antes do FTP. Configuração privada, SQL, ferramentas, testes e documentos internos excluídos do envio.

## Verificação

Com o ambiente local ligado:

```powershell
& 'C:\xampp\php\php.exe' .\tests\security.php
```

A suíte cria contas temporárias no banco local, testa requisições HTTP reais e remove seus dados/arquivos ao terminar. Recusa configuração de produção. Foram aprovadas **47 verificações**, cobrindo autenticação, bloqueio, CSRF, autoria, orçamento, uploads, arquivo/restauração, cadastro, recuperação, limitação de tentativas, permissões do banco e logout.

Também foram conferidos a sintaxe dos 35 arquivos PHP e dos cinco scripts JavaScript, os scripts inline renderizados no painel do contratante e os cenários de menu ausente e mensagem maliciosa tratada como texto. A etapa 2 acrescenta 90 verificações de contas/perfis e conferência visual de cadastro, login e perfil de prestador/MEI no navegador e em largura móvel. O ciclo de parar e reiniciar foi validado preservando os dados.

## Antes de uma futura publicação

As alterações estão locais. A credencial antiga continua comprometida pelo histórico público e precisa ser trocada no painel da hospedagem; retirar o segredo do arquivo atual não revoga essa credencial nem remove cópias anteriores.

Na publicação futura, primeiro fazer backup e preparar a configuração privada de produção com a nova credencial e `APP_URL` correto. Para o banco existente, usar **`database/infinityfree/ATUALIZAR_BANCO_ATUAL.sql`**, que aplica as mudanças das migrações 002/003 preservando inclusive os estados vazios do dump. A 001 representa a instalação de um banco novo e não deve ser reaplicada sobre o site atual. O usuário da aplicação não precisa de permissões de DDL. O novo mecanismo de hash invalida links de recuperação antigos.

Validar as regras `.htaccess` no servidor Apache da hospedagem antes de liberar o uso; o teste local foi feito com o roteador PHP, não com a configuração Apache de produção. Para outro servidor web, aplicar bloqueios equivalentes aos diretórios internos e à execução de scripts em uploads. A entrega não certifica envio de e-mail, HTTPS, configuração de servidor ou segurança integral do ambiente publicado.
