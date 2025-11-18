<?php
// solicitacao_orcamento.php
session_start();

// ... (Restante do código de verificação de segurança) ...

require_once 'backend/conexao.php';
require_once 'backend/models/UsuarioModel.php'; 
// Requer o Model porque estamos buscando o nome da empresa Terceirizada aqui

$contratante_id = $_SESSION['user_id'];
$terceirizada_id = $_GET['terceirizada_id'] ?? null;

if (!$terceirizada_id || !is_numeric($terceirizada_id)) {
    header('Location: dashboard.php');
    exit;
}

// 🔑 CORREÇÃO CRÍTICA AQUI: Buscamos a coluna 'nome'
$stmt = $pdo->prepare("SELECT nome AS nome_empresa FROM empresas WHERE usuario_id = ?"); 
$stmt->execute([$terceirizada_id]);
$terceirizada_info = $stmt->fetch(PDO::FETCH_ASSOC);

// Usamos um ALIAS (AS nome_empresa) para que o restante do código PHP/HTML (que usa $terceirizada_nome)
// não precise ser reescrito, mantendo o nome como 'nome_empresa' para exibição.

$terceirizada_nome = $terceirizada_info['nome_empresa'] ?? "Empresa Não Encontrada"; 

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Solicitar Orçamento</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css"> 
</head>
<body>
    <div class="dashboard-box" style="margin-top: 50px;">
        <h2 class="section-title">Enviar Solicitação de Orçamento</h2>
        <p>Seu pedido será enviado diretamente para: <strong><?php echo htmlspecialchars($terceirizada_nome); ?></strong></p>
        
        <form action="backend/controllers/SolicitacaoController.php" method="POST" id="formSolicitacao" class="widget-card">
            
            <input type="hidden" name="contratante_id" value="<?php echo $contratante_id; ?>">
            <input type="hidden" name="terceirizada_id" value="<?php echo $terceirizada_id; ?>">

            <div style="display: flex; gap: 20px;">
                <div style="flex: 1;">
                    <label for="numero_funcionarios">Funcionários Necessários:</label>
                    <input type="number" id="numero_funcionarios" name="numero_funcionarios" min="1" placeholder="Ex: 6" required>
                </div>
                <div style="flex: 1;">
                    <label for="area_servico_solicitada">Área Principal Solicitada:</label>
                    <input type="text" id="area_servico_solicitada" name="area_servico_solicitada" placeholder="Ex: Limpeza, Segurança, TI" required>
                </div>
            </div>

            <div>
                <label for="descricao_servico">Detalhes Adicionais do Serviço:</label>
                <textarea id="descricao_servico" name="descricao_servico" rows="4" placeholder="Descreva a frequência (diária, semanal) e a duração estimada do serviço."></textarea>
            </div>

            <div>
                <label for="localizacao_servico">Localização Onde o Serviço Será Executado:</label>
                <input type="text" id="localizacao_servico" name="localizacao_servico" placeholder="Endereço, CEP ou Ponto de Referência" required>
            </div>
            
            <button type="submit" class="btn-primary search-btn" style="margin-top: 20px;">
                <i class="fa-solid fa-paper-plane"></i> Enviar Solicitação de Orçamento
            </button>
            
            <div style="margin-top: 15px; text-align: center;">
                <a href="dashboard.php" class="btn-link">Voltar ao Feed de Empresas</a>
            </div>
        </form>
    </div>
</body>
</html>