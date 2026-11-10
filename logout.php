<?php
require_once __DIR__ . '/backend/security.php';
require_post();
require_csrf();
$_SESSION = [];
$params = session_get_cookie_params();
setcookie(session_name(), '', ['expires'=>time()-42000,'path'=>$params['path'],'secure'=>$params['secure'],'httponly'=>true,'samesite'=>'Lax']);
session_destroy();
header('Location: /login.php',true,303);
