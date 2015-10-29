<?php

lets_sure_loaded('core_config');

global $_core_config_data;

function core_config_load () {
    global $_core_config_data;
    
    if (!$_core_config_data) {
        $_core_config_data = [
            'db' => [
                'db_part1' => [
                    'host' => '127.0.0.1',
                    'port' => '3606', //fixme
                    'user' => 'lets_db',
                    'pass' => 'K&25^Ldf^&A9((&sd%#',
                    'db_name' => 'lets_db_1',
                ],
                'db_part2' => [
                    'host' => '127.0.0.1',
                    'port' => '3606', //fixme
                    'user' => 'lets_db',
                    'pass' => 'K&25^Ldf^&A9((&sd%#',
                    'db_name' => 'lets_db_2',
                ],
            ],
            'redis' => [
                'host' => '127.0.0.1',
                'port' => '3738', //fixme
            ],
            'db_tables' => [
                'db_part1' => [
                    '*',
                    'users',
                ],
                'db_part2' => [
                    'orders',
                ],
            ],
        ];    
    }
    
    return $_core_config_data;
}

function core_config_get($key, $default = null) {
    global $_core_config_data;
    
    return isset ($_core_config_data[$key]) ? $_core_config_data[$key] : $default; 
}