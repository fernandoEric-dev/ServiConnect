<?php
// Simulador usado somente pelo servidor PHP local do teste. Nunca é publicado.
if (PHP_SAPI !== 'cli-server' || ($_SERVER['REMOTE_ADDR'] ?? '') !== '127.0.0.1') { http_response_code(404); exit; }
$path = getenv('OPENCNPJ_FIXTURE_FILE');
if (!$path || !is_file($path)) { http_response_code(503); exit; }
$fixture = json_decode(file_get_contents($path), true);
$document = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
if (!preg_match('/^[A-Z0-9]{12}[0-9]{2}$/D', $document)) { http_response_code(400); exit; }
$response = $fixture['documents'][$document] ?? $fixture['default'] ?? [];
if (!empty($response['delay'])) usleep((int)($response['delay'] * 1000000));
http_response_code($response['status'] ?? 200);
header('Content-Type: application/json');
echo $response['body'] ?? json_encode(['cnpj'=>$document,'situacao_cadastral'=>$response['situacao'] ?? 'Ativa','razao_social'=>'Empresa fictícia de teste']);
