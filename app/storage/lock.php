<?php

lets_sure_loaded('storage_lock');

function storage_lock_get($lockId, $expire = 60, $value = 1) {
    lets_use('storage_nosql', 'core_config');
    
    return storage_nosql_setnx(CORE_CONFIG_REDIS_MAIN, 'cLock:'.$lockId, 1, $expire);
}

function storage_lock_release($lockId) {
    lets_use('storage_nosql', 'core_config');
    
    return storage_nosql_set(CORE_CONFIG_REDIS_MAIN, 'cLock:'.$lockId, null);
}