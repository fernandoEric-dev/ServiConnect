# Importação no InfinityFree

## Etapa 2 — se a atualização de segurança já foi importada

Importar **`ATUALIZAR_ETAPA_2_CADASTRO_PERFIS.sql`** no banco existente, após exportar um backup. Acrescenta contratante CPF/CNPJ e prestador MEI, preservando contas e vínculos. Também é necessário enviar o código atualizado; os detalhes estão em `docs/ETAPA_2.md`. O pacote `ServiConnect_Etapa_2_Site_e_SQL.zip` reúne código, SQL incremental e instruções, sem credenciais nem dados de demonstração.

Os arquivos consolidados abaixo já incluem esta segunda etapa. Quem importou a versão anterior pode usar somente o SQL incremental acima.

## Para atualizar o banco atual — opção indicada

Arquivo: `ATUALIZAR_BANCO_ATUAL.sql`.

Foi preparado a partir da estrutura do SQL fornecido. Não apaga tabelas, contas, perfis, senhas ou pedidos; acrescenta os campos, estados e tabelas necessários às correções. Pode ser reaplicado sem duplicar colunas, índices ou tabelas.

1. No painel do InfinityFree, abra o phpMyAdmin do banco atual.
2. Use **Exportar**, em formato SQL, para salvar um backup atualizado.
3. Confira o banco selecionado. Use **Importar**, selecione `ATUALIZAR_BANCO_ATUAL.sql`, mantenha UTF-8 e execute.
4. Confira o resultado antes de prosseguir. Havendo erro, pare e guarde a mensagem; não exclua tabelas para tentar resolver.

O dump fornecido contém dois pedidos com status vazio. O arquivo preserva esses registros e o valor vazio, assim como eventuais valores NULL. Esses pedidos precisam de revisão do histórico para definir o estado correto. Não é possível deduzir com segurança a decisão original somente desse campo.

Não execute a antiga migração 002 isoladamente nesse dump: a conversão estrita dos estados vazios pode falhar. O arquivo de atualização já faz a conversão compatível e inclui as mudanças das migrações 002 e 003.

## Para criar um banco novo, sem dados

Arquivo: `BANCO_NOVO_VAZIO.sql`.

Crie o banco pelo painel do InfinityFree e abra seu phpMyAdmin. Selecione o banco **vazio** e importe esse arquivo em UTF-8. Ele cria a estrutura completa, sem dados reais ou contas de demonstração. Não fornece um administrador ou senha padrão. As contas antigas não aparecem nesse banco.

Esse arquivo não deve ser importado sobre as tabelas existentes. Para manter os dados do site, use o arquivo de atualização acima. Os dois arquivos foram preparados sem CREATE DATABASE, USE, CREATE USER ou GRANT; usam o banco selecionado no phpMyAdmin.

## Código e conexão

Importar o SQL atualiza apenas o banco. As correções de segurança também dependem dos arquivos PHP, JavaScript e `.htaccess` preparados no projeto, que ainda não foram publicados automaticamente.

Para publicar o código corrigido, configure no servidor `htdocs/backend/config.local.php` com os dados do InfinityFree. Esse arquivo é privado, não deve entrar no Git e é excluído do deploy automático. Não envie a configuração de desenvolvimento com `127.0.0.1:3307`.

Exemplo de conteúdo, substituindo os campos pelos valores do painel:

```php
<?php
return [
    'environment' => 'production',
    'app_url' => 'https://SEU_DOMINIO',
    'db_host' => 'HOST_MYSQL_DO_PAINEL',
    'db_port' => 3306,
    'db_name' => 'NOME_COMPLETO_DO_BANCO_COM_PREFIXO',
    'db_user' => 'USUARIO_MYSQL_DO_PAINEL',
    'db_password' => 'NOVA_SENHA_CONFIGURADA_NA_HOSPEDAGEM',
];
```

Troque a credencial antiga que esteve no repositório público. Na publicação, confirme a aplicação das regras `.htaccess`, login, upload e envio/resposta de orçamento. A nova recuperação de senha invalida links antigos; o envio real de e-mail depende da hospedagem. Evite operações de alteração de contas durante a troca do código antigo pelo novo, pois o arquivamento antigo apaga registros.

Os arquivos SQL devem ser importados pelo phpMyAdmin; não precisam ser colocados em `htdocs`.

## Verificação dos arquivos

Ambos foram importados em bancos temporários separados no MariaDB local. A atualização preservou os campos e registros originais de contas, perfis e pedidos fictícios, incluindo status vazio e NULL, e foi reaplicada sem duplicar estruturas. A instalação vazia criou as sete tabelas sem dados pessoais ou contas padrão. Esses testes foram locais; a importação no InfinityFree ainda não foi executada.

O teste reproduzível está em `tests/database-exports.php` e usa exclusivamente o servidor separado de desenvolvimento. O arquivo de atualização foi preparado para a estrutura do SQL fornecido; mudanças posteriores nas tabelas devem ser conferidas antes da importação.

Referências oficiais:
- https://forum.infinityfree.com/t/how-to-import-a-mysql-database-backup/49341
- https://forum.infinityfree.com/t/how-to-migrate-your-website-to-infinityfree/49315/1
