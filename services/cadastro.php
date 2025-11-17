<?php

if(empty($_POST['nome'])){
    die('Nome é obrigatório.');
}

if(! filter_var($_POST['email'], FILTER_VALIDADTE_EMAIL)){
    die('E-mail válido é obrigatório.');
}

if(strlen($_POST['senha']<6)){
    die('Mínimo de 6 caracteres');
}

if($_POST['senha'] !== $_POST['confirmacao_senha']){
    die('Senhas não se condizem');
}

$senha_hash = password_hash($_POST["senha"], PASSWORD_DEFAULT);

$mysqli = require __DIR__ ."/../database/database.php;

$sql = "INSERT INTO usuario (nome, email, cnpj, senha_hash)
    VALUES (?,?,?,?,?,?);

$stmt = $mysqli->stmt_init();

if( ! $stmt->prepare($sql)){
    die("SQL error:" . $mysqli->error);

$stmt->bind_param("ssssss",
                    $_POST['nome'],
                    $_POST['email'],
                    $_POST['cnpj'],
                    $senha_hash;

if ($stmt->execute()){
    header("Location: ../views/login.php");
    exit;
}else{
    if($mysqli->errno == 1062){
        die("E-mail já utilizado");
    }else{
        die($mysqli->error. " ". $mysqli->errno)
    }
}
}