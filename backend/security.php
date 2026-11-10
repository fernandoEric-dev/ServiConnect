<?php
date_default_timezone_set('America/Sao_Paulo');

function app_config(): array {
    static $config;
    if ($config !== null) return $config;
    $localPath = __DIR__ . '/config.local.php';
    $local = is_file($localPath) ? require $localPath : [];
    $value = static function ($env, $key, $default = '') use ($local) {
        $v = getenv($env);
        return $v !== false ? $v : ($local[$key] ?? $default);
    };
    $config = [
        'environment' => $value('APP_ENV', 'environment', 'production'),
        'app_url' => rtrim($value('APP_URL', 'app_url'), '/'),
        'db_host' => $value('DB_HOST', 'db_host'),
        'db_port' => (int)$value('DB_PORT', 'db_port', 3306),
        'db_name' => $value('DB_NAME', 'db_name'),
        'db_user' => $value('DB_USER', 'db_user'),
        'db_password' => $value('DB_PASSWORD', 'db_password'),
        'map_tile_url' => $value('MAP_TILE_URL','map_tile_url','https://tile.openstreetmap.org/{z}/{x}/{y}.png'),
    ];
    return $config;
}

if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_name('serviconnect_session');
    session_set_cookie_params([
        'httponly' => true, 'samesite' => 'Lax', 'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('X-Frame-Options: SAMEORIGIN');
}

function abort_request(int $status, string $message): void {
    http_response_code($status);
    if (strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false
        || strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => $message], JSON_UNESCAPED_UNICODE);
    } else {
        header('Content-Type: text/plain; charset=utf-8');
        echo $message;
    }
    exit;
}

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

function require_post(): void {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        header('Allow: POST');
        abort_request(405, 'Método não permitido.');
    }
}

function require_csrf(?array $data = null): void {
    $token = $data['csrf_token'] ?? $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        abort_request(403, 'Sua sessão expirou ou a solicitação é inválida. Atualize a página e tente novamente.');
    }
}

function json_input(): array {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data)) abort_request(400, 'Dados inválidos.');
    return $data;
}

function require_role(PDO $pdo, array $roles = []): array {
    if (empty($_SESSION['user_id']) || empty($_SESSION['user_role'])) {
        header('Location: /login.php', true, 303);
        exit;
    }
    if ($_SESSION['user_role'] === 'admin') {
        $stmt = $pdo->prepare('SELECT id FROM administradores WHERE id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        $role = 'admin';
    } else {
        $stmt = $pdo->prepare('SELECT u.id, u.status, u.tipo_pessoa, e.tipo_empresa FROM usuarios u LEFT JOIN empresas e ON e.usuario_id = u.id WHERE u.id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        $role = account_role($user['tipo_empresa'] ?? '');
        if (($user['status'] ?? '') !== 'ativo') $user = false;
    }
    if (!$user || $role === '' || $role !== account_role($_SESSION['user_role'])) {
        $_SESSION = [];
        session_regenerate_id(true);
        abort_request(403, 'Conta indisponível. Entre novamente ou contate o suporte.');
    }
    $_SESSION['user_role'] = $role;
    if ($role !== 'admin') {
        $_SESSION['account_type'] = $user['tipo_empresa'];
        $_SESSION['person_type'] = $user['tipo_pessoa'];
    }
    if ($roles && !in_array($role, array_map('account_role', $roles), true)) abort_request(403, 'Acesso não autorizado.');
    return $user;
}

function field_text(array $data, string $key, int $max, bool $required = false): string {
    $value = $data[$key] ?? '';
    if (!is_string($value)) abort_request(422, 'Campo inválido: ' . $key);
    $value = trim($value);
    if (($required && $value === '') || mb_strlen($value) > $max) abort_request(422, 'Revise o campo: ' . $key);
    return $value;
}

function normalize_document(string $document): string {
    $normalized = preg_replace('~[./\s-]~u', '', trim($document));
    return is_string($normalized) ? strtoupper($normalized) : '';
}

function valid_cpf(string $cpf): bool {
    if (!preg_match('/^[0-9]{11}$/D', $cpf) || preg_match('/^([0-9])\1{10}$/D', $cpf)) return false;
    foreach ([9, 10] as $length) {
        $sum = 0;
        for ($i = 0; $i < $length; $i++) $sum += (int)$cpf[$i] * ($length + 1 - $i);
        $digit = (11 - $sum % 11) % 11;
        if ($digit > 9) $digit = 0;
        if ((int)$cpf[$length] !== $digit) return false;
    }
    return true;
}

function valid_cnpj(string $cnpj): bool {
    if (!preg_match('/^[A-Z0-9]{12}[0-9]{2}$/D', $cnpj) || preg_match('/^([0-9])\1{13}$/D', $cnpj)) return false;
    foreach ([12, 13] as $length) {
        $sum = 0; $weight = $length - 7;
        for ($i = 0; $i < $length; $i++) {
            $sum += (ord($cnpj[$i]) - 48) * $weight;
            $weight = $weight === 2 ? 9 : $weight - 1;
        }
        $digit = ($sum % 11) < 2 ? 0 : 11 - ($sum % 11);
        if ((int)$cnpj[$length] !== $digit) return false;
    }
    return true;
}

function account_role(string $type): string {
    if (in_array($type, ['mei', 'terceirizada', 'prestador'], true)) return 'prestador';
    return in_array($type, ['contratante', 'admin'], true) ? $type : '';
}

function account_type_label(string $type, string $personType = 'juridica'): string {
    if ($type === 'mei') return 'MEI';
    if ($type === 'terceirizada' || $type === 'prestador') return 'Empresa terceirizada';
    if ($type === 'admin') return 'Administrador';
    if ($type === 'contratante') return $personType === 'fisica' ? 'Pessoa física' : 'Empresa contratante';
    return 'Perfil pendente';
}

function format_document(string $document): string {
    $document = normalize_document($document);
    if (preg_match('/^[0-9]{11}$/D', $document)) return preg_replace('/^(.{3})(.{3})(.{3})(.{2})$/D', '$1.$2.$3-$4', $document);
    if (preg_match('/^[A-Z0-9]{12}[0-9]{2}$/D', $document)) return preg_replace('/^(.{2})(.{3})(.{3})(.{4})(.{2})$/D', '$1.$2.$3/$4-$5', $document);
    return '';
}

function safe_photo(?string $path): string {
    return $path && preg_match('~^foto/[a-zA-Z0-9_.-]+\.(?:jpe?g|png|webp)$~iD', $path)
        ? $path : 'img/default_avatar.svg';
}

function save_profile_photo(string $field, int $userId, ?string $current): string {
    $file = $_FILES[$field] ?? null;
    if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) return safe_photo($current);
    if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
        abort_request(422, 'Não foi possível receber a imagem.');
    }
    if ($file['size'] > 2 * 1024 * 1024 || $file['size'] < 1) abort_request(422, 'Envie uma imagem de até 2 MB.');
    $types = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $info = @getimagesize($file['tmp_name']);
    if (!isset($types[$mime]) || !$info || ($info['mime'] ?? '') !== $mime
        || $info[0] > 6000 || $info[1] > 6000) abort_request(422, 'Use uma imagem JPG, PNG ou WebP válida, de até 6000 pixels por lado.');
    $name = 'upload_' . $userId . '_' . bin2hex(random_bytes(16)) . '.' . $types[$mime];
    $directory = dirname(__DIR__) . '/foto';
    if (!move_uploaded_file($file['tmp_name'], $directory . '/' . $name)) abort_request(500, 'Não foi possível salvar a imagem.');
    return 'foto/' . $name;
}
