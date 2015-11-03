<?php

lets_sure_loaded('user_session');

const USER_SESSION_REDIS_KEY = 'session';
const USER_SESSION_DB_TABLE= 'sessions';

const USER_SESSION_TOKEN_SECRET = 'Bo)(Hc9an5,234yTXTdrf78IF*(^FV*A%#@UK3N>ZAas4(BV*(N@<JBV*A^%WgFhbc)*6KIVXt#.12=bcLKAksgfd;';
const USER_SESSION_SECRET_GEN = 'ABBVHJxnc^aH>KJ#$fjcb^A$IGbfvyu6a4JKBV76(*&A3br4tll"(VY&T*^YG#MKVPNY(T*&A4 saas';

const USER_SESSION_COOKIE_UID = 'uid';
const USER_SESSION_COOKIE_TOKEN = 'token';


function user_session_init()
{
    $userId    = isset($_COOKIE[USER_SESSION_COOKIE_UID]) ? (int)$_COOKIE[USER_SESSION_COOKIE_UID] : null;
    $authToken = isset($_COOKIE[USER_SESSION_COOKIE_TOKEN]) ? $_COOKIE[USER_SESSION_COOKIE_TOKEN] : null;
    
    return user_session_check_token($userId, $authToken);
}

function user_session_get_current_user()
{
    return user_session_init();
}

function user_session_check_token($userId, $authToken)
{
    lets_use('core_config', 'core_storage_nosql');
    
    $secret = core_storage_nosql_get_prefix(CORE_CONFIG_REDIS_MAIN, USER_SESSION_REDIS_KEY, $userId);
    
    if (!$secret) {
        lets_use('core_storage_db');
    
        $secret = core_storage_db_get_value(USER_SESSION_DB_TABLE, 'secret', [
            'user_id' => $userId,
        ]);
        
        if (!$secret) {
            return false;
        }
        
        core_storage_nosql_set_prefix(CORE_CONFIG_REDIS_MAIN, USER_SESSION_REDIS_KEY, $userId, $secret);
    }
    
    $token = sha1(USER_SESSION_TOKEN_SECRET.$secret.$userId);
    
    if ($authToken === $token) {
        return $userId;
    }
    
    return false;
}

function user_session_get_user_token ($userId) {
    $secret = core_storage_db_get_value(USER_SESSION_DB_TABLE, 'secret', [
        'user_id' => $userId,
    ]);
    
    return sha1(USER_SESSION_TOKEN_SECRET.$secret.$userId);
}

function user_session_create_token($userId, $secretSource = null) {
    lets_use('core_config', 'core_storage_nosql');
    
    if (!$secretSource) {
        $secretSource = md5(microtime(1).mt_rand(1, 99999999).microtime(1));
    }
    
    $secret = md5(USER_SESSION_SECRET_GEN.$secretSource.USER_SESSION_SECRET_GEN);

    $data = core_storage_db_insert_row(USER_SESSION_DB_TABLE, [
        'user_id' => $userId,
        'secret' => $secret,
    ]);
    
    if (!$data) {
        core_error('cannot write new session to db: '.core_storage_db_get_last_error(USER_SESSION_DB_TABLE));
        return false;
    }
        
    // clear cache
    core_storage_nosql_set_prefix(CORE_CONFIG_REDIS_MAIN, USER_SESSION_REDIS_KEY, $userId, null);
    
    return sha1(USER_SESSION_TOKEN_SECRET.$secret.$userId);
}

function user_session_write_session_cookie($userId, $token, $ttl = 86400) {
    lets_use('web_response');
    
    web_response_set_cookie(USER_SESSION_COOKIE_UID, $userId, $ttl);
    web_response_set_cookie(USER_SESSION_COOKIE_TOKEN, $token, $ttl);
}