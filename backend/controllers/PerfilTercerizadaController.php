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
$changes = array_replace($changes, profile_extended($current, $changes));
if ($current['tipo_empresa'] === 'terceirizada' && array_key_exists('num_funcionarios',$_POST)) {
    $people = filter_var($_POST['num_funcionarios'],FILTER_VALIDATE_INT,['options'=>['min_range'=>0,'max_range'=>100000]]);
    if ($people === false) abort_request(422,'Informe uma quantidade de colaboradores válida.');
    $changes['num_funcionarios'] = $people;
}
foreach (['cep','logradouro','numero','complemento','bairro','cidade','estado'] as $field) {
    if ($changes[$field] !== (string)($current[$field] ?? '')) { $changes['latitude']=null; $changes['longitude']=null; break; }
}
$changes = array_replace($changes, profile_map($current, $changes));
$selectedServices = null;
if (($_POST['servicos_selecionados'] ?? '') === '1') {
    $selectedServices = profile_choices('servicos',service_catalog(),20);
    if ($selectedServices || provider_services($pdo,$userId)) $changes['area_atuacao'] = mb_substr(implode(', ',array_intersect_key(service_catalog(),array_flip($selectedServices))),0,100);
}
$changes['foto_path'] = save_profile_photo('foto_perfil',$userId,$current['foto_path']);
try {
    $pdo->beginTransaction();
    profile_save($pdo,$userId,$changes);
    if ($selectedServices !== null) {
        $pdo->prepare('DELETE FROM empresa_servicos WHERE usuario_id=?')->execute([$userId]);
        $insert = $pdo->prepare('INSERT INTO empresa_servicos(usuario_id,servico_id,nome) VALUES(?,?,?)');
        foreach ($selectedServices as $service) $insert->execute([$userId,$service,service_catalog()[$service]]);
    }
    $pdo->commit();
    header('Location: /dashboard_tercerizada.php?status=perfil_atualizado#perfil',true,303);
} catch (PDOException $e) { if ($pdo->inTransaction()) $pdo->rollBack(); error_log('Perfil: '.$e->getMessage()); abort_request(500,'Não foi possível salvar o perfil.'); }
