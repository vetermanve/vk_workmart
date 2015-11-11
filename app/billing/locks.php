<?php

lets_sure_loaded('billing_locks');

global $_billing_locks_by_transaction;

function billing_locks_lock_transaction($transactionId, $accountsIds) {
    global $_billing_locks_by_transaction;
    
    if (isset($_billing_locks_by_transaction[$transactionId])) {
        core_error('trying to re-lock non-empty transaction');
        return false;
    }
    
    $_billing_locks_by_transaction[$transactionId] = [];
    
    foreach ($accountsIds as $accountId) {
        $lock = _billing_locks_lock($transactionId, $accountId);
        
        if (!$lock) {
            billing_locks_unlock_transaction($transactionId);
            return false;
        }
    
        $_billing_locks_by_transaction[$transactionId][] = $accountId;
    }
    
    return true;    
}

function billing_locks_unlock_transaction($transactionId)
{
    global $_billing_locks_by_transaction;
    
    foreach ($_billing_locks_by_transaction[$transactionId] as $accountId) {
        _billing_locks_unlock($accountId);
    }
    
    unset ($_billing_locks_by_transaction[$transactionId]);
}


function _billing_locks_get_lock_key($accountId)
{
    return 'ac_lock:' . $accountId;
}

function _billing_locks_lock($transaction, $accountId, $retry = 3, $lockTime = 60)
{
    lets_use('core_storage_lock');
    
    $lockId = _billing_locks_get_lock_key($accountId);
    
    while (--$retry <= 0 ) {
        if (core_storage_lock_get($lockId, $lockTime, $transaction)) {
           return true; 
        }
        usleep(mt_rand(1, 200));
    }
    
    return false;
}


function _billing_locks_unlock($accountId)
{
    lets_use('core_storage_lock');
    
    $lockId = _billing_locks_get_lock_key($accountId);
    
    return core_storage_lock_release($lockId);
}

