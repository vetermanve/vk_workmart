<?php

lets_use('core_main');

function web_render_page($module, $template, $data, $layout = 'main') {
    $templateFile = __DIR__.'/templates/'.$module.'/'.$template.'.tpl';
    $layoutFile = __DIR__.'/templates/layouts/'.$layout.'.tpl';
    
    if (!file_exists($templateFile)) {
        core_main_log('tpl file: '.$templateFile.' not found');
        return '';
    }
    
    if (!file_exists($layoutFile)) {
        core_main_log('layout file: '.$layoutFile.' not found');
        return '';
    }
    
    ob_start();
    extract($data);
    require $templateFile;
    $content = ob_get_clean();
    require $layoutFile;
}