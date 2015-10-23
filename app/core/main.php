<?php

function core_main_init($config) {
    core_main_log('core init');
}

function core_main_log($data) {
    echo $data."\n";    
}

function core_main_error($data) {
    core_main_log('Error! '.$data);
}
