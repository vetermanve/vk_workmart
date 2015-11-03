<?php

lets_sure_loaded('web_response');

const _WEB_RESPONSE_BODY         = 'body';
const _WEB_RESPONSE_CODE         = 'code';
const _WEB_RESPONSE_CONTENT_TYPE = 'content_type';
const _WEB_RESPONSE_HEADERS      = 'headers';

global $_web_response_content_proto;
global $_web_response_content;
global $_web_response_lock;
global $_web_response_cookie;

$_web_response_content_proto = [
    _WEB_RESPONSE_CODE         => 200,
    _WEB_RESPONSE_CONTENT_TYPE => '',
    _WEB_RESPONSE_HEADERS      => [],
    _WEB_RESPONSE_BODY         => '',
];

$_web_response_content = $_web_response_content_proto;
$_web_response_cookie = [];

$_web_response_lock = false;

function web_response_flush () {
    global $_web_response_lock;
    global $_web_response_content;
    global $_web_response_cookie;
    
    // check response already sent
    if ($_web_response_lock) {
        core_error('trying to flush on locked response');
        return ; 
    }
    
    // lock response, to prevent second sending 
    $_web_response_lock = true;
    
    // set http code =)
    http_response_code($_web_response_content[_WEB_RESPONSE_CODE]);
    
    // first of all send content type if set
    if ($_web_response_content[_WEB_RESPONSE_CONTENT_TYPE]) {
        header('Content-Type: '.$_web_response_content[_WEB_RESPONSE_CONTENT_TYPE].'; charset=utf-8');    
    }
    
    if ($_web_response_cookie) {
        foreach ($_web_response_cookie as $name => $params) {
            call_user_func_array('setcookie', $params);
        }
    }
    
    // send additional headers if set
    if ($_web_response_content[_WEB_RESPONSE_HEADERS]) {
       foreach ($_web_response_content[_WEB_RESPONSE_HEADERS] as $header) {
            header($header, true);      
       }
    }
    
    // send body if set
    if ($_web_response_content[_WEB_RESPONSE_BODY]) {
        echo $_web_response_content[_WEB_RESPONSE_BODY];
    }
}

function web_response_clear() {
    global $_web_response_content;
    global $_web_response_content_proto;
    
    $_web_response_content = $_web_response_content_proto;
} 


function web_response_redirect ($uri, $host = null, $code = 302) {
    $host = $host ? $host : $_SERVER['HTTP_HOST'];
    
    web_response_clear();
    
    web_response_add_header('Location: http://'.$host.'/'.ltrim($uri,'/'));
    web_response_set_http_code($code);
    web_response_flush();
}


function web_response_set_body ($body) {
    global $_web_response_content;
    
    $_web_response_content[_WEB_RESPONSE_BODY] = $body;
}

function web_response_set_content_type($type) {
    global $_web_response_content;
    
    $_web_response_content[_WEB_RESPONSE_CONTENT_TYPE] = $type;
}

function web_response_set_http_code($code) {
    global $_web_response_content;
    
    $_web_response_content[_WEB_RESPONSE_CODE] = $code;
}

function web_response_add_header($header, $name = null) {
    global $_web_response_content;
    
    if ($name) {
        $_web_response_content[_WEB_RESPONSE_HEADERS][] = $header;     
    } else{
        $_web_response_content[_WEB_RESPONSE_HEADERS][$name] = $header;
    }
}

function web_response_set_cookie ($key, $value, $ttl = 86400, $path = '/', $domain = null) {
    global $_web_response_cookie;
    
    $expire = time() + $ttl;
    
    $_web_response_cookie[$key] = [$key, $value, $expire, $path, $domain];
}