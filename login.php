<?php
require_once __DIR__ . '/backend/security.php';
$requestedProvider = isset($_GET['prestador']) && is_string($_GET['prestador'])
    ? (filter_var($_GET['prestador'], FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]) ?: 0) : 0;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entrar | ServiConnect</title>
    <link rel="stylesheet" href="css/login.css">
</head>
<body>
    <main class="auth-shell">
        <a href="index.php" class="auth-brand" aria-label="ServiConnect — página inicial">Servi<span>Connect</span></a>
        <form action="#" method="post" id="formLogin" novalidate>
            <?php echo csrf_field(); ?>
            <?php if ($requestedProvider): ?><input type="hidden" name="prestador" value="<?php echo $requestedProvider; ?>"><?php endif; ?>
            <h1>Bem-vindo de volta</h1>
            <p class="form-intro">Acesse com o CPF ou CNPJ cadastrado e sua senha.</p>
            <?php if ($requestedProvider): ?><p class="form-intro">Após entrar, você será levado ao perfil do prestador escolhido.</p><?php endif; ?>
            <div id="loginFeedback" class="form-feedback" role="alert" tabindex="-1" hidden></div>
            <div id="cadastroSucesso" class="form-feedback form-feedback-success" role="status" <?php if (($_GET['status'] ?? '') !== 'conta_criada') echo 'hidden'; ?>>Conta criada! Entre para completar seu perfil.</div>
            <div class="field">
                <label for="identificacao">CPF ou CNPJ</label>
                <input type="text" id="identificacao" name="identificacao" placeholder="Seu CPF ou CNPJ" autocomplete="username" autocapitalize="characters" spellcheck="false" required maxlength="18" aria-describedby="identificacaoAjuda">
                <p id="identificacaoAjuda" class="field-help">Use o mesmo documento informado no cadastro.</p>
            </div>
            <div class="field">
                <label for="senhaLogin">Senha</label>
                <input type="password" id="senhaLogin" name="senha" placeholder="Digite sua senha" autocomplete="current-password" required minlength="6" maxlength="72">
            </div>
            <p class="forgot-password"><a href="esqueci_senha.php">Esqueci minha senha</a></p>
            <button type="submit" id="submitButtonLogin">Entrar</button>
            <p class="account-link">Ainda não tem uma conta? <a href="cadastro_empresa.php">Criar conta</a></p>
        </form>
    </main>
    <script src="js/validacao.js?v=3"></script>
</body>
</html>
