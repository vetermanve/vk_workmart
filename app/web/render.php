<?php

lets_sure_loaded('web_render');

lets_use('core');

$_web_render_global_params = [];

function web_render_page_content($module, $template, $data = [], $layout = 'main') {
    global $_web_render_global_params;
    
    $templateFile = __DIR__.'/templates/'.$module.'/'.$template.'.phtml';
    $layoutFile = __DIR__.'/templates/layouts/'.$layout.'.phtml';
    
    if (!file_exists($templateFile)) {
        core_error('tpl file: '.$templateFile.' not found');
        return '';
    }
    
    if (!file_exists($layoutFile)) {
        core_error('layout file: '.$layoutFile.' not found');
        return '';
    }
    
    ob_start();
    extract((array)$data);
    extract((array)$_web_render_global_params);
    require $templateFile;
    $content = ob_get_clean();
    ob_start();
    require $layoutFile;
    $result = ob_get_clean();
    return $result;
}

function web_render_add_data ($key, $data) {
    global $_web_render_global_params;
    
    $_web_render_global_params[$key] = $data;
}

function web_render_add_data_array ($paramsArray) {
    global $_web_render_global_params;
    
    $_web_render_global_params = $paramsArray + $_web_render_global_params; 
}
