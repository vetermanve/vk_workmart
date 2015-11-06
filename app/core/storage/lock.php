<?php

lets_sure_loaded('core_storage_lock');

function core_storage_lock_get($lockId, $expire = 60, $value = 1) {
    lets_use('core_storage_nosql', 'core_config');
    
    return core_storage_nosql_setnx(CORE_CONFIG_REDIS_MAIN, 'cLock:'.$lockId, 1, $expire);
}

function core_storage_lock_release($lockId) {
    lets_use('core_storage_nosql', 'core_config');
    
    return core_storage_nosql_set(CORE_CONFIG_REDIS_MAIN, 'cLock:'.$lockId, null);
}