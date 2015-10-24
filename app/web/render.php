<?php

lets_sure_loaded('web_render');

lets_use('core');

function web_render_page($module, $template, $data, $layout = 'main') {
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
    extract($data);
    require $templateFile;
    $content = ob_get_clean();
    require $layoutFile;
}