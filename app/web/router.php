<?php

lets_sure_loaded('web_router');

lets_use('web_render', 'web_response');

global $_web_router_request_data;

$_web_router_request_data = [];

function web_router_route($uri, $get, $post) {
    global $_web_router_request_data;
    
    $_web_router_request_data = (array)$get + (array)$post;
    
    $uri = strpos($uri, '?') ? strstr($uri, '?', true) : $uri;
    
    $pathInfo = explode('/', trim($uri, '/'));
    
    $controllerPart = !empty($pathInfo[0]) ? $pathInfo[0] : 'index';
    $actionPart     = !empty($pathInfo[1]) ? $pathInfo[1] : 'index';
    
    $controller = str_replace(['_', '/', ' '], '', $controllerPart);
    $action     = str_replace(['_', '/', ' '], '', $actionPart);
    
    web_router_call($controller, $action, $uri);
}

function web_router_call($controller, $action, $uri) {
    $module = 'web_controller_'.$controller;
    lets_use($module);
    
    // pre dispatch
    $function = $module.'_precall';
    
    if (function_exists($function)) {
        try {
            $function();
        } catch (Exception $e) {
            web_router_error($e->getMessage()); // @todo show error only in debug
            return ;
        }
    }
    
    // dispatch
    $function = $module.'_'.$action;
    
    if (!function_exists($function)) {
        web_router_notfound($uri);
        return ;
    }
    
    try {
        $function();
    } catch (Exception $e) {
        web_router_error('');
        return ;
    }
}

function web_router_get_param($key, $default = null) {
    global $_web_router_request_data;
    
    return isset($_web_router_request_data[$key]) ? $_web_router_request_data[$key] : $default; 
}

function web_router_notfound ($uri = '') {
    web_response_set_http_code(404);
    web_response_set_content_type('text/html');
    web_response_set_body(web_render_page_content('error', 'notFound', ['uri' => $uri]));
    web_response_flush();
}

function web_router_error ($msg) {
    web_response_set_http_code(500);
    web_response_set_content_type('text/html');
    web_response_set_body(web_render_page_content('error', 'error', ['msg' => $msg]));
    web_response_flush();
}

function web_router_render_page($module, $template, $data = [], $layout = 'main') {
    web_response_set_http_code(200);
    web_response_set_content_type('text/html');
    web_response_set_body(web_render_page_content($module, $template, $data, $layout));
    web_response_flush();
}

function web_router_redirect ($location) {
    web_response_redirect($location);
}

function web_router_get_method () {
    return isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
}