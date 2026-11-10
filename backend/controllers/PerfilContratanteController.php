<?php
require_once __DIR__ . '/../conexao.php';
require_once __DIR__ . '/../profile.php';
require_post();
require_role($pdo, ['contratante']);
require_csrf();
$userId = (int)$_SESSION['user_id'];
$stmt = $pdo->prepare('SELECT * FROM empresas WHERE usuario_id=?');
$stmt->execute([$userId]);
$current = $stmt->fetch();
if (!$current) abort_request(404, 'Perfil não encontrado.');
$changes = [
    'nome'=>profile_text($current,'nome','nome',150,true),
    'responsavel'=>profile_text($current,'responsavel','responsavel',100),
    'telefone'=>profile_phone($current),
    'descricao'=>profile_text($current,'texto_perfil','descricao',10000),
] + profile_address($current, false);
$changes['foto_path'] = save_profile_photo('foto_perfil',$userId,$current['foto_path']);
try {
    profile_save($pdo,$userId,$changes);
    header('Location: /dashboard_contratante.php?status=perfil_atualizado#perfil',true,303);
} catch (PDOException $e) { error_log('Perfil: '.$e->getMessage()); abort_request(500,'Não foi possível salvar o perfil.'); }
