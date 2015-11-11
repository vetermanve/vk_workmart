<?php

lets_sure_loaded('storage_nosql');

/**
 * @param $redisId
 *
 * @return Redis|bool
 */
function _storage_nosql_connect ($redisId) {
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


function storage_nosql_get($redisId, $key) {
    $connect = _storage_nosql_connect($redisId);
    if (!$connect) {
        core_error('Missing connection to redis on '.__FUNCTION__);
        return null; 
    }
    
    core_log('REDIS GET: '.$key, __FUNCTION__);
    
    return $connect->get($key);
}

function storage_nosql_set($redisId, $key, $value) {
    $connect = _storage_nosql_connect($redisId);
    if (!$connect) {
        core_error('Missing connection to redis on '.__FUNCTION__);
        return null;
    }
    
    if ($value === null) {
        core_log('REDIS DEL: '.$key, __FUNCTION__);
        
        return $connect->del($key);
    }
    
    core_log('REDIS SET: '.$key.' = '.$value, __FUNCTION__);
    
    return $connect->set($key, $value);
}

function storage_nosql_setnx($redisId, $key, $value, $expire) {
    $connect = _storage_nosql_connect($redisId);
    
    if (!$connect) {
        core_error('Missing connection to redis on '.__FUNCTION__);
        return null;
    }
    
    $res = $connect->setnx($key, $value);
    
    core_log('REDIS SETNX: '.$key.' = '.$value.'; res: '.json_encode($res), __FUNCTION__);
    
    if ($res) {
       $connect->expire($key, $expire);
        
        core_log('REDIS EXPIRE: '.$key.' TTL '.$expire, __FUNCTION__);
    }
    
    return $res;
}

function storage_nosql_get_prefix($redisId, $prefix, $key) {
    $connect = _storage_nosql_connect($redisId);
    if (!$connect) {
        core_error('Missing connection to redis on '.__FUNCTION__);
        return null;
    }
    
    core_log('REDIS GET PREFIX: '.$prefix.'::'.$key, __FUNCTION__);
    
    return $connect->hGet($prefix, $key);
}

function storage_nosql_set_prefix($redisId, $prefix, $key, $value) {
    $connect = _storage_nosql_connect($redisId);
    
    if (!$connect) {
        core_error('Missing connection to redis on '.__FUNCTION__);
        return null;
    }
    
    if ($value === null) {
        core_log('REDIS DEL PREFIX: '.$prefix.'::'.$key, __FUNCTION__);
        return $connect->hDel($prefix, $key);
    }
    
    core_log('REDIS SET PREFIX: '.$prefix.'::'.$key.' = '.$value, __FUNCTION__);
    
    return $connect->hSet($prefix, $key, $value);
}