<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$runtime = json_decode(file_get_contents($argv[1] ?? ''),true,512,JSON_THROW_ON_ERROR);
try {
    $pdo = new PDO('mysql:host=127.0.0.1;port='.(int)$runtime['port'],'root',$runtime['root_password']);
    // Autenticação no servidor separado, em vez de encerrar um PID arbitrário.
    $pdo->exec('SHUTDOWN');
} catch (PDOException $e) {
    if ((int)($e->errorInfo[1] ?? 0) !== 2002 && (int)($e->errorInfo[1] ?? 0) !== 2006) throw $e;
}
