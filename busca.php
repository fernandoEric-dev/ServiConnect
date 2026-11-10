<?php
require_once __DIR__ . '/backend/conexao.php';
require_once __DIR__ . '/backend/search.php';
$search = search_providers($pdo, search_filters($_GET));
if ($search['filters']['errors']) http_response_code(400);
$searchPath = 'busca.php';
$searchAnchor = '';
$searchMap = false;
$searchSignedIn = !empty($_SESSION['user_id']) && !empty($_SESSION['user_role']);
$searchContractor = false;
if ($searchSignedIn) {
    require_role($pdo);
    $searchContractor = $_SESSION['user_role'] === 'contratante';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Busque MEIs e empresas terceirizadas por serviço, cidade e região de atendimento na ServiConnect.">
    <title>Buscar serviços | ServiConnect</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/busca.css">
</head>
<body>
<header class="search-header"><div class="search-header-inner"><a class="search-brand" href="index.php" aria-label="ServiConnect — página inicial">Servi<span>Connect</span></a><nav aria-label="Acesso à plataforma"><?php if ($searchSignedIn): ?><a href="dashboard.php">Meu painel</a><?php else: ?><a href="login.php">Entrar</a><a href="cadastro_empresa.php" class="search-button">Criar conta</a><?php endif; ?></nav></div></header>
<main class="search-container">
    <a class="search-back" href="index.php">← Página inicial</a>
    <div class="search-intro"><p class="search-eyebrow">SERVIÇOS PARA SUA CASA OU EMPRESA</p><h1>Encontre quem faz o serviço</h1><p>Busque MEIs e empresas terceirizadas e confira onde atendem.</p></div>
    <?php require __DIR__ . '/backend/views/busca-filtros.php'; ?>
    <?php require __DIR__ . '/backend/views/busca-resultados.php'; ?>
</main>
<footer class="search-footer">ServiConnect · Conectando pessoas e empresas a prestadores de serviços.</footer>
</body>
</html>
