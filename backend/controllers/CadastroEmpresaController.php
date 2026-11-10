<?php
require_once __DIR__ . '/../conexao.php';
require_once __DIR__ . '/../models/UsuarioModel.php';
require_once __DIR__ . '/../opencnpj.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
require_post();
$data = json_input();
require_csrf($data);
$newPayload = array_key_exists('documento', $data);
$document = normalize_document(field_text($data, $newPayload ? 'documento' : 'cnpj', 30, true));
$email = field_text($data, 'email_acesso', 100, true);
$tipo = field_text($data, 'tipo', 30, true);
$personType = field_text($data, 'tipo_pessoa', 10) ?: 'juridica';
$nome = field_text($data, 'nome', 150, $newPayload) ?: 'Empresa em Configuração';
$senha = $data['senha'] ?? '';
$confirmation = $data['confirmar_senha'] ?? null;
if (!in_array($tipo, ['contratante','mei','terceirizada'], true)
    || !in_array($personType, ['fisica','juridica'], true)
    || ($tipo !== 'contratante' && $personType !== 'juridica')) abort_request(422, 'Selecione um tipo de conta válido. Prestadores devem informar CNPJ.');
if (!(($personType === 'fisica') ? valid_cpf($document) : valid_cnpj($document))) abort_request(422, 'Informe um '.($personType === 'fisica' ? 'CPF' : 'CNPJ').' válido.');
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !is_string($senha) || strlen($senha) < 6 || strlen($senha) > 72) abort_request(422, 'Confira o e-mail e a senha (6 a 72 bytes).');
if (($newPayload || $confirmation !== null) && (!is_string($confirmation) || $senha !== $confirmation)) abort_request(422, 'A confirmação da senha deve coincidir com a senha.');
$model = new UsuarioModel($pdo);
if ($model->existeUsuario($document, $email)) abort_request(409, 'Documento ou e-mail já cadastrado.');
// A consulta é obrigatória no servidor, inclusive para payloads antigos.
// Uma consulta anterior no navegador nunca autoriza a criação por si só.
if ($personType === 'juridica') require_active_cnpj($pdo, $document);
try {
    $pdo->beginTransaction();
    // tipo_conta mantém o valor legado; o papel vem do perfil e o documento de tipo_pessoa.
    $id = $model->cadastrarUsuario(['cpf_cnpj'=>$document,'email'=>$email,'senha'=>password_hash($senha,PASSWORD_DEFAULT),'tipo_conta'=>'empresa','tipo_pessoa'=>$personType]);
    $empresa = array_fill_keys(['telefone','responsavel','descricao','regiao','horario','cep','logradouro','numero','complemento','bairro','cidade','estado'], '');
    $empresa += ['usuario_id'=>$id,'nome'=>$nome,'tipo_empresa'=>$tipo];
    $model->cadastrarEmpresa($empresa);
    $pdo->commit();
    echo json_encode(['success'=>true,'message'=>'Conta criada com sucesso. Entre para completar seu perfil.']);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    if ($e->getCode() === '23000') abort_request(409, 'Documento ou e-mail já cadastrado.');
    error_log('Cadastro: '.$e->getMessage());
    abort_request(500, 'Não foi possível cadastrar. Tente novamente.');
}
