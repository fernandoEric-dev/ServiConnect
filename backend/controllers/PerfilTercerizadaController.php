<?php
require_once __DIR__ . '/../conexao.php';
require_once __DIR__ . '/../profile.php';
require_post();
require_role($pdo, ['prestador']);
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
    'area_atuacao'=>profile_text($current,'area_atuacao','area_atuacao',100),
    'regiao'=>profile_text($current,'regiao','regiao',255),
    'horario'=>profile_text($current,'horario','horario',50),
] + profile_address($current, true);
if ($current['tipo_empresa'] === 'terceirizada' && array_key_exists('num_funcionarios',$_POST)) {
    $people = filter_var($_POST['num_funcionarios'],FILTER_VALIDATE_INT,['options'=>['min_range'=>0,'max_range'=>100000]]);
    if ($people === false) abort_request(422,'Informe uma quantidade de colaboradores válida.');
    $changes['num_funcionarios'] = $people;
}
foreach (['cep','logradouro','numero','complemento','bairro','cidade','estado'] as $field) {
    if ($changes[$field] !== (string)($current[$field] ?? '')) { $changes['latitude']=null; $changes['longitude']=null; break; }
}
$changes['foto_path'] = save_profile_photo('foto_perfil',$userId,$current['foto_path']);
try {
    profile_save($pdo,$userId,$changes);
    header('Location: /dashboard_tercerizada.php?status=perfil_atualizado#perfil',true,303);
} catch (PDOException $e) { error_log('Perfil: '.$e->getMessage()); abort_request(500,'Não foi possível salvar o perfil.'); }
