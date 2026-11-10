<?php
$loggedIn = !empty($_SESSION['user_id']) && !empty($_SESSION['user_role']);
$appRole = $_SESSION['user_role'] ?? '';
$homePanel = $appRole === 'contratante' ? 'dashboard_contratante.php' : 'dashboard_tercerizada.php';
?>
<header class="app-header"><div class="app-header-inner">
<a class="app-brand" href="index.php" aria-label="ServiConnect — página inicial"><img src="foto/logo.jpg" width="40" height="48" alt=""><span>Servi<span class="brand-accent">Connect</span></span></a>
<nav aria-label="Navegação principal" class="app-nav">
<a href="busca.php"<?php if (($activeNav ?? '') === 'busca') echo ' aria-current="page"'; ?>>Buscar serviços</a>
<?php if ($loggedIn && in_array($appRole, ['contratante','prestador'], true)): ?>
<a href="conversas.php"<?php if (($activeNav ?? '') === 'conversas') echo ' aria-current="page"'; ?>>Conversas</a>
<a href="orcamentos.php"<?php if (($activeNav ?? '') === 'orcamentos') echo ' aria-current="page"'; ?>>Meus orçamentos</a>
<a href="<?php echo $homePanel; ?>#perfil"<?php if (($activeNav ?? '') === 'perfil') echo ' aria-current="page"'; ?>>Meu perfil</a>
<form action="logout.php" method="post"><?php echo csrf_field(); ?><button class="button-quiet" type="submit">Sair</button></form>
<?php elseif ($loggedIn): ?><a href="dashboard.php">Meu painel</a>
<?php else: ?><a href="login.php">Entrar</a><a href="cadastro_empresa.php" class="button">Criar conta</a><?php endif; ?>
</nav></div></header>
