<?php

lets_sure_loaded('core');

function core_init($appRole) {
    
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

function _core_error_handler($code, $msg, $context) {
    $trace = debug_backtrace();
    core_dump($code, $msg, $context, $trace);
}

set_error_handler('_core_error_handler', E_ALL);