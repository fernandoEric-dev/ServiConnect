<?php
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/catalog.php';

function profile_text(array $current, string $formKey, string $column, int $max, bool $required = false): string {
    $data = array_key_exists($formKey, $_POST) ? $_POST : [$formKey => (string)($current[$column] ?? '')];
    return field_text($data, $formKey, $max, $required);
}

function profile_phone(array $current): string {
    $phone = profile_text($current, 'telefone', 'telefone', 30);
    if ($phone === '') return '';
    if (!preg_match('/^[+0-9().\s-]+$/D', $phone)) abort_request(422, 'Informe um telefone válido.');
    $digits = preg_replace('/[^0-9]/', '', $phone);
    if (strlen($digits) < 10 || strlen($digits) > 15) abort_request(422, 'Informe o telefone com DDD.');
    return (strpos($phone, '+') === 0 ? '+' : '') . $digits;
}

function profile_address(array $current, bool $provider): array {
    $address = [];
    foreach (['cep'=>10, 'logradouro'=>100, 'numero'=>10, 'complemento'=>100, 'bairro'=>100, 'cidade'=>100, 'estado'=>2] as $field=>$max) {
        $address[$field] = profile_text($current, $field . ($provider ? '_empresa' : ''), $field, $max);
    }
    $address['cep'] = preg_replace('/[\s-]/', '', $address['cep']);
    if ($address['cep'] !== '' && !preg_match('/^[0-9]{8}$/D', $address['cep'])) abort_request(422, 'CEP inválido.');
    $address['estado'] = strtoupper($address['estado']);
    if ($address['estado'] !== '' && !in_array($address['estado'], ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'], true)) abort_request(422, 'Informe uma UF válida.');
    return $address;
}

function profile_save(PDO $pdo, int $userId, array $changes): void {
    // As chaves são definidas pelos controllers, nunca pelo cliente.
    $fields = implode(',', array_map(static fn($name) => $name . '=?', array_keys($changes)));
    $pdo->prepare('UPDATE empresas SET ' . $fields . ' WHERE usuario_id=?')->execute(array_merge(array_values($changes), [$userId]));
}

function profile_choices(string $key, array $options, int $max): array {
    $values = $_POST[$key] ?? [];
    if (!is_array($values) || count($values) > $max) abort_request(422, 'Revise as opções selecionadas.');
    foreach ($values as $value) if (!is_string($value) || !isset($options[$value])) abort_request(422, 'Selecione uma opção válida da lista.');
    return array_values(array_unique($values));
}

function profile_city(string $state, string $name, string $id): array {
    if (!preg_match('/^[A-Z]{2}$/D', $state)) abort_request(422, 'Selecione o estado da cidade.');
    $file = dirname(__DIR__) . '/data/cidades/' . $state . '.json';
    $cities = is_file($file) ? json_decode(file_get_contents($file), true) : [];
    foreach ($cities as $city) if (($id !== '' && $city['id'] === $id) || ($id === '' && $city['nome'] === $name)) return ['cidade'=>$city['nome'],'uf'=>$state,'id'=>$city['id']];
    abort_request(422, 'Selecione uma cidade válida da lista.');
}

function profile_extended(array $current, array $address): array {
    if (($_POST['perfil_estruturado'] ?? '') !== '1') return [];
    $changes = [];
    $website = field_text($_POST, 'website', 255);
    if ($website !== '' && (!filter_var($website, FILTER_VALIDATE_URL) || !in_array(strtolower(parse_url($website, PHP_URL_SCHEME) ?? ''), ['http','https'], true))) abort_request(422, 'Informe um site válido começando por https://.');
    $years = $_POST['experiencia_anos'] ?? '';
    if ($years !== '' && filter_var($years,FILTER_VALIDATE_INT,['options'=>['min_range'=>0,'max_range'=>100]]) === false) abort_request(422, 'Informe uma experiência de 0 a 100 anos.');
    $changes['website'] = $website ?: null;
    $changes['experiencia_anos'] = $years === '' ? null : (int)$years;
    foreach (['pagamentos'=>payment_options(), 'modalidades'=>attendance_options(), 'dias_atendimento'=>weekday_options()] as $field=>$options) $changes[$field] = json_encode(profile_choices($field,$options,count($options)),JSON_UNESCAPED_UNICODE);
    $changes['atendimento_24h'] = ($_POST['atendimento_24h'] ?? '') === '1' ? 1 : 0;
    $changes['emite_nota'] = ($_POST['emite_nota'] ?? '') === '1' ? 1 : 0;
    foreach (['horario_inicio','horario_fim'] as $field) {
        $value = field_text($_POST,$field,5);
        if ($value !== '' && !preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/D',$value)) abort_request(422,'Informe um horário válido.');
        $changes[$field] = $value ?: null;
    }
    if (!$changes['atendimento_24h'] && (bool)$changes['horario_inicio'] !== (bool)$changes['horario_fim']) abort_request(422,'Informe os horários de início e fim.');
    $days = selected_options($changes['dias_atendimento']);
    if ($changes['atendimento_24h']) $changes['horario'] = 'Atendimento 24 horas';
    elseif ($days || $changes['horario_inicio']) {
        $labels = array_intersect_key(weekday_options(),array_flip($days));
        $changes['horario'] = mb_substr(implode(', ',array_map(static fn($day)=>mb_substr($day,0,3),$labels)) . ($changes['horario_inicio'] ? ' · '.$changes['horario_inicio'].'–'.$changes['horario_fim'] : ''),0,50);
    }
    if ($address['cidade'] !== '' && $address['estado'] !== '') {
        $id = field_text($_POST,'municipio_ibge',7);
        $city = profile_city($address['estado'],$address['cidade'],$id);
        $changes['cidade'] = $city['cidade']; $changes['municipio_ibge'] = $city['id'];
    } else $changes['municipio_ibge'] = null;
    $regionsJson = $_POST['cidades_atendidas'] ?? '[]';
    if (!is_string($regionsJson) || strlen($regionsJson)>10000) abort_request(422,'Revise as cidades de atendimento.');
    $regions = json_decode($regionsJson,true);
    if (!is_array($regions) || count($regions)>20) abort_request(422,'Selecione até 20 cidades de atendimento.');
    $normalized = [];
    foreach ($regions as $region) {
        if (!is_array($region) || !is_string($region['uf'] ?? null) || !is_string($region['id'] ?? null)) abort_request(422,'Selecione as cidades de atendimento na lista.');
        $city = profile_city($region['uf'],'',$region['id']); $normalized[$city['id']] = $city;
    }
    $changes['cidades_atendidas'] = json_encode(array_values($normalized),JSON_UNESCAPED_UNICODE);
    if ($normalized || selected_options($current['cidades_atendidas'] ?? '')) $changes['regiao'] = mb_substr(implode(' · ',array_map(static fn($city)=>$city['cidade'].' / '.$city['uf'],$normalized)),0,255);
    return $changes;
}

function profile_map(array $current, array $address): array {
    if (($_POST['perfil_estruturado'] ?? '') !== '1') return [];
    if ($current['tipo_empresa'] === 'mei') return ['mapa_publico'=>0,'latitude'=>null,'longitude'=>null];
    $public = ($_POST['mapa_publico'] ?? '') === '1';
    $changed = ($_POST['mapa_alterado'] ?? '') === '1';
    $lat = $_POST['latitude'] ?? ''; $lng = $_POST['longitude'] ?? '';
    $explicitPoint = $changed || (is_string($lat) && is_string($lng) && is_numeric($lat) && is_numeric($lng)
        && ($current['latitude'] === null || $current['longitude'] === null || abs((float)$lat-(float)$current['latitude'])>0.00000001 || abs((float)$lng-(float)$current['longitude'])>0.00000001));
    $changed = $changed || $explicitPoint;
    foreach (['cep','logradouro','numero','complemento','bairro','cidade','estado'] as $field) if (($address[$field] ?? '') !== (string)($current[$field] ?? '')) $changed = true;
    $changes = ['mapa_publico'=>$public ? 1 : 0];
    if (!$changed) {
        if ($public && ($current['latitude'] === null || $current['longitude'] === null)) abort_request(422,'Marque a localização da empresa no mapa antes de publicá-la.');
        return $changes;
    }
    if ($lat === '' && $lng === '') return ['mapa_publico'=>0,'latitude'=>null,'longitude'=>null];
    if (!is_string($lat) || !is_string($lng) || !is_numeric($lat) || !is_numeric($lng) || !is_finite((float)$lat) || !is_finite((float)$lng) || abs((float)$lat)>90 || abs((float)$lng)>180) abort_request(422,'Marque uma localização válida no mapa.');
    if (!$explicitPoint) return ['mapa_publico'=>0,'latitude'=>null,'longitude'=>null];
    return ['mapa_publico'=>$public ? 1 : 0,'latitude'=>sprintf('%.8F',(float)$lat),'longitude'=>sprintf('%.8F',(float)$lng)];
}

function profile_completeness(array $profile, array $services): array {
    $checks = ['Escolher os serviços oferecidos'=>(bool)$services,'Adicionar uma apresentação'=>mb_strlen(trim($profile['descricao'] ?? ''))>=30,'Informar telefone de contato'=>!empty($profile['telefone']),'Preencher cidade e estado'=>!empty($profile['cidade']) && !empty($profile['estado']),'Selecionar regiões de atendimento'=>!empty($profile['regiao']),'Informar dias ou horários'=>!empty($profile['horario'])];
    return ['percent'=>(int)round(count(array_filter($checks))/count($checks)*100),'missing'=>array_keys(array_filter($checks,static fn($done)=>!$done))];
}
