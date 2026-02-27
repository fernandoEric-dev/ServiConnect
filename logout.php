<?php
// Inicia a sessão para poder acessá-la
session_start();

// Limpa todas as variáveis da sessão (remove o ID do usuário, regras, etc.)
session_unset();

// Destrói a sessão completamente
session_destroy();

// Redireciona o usuário de volta para a página de login
header('Location: login.php');
exit;
?>