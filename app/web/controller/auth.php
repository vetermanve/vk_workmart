<?php

lets_sure_loaded('web_controller_auth');
lets_use('web_render', 'web_router');

const _AUTH_HASH_SECRET = '^7sc9v6aj%%6a99s0!#d,';
const _AUTH_SALT_SECRET = 'AS@)Nsy8#,a!Rsdf^$';


function web_controller_auth_auth () {
    $userName = web_router_get_param('email');
    $pass = web_router_get_param('pass');
    
    $salt = md5(microtime(1)._AUTH_SALT_SECRET.mt_rand(1, 199999999));
    $hash = md5($pass._AUTH_HASH_SECRET.$salt);
    
    web_router_render_page('auth', 'auth', []);
}

function web_controller_auth_redirect () {
    lets_use('web_response');
    web_response_redirect('/auth/nopage');
}

function web_controller_auth_register () {
    lets_use('core_storage_db');
    
    $email = web_router_get_param('email');
    
    if (!$email) {
        web_router_render_page('auth', 'register', ['msg' => 'Введите email', 'wrong' => 'email',]);
        return ;
    }
    
    preg_match('/[\w\d]+@[\w\d]+[\w\d\.]+/', $email, $matches);
    
    if(!isset($matches[0])) {
        web_router_render_page('auth', 'register', ['msg' => 'Введите корректный email', 'wrong' => 'email',]);
        return ;
    }
    
    $authUser = core_storage_db_get_row_one('users', '*', [
        ['email', $email],
    ]);
    
    if ($authUser) {
        web_router_render_page('auth', 'register', ['msg' => 'Пользователь с таким email уже существует', 'wrong' => 'email',]);
        return ;
    }
    
    $userName = web_router_get_param('name');
    if (!$userName) {
        web_router_render_page('auth', 'register', ['msg' => 'Введите ваше имя', 'wrong' => 'name',]);
        return ;
    }
    
    $pass = web_router_get_param('pass');
    if (!$pass || mb_strlen($pass) < 6) {
        web_router_render_page('auth', 'register', ['msg' => 'Пароль должен быть задан и не менее 6ти символов', 'wrong' => 'pass',]);
        return ;
    }
    
    $salt = md5(microtime(1)._AUTH_SALT_SECRET.mt_rand(1, 199999999));
    $passHash = md5($pass._AUTH_HASH_SECRET.$salt);
    
    lets_use('core_storage_db');
    
    $res = core_storage_db_insert_row('users', [
        'name' => $userName,
        'salt' => $salt,
        'pass' => $passHash,
        'email' => $email,
    ]);
    
    if(!$res) {
        web_router_render_page('auth', 'register', ['msg' => 'Ошибка при сохранении пользвателя, повторите позднее', 'wrong' => 'error',]);
        return ;
    }
    
    web_router_redirect('/');
}

function web_controller_auth_create () {
    lets_use('core_storage_db');
    
    $res = core_storage_db_insert_row('users', ['name' => 'Test User '.date('c'),]);
    core_dump($res);
    
    
//    web_render_page('auth', 'register', []);
}