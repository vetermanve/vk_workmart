<?php

lets_sure_loaded('web_controller_auth');
lets_use('web_render', 'web_router');

const _AUTH_HASH_SECRET = '^7sc9v6aj%%6a99s0!#d,';
const _AUTH_SALT_SECRET = 'AS@)Nsy8#,a!Rsdf^$';


function web_controller_auth_auth () {
//    $userName = web_router_get_param('email');
//    $pass = web_router_get_param('pass');
//    
//    $salt = md5(microtime(1)._AUTH_SALT_SECRET.mt_rand(1, 199999999));
//    $hash = md5($pass._AUTH_HASH_SECRET.$salt);
//    
//    web_router_render_page('auth', 'auth', []);
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
    
    lets_use('user_register');
    
    $authUserId = user_register_check_email_exists($email);
    
    if ($authUserId) {
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
    
    $userId = user_register_new_user($userName, $email, $pass);
    
    if (!$userId) {
        web_router_render_page('auth', 'register', ['msg' => 'Ошибка при сохранении пользвателя, повторите позднее', 'wrong' => 'error',]);
        return ;
    }
    
    lets_use('user_session');
    
    user_session_write_session_cookie($userId, user_session_get_user_token($userId), 86400 * 30);
    
    web_router_redirect('/');
}