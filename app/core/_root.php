<?php

lets_sure_loaded('core');

function core_init($appRole) {
    lets_use('core_config');
    core_config_load();
}

function core_error($data) {
    core_log('Error! '.$data);
}

function core_log($sting) {
    echo trim($sting)."\n";
}

function core_dump($data, $data2 = null) {
    echo '<pre>';
    var_dump(func_get_args());
    echo '</pre>';
}

function _core_error_handler($code, $msg, $context, $line) {
    static $cwd;
    
    if(!$cwd) {
        $cwd = getcwd();
    }
    
    $prevErrorLvl = error_reporting(0);
    
    $trace = array_slice(debug_backtrace(), 2, 8);
    
    foreach ($trace as $id => $traceData) {
        $trace[$id] = str_replace($cwd, '', $traceData['file']).':'.$traceData['line'].' => '.$traceData['function'];
    }
    
    error_reporting($prevErrorLvl);
    
    core_dump($msg, $line,  $context, $trace);
}

set_error_handler('_core_error_handler', E_ALL);