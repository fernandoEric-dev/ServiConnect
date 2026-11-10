<?php
require_once __DIR__ . '/security.php';

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
