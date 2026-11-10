<?php
// Roteador apenas para o servidor PHP de desenvolvimento. Apache usa .htaccess.
if (PHP_SAPI !== 'cli-server') { http_response_code(404); exit; }
$root = dirname(__DIR__);
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/');
if ($path === '/') $path = '/index.php';
$target = realpath($root . $path);
$relative = $target ? str_replace('\\', '/', substr($target, strlen($root))) : '';
if (!$target || !is_file($target) || strpos($target, $root . DIRECTORY_SEPARATOR) !== 0
    || preg_match('~(?:^|/)[.]|^/(?:database|docs|tests|tools)(?:/|$)|^/backend/(?!controllers/)|[.](?:sql|log|ini|md|ps1)$~i', $relative)
    || (strpos($relative, '/foto/') === 0 && !preg_match('~\.(?:jpg|jpeg|png|webp)$~i', $relative))) {
    http_response_code(404); exit;
}
if (strtolower(pathinfo($target, PATHINFO_EXTENSION)) === 'php') { require $target; return true; }
return false;
