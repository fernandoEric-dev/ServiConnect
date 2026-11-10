<?php
require_once __DIR__ . '/../conexao.php';
require_once __DIR__ . '/../opencnpj.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
require_post();
$data = json_input();
require_csrf($data);
$document = normalize_document(field_text($data, 'documento', 30, true));
if (!valid_cnpj($document)) abort_request(422, 'Informe um CNPJ válido para consultar.');
$company = require_active_cnpj($pdo, $document);
echo json_encode(['success' => true, 'message' => 'CNPJ ativo confirmado na OpenCNPJ.', 'empresa' => $company], JSON_UNESCAPED_UNICODE);
