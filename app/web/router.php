<?php

lets_sure_loaded('web_router');

lets_use('web_render');

function web_router_route($uri, $get, $post) {
    $uri = strpos($uri, '?') ? strstr($uri, '?', true) : $uri;
    
    $pathInfo = explode('/', trim($uri, '/'));
    
    $controller = !empty($pathInfo[0]) ? $pathInfo[0] : 'index';
    $action     = !empty($pathInfo[1]) ? $pathInfo[1] : 'index';
    
    $controllerName = str_replace(['_', '/'], '', $controller );
    $actionName = str_replace(['_', '/'], '', $action );
    
    $module = 'web_controller_'.$controllerName;
    lets_use($module);
    $function = $module.'_'.$actionName;
    
    if (!function_exists($function)) {
        return web_router_notfound($uri);
    }
    
    return web_render_page($controller, $action, array_merge($post, $get));
}

function web_router_notfound ($uri) {
    return web_render_page('error', 'notFound', ['uri' => $uri]);
}
