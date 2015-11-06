<?php

lets_sure_loaded('user_session');

/* redis */
const USER_SESSION_REDIS_KEY_PREFIX = 'session';
const USER_SESSION_REDIS_MISSING_RECORD = '-1';

/* session table */
const USER_SESSION_DB_TABLE= 'sessions';

/* session secrets */
const USER_SESSION_TOKEN_SECRET = 'Bo)(Hc9an5,234yTXTdrf78IF*(^FV*A%#@UK3N>ZAas4(BV*(N@<JBV*A^%WgFhbc)*6KIVXt#.12=bcLKAksgfd;';
const USER_SESSION_SECRET_GEN = 'ABBVHJxnc^aH>KJ#$fjcb^A$IGbfvyu6a4JKBV76(*&A3br4tll"(VY&T*^YG#MKVPNY(T*&A4 saas';

/* cookie fields */
const USER_SESSION_COOKIE_UID = 'uid';
const USER_SESSION_COOKIE_TOKEN = 'token';

function user_session_init()
{
    $userId    = isset($_COOKIE[USER_SESSION_COOKIE_UID]) ? (int)$_COOKIE[USER_SESSION_COOKIE_UID] : null;
    $authToken = isset($_COOKIE[USER_SESSION_COOKIE_TOKEN]) ? $_COOKIE[USER_SESSION_COOKIE_TOKEN] : null;
    
    if (!$userId || !$authToken) {
        return false;
    }
    
    return user_session_check_token($userId, $authToken);
}

function user_session_get_current_user()
{
    return user_session_init();
}

function user_session_check_token($userId, $authToken)
{
    $secret = user_session_get_secret($userId);
    $token = user_session_build_token($userId, $secret);
    
    if ($authToken === $token) {
        return $userId;
    }
    
    core_log('auth token is not valid', __FUNCTION__);
    
    return false;
}

function user_session_get_secret($userId) {
    lets_use('core_config', 'core_storage_nosql');
    
    $secret = core_storage_nosql_get_prefix(CORE_CONFIG_REDIS_MAIN, USER_SESSION_REDIS_KEY_PREFIX, $userId);
    
    if ($secret === USER_SESSION_REDIS_MISSING_RECORD) {
        core_log('cache stored missing secret for user:'.$userId);
        return false;
    }
    
    if (!$secret) {
        lets_use('core_storage_db');
        
        $secret = core_storage_db_get_value(USER_SESSION_DB_TABLE, 'secret', [
            ['user_id', $userId],
        ]);
    
        core_log('secret found in db: '.$secret);
        
        core_storage_nosql_set_prefix(CORE_CONFIG_REDIS_MAIN, USER_SESSION_REDIS_KEY_PREFIX, $userId, 
            $secret 
                ? $secret 
                : USER_SESSION_REDIS_MISSING_RECORD
        );
    }
    
    return $secret;
}

function user_session_set_secret($userId, $secret) {
    lets_use('core_config', 'core_storage_db', 'core_storage_nosql');
    
    $dbResult = core_storage_db_insert_row(USER_SESSION_DB_TABLE, [
        'user_id' => $userId,
        'secret' => $secret,
    ]);
    
    if (!$dbResult) {
        core_error('cannot write new session to db: '.core_storage_db_get_last_error(USER_SESSION_DB_TABLE));
        return false;
    }
    
    core_storage_nosql_set_prefix(CORE_CONFIG_REDIS_MAIN, USER_SESSION_REDIS_KEY_PREFIX, $userId, null);
    
    return true;
}

function user_session_build_secret($secretSource) {
    return md5(USER_SESSION_SECRET_GEN.$secretSource.USER_SESSION_SECRET_GEN);
}

function user_session_build_token ($userId, $secret) {
    return sha1(USER_SESSION_TOKEN_SECRET.$secret.$userId);
}

function user_session_create_token($userId, $secretSource = null) {
    lets_use('core_config', 'core_storage_nosql');
    
    if (!$secretSource) {
        $secretSource = md5(microtime(1).mt_rand(1, 99999999).microtime(1));
    }
    
    $secret = user_session_build_secret($secretSource);
    $setResult = user_session_set_secret($userId, $secret);
    
    if (!$setResult) {
        return false;
    }
    
    return user_session_build_token($userId, $secret);
}

function user_session_write_session_cookie($userId, $token, $ttl = 86400) {
    lets_use('web_response');
    
    web_response_set_cookie(USER_SESSION_COOKIE_UID, $userId, $ttl);
    web_response_set_cookie(USER_SESSION_COOKIE_TOKEN, $token, $ttl);
}