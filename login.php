<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ServiConnect</title>
    <link rel="stylesheet" href="css/login.css"> 
</head>
<body>
    <form action="#" method="post" id="formLogin">
        <h2>Acesso à Plataforma</h2>
        <p>Acesse com seu CNPJ e senha cadastrados.</p>

        <div>
            <label for="identificacao">CNPJ</label>
            <input type="text" id="identificacao" name="cnpj" placeholder="99.999.999/9999-99" required maxlength="18">
        </div>
        <div>
            <label for="senhaLogin">Senha</label>
            <input type="password" id="senhaLogin" name="senha" placeholder="Digite sua senha" required>
        </div>
        
        <button type="submit" id="submitButtonLogin" style="margin-top: 20px;">Entrar</button>

        <p class="cadastro-link" style="margin-top: 20px;">
            Ainda não tem cadastro? <a href="cadastro_empresa.php">Cadastre sua empresa aqui.</a>
        </p>
    </form>

    <script src="js/validacao.js"></script>
</body>
</html>