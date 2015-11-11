<?php

lets_sure_loaded('core');

global $_core_internal_log;

$_core_internal_log = [];

function core_init($appRole) {
    lets_use('core_config');
    core_config_load();
}

function core_error($data, $function = '') {
    core_log('Error! '.$data.' in @'.$function);
}

function core_log($sting, $function = '') {
    global $_core_internal_log;
    $_core_internal_log[] =($function ? '['.$function.'] ' : ''). trim($sting);
}

function core_get_log() {
    global $_core_internal_log;
    return $_core_internal_log;
}

function core_has_log() {
    global $_core_internal_log;
    
    return count($_core_internal_log);
}

function core_dump($data, $data2 = null) {
    ob_start();
    foreach (func_get_args() as $val) {
        var_dump($val);    
    }
    core_log(ob_get_clean());
}

function _core_error_handler($code, $msg, $lie, $line) {
    static $cwd;
    
    if (!$cwd) {
        $cwd = getcwd();
    }
    
    $prevErrorLvl = error_reporting(0);
    
    $trace = array_slice(debug_backtrace(), 2, 8);
    
    foreach ($trace as $id => $traceData) {
        $trace[$id] = str_replace($cwd, '', $traceData['file']).':'.$traceData['line'].' => '.$traceData['function'];
    }
    
    error_reporting($prevErrorLvl);
    
    core_dump($msg , $lie . ':' . $line, $trace);
}

set_error_handler('_core_error_handler', E_ALL);