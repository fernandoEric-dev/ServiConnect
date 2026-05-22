<?php
session_start();

// Verifica se a sessão é válida
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'terceirizada') {
    header('Location: ../../login.php');
    exit;
}

require_once '../conexao.php'; 

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../dashboard_terceirizada.php');
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // 1. TRATAMENTO DO UPLOAD DA FOTO
    $caminho_foto = $_POST['foto_atual'] ?? '';

    if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
        $arquivo = $_FILES['foto_perfil'];
        $extensao = pathinfo($arquivo['name'], PATHINFO_EXTENSION);
        
        $novo_nome = 'terceirizada_' . $user_id . '_' . time() . '.' . $extensao;
        $diretorio_destino = '../../foto/'; 
        
        if (!is_dir($diretorio_destino)) {
            mkdir($diretorio_destino, 0777, true);
        }

        $caminho_fisico = $diretorio_destino . $novo_nome;
        
        if (move_uploaded_file($arquivo['tmp_name'], $caminho_fisico)) {
            $caminho_foto = 'foto/' . $novo_nome; // Caminho para salvar no banco
        }
    }

    // 2. COLETA DOS DADOS DO FORMULÁRIO
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

    // 3. ATUALIZAÇÃO NO BANCO DE DADOS
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

  // 4. RETORNO DE SUCESSO OU ERRO PARA O AJAX (Se você usar AJAX)
if ($success) {
    echo json_encode(['success' => true, 'message' => $mensagem]);
} else {
    echo json_encode(['success' => false, 'message' => $mensagem]);
}
?>