<?php
// backend/controllers/PerfilTerceirizadaController.php

session_start();
header('Content-Type: application/json');

// ⚠️ SEGURANÇA: Verifica se a sessão é válida
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'terceirizada') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
    exit;
}

// ⚠️ Inclusão da Conexão
require_once '../conexao.php'; 

// O FORMULÁRIO ESTÁ ENVIANDO POR POST COM ENCTYPE="MULTIPART/FORM-DATA"
// Usamos $_POST para campos de texto e $_FILES para arquivos.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$mensagem = '';
$success = false;

try {
    // 1. TRATAMENTO DO UPLOAD DA FOTO
    $caminho_foto = null;
    $caminho_atual = $_POST['foto_atual'] ?? ''; // Campo oculto para saber a foto atual

    if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
        $arquivo = $_FILES['foto_perfil'];
        $extensao = pathinfo($arquivo['name'], PATHINFO_EXTENSION);
        
        // Define o novo nome do arquivo: ID do usuário + timestamp
        $novo_nome = $user_id . '_' . time() . '.' . $extensao;
        $diretorio_destino = '../../img/profiles/'; // ⚠️ Ajuste o caminho conforme sua estrutura
        
        if (!is_dir($diretorio_destino)) {
            mkdir($diretorio_destino, 0777, true);
        }

        $caminho_foto = $diretorio_destino . $novo_nome;
        
        if (!move_uploaded_file($arquivo['tmp_name'], $caminho_foto)) {
            throw new Exception("Falha ao mover o arquivo de upload.");
        }
        
        // Se a foto anterior não for a default, exclui a antiga
        // if ($caminho_atual && $caminho_atual !== 'img/default_avatar.png' && file_exists($caminho_atual)) {
        //     unlink($caminho_atual); 
        // }
        
        // Caminho relativo para salvar no banco
        $caminho_foto = 'img/profiles/' . $novo_nome;

    } else {
        // Se não houve novo upload, mantém o caminho atual (se existir)
        $caminho_foto = $caminho_atual; 
    }

    // 2. COLETA E TRATAMENTO DOS DADOS DO FORMULÁRIO
    $dados_form = [
        'foto_path' => $caminho_foto,
        'texto_perfil' => $_POST['texto_perfil'] ?? '',
        'area_atuacao' => $_POST['area_atuacao'] ?? '',
        'num_funcionarios' => (int)($_POST['num_funcionarios'] ?? 0),
        'regiao' => $_POST['regiao'] ?? '',
        'horario' => $_POST['horario'] ?? '',
        
        'cep' => preg_replace('/\D/', '', $_POST['cep_empresa'] ?? ''),
        'logradouro' => $_POST['logradouro_empresa'] ?? '',
        'numero' => $_POST['numero_empresa'] ?? '',
        'complemento' => $_POST['complemento_empresa'] ?? '',
        'bairro' => $_POST['bairro_empresa'] ?? '',
        'cidade' => $_POST['cidade_empresa'] ?? '',
        'estado' => $_POST['estado_empresa'] ?? '',
        'usuario_id' => $user_id,
    ];

    // 3. ATUALIZAÇÃO NO BANCO DE DADOS (Tabela empresas)
    $sql_update = "
        UPDATE empresas SET
            foto_path = :foto_path,
            descricao = :texto_perfil,
            area_atuacao = :area_atuacao,
            num_funcionarios = :num_funcionarios,
            regiao = :regiao,
            horario = :horario,
            cep = :cep,
            logradouro = :logradouro,
            numero = :numero,
            complemento = :complemento,
            bairro = :bairro,
            cidade = :cidade,
            estado = :estado
        WHERE usuario_id = :usuario_id
    ";

    $stmt = $pdo->prepare($sql_update);
    $stmt->execute($dados_form);

    $mensagem = "Perfil atualizado e publicado com sucesso!";
    $success = true;

} catch (Exception $e) {
    // Erro de exceção (DB, upload de arquivo, etc.)
    $mensagem = "Erro ao salvar o perfil: " . $e->getMessage();
    http_response_code(500); 
}

// 4. RETORNO DE SUCESSO OU ERRO PARA O AJAX (Se você usar AJAX)
// Como o form está com action="controller", o ideal é redirecionar, mas 
// se for usar JS, este é o retorno:
if ($success) {
    echo json_encode(['success' => true, 'message' => $mensagem]);
} else {
    echo json_encode(['success' => false, 'message' => $mensagem]);
}
?>