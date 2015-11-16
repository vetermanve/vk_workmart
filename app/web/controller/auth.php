<?php

lets_sure_loaded('web_controller_auth');
lets_use('web_render', 'web_router');

const _AUTH_HASH_SECRET = '^7sc9v6aj%%6a99s0!#d,';
const _AUTH_SALT_SECRET = 'AS@)Nsy8#,a!Rsdf^$';


function web_controller_auth_auth () {
    $email = web_router_get_param('email');
    $pass = web_router_get_param('pass');
    
    if (web_router_get_method() === 'POST') {
        lets_use('user_register');
        
        $userId = user_register_get_user_id_by_email($email);
        if ($userId) {
            lets_use('user_session');
            core_log('user found: '.$userId);
            
            $realSecret = user_session_get_secret($userId);
            $checkSecret = user_session_build_secret($pass);
    
            if ($realSecret === $checkSecret) {
                $token = user_session_build_token($userId, $checkSecret);
                user_session_write_session_cookie($userId, $token, 86400*30);
    
                web_response_redirect('/');
                return ;
            }
        }
        
        web_router_render_page('auth', 'auth', [
            'msg' => 'Для данного адреса почты и пароля не найдено ни одного пользователя.',
        ]);
        
        return ;
    }
    
    web_router_render_page('auth', 'auth');
}

function web_controller_auth_logout() {
    lets_use('user_session');
    user_session_write_session_cookie(null, null, -1);
    web_response_redirect('/');
}

function web_controller_auth_register () {
    
    if (web_router_get_method() !== 'POST') {
        web_router_render_page('auth', 'register', []);
        return ;
    }
    
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
    
    $authUserId = user_register_get_user_id_by_email($email);
    
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
    
    $secret = user_session_get_secret($userId);
    $token  = user_session_build_token($userId, $secret);
    
    user_session_write_session_cookie($userId, $token, 86400 * 30);
    
    web_router_redirect('/');
}