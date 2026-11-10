<?php
require_once __DIR__ . '/backend/conexao.php';
require_once __DIR__ . '/backend/search.php';
require_once __DIR__ . '/backend/app.php';
$search = search_providers($pdo, search_filters($_GET), true);
if ($search['filters']['errors']) http_response_code(400);
$searchPath = 'busca.php';
$searchAnchor = '';
$searchMap = false;
$searchShowMap = true;
$activeNav = 'busca';
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
    <link rel="stylesheet" href="css/app.css?v=4">
    <link rel="stylesheet" href="css/busca.css?v=4">
    <link rel="stylesheet" href="vendor/leaflet/leaflet.css">
    <script src="vendor/leaflet/leaflet.js" defer></script><script src="js/maps.js?v=4" defer></script>
</head>
<body>
<?php require __DIR__ . '/backend/views/app-header.php'; ?>
<main class="search-container">
    <a class="search-back" href="index.php">← Página inicial</a>
    <div class="search-intro"><p class="search-eyebrow">SERVIÇOS PARA SUA CASA OU EMPRESA</p><h1>Encontre quem faz o serviço</h1><p>Busque MEIs e empresas terceirizadas e confira onde atendem.</p></div>
    <?php require __DIR__ . '/backend/views/busca-filtros.php'; ?>
    <?php require __DIR__ . '/backend/views/mapa-prestadores.php'; ?>
    <?php require __DIR__ . '/backend/views/busca-resultados.php'; ?>
</main>
<footer class="search-footer">ServiConnect · Conectando pessoas e empresas a prestadores de serviços.</footer>
</body>
</html>
