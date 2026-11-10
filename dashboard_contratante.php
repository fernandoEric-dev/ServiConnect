<?php
require_once __DIR__ . '/backend/conexao.php';
require_role($pdo,['contratante']);
require_once __DIR__ . '/backend/app.php';
require_once __DIR__ . '/backend/profile.php';
$stmt=$pdo->prepare('SELECT u.email,u.tipo_pessoa,e.* FROM usuarios u JOIN empresas e ON e.usuario_id=u.id WHERE u.id=?');$stmt->execute([$_SESSION['user_id']]);$profile=$stmt->fetch();
if(!$profile)abort_request(404,'Perfil indisponível.');
$search=search_providers($pdo,search_filters($_GET),true);if($search['filters']['errors'])http_response_code(400);
$searchPath='dashboard_contratante.php';$searchAnchor='';$searchMap=false;$searchShowMap=true;$searchSignedIn=true;$searchContractor=true;
$listing=order_list($pdo,(int)$_SESSION['user_id'],'contratante',[]);
$activeNav='busca';
?>
<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Área do contratante | ServiConnect</title><link rel="stylesheet" href="css/app.css?v=4"><link rel="stylesheet" href="css/busca.css?v=4"><link rel="stylesheet" href="vendor/leaflet/leaflet.css"><script src="vendor/leaflet/leaflet.js" defer></script><script src="js/maps.js?v=4" defer></script><script src="js/profile-editor.js?v=4" defer></script><script src="js/app.js?v=4" defer></script></head><body data-default-panel="busca">
<?php require __DIR__ . '/backend/views/app-header.php'; ?>
<main class="app-container"><section id="busca" data-panel><div class="page-intro"><p class="eyebrow">PARA SUA CASA OU EMPRESA</p><h1>Encontre o serviço que você precisa</h1><p>Compare os serviços e regiões de atendimento, confira os perfis e solicite um orçamento.</p></div>
<?php require __DIR__ . '/backend/views/busca-filtros.php'; ?>
<?php require __DIR__ . '/backend/views/mapa-prestadores.php'; ?>
<?php require __DIR__ . '/backend/views/busca-resultados.php'; ?>
</section>
<section id="pedidos" data-panel><div class="page-intro"><h1>Meus orçamentos</h1><p>Todos os pedidos agora têm uma área com proposta, mensagens e histórico.</p></div><a class="button" href="orcamentos.php">Abrir meus orçamentos</a></section>
<section id="perfil" data-panel><div class="page-intro"><p class="eyebrow">DADOS DO CONTRATANTE</p><p>Mantenha seu contato e endereço atualizados para facilitar as solicitações.</p></div><?php require __DIR__ . '/backend/views/perfil-editor.php'; ?></section>
</main><footer class="app-footer">ServiConnect · Serviços para pessoas e empresas.</footer></body></html>
