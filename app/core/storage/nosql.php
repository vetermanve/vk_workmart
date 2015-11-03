<?php

lets_sure_loaded('core_storage_nosql');

/**
 * @param $redisId
 *
 * @return Redis|bool
 */
function _core_storage_nosql_connect ($redisId) {
    static $allConnectionsConfig;
    static $connections;
    
    lets_use('core_config');
    
    if (isset($connections[$redisId])) {
        return $connections[$redisId];
    }
    
    if (!isset($allConnectionsConfig)) {
        $allConnectionsConfig = core_config_get('redis', []);
    }
    
    if(!isset($allConnectionsConfig[$redisId])) {
        core_error('redis config not found for id:'.serialize($redisId));
        return false;
    }
    
    $connectionConfig = $allConnectionsConfig[$redisId];
    
    $connection = new Redis();
    
    $connected = $connection->connect($connectionConfig['host'], $connectionConfig['port'], $connectionConfig['connect_timeout']);
    
    if (!$connected) {
        core_error('Cannot connect redis driver');
        return false;
    }
    
    $connection->setOption(Redis::OPT_READ_TIMEOUT, $connectionConfig['read_timeout']);
    
    return $connections[$redisId] = $connection;
}


function core_storage_nosql_get($redisId, $key) {
    $connect = _core_storage_nosql_connect($redisId);
    if (!$connect) {
        core_error('Missing connection to redis on '.__FUNCTION__);
        return null; 
    }
    
    return $connect->get($key);
}

function core_storage_nosql_set($redisId, $key, $value) {
    $connect = _core_storage_nosql_connect($redisId);
    if (!$connect) {
        core_error('Missing connection to redis on '.__FUNCTION__);
        return null;
    }
    
    return $connect->set($key, $value);
}

function core_storage_nosql_get_prefix($redisId, $prefix, $key) {
    $connect = _core_storage_nosql_connect($redisId);
    if (!$connect) {
        core_error('Missing connection to redis on '.__FUNCTION__);
        return null;
    }
    
    return $connect->hGet($prefix, $key);
}

function core_storage_nosql_set_prefix($redisId, $prefix, $key, $value) {
    $connect = _core_storage_nosql_connect($redisId);
    
    if (!$connect) {
        core_error('Missing connection to redis on '.__FUNCTION__);
        return null;
    }
    
    if ($value === null) {
        return $connect->hDel($prefix, $key);
    }
    
    return $connect->hSet($prefix, $key, $value);
}