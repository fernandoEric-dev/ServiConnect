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
    header('Location: dashboard.php?status=solicitacao_invalida');
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
    header('Location: dashboard.php?status=nao_autorizado');
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
        
        .orcamento-header h2::after {
            display: none; 
        }

        .orcamento-body {
            padding: 35px;
        }

        /* Seção de Detalhes do Pedido */
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

        /* Status Badge */
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 50px;
            font-size: 0.85em;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-aberta { background: #e0f2fe; color: #0284c7; }
        .status-orcamento { background: #dcfce7; color: #166534; }
        .status-recusada { background: #fee2e2; color: #991b1b; }

        /* Formulário de Ação */
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