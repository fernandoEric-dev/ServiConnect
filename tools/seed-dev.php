<?php
if (PHP_SAPI !== 'cli' || !isset($pdo, $runtime) || $pdo->query('SELECT DATABASE()')->fetchColumn() !== 'serviconnect_dev') exit(1);
function demo_cnpj(int $number): string {
    $cnpj = '9000000' . $number . '0001';
    foreach ([12, 13] as $len) {
        $sum = 0; $w = $len - 7;
        for ($i = 0; $i < $len; $i++) { $sum += (int)$cnpj[$i] * $w; $w = $w === 2 ? 9 : $w - 1; }
        $cnpj .= ($sum % 11 < 2 ? 0 : 11 - $sum % 11);
    }
    return $cnpj;
}
$hash = password_hash('ServiDev!2026', PASSWORD_DEFAULT);
function demo_cpf(): string {
    $cpf = '900000006';
    foreach ([9,10] as $len) {
        $sum=0;
        for ($i=0;$i<$len;$i++) $sum+=(int)$cpf[$i]*($len+1-$i);
        $digit=(11-$sum%11)%11;
        $cpf.= $digit>9 ? 0 : $digit;
    }
    return $cpf;
}
$pdo->beginTransaction();
foreach ([1 => ['contratante', 'Contratante Demonstração'], 2 => ['terceirizada', 'Jardins Demonstração'], 3 => ['terceirizada', 'Limpeza Demonstração'], 5 => ['mei', 'Jardinagem MEI Demonstração'], 6 => ['contratante', 'Pessoa Física Demonstração']] as $n => [$role, $name]) {
    $cnpj = $n === 6 ? demo_cpf() : demo_cnpj($n);
    $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE cpf_cnpj = ?');
    $stmt->execute([$cnpj]);
    if ($stmt->fetchColumn()) continue;
    $pdo->prepare("INSERT INTO usuarios (cpf_cnpj,email,senha,tipo_conta,status,tipo_pessoa) VALUES (?,?,?,'empresa','ativo',?)")
        ->execute([$cnpj, "demo$n@example.invalid", $hash, $n === 6 ? 'fisica' : 'juridica']);
    $id = $pdo->lastInsertId();
    $pdo->prepare('INSERT INTO empresas (usuario_id,nome,tipo_empresa,descricao,regiao,area_atuacao,cep,logradouro,numero,bairro,cidade,estado) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)')
        ->execute([$id, $name, $role, 'Perfil fictício para testes locais.', 'Ferraz de Vasconcelos', in_array($n,[2,5],true) ? 'Jardinagem' : 'Limpeza', '00000000', 'Rua de Demonstração', '0', 'Bairro de Testes', 'Ferraz de Vasconcelos', 'SP']);
}
$pdo->prepare('INSERT IGNORE INTO administradores (login_cnpj,email,senha) VALUES (?,?,?)')->execute([demo_cnpj(4), 'admin@example.invalid', $hash]);
$pdo->commit();
echo 'Contas fictícias (senha: ServiDev!2026): contratante ' . demo_cnpj(1) . ', prestador ' . demo_cnpj(2) . ', admin ' . demo_cnpj(4) . ".\n";
echo 'Novos perfis fictícios: MEI ' . demo_cnpj(5) . ', contratante CPF ' . demo_cpf() . ".\n";
