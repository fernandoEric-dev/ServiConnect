<?php
// responder_orcamento.php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'terceirizada') {
    header('Location: login.php');
    exit;
}

$terceirizada_id = $_SESSION['user_id'];
$solicitacao_id = $_GET['id'] ?? null;

if (!$solicitacao_id || !is_numeric($solicitacao_id)) {
    header('Location: dashboard_tercerizada.php?status=solicitacao_invalida');
    exit;
}

require_once 'backend/conexao.php';

// Busca os detalhes da solicitação
$sql = "
    SELECT 
        s.*, 
        e.nome AS nome_contratante,
        u.email AS email_contratante
    FROM solicitacoes s
    JOIN empresas e ON s.contratante_id = e.usuario_id
    JOIN usuarios u ON s.contratante_id = u.id
    WHERE s.id = ? AND s.terceirizada_id = ?
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$solicitacao_id, $terceirizada_id]);
$solicitacao = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$solicitacao) {
    header('Location: dashboard_tercerizada.php?status=nao_autorizado');
    exit;
}

// Verifica se já foi respondida
$status_respondida = ($solicitacao['status'] !== 'aberta');
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Responder Orçamento #<?php echo htmlspecialchars($solicitacao_id); ?> - ServiConnect</title>
    
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
    <style>
        body {
            background-color: var(--light-grey);
            font-family: 'Poppins', sans-serif;
        }
        
        .orcamento-container {
            max-width: 800px;
            margin: 50px auto;
            background: var(--white-color);
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .orcamento-header {
            background: var(--primary-blue);
            color: var(--white-color);
            padding: 25px 30px;
            text-align: center;
            border-bottom: 4px solid var(--secondary-yellow);
        }
        
        .orcamento-header h2 {
            color: var(--white-color);
            margin: 0;
            font-size: 1.8em;
            padding-bottom: 0;
        }

        .orcamento-body {
            padding: 35px;
        }

        .detalhes-pedido {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 30px;
        }

        .detalhes-pedido h3 {
            margin-top: 0;
            color: var(--primary-blue);
            font-size: 1.2em;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .detalhe-item {
            display: flex;
            margin-bottom: 15px;
            align-items: flex-start;
        }

        .detalhe-item i {
            color: var(--secondary-yellow);
            width: 25px;
            font-size: 1.1em;
            margin-top: 4px;
        }

        .detalhe-conteudo {
            flex: 1;
        }

        .detalhe-conteudo strong {
            color: var(--primary-blue);
            display: block;
            font-size: 0.9em;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 3px;
        }

        .detalhe-conteudo span {
            color: var(--text-color);
            font-size: 1.05em;
        }

        .acao-form {
            background: var(--white-color);
            padding: 20px 0 0 0;
            border-top: 1px dashed #cbd5e1;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            font-weight: 600;
            color: var(--primary-blue);
            display: block;
            margin-bottom: 8px;
        }

        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 1em;
        }

        .btn {
            padding: 12px 20px;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            width: 100%;
            margin-bottom: 10px;
        }

        .btn-success { background: #28a745; color: #fff; }
        .btn-danger { background: #dc3545; color: #fff; }
        
        .btn-back {
            display: inline-block;
            margin-top: 15px;
            color: var(--primary-blue);
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="orcamento-container">
        <div class="orcamento-header">
            <h2>Detalhes da Solicitação #<?php echo $solicitacao['id']; ?></h2>
        </div>
        
        <div class="orcamento-body">
            <div class="detalhes-pedido">
                <h3>Informações do Pedido</h3>
                <div class="detalhe-item">
                    <i class="fa-solid fa-building"></i>
                    <div class="detalhe-conteudo">
                        <strong>Contratante</strong>
                        <span><?php echo htmlspecialchars($solicitacao['nome_contratante']); ?></span>
                    </div>
                </div>
                <div class="detalhe-item">
                    <i class="fa-solid fa-briefcase"></i>
                    <div class="detalhe-conteudo">
                        <strong>Serviço</strong>
                        <span><?php echo htmlspecialchars($solicitacao['area_servico_solicitada']); ?> (<?php echo htmlspecialchars($solicitacao['numero_funcionarios']); ?> Funcionários)</span>
                    </div>
                </div>
                <div class="detalhe-item">
                    <i class="fa-solid fa-location-dot"></i>
                    <div class="detalhe-conteudo">
                        <strong>Localização</strong>
                        <span><?php echo htmlspecialchars($solicitacao['localizacao_servico']); ?></span>
                    </div>
                </div>
                <div class="detalhe-item">
                    <i class="fa-solid fa-align-left"></i>
                    <div class="detalhe-conteudo">
                        <strong>Descrição Completa</strong>
                        <span><?php echo nl2br(htmlspecialchars($solicitacao['descricao_servico'])); ?></span>
                    </div>
                </div>
            </div>

            <?php if (!$status_respondida): ?>
                <div class="acao-form">
                    <form action="backend/controllers/RespostaOrcamentoController.php" method="POST">
                        <input type="hidden" name="solicitacao_id" value="<?php echo $solicitacao['id']; ?>">
                        
                        <div class="form-group">
                            <label for="valor_orcamento">Valor Estimado (R$):</label>
                            <input type="number" step="0.01" name="valor_orcamento" id="valor_orcamento" class="form-control" placeholder="0.00">
                        </div>

                        <button type="submit" name="action" value="orcamento" class="btn btn-success">
                            <i class="fa-solid fa-check"></i> Enviar Orçamento
                        </button>
                        
                        <button type="submit" name="action" value="recusa" class="btn btn-danger" onclick="return confirm('Tem certeza que deseja recusar este pedido?');">
                            <i class="fa-solid fa-xmark"></i> Recusar Solicitação
                        </button>
                    </form>
                </div>
            <?php else: ?>
                <div style="text-align: center; color: #555;">
                    <h3>Esta solicitação já foi respondida (Status: <?php echo htmlspecialchars($solicitacao['status']); ?>)</h3>
                </div>
            <?php endif; ?>

            <div style="text-align: center;">
                <a href="dashboard_tercerizada.php" class="btn-back"><i class="fa-solid fa-arrow-left"></i> Voltar ao Dashboard</a>
            </div>
        </div>
    </div>
</body>
</html>