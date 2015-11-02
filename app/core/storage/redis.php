<?php

lets_sure_loaded('core_storage_redis');

/**
 * @param $redisId
 *
 * @return \Redis|bool
 */
function core_storage_redis_connection ($redisId) {
    static $config;
    static $connections;
    
    lets_use('core_config');
    
    if (isset($connections[$redisId])) {
        return $connections[$redisId];
    }
    
    if (!isset($config)) {
        $config = core_config_get('redis', []);
    }
    
    $connection = new Redis();
    
    $connected = $connection->connect($config['host'], $config['port'], $config['connect_timeout']);
    
    if (!$connected) {
        core_error('Cannot connect redis driver');
        return false;
    }
    
    $this->setOption(Redis::OPT_READ_TIMEOUT, $config['read_timeout']);
    
    return $connections[$redisId] = $connection;
    
}

public function core_storage_redis_get($serverId, $key)
{
    
}