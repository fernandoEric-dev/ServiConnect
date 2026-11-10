<?php require_once __DIR__ . '/backend/security.php'; ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crie sua conta | ServiConnect</title>
    <link rel="stylesheet" href="css/app.css?v=4"><link rel="stylesheet" href="css/cadastro_empresa.css?v=5">
</head>
<body>
    <main class="auth-shell">
        <a href="index.php" class="auth-brand" aria-label="ServiConnect — página inicial">Servi<span>Connect</span></a>
        <form action="#" method="post" id="formCadastroEmpresa" novalidate>
            <?php echo csrf_field(); ?>
            <h1>Crie sua conta</h1>
            <p class="form-intro">Encontre quem faz o serviço ou apresente o que seu negócio oferece.</p>
            <div id="cadastroFeedback" class="form-feedback" role="alert" tabindex="-1" hidden></div>

            <fieldset>
                <legend>Como você quer usar a plataforma?</legend>
                <div class="field">
                    <label for="tipo">Seu objetivo</label>
                    <select id="tipo" name="tipo" required aria-describedby="tipoAjuda">
                        <option value="contratante">Quero contratar serviços</option>
                        <option value="mei"<?php if (($_GET['perfil'] ?? '')==='mei') echo ' selected'; ?>>Quero oferecer serviços como MEI</option>
                        <option value="terceirizada"<?php if (($_GET['perfil'] ?? '')==='terceirizada') echo ' selected'; ?>>Quero oferecer serviços como empresa terceirizada</option>
                    </select>
                    <p id="tipoAjuda" class="field-help">Busque prestadores e solicite orçamentos para o serviço de que precisa.</p>
                </div>
                <div class="field" id="tipoPessoaGrupo">
                    <label for="tipoPessoa">Você vai contratar como</label>
                    <select id="tipoPessoa" name="tipo_pessoa" required>
                        <option value="fisica">Pessoa física — CPF</option>
                        <option value="juridica">Empresa — CNPJ</option>
                    </select>
                </div>
            </fieldset>

            <fieldset>
                <legend>Seus dados</legend>
                <div class="field">
                    <label for="nome" id="nomeLabel">Nome completo</label>
                    <input type="text" id="nome" name="nome" placeholder="Como você quer ser identificado" autocomplete="name" required maxlength="150">
                </div>
                <div class="field">
                    <label for="documento" id="documentoLabel">CPF</label>
                    <input type="text" id="documento" name="documento" placeholder="000.000.000-00" inputmode="numeric" autocapitalize="characters" autocomplete="off" spellcheck="false" required maxlength="14" aria-describedby="documentoAjuda">
                    <p class="field-help" id="documentoAjuda">Use seu CPF para criar uma conta de pessoa física.</p>
                    <div id="cnpjConsultaGrupo" hidden>
                        <button type="button" id="consultarCnpj" class="cnpj-check">Consultar CNPJ</button>
                        <p id="cnpjConsultaResultado" class="field-help" role="status" aria-live="polite" aria-atomic="true" hidden></p>
                    </div>
                </div>
            </fieldset>

            <fieldset>
                <legend>Dados de acesso</legend>
                <div class="field">
                    <label for="emailAcesso">E-mail</label>
                    <input type="email" id="emailAcesso" name="email_acesso" placeholder="voce@exemplo.com" autocomplete="email" required maxlength="100">
                </div>
                <div class="field">
                    <label for="senha">Senha</label>
                    <input type="password" id="senha" name="senha" placeholder="Crie sua senha" autocomplete="new-password" required minlength="6" maxlength="72" aria-describedby="senhaAjuda">
                    <p class="field-help" id="senhaAjuda">Use pelo menos 6 caracteres. Prefira uma senha que você não usa em outros sites.</p>
                </div>
                <div class="field">
                    <label for="confirmarSenha">Confirmar senha</label>
                    <input type="password" id="confirmarSenha" name="confirmar_senha" placeholder="Digite a senha novamente" autocomplete="new-password" required minlength="6" maxlength="72">
                </div>
            </fieldset>

            <button type="submit" id="submitCadastro">Criar conta</button>
            <p class="account-link">Já tem uma conta? <a href="login.php">Entrar</a></p>
        </form>
    </main>
    <script src="js/validacao.js?v=5"></script>
</body>
</html>
