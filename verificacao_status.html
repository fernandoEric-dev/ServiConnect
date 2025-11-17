<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificação de Cadastro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            background-color: #f5f5f5;
            text-align: center;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        .status-icon {
            font-size: 3em;
            margin-bottom: 20px;
        }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .loading { color: #FFC400; }
        .btn-login {
            display: inline-block;
            padding: 10px 25px;
            margin-top: 20px;
            background-color: #0A192F;
            color: white;
            text-decoration: none;
            border-radius: 50px;
            transition: background-color 0.3s;
        }
        .btn-login:hover {
            background-color: #1a3a69;
        }
    </style>
</head>
<body>

    <div class="container">
        <div id="statusContent">
            <i class="fa-solid fa-sync fa-spin status-icon loading"></i>
            <h3>Verificando CNPJ... Por favor, aguarde.</h3>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const params = new URLSearchParams(window.location.search);
            const status = params.get('status');
            const mensagem = params.get('mensagem');
            const contentDiv = document.getElementById('statusContent');
            const decodedMensagem = mensagem ? decodeURIComponent(mensagem) : 'Erro desconhecido.';
            
            contentDiv.innerHTML = ''; // Limpa o conteúdo de "Aguarde"

            if (status === 'sucesso') {
                contentDiv.innerHTML = `
                    <i class="fa-solid fa-circle-check status-icon success"></i>
                    <h2>CNPJ Válido!</h2>
                    <p>Cadastro realizado com sucesso. ${decodedMensagem}</p>
                    <a href="login.html" class="btn-login">Retornar à Página de Login</a>
                `;
            } else if (status === 'erro') {
                contentDiv.innerHTML = `
                    <i class="fa-solid fa-circle-xmark status-icon error"></i>
                    <h2>Falha na Verificação</h2>
                    <p>${decodedMensagem}</p>
                    <a href="login.html" class="btn-login">Retornar à Página de Login</a>
                `;
            } else {
                 // Mantém o estado de carregamento se não houver status (ou um erro na URL)
                 contentDiv.innerHTML = '<i class="fa-solid fa-sync fa-spin status-icon loading"></i><h3>Verificando CNPJ... Por favor, aguarde.</h3>';
            }
        });
    </script>
</body>
</html>