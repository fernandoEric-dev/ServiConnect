<?php
// solicitacao_orcamento.php
session_start();

// ... (Restante do código de verificação de segurança) ...

require_once 'backend/conexao.php';
require_once 'backend/models/UsuarioModel.php'; 

$contratante_id = $_SESSION['user_id'];
$terceirizada_id = $_GET['terceirizada_id'] ?? null;

if (!$terceirizada_id || !is_numeric($terceirizada_id)) {
    header('Location: dashboard.php');
    exit;
}

// Buscamos a coluna 'nome'
$stmt = $pdo->prepare("SELECT nome AS nome_empresa FROM empresas WHERE usuario_id = ?"); 
$stmt->execute([$terceirizada_id]);
$terceirizada_info = $stmt->fetch(PDO::FETCH_ASSOC);

$terceirizada_nome = $terceirizada_info['nome_empresa'] ?? "Empresa Não Encontrada"; 
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Solicitar Orçamento - ServiConnect</title>
    <link rel="stylesheet" href="css/style.css">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
    <style>
        body {
            background-color: var(--light-grey);
            font-family: 'Poppins', sans-serif;
        }
        
        /* Container principal do formulário (Formato de Cartão/Card) */
        .orcamento-container {
            max-width: 850px;
            margin: 60px auto;
            background: var(--white-color);
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1); /* Sombra mais bonita */
            overflow: hidden;
        }

        /* Cabeçalho do Card */
        .orcamento-header {
            background: var(--primary-blue);
            color: var(--white-color);
            padding: 30px;
            text-align: center;
        }
        
        .orcamento-header h2 {
            color: var(--secondary-yellow);
            margin-bottom: 10px;
            font-size: 2.2em;
        }
        
        .orcamento-header p {
            font-size: 1.1em;
            margin: 0;
            color: #d1d5db;
        }
        
        .orcamento-header p strong {
            color: var(--white-color);
            font-size: 1.2em;
        }

        /* Corpo do Card */
        .orcamento-body {
            padding: 40px;
        }

        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--primary-blue);
            font-size: 0.95em;
        }

        .form-group label i {
            color: var(--secondary-yellow); /* Destaque amarelo nos ícones */
            margin-right: 5px;
        }

        /* Estilização dos Inputs e Selects */
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-family: 'Poppins', sans-serif;
            font-size: 1em;
            transition: all 0.3s ease;
            box-sizing: border-box;
            background-color: #f9fafa;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--secondary-yellow);
            background-color: var(--white-color);
            box-shadow: 0 0 0 3px rgba(255, 196, 0, 0.2); /* Efeito de brilho ao focar */
        }

        /* Ajuste do ícone na caixa de seleção */
        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%230a192f' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 15px center;
            background-size: 15px;
            cursor: pointer;
        }

        /* Botão de Enviar */
        .btn-submit {
            width: 100%;
            padding: 15px;
            background: var(--primary-blue);
            color: var(--white-color);
            border: none;
            border-radius: 8px;
            font-size: 1.1em;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 15px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .btn-submit:hover {
            background: var(--secondary-yellow);
            color: var(--primary-blue);
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(255, 196, 0, 0.4);
        }

        /* Botão de Voltar */
        .btn-back {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: var(--primary-blue);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .btn-back:hover {
            color: var(--secondary-yellow);
        }

        /* Responsividade para celular */
        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
                gap: 0;
                margin-bottom: 0;
            }
            .form-group {
                margin-bottom: 20px;
            }
            .orcamento-body {
                padding: 25px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="orcamento-container">
            
            <div class="orcamento-header">
                <h2>Solicitar Orçamento</h2>
                <p>Enviando solicitação para:<br><strong><?php echo htmlspecialchars($terceirizada_nome); ?></strong></p>
            </div>
            
            <div class="orcamento-body">
                <form action="backend/controllers/SolicitacaoController.php" method="POST" id="formSolicitacao">
                    
                    <input type="hidden" name="contratante_id" value="<?php echo $contratante_id; ?>">
                    <input type="hidden" name="terceirizada_id" value="<?php echo $terceirizada_id; ?>">
                    
                    <input type="hidden" name="descricao_servico" id="descricao_servico_real">

                    <div class="form-row">
                        <div class="form-group">
                            <label for="numero_funcionarios"><i class="fa-solid fa-users"></i> Quantidade de Pessoas:</label>
                            <input type="number" id="numero_funcionarios" name="numero_funcionarios" class="form-control" min="1" placeholder="Ex: 6 funcionários" required>
                        </div>
                        <div class="form-group">
                            <label for="area_servico_solicitada"><i class="fa-solid fa-briefcase"></i> Área Solicitada:</label>
                            <input type="text" id="area_servico_solicitada" name="area_servico_solicitada" class="form-control" placeholder="Ex: Limpeza, Segurança, TI" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="frequencia"><i class="fa-solid fa-calendar-days"></i> Frequência do Serviço:</label>
                            <select id="frequencia" class="form-control" required>
                                <option value="" disabled selected>Selecione uma opção...</option>
                                <option value="Serviço Único (1 dia)">Serviço Único (1 dia)</option>
                                <option value="Diário">Diário (Todos os dias)</option>
                                <option value="Semanal">Semanal (Toda semana)</option>
                                <option value="Quinzenal">Quinzenal (A cada 15 dias)</option>
                                <option value="Mensal">Mensal (1 vez ao mês)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="localizacao_servico"><i class="fa-solid fa-map-location-dot"></i> Localização / CEP:</label>
                            <input type="text" id="localizacao_servico" name="localizacao_servico" class="form-control" placeholder="Endereço onde será executado" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="detalhes_opcionais"><i class="fa-solid fa-circle-info"></i> Detalhes Adicionais (Opcional):</label>
                        <textarea id="detalhes_opcionais" class="form-control" rows="3" placeholder="Ex: Preciso que os produtos de limpeza estejam inclusos, será no período noturno, etc..."></textarea>
                    </div>
                    
                    <button type="submit" class="btn-submit">
                        <i class="fa-solid fa-paper-plane"></i> Enviar Pedido de Orçamento
                    </button>
                    
                    <a href="dashboard.php" class="btn-back"><i class="fa-solid fa-arrow-left"></i> Voltar ao Feed</a>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('formSolicitacao').addEventListener('submit', function(e) {
            // Pega o valor do dropdown e o que a pessoa digitou na caixa de texto
            const frequencia = document.getElementById('frequencia').value;
            const detalhes = document.getElementById('detalhes_opcionais').value;
            
            // Pega o input escondido que o backend do PHP está esperando
            const campoReal = document.getElementById('descricao_servico_real');
            
            // Monta o texto bonitinho
            let textoFinal = "Frequência: " + frequencia;
            if (detalhes.trim() !== "") {
                textoFinal += " | Detalhes: " + detalhes;
            }
            
            // Atribui o texto final ao input escondido
            campoReal.value = textoFinal;
        });
    </script>
</body>
</html>