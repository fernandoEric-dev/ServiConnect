# Entrada do aplicativo

O aplicativo Expo reutiliza o site HTTPS em uma WebView. Não há credencial MySQL no aplicativo, nova API pública ou alteração de esquema.

`app_entrada.php` aceita apenas destinos fixos: `orcamentos`, `perfil`, `conta`. Caso não haja sessão autenticada, guarda o destino na sessão e abre o login normal. Depois do login, `AuthController` renova a sessão e envia o destino interno validado na resposta existente. Se houver um prestador específico solicitado no login, esse destino continua tendo prioridade. Contas administrativas mantêm seu painel padrão.

Foram adicionadas verificações em `tests/accounts.php` para entrada protegida, retorno após login, preservação da sessão, bloqueio de URL externa e destino estruturado. A suíte usa o banco local e não cria pedidos de teste em produção.

A fonte do aplicativo está separada em `C:\Users\Fernando\Documents\ChatGPT\ServiConnect App`. O Snack original está preservado no ZIP baixado antes da edição.
