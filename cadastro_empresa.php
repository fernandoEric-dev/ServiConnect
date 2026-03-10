<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro Rápido de Empresa</title>
    <link rel="stylesheet" href="css/cadastro_empresa.css">
</head>
<body>
    
    <form action="#" method="post" id="formCadastroEmpresa">

        <h2>Crie sua Conta</h2>
        <p>Cadastro rápido. Você poderá preencher o perfil da sua empresa depois.</p>

        <div>
            <label for="cnpj">CNPJ</label>
            <input type="text" id="cnpj" name="cnpj" placeholder="99.999.999/9999-99" required maxlength="18">
        </div>

        <div>
            <label for="tipo">Tipo de Conta</label>
            <select id="tipo" name="tipo" required>
                <option value="" disabled selected>Selecione seu objetivo</option>
                <option value="terceirizada">Quero Prestar Serviços (Terceirizada)</option>
                <option value="contratante">Quero Contratar Serviços (Contratante)</option>
            </select>
        </div>

        <h3 style="margin-top: 30px;">Dados de Acesso</h3>
        <div>
            <label for="email_acesso">Email para Login</label>
            <input type="email" id="emailAcesso" name="email_acesso" placeholder="email@empresa.com" required>
        </div>
        <div>
            <label for="senha">Senha</label>
            <input type="password" id="senha" name="senha" placeholder="Crie uma senha (mín. 6 caracteres)" required>
        </div>
        <div>
            <label for="confirmar_senha">Confirmar Senha</label>
            <input type="password" id="confirmarSenha" name="confirmar_senha" placeholder="Confirme a senha" required>
        </div>

        <button type="submit" style="margin-top: 20px;">Criar Conta e Continuar</button>
    </form>

    <script src="js/validacao.js"></script> 
</body>
</html>