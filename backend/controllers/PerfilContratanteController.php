<?php
// backend/controllers/PerfilContratanteController.php

session_start();
require_once '../conexao.php';

// Verifica se está logado e se é contratante
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'contratante') {
    header('Location: ../../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario_id = $_SESSION['user_id'];
    
    // Recolhe os dados do formulário
    $responsavel = trim($_POST['responsavel'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $cep = trim($_POST['cep'] ?? '');
    $logradouro = trim($_POST['logradouro'] ?? '');
    $numero = trim($_POST['numero'] ?? '');
    $bairro = trim($_POST['bairro'] ?? '');
    $cidade = trim($_POST['cidade'] ?? '');
    $estado = trim($_POST['estado'] ?? '');

    // 1. Processamento de Upload da Foto (se foi enviada uma nova)
    $foto_path = null;
    if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
        
        $upload_dir = '../../foto/'; 
        // Cria a pasta se não existir
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_tmp = $_FILES['foto_perfil']['tmp_name'];
        $file_name = basename($_FILES['foto_perfil']['name']);
        
        // Gera um nome único para não substituir imagens com o mesmo nome
        $new_file_name = 'contratante_' . $usuario_id . '_' . time() . '_' . $file_name;
        $destino_final = $upload_dir . $new_file_name;

        // Move o arquivo
        if (move_uploaded_file($file_tmp, $destino_final)) {
            // O caminho salvo na base de dados (relativo à raiz)
            $foto_path = 'foto/' . $new_file_name; 
        }
    }

    try {
        // 2. Atualiza a base de dados
        
        // Se houver uma foto nova, atualizamos também o campo foto_path
        if ($foto_path) {
            $stmt = $pdo->prepare("
                UPDATE empresas SET 
                    responsavel = :responsavel,
                    telefone = :telefone,
                    cep = :cep,
                    logradouro = :logradouro,
                    numero = :numero,
                    bairro = :bairro,
                    cidade = :cidade,
                    estado = :estado,
                    foto_path = :foto_path
                WHERE usuario_id = :usuario_id
            ");
            $stmt->bindParam(':foto_path', $foto_path);
        } else {
            // Se NÃO houver foto nova, atualiza apenas os textos
            $stmt = $pdo->prepare("
                UPDATE empresas SET 
                    responsavel = :responsavel,
                    telefone = :telefone,
                    cep = :cep,
                    logradouro = :logradouro,
                    numero = :numero,
                    bairro = :bairro,
                    cidade = :cidade,
                    estado = :estado
                WHERE usuario_id = :usuario_id
            ");
        }

        $stmt->bindParam(':responsavel', $responsavel);
        $stmt->bindParam(':telefone', $telefone);
        $stmt->bindParam(':cep', $cep);
        $stmt->bindParam(':logradouro', $logradouro);
        $stmt->bindParam(':numero', $numero);
        $stmt->bindParam(':bairro', $bairro);
        $stmt->bindParam(':cidade', $cidade);
        $stmt->bindParam(':estado', $estado);
        $stmt->bindParam(':usuario_id', $usuario_id);

        $stmt->execute();

        // Volta para o dashboard com a aba de perfil aberta
        header('Location: ../../dashboard_contratante.php#perfil');
        exit;

    } catch (PDOException $e) {
        error_log("Erro ao atualizar perfil do contratante: " . $e->getMessage());
        echo "<script>alert('Ocorreu um erro ao guardar os dados. Tente novamente.'); window.history.back();</script>";
        exit;
    }
} else {
    header('Location: ../../dashboard_contratante.php');
    exit;
}