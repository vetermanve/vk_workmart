<?php

// require-style autoloader
function lets_use ($moduleName, $anotherModule = null) {
    static $loadedModules;
    $modules = $anotherModule ? func_get_args() : [$moduleName];
    
    foreach ($modules as $module) {
        if (!isset($loadedModules[$module])) {
            $requiredFile =  'app/'.str_replace('_', '/', $module).'.php';
            
            $startLoadTime = microtime(1);
            require_once ($requiredFile);
            $loadedModules[$module] = microtime(1) - $startLoadTime;
        }
    }
};
