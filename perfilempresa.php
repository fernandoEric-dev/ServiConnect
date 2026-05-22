<?php
// perfil_empresa.php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'backend/conexao.php';

$empresa_usuario_id = $_GET['id'] ?? 0;

// Busca todos os dados da empresa através do ID de usuário
$stmt = $pdo->prepare("SELECT u.email, e.* FROM usuarios u JOIN empresas e ON u.id = e.usuario_id WHERE u.id = ?");
$stmt->execute([$empresa_usuario_id]);
$empresa = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$empresa) {
    echo "<script>alert('Empresa não encontrada.'); window.history.back();</script>";
    exit;
}

$foto_perfil = !empty($empresa['foto_path']) ? $empresa['foto_path'] : 'img/default_avatar.png';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ServiConnect | Perfil de <?php echo htmlspecialchars($empresa['nome']); ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        .profile-container { max-width: 800px; margin: 40px auto; padding: 20px; }
        .profile-card-header { display: flex; align-items: center; gap: 25px; border-bottom: 2px solid #eee; padding-bottom: 20px; margin-bottom: 20px; }
        .profile-avatar { width: 120px; height: 120px; border-radius: 12px; object-fit: cover; border: 2px solid #ddd; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px; }
        .info-item { background: #f9f9f9; padding: 15px; border-radius: 6px; border-left: 4px solid var(--primary-blue, #0056b3); }
        .info-item h4 { margin: 0 0 5px 0; color: #55px; font-size: 0.9em; text-transform: uppercase; }
        .info-item p { margin: 0; font-weight: bold; color: #222; }
        .full-width { grid-column: span 2; }
        .btn-back { display: inline-block; margin-bottom: 20px; padding: 10px 20px; background: #333; color: #fff; text-decoration: none; border-radius: 5px; font-weight: bold; }
        .btn-back:hover { background: #505050; }
    </style>
</head>
<body>
    <div class="profile-container">
        <a href="text" onclick="window.history.back(); return false;" class="btn-back"><i class="fa-solid fa-arrow-left"></i> Voltar</a>
        
        <div class="widget-card">
            <div class="profile-card-header">
                <img src="<?php echo $foto_perfil; ?>" alt="Logo da Empresa" class="profile-avatar">
                <div>
                    <h2 style="margin: 0;"><?php echo htmlspecialchars($empresa['nome']); ?></h2>
                    <span class="badge-role" style="display: inline-block; margin-top: 8px; background-color: <?php echo $empresa['tipo_empresa'] === 'terceirizada' ? '#28a745' : '#ffc107'; ?>; color: #fff; padding: 3px 8px; border-radius: 3px; font-size: 0.85em; text-transform: capitalize;">
                        <?php echo htmlspecialchars($empresa['tipo_empresa']); ?>
                    </span>
                </div>
            </div>

            <div class="info-grid">
                <div class="info-item full-width">
                    <h4><i class="fa-solid fa-file-lines"></i> Descrição / Apresentação</h4>
                    <p style="font-weight: normal; white-space: pre-wrap;"><?php echo !empty($empresa['descricao']) ? htmlspecialchars($empresa['descricao']) : 'Nenhuma descrição fornecida.'; ?></p>
                </div>

                <?php if ($empresa['tipo_empresa'] === 'terceirizada'): ?>
                    <div class="info-item">
                        <h4><i class="fa-solid fa-briefcase"></i> Área de Atuação</h4>
                        <p><?php echo !empty($empresa['area_atuacao']) ? htmlspecialchars($empresa['area_atuacao']) : 'Não Informada'; ?></p>
                    </div>
                    <div class="info-item">
                        <h4><i class="fa-solid fa-users"></i> Qtd. Funcionários</h4>
                        <p><?php echo (int)$empresa['num_funcionarios']; ?> colaboradores</p>
                    </div>
                    <div class="info-item">
                        <h4><i class="fa-solid fa-map-location-dot"></i> Regiões Atendidas</h4>
                        <p><?php echo !empty($empresa['regiao']) ? htmlspecialchars($empresa['regiao']) : 'Não Informada'; ?></p>
                    </div>
                <?php endif; ?>

                <div class="info-item">
                    <h4><i class="fa-solid fa-clock"></i> Horário de Funcionamento</h4>
                    <p><?php echo !empty($empresa['horario']) ? htmlspecialchars($empresa['horario']) : 'Não Informado'; ?></p>
                </div>
                <div class="info-item">
                    <h4><i class="fa-solid fa-phone"></i> Telefone de Contato</h4>
                    <p><?php echo !empty($empresa['telefone']) ? htmlspecialchars($empresa['telefone']) : 'Não Informado'; ?></p>
                </div>
                <div class="info-item">
                    <h4><i class="fa-solid fa-user"></i> Responsável</h4>
                    <p><?php echo !empty($empresa['responsavel']) ? htmlspecialchars($empresa['responsavel']) : 'Não Informado'; ?></p>
                </div>

                <div class="info-item full-width">
                    <h4><i class="fa-solid fa-location-dot"></i> Localização / Endereço Principal</h4>
                    <p style="font-weight: normal;">
                        <?php 
                        if (!empty($empresa['logradouro'])) {
                            echo htmlspecialchars($empresa['logradouro']) . ", " . htmlspecialchars($empresa['numero']);
                            if (!empty($empresa['complemento'])) echo " - " . htmlspecialchars($empresa['complemento']);
                            echo "<br>" . htmlspecialchars($empresa['bairro']) . " - " . htmlspecialchars($empresa['cidade']) . "/" . htmlspecialchars($empresa['estado']);
                            echo "<br>CEP: " . htmlspecialchars($empresa['cep']);
                        } else {
                            echo "Endereço ainda não configurado.";
                        }
                        ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>