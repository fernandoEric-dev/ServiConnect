# Publicar as etapas 1 e 2 no InfinityFree

O pacote `ServiConnect_Etapa_2_Site_e_SQL.zip` não contém senhas nem contas de demonstração. A pasta `htdocs/` contém o código corrigido do site; `SQL/` contém a atualização incremental do banco para quem já importou a etapa 1.

1. Exporte um backup atualizado do banco e guarde uma cópia dos arquivos atuais da hospedagem.
2. No phpMyAdmin, selecione o banco atual e importe `SQL/ATUALIZAR_ETAPA_2_CADASTRO_PERFIS.sql` em UTF-8. Esse SQL preserva usuários, perfis, senhas e pedidos.
3. Na hospedagem, prepare `htdocs/backend/config.local.php` conforme o exemplo abaixo, com os dados reais do painel. Use a nova credencial após trocar a antiga que foi exposta no repositório público. Não use a configuração de desenvolvimento.
4. Envie o conteúdo de `htdocs/` do pacote para o `htdocs/` existente da hospedagem, mantendo as subpastas. Preserve as fotos já existentes e o arquivo privado `backend/config.local.php`. Inclua os arquivos `.htaccess` da raiz e de `foto/`.
5. Confira login antigo, cadastro CPF, cadastro MEI, edição de perfil e solicitação/resposta de orçamento. Faça também um teste de upload e recuperação de senha no ambiente publicado. Se houver erro de importação ou servidor, guarde a mensagem para diagnóstico.

Evite usar as ações administrativas durante o envio parcial dos arquivos, porque a versão anterior do arquivamento excluía perfis. Não é necessário apagar tabelas ou a pasta da hospedagem.

Exemplo de `htdocs/backend/config.local.php` (substituir todos os valores em maiúsculas):

```php
<?php
return [
    'environment' => 'production',
    'app_url' => 'https://SEU_DOMINIO',
    'db_host' => 'HOST_MYSQL_DO_PAINEL',
    'db_port' => 3306,
    'db_name' => 'NOME_COMPLETO_DO_BANCO_COM_PREFIXO',
    'db_user' => 'USUARIO_MYSQL_DO_PAINEL',
    'db_password' => 'NOVA_SENHA_DA_HOSPEDAGEM',
];
```

Importar o SQL sozinho não publica as correções de segurança. Este pacote foi testado localmente, mas não enviado automaticamente ao InfinityFree. As regras `.htaccess`, o HTTPS e o envio de e-mail precisam ser conferidos na hospedagem. O cadastro de MEI é autodeclarado; validar os dígitos do documento não certifica a situação cadastral ou o enquadramento do negócio.
