<?php

global $_letsLoadCache;

$_letsLoadCache = [];

register_shutdown_function(function () {
    $error = error_get_last();
    if ($error) {
        var_dump($error);
    }
});

// require-style autoloader
function lets_use ($moduleName, $anotherModule = null) {
    static $loadedModules;
    global $_letsLoadCache;
    $modules = $anotherModule ? func_get_args() : [$moduleName];
    
    foreach ($modules as $module) {
        if (!isset($loadedModules[$module])) {
            
            if(strpos($module, '_')) {
                $requiredFile =  'app/'.str_replace('_', '/', $module).'.php';    
            } else{
                $requiredFile =  'app/'.$module.'/_root.php';
            }
            
            if (!file_exists($requiredFile)) {
                _lets_report_load_error('File not found', $module, $requiredFile);
            }
            
            $startLoadTime = microtime(1);
            require_once ($requiredFile);
            
            if (!isset ($_letsLoadCache[$module])) {
                _lets_report_load_error('Required file not provide @lets_sure_loaded', $module, $requiredFile);    
            }
            
            $loadedModules[$module] = microtime(1) - $startLoadTime;
        }
    }
};

function lets_sure_loaded($moduleName) {
    global $_letsLoadCache;
    $_letsLoadCache[$moduleName] = 1;
}

function _lets_report_load_error($error, $moduleName, $location) {
    trigger_error('Module "'.$moduleName .'" not loaded. '.$error.' at location '.$location);
}


