<?php
require_once __DIR__ . '/security.php';

class CnpjLookupProblem extends RuntimeException {
    public int $status;
    public function __construct(int $status, string $message) {
        parent::__construct($message);
        $this->status = $status;
    }
}

function opencnpj_unavailable(): CnpjLookupProblem {
    return new CnpjLookupProblem(503, 'Não foi possível confirmar a situação do CNPJ na OpenCNPJ agora. A conta não foi criada. Aguarde um pouco e tente novamente.');
}

function opencnpj_api_base(): string {
    // O substituto existe apenas no servidor PHP de testes, com banco local separado.
    // Em Apache/FPM (InfinityFree), o destino é sempre o HTTPS oficial.
    $testUrl = getenv('OPENCNPJ_TEST_URL');
    $config = app_config();
    if (PHP_SAPI === 'cli-server' && $config['environment'] === 'development'
        && $config['db_host'] === '127.0.0.1' && $config['db_name'] === 'serviconnect_dev'
        && $config['db_port'] === 3307 && ($_SERVER['REMOTE_ADDR'] ?? '') === '127.0.0.1'
        && is_string($testUrl) && preg_match('~^http://127\.0\.0\.1:[0-9]{2,5}$~D', $testUrl)) {
        return $testUrl;
    }
    return 'https://api.opencnpj.org';
}

function opencnpj_decode(string $document, int $status, string $body): array {
    if ($status === 404) {
        throw new CnpjLookupProblem(422, 'CNPJ não encontrado na OpenCNPJ. Confira o número informado. A conta não foi criada.');
    }
    if ($status !== 200) throw opencnpj_unavailable();
    $company = json_decode($body, true);
    if (!is_array($company) || !is_string($company['cnpj'] ?? null)
        || normalize_document($company['cnpj']) !== $document
        || !is_string($company['situacao_cadastral'] ?? null)
        || trim($company['situacao_cadastral']) === '') {
        throw opencnpj_unavailable();
    }
    if (strcasecmp(trim($company['situacao_cadastral']), 'Ativa') !== 0) {
        throw new CnpjLookupProblem(422, 'Este CNPJ não está com a situação cadastral ativa. Somente empresas com CNPJ ativo podem criar uma conta.');
    }
    return [
        'cnpj' => $document,
        'situacao_cadastral' => 'Ativa',
        'razao_social' => is_string($company['razao_social'] ?? null) ? mb_substr(trim($company['razao_social']), 0, 250) : '',
    ];
}

function opencnpj_lookup(string $document): array {
    if (!valid_cnpj($document)) throw new CnpjLookupProblem(422, 'Informe um CNPJ válido.');
    if (!function_exists('curl_init')) throw opencnpj_unavailable();
    $body = '';
    $curl = curl_init(opencnpj_api_base() . '/' . rawurlencode($document) . '?datasets=receita');
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => false,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_CONNECTTIMEOUT => 4,
        CURLOPT_TIMEOUT => 12,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
        CURLOPT_USERAGENT => 'ServiConnect/1.0 (+https://serviconnect.online)',
        CURLOPT_WRITEFUNCTION => static function ($handle, string $chunk) use (&$body): int {
            if (strlen($body) + strlen($chunk) > 262144) return 0;
            $body .= $chunk;
            return strlen($chunk);
        },
    ]);
    $success = curl_exec($curl);
    $status = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_errno($curl);
    curl_close($curl);
    if ($success === false) {
        // Não registrar documento nem corpo da resposta em logs.
        error_log('OpenCNPJ: falha de transporte, código cURL ' . $error);
        throw opencnpj_unavailable();
    }
    return opencnpj_decode($document, $status, $body);
}

function opencnpj_verify(PDO $pdo, string $document): array {
    // Limite compartilhado entre consulta e criação; trocar a sessão não o remove.
    $key = hash('sha256', 'opencnpj:' . ($_SERVER['REMOTE_ADDR'] ?? ''));
    $pdo->prepare('INSERT INTO login_tentativas (chave,tentativas,janela_inicio) VALUES (?,1,NOW()) ON DUPLICATE KEY UPDATE tentativas=IF(janela_inicio < DATE_SUB(NOW(),INTERVAL 15 MINUTE),1,tentativas+1), janela_inicio=IF(janela_inicio < DATE_SUB(NOW(),INTERVAL 15 MINUTE),NOW(),janela_inicio)')->execute([$key]);
    $attempt = $pdo->prepare('SELECT tentativas FROM login_tentativas WHERE chave=?');
    $attempt->execute([$key]);
    if ((int)$attempt->fetchColumn() > 60) {
        throw new CnpjLookupProblem(429, 'Muitas consultas de CNPJ. Aguarde 15 minutos e tente novamente.');
    }
    return opencnpj_lookup($document);
}

function require_active_cnpj(PDO $pdo, string $document): array {
    try {
        return opencnpj_verify($pdo, $document);
    } catch (CnpjLookupProblem $e) {
        if ($e->status === 429) header('Retry-After: 900');
        if ($e->status === 503) header('Retry-After: 30');
        abort_request($e->status, $e->getMessage());
    }
}
