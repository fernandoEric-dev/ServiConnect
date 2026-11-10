<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$root=dirname(__DIR__);$source=file_get_contents($root.'/database/migrations/005_orcamentos_perfis.sql');
function upgrade_ddl(string $table,string $column,string $definition):string{
    $ddl=str_replace("'","''","ALTER TABLE $table ADD COLUMN $column $definition");
    return "SET @serviconnect_ddl = IF((SELECT COUNT(*)=0 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='$table' AND COLUMN_NAME='$column'), '$ddl', 'SELECT 1');\nPREPARE serviconnect_stmt FROM @serviconnect_ddl;\nEXECUTE serviconnect_stmt;\nDEALLOCATE PREPARE serviconnect_stmt;\n\n";
}
$sql="-- SERVICONNECT ETAPAS 4 E 5: ORCAMENTOS, PERFIS E MAPA.\n-- Atualizacao incremental. Nao apaga usuarios, perfis, senhas ou pedidos.\n-- Exporte um backup atualizado e selecione o banco do site antes de executar.\nSET NAMES utf8mb4;\nCREATE TABLE IF NOT EXISTS schema_migrations (versao VARCHAR(100) PRIMARY KEY, aplicada_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP);\n\n";
preg_match_all('/ALTER TABLE (empresas|solicitacoes)\s+([\s\S]*?);/',$source,$alters,PREG_SET_ORDER);
$count=0;
foreach($alters as $alter){
    preg_match_all('/^\s*ADD COLUMN (\w+) (.+)$/m',$alter[2],$columns,PREG_SET_ORDER);
    foreach($columns as $column){$sql.=upgrade_ddl($alter[1],$column[1],rtrim(trim($column[2]),','));$count++;}
}
if($count!==24)throw new RuntimeException('Quantidade inesperada de colunas da migracao.');
preg_match('/MODIFY status (ENUM\([^\r\n]+\)) DEFAULT \'aberta\'/',$source,$status);
$statusDdl="ALTER TABLE solicitacoes MODIFY status ".$status[1]." DEFAULT 'aberta';";
$sql.=$statusDdl."\n\n";
foreach(['idx_solicitacao_chave'=>"CREATE UNIQUE INDEX idx_solicitacao_chave ON solicitacoes(chave_criacao)",'idx_solicitacao_contratante_status'=>"CREATE INDEX idx_solicitacao_contratante_status ON solicitacoes(contratante_id,status,id)"] as $index=>$ddl){
    $sql.="SET @serviconnect_ddl = IF((SELECT COUNT(*)=0 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='solicitacoes' AND INDEX_NAME='$index'), '$ddl', 'SELECT 1');\nPREPARE serviconnect_stmt FROM @serviconnect_ddl;\nEXECUTE serviconnect_stmt;\nDEALLOCATE PREPARE serviconnect_stmt;\n\n";
}
preg_match_all('/CREATE TABLE (empresa_servicos|solicitacoes_eventos) \([\s\S]*?;/',$source,$tables);
foreach($tables[0] as $table)$sql.=str_replace('CREATE TABLE ','CREATE TABLE IF NOT EXISTS ',$table)."\n\n";
$sql.="UPDATE empresas SET mapa_publico=1 WHERE tipo_empresa='terceirizada' AND latitude IS NOT NULL AND longitude IS NOT NULL AND NOT EXISTS(SELECT 1 FROM schema_migrations WHERE versao='005_orcamentos_perfis.sql');\n\n";
$sql.="INSERT INTO solicitacoes_eventos(solicitacao_id,autor_nome,papel,tipo,status_para,mensagem)\n SELECT s.id,'Sistema','sistema','historico_iniciado',s.status,'Historico ativado para um pedido anterior. Movimentos anteriores nao foram registrados.' FROM solicitacoes s WHERE NOT EXISTS(SELECT 1 FROM solicitacoes_eventos e WHERE e.solicitacao_id=s.id);\n\nINSERT IGNORE INTO schema_migrations(versao) VALUES('005_orcamentos_perfis.sql');\n";
file_put_contents($root.'/database/infinityfree/ATUALIZAR_ETAPAS_4_E_5.sql',$sql);
$current=file_get_contents($root.'/database/infinityfree/ATUALIZAR_BANCO_ATUAL.sql');
$current=explode('-- SERVICONNECT ETAPAS 4 E 5:',$current)[0];
$current=preg_replace("/ALTER TABLE solicitacoes MODIFY status ENUM\([^\r\n;]+\) DEFAULT 'aberta';/",$statusDdl,$current);
file_put_contents($root.'/database/infinityfree/ATUALIZAR_BANCO_ATUAL.sql',rtrim($current)."\n\n".$sql);
$empty="-- INSTALACAO NOVA. EXCLUSIVO PARA BANCO VAZIO. SEM USUARIOS DE DEMONSTRACAO.\nSET NAMES utf8mb4;\n\n";
foreach(['001_base.sql','002_integridade.sql','003_preservar_status_legado.sql','004_cadastro_perfis.sql'] as $migration)$empty.=file_get_contents($root.'/database/migrations/'.$migration)."\n\n";
$empty.="CREATE TABLE IF NOT EXISTS schema_migrations(versao VARCHAR(100) PRIMARY KEY,aplicada_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP);\nINSERT IGNORE INTO schema_migrations(versao) VALUES('001_base.sql'),('002_integridade.sql'),('003_preservar_status_legado.sql'),('004_cadastro_perfis.sql');\n\n".$sql;
file_put_contents($root.'/database/infinityfree/BANCO_NOVO_VAZIO.sql',$empty);
echo "SQL incremental, consolidado e instalacao vazia preparados.\n";
