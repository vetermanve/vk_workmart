<?php

lets_sure_loaded('core_config');

const CORE_CONFIG_REDIS_MAIN = 'main';

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
                CORE_CONFIG_REDIS_MAIN => [
                    'host' => '127.0.0.1',
                    'port' => '6379',
                    'connect_timeout'=> 1,
                    'read_timeout'=> 3,
                ],
            ],
            'db_tables' => [
                'db_part1' => [
                    '*',
                    'orders',
                    'sessions',
                ],
                'db_part2' => [
                    'users',
                    'balance',
                    'transactions',
                    'accounts',
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