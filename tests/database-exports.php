<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/backend/security.php';
$config = app_config();
if ($config['environment'] !== 'development' || $config['db_host'] !== '127.0.0.1' || $config['db_name'] !== 'serviconnect_dev') {
    throw new RuntimeException('Teste restrito ao servidor separado de desenvolvimento.');
}
$runtime = json_decode(file_get_contents($argv[1] ?? ''), true, 512, JSON_THROW_ON_ERROR);
if ((int)$runtime['port'] !== $config['db_port']) throw new RuntimeException('Porta diferente do servidor local.');
$pdo = new PDO('mysql:host=127.0.0.1;port='.(int)$runtime['port'].';charset=utf8mb4','root',$runtime['root_password'],[
    PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$root = dirname(__DIR__);
$created = [];
function apply_sql(PDO $pdo,string $file): void { $pdo->exec(file_get_contents($file)); }
function snapshot_rows(PDO $pdo,array $columns): string {
    $rows=[];
    foreach ($columns as $table=>$names) {
        $select=implode(',',array_map(static fn($n)=>'`'.$n.'`',$names));
        $rows[$table]=$pdo->query("SELECT $select FROM `$table` ORDER BY id")->fetchAll();
    }
    return hash('sha256',json_encode($rows));
}
try {
    foreach (['update','stage2','empty'] as $kind) {
        $name='serviconnect_export_'.$kind.'_'.bin2hex(random_bytes(6));
        $pdo->exec("CREATE DATABASE `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
        $created[]=$name;
        $pdo->exec("USE `$name`");
        if ($kind==='empty') {
            apply_sql($pdo,$root.'/database/infinityfree/BANCO_NOVO_VAZIO.sql');
            $tables=$pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
            if (count($tables)!==9) throw new RuntimeException('Estrutura nova incompleta.');
            foreach (['usuarios','empresas','solicitacoes','administradores','usuarios_arquivados'] as $table) {
                if ((int)$pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn()!==0) throw new RuntimeException('Exportação contém dados inesperados.');
            }
            echo "OK: instalação em banco vazio, nove tabelas, sem contas ou dados de produção.\n";
            continue;
        }
        apply_sql($pdo,$root.'/database/migrations/001_base.sql');
        $pdo->exec("INSERT INTO usuarios(id,cpf_cnpj,email,senha,tipo_conta,status) VALUES
            (1,'90000001000129','test1@example.invalid','hash-ficticio','empresa','ativo'),
            (2,'90000002000173','test2@example.invalid','hash-ficticio','empresa','bloqueado')");
        $pdo->exec("INSERT INTO empresas(usuario_id,nome,tipo_empresa,cep,logradouro,numero,bairro,cidade,estado) VALUES
            (1,'Teste contratante','contratante','00000000','Rua Teste','0','Teste','Teste','SP'),
            (2,'Teste prestador','terceirizada','00000000','Rua Teste','0','Teste','Teste','SP')");
        $mode=$pdo->query('SELECT @@SESSION.sql_mode')->fetchColumn();
        $pdo->exec("SET SESSION sql_mode=''");
        $pdo->exec("INSERT INTO solicitacoes(contratante_id,terceirizada_id,descricao_servico,status,valor_orcamento) VALUES
            (1,2,'Pedido fictício com estado vazio','',150.00),
            (1,2,'Pedido fictício com estado nulo',NULL,NULL),
            (1,2,'Pedido fictício aberto','aberta',NULL)");
        $pdo->exec('SET SESSION sql_mode='.$pdo->quote($mode));
        if ($kind === 'stage2') apply_sql($pdo,$root.'/tests/fixtures/atualizacao_etapa1.sql');
        $columns=[];
        foreach (['usuarios','empresas','solicitacoes','administradores','usuarios_arquivados'] as $table) {
            $columns[$table]=array_column($pdo->query("SHOW COLUMNS FROM `$table`")->fetchAll(),'Field');
        }
        $before=snapshot_rows($pdo,$columns);
        $patch=$root.'/database/infinityfree/'.($kind === 'stage2' ? 'ATUALIZAR_ETAPA_2_CADASTRO_PERFIS.sql' : 'ATUALIZAR_BANCO_ATUAL.sql');
        apply_sql($pdo,$patch);
        apply_sql($pdo,$root.'/database/infinityfree/ATUALIZAR_ETAPAS_4_E_5.sql');
        if (snapshot_rows($pdo,$columns)!==$before) throw new RuntimeException('Dados existentes foram alterados.');
        echo "OK: atualização preserva contas, perfis, senhas, IDs, pedidos, estados vazios e NULL.\n";
        apply_sql($pdo,$patch);
        apply_sql($pdo,$root.'/database/infinityfree/ATUALIZAR_ETAPAS_4_E_5.sql');
        if (snapshot_rows($pdo,$columns)!==$before) throw new RuntimeException('Reaplicação alterou dados.');
        echo "OK: reaplicação não duplica colunas, tabelas ou índices.\n";
        $pdo->exec("UPDATE solicitacoes SET status='orçada' WHERE id=1");
        $pdo->exec("UPDATE solicitacoes SET status='recusada' WHERE id=2");
        echo "OK: novos estados de orçamento aceitos após a atualização.\n";
        if ((int)$pdo->query("SELECT COUNT(*) FROM usuarios WHERE tipo_pessoa='juridica'")->fetchColumn()!==2) throw new RuntimeException('Tipo das contas legadas não foi preservado.');
        $pdo->exec("INSERT INTO usuarios(id,cpf_cnpj,email,senha,tipo_conta,tipo_pessoa) VALUES (3,'12345678901','cpf@example.invalid','hash-ficticio','empresa','fisica')");
        $pdo->exec("UPDATE empresas SET tipo_empresa='mei' WHERE usuario_id=2");
        if ($pdo->query('SELECT tipo_pessoa FROM usuarios WHERE id=3')->fetchColumn()!=='fisica'
            || $pdo->query('SELECT tipo_empresa FROM empresas WHERE usuario_id=2')->fetchColumn()!=='mei') throw new RuntimeException('Novos tipos não estão disponíveis.');
        echo "OK: $kind suporta CPF e MEI, preservando pessoas jurídicas anteriores.\n";
        $pdo->exec("UPDATE solicitacoes SET status='concluida' WHERE id=1");
        $pdo->exec("UPDATE empresas SET tipo_empresa='terceirizada',mapa_publico=0,latitude=-23.5,longitude=-46.5 WHERE usuario_id=2");
        $historyCount=(int)$pdo->query('SELECT COUNT(*) FROM solicitacoes_eventos')->fetchColumn();
        apply_sql($pdo,$patch);
        apply_sql($pdo,$root.'/database/infinityfree/ATUALIZAR_ETAPAS_4_E_5.sql');
        if ($pdo->query('SELECT status FROM solicitacoes WHERE id=1')->fetchColumn()!=='concluida') throw new RuntimeException('Atualização perdeu o novo estado de orçamento.');
        if ((int)$pdo->query('SELECT mapa_publico FROM empresas WHERE usuario_id=2')->fetchColumn()!==0) throw new RuntimeException('Reaplicação republicou o mapa sem autorização.');
        if ((int)$pdo->query('SELECT COUNT(*) FROM solicitacoes_eventos')->fetchColumn()!==$historyCount) throw new RuntimeException('Reaplicação duplicou eventos.');
        echo "OK: novos estados, privacidade do mapa e eventos permanecem estáveis na reaplicação.\n";
    }
} finally {
    // Somente bancos temporários criados com sucesso por esta execução.
    foreach ($created as $name) {
        if (!preg_match('/^serviconnect_export_(?:update|stage2|empty)_[a-f0-9]{12}$/D',$name)) throw new RuntimeException('Nome de teste inválido.');
        $pdo->exec("DROP DATABASE `$name`");
    }
}
