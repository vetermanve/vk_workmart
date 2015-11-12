<?php

global $_lets_load_recheck;
global $_lets_load_lock;

$_lets_load_recheck = [];

register_shutdown_function(function () {
    $error = error_get_last();
    if ($error) {
        var_dump($error);
    }
});

// require-style autoloader
function lets_use ($moduleName, $anotherModule = null) {
    global $_lets_load_lock;
    global $_lets_load_recheck;
    
    $modules = $anotherModule ? func_get_args() : [$moduleName];
    
    foreach ($modules as $module) {
        if (!isset($_lets_load_lock[$module])) {
            
            if(strpos($module, '_')) {
                $requiredFile =  'app/'.str_replace('_', '/', $module).'.php';    
            } else{
                $requiredFile =  'app/'.$module.'/_root.php';
            }
            
            if (!file_exists($requiredFile)) {
                _lets_report_load_error('File not found', $module, $requiredFile);
                return ;
            }
            
            $startLoadTime = microtime(1);
            require_once ($requiredFile);
            
            if (!isset ($_lets_load_recheck[$module])) {
                _lets_report_load_error('Required file not provide @lets_sure_loaded', $module, $requiredFile);    
            }
            
            $_lets_load_lock[$module] = microtime(1) - $startLoadTime;
        }
    }
};

function lets_sure_loaded($moduleName) {
    global $_lets_load_recheck;
    $_lets_load_recheck[$moduleName] = 1;
}

function lets_load_get_stats() {
    global $_lets_load_recheck;
    return $_lets_load_recheck;
}

function _lets_report_load_error($error, $moduleName, $location) {
    trigger_error('Module "'.$moduleName .'" not loaded. '.$error.' at location '.$location);
}


