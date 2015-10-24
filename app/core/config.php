<?php

lets_sure_loaded('core_config');

function core_config_data () {
    return [
        'db' => [
            'host' => '127.0.0.1',
            'port' => '3606', //fixme
        ],
        'redis' => [
            'host' => '127.0.0.1',
            'port' => '3738', //fixme
        ],
    ];
    
}
