<?php

lets_sure_loaded('core_shutdown');

global $_core_shutdown_callbacks;
global $_core_shutdown_registered;

$_core_shutdown_callbacks = [];

function core_shutdown_add_check ($name, $callable, $replace = true) {
    static $registered;
    global $_core_shutdown_callbacks;
    
    if (isset($_core_shutdown_callbacks[$name]) && !$replace) {
        return ;
    }
    
    if (!$registered) {
        register_shutdown_function('core_shutdown_process_checks');
    }
    
    if (!is_callable($callable)) {
        core_error('trying to add not callable shutdown callback with name '.$name);
        return ;
    }
    
    $_core_shutdown_callbacks[$name] = $callable;
}

function core_shutdown_remove_check ($name) {
    global $_core_shutdown_callbacks;
    
    unset($_core_shutdown_callbacks[$name]);
}

function core_shutdown_process_checks () {
    global $_core_shutdown_callbacks;
    
    if (empty($_core_shutdown_callbacks)) {
        return ;   
    }
    
    foreach ($_core_shutdown_callbacks as $name => $callback) {
        try {
            $callback();    
        } catch (Exception $e) {
            core_error($e->getMessage().'; on shutdown callback :'.$name);       
        }
    }
}