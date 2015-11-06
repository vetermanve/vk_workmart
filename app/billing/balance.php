<?php

lets_sure_loaded('billing_balance');

const BILLING_BALANCE_DB_TABLE = 'balance';
const BILLING_BALANCE_DB_TABLE_TRANSACTIONS = 'transactions';

global $_billing_balance_transaction_locks;
global $_billing_balance_user_locks;
global $_billing_balance_error;

$_billing_balance_transaction_locks = [];

/**
 * Не безопасный метод получения кол-ва денег у пользователя.
 * Убедитесь что получили лок, прежде чем использовать его в важных 
 * операциях
 * 
 * @param $userId
 *
 * @return float
 */
function billing_balance_get_money($userId) {
    lets_use('core_storage_db');
    
    $fromUserMoneyCount = core_storage_db_get_value(BILLING_BALANCE_DB_TABLE, 'amount', [
        ['user_id' , $userId],
    ]);
    
    return $fromUserMoneyCount ?  billing_balance_unpack_money($fromUserMoneyCount) : 0;
}

function billing_balance_get_error() {
    global $_billing_balance_error;
    return $_billing_balance_error;
}

function billing_balance_set_error($error) {
    global $_billing_balance_error;
    $_billing_balance_error = $error;
}


function billing_balance_pack_money($amount) {
    return round($amount, 2) * 100;
}

function billing_balance_unpack_money($amount) {
    return $amount / 100;
}

function billing_balance_process_transaction($fromUserId, $toUserId, $type, $relatedId, $amount, $systemPercent = 0) {
    
    $transactionId = billing_balance_get_transaction_id($fromUserId, $toUserId, $amount);
    
    if (!$transactionId) {
        core_error('can\'t get transaction id', __FUNCTION__);
        return false;
    }
    
    if (
        billing_balance_get_full_lock($fromUserId, $transactionId) && 
        billing_balance_get_full_lock($toUserId, $transactionId)
    ) {
        $fromUserMoney = billing_balance_get_money($fromUserId);
        
        if ($fromUserMoney >= $amount) {
            lets_use('core_storage_db');
            
            core_storage_db_transaction_begin(BILLING_BALANCE_DB_TABLE);
            
            if ($systemPercent) {
                $systemAmount = $amount * $systemPercent;
                $amount = $amount - $systemAmount;
                $systemTransfer = billing_balance_process_increment_system($transactionId, $fromUserId, $amount);
                
                if (!$systemTransfer) {
                    billing_balance_transaction_fails($transactionId, 'cant_save_system_commission');
                    return false;
                }
            }
    
            $fromUserMoves = billing_balance_process_money_update($fromUserId, -$amount);
            if (!$fromUserMoves) {
                billing_balance_transaction_fails($transactionId, 'cant_move_money_from_payer');
                return false;
            }
            
            $toUserMoves   = billing_balance_process_money_update($toUserId, $amount);
            if (!$toUserMoves) {
                billing_balance_transaction_fails($transactionId, 'cant_move_money_to_receiver');
                return false;
            }
            
            $commitResult  = core_storage_db_transactions_commit_all();
            if (!$commitResult) {
                billing_balance_transaction_fails($transactionId, 'cant_commit_transactions');
                return false;
            }
            
            billing_balance_transaction_success($transactionId);        
            return true;
        } else {
            billing_balance_transaction_fails($transactionId, 'less_money');
            return false;
        }
    }
    
    billing_balance_transaction_fails($transactionId, 'cant_get_lock');
    return false;
}

function billing_balance_process_increment_system($transactionId, $fromUserId, $amount) {
    $systemAccounts = range(2,19);
    
    $currentSystemAccount = false;
    foreach ($systemAccounts as $systemUserId) {
        if (billing_balance_get_full_lock($systemUserId, $transactionId)) {
            $currentSystemAccount = $systemUserId;
            break;
        }
    }
    
    $toSystemMoves = billing_balance_process_money_update($currentSystemAccount, $amount);
    $fromUserMoves = billing_balance_process_money_update($fromUserId, -$amount);
    
    return $toSystemMoves && $fromUserMoves;
}

function billing_balance_process_money_update($userId, $amount, $moneyCount = null) {
    lets_use('core_storage_db');
    
    if (!$moneyCount) {
        $moneyCount = billing_balance_get_money($userId);
    }
    
    $moneyCount = $moneyCount + $amount;
    
    $queryResult = core_storage_db_set(BILLING_BALANCE_DB_TABLE, [
        'user_id' => $userId,
        'amount' => billing_balance_pack_money($moneyCount),
    ]);
    
    return $queryResult;
}

function billing_balance_get_full_lock ($userId, $transactionId) {
    $localLock = billing_balance_local_lock($transactionId, $userId);
    
    if ($localLock) {
        $globalLock = billing_balance_lock($transactionId, $localLock);
        
        if ($globalLock) {
            return true;
        }
    }
    
    billing_balance_local_unlock($transactionId, $userId);
    return false;
}

function billing_balance_get_transaction_id($fromUser, $toUser, $amount) {
    lets_use('core_storage_db');
    
    $transactionId = core_storage_db_insert_row(BILLING_BALANCE_DB_TABLE_TRANSACTIONS, [
        'from_user_id' => $fromUser,
        'to_user_id' => $toUser,
        'amount' => $amount,
    ]);
    
    return $transactionId;
}

function billing_balance_build_lockId ($userId) {
    return 'balance:' . $userId;   
}

function billing_balance_local_lock($transaction, $userId)
{
    global $_billing_balance_user_locks;
    global $_billing_balance_transaction_locks;
    
    if (isset($_billing_balance_user_locks[$userId])) {
        return false;
    }
    
    $lockId = billing_balance_build_lockId($userId);
    
    $_billing_balance_user_locks[$userId] = $lockId;
    $_billing_balance_transaction_locks[$transaction][$userId] = $lockId;
    
    return $lockId;
}

function billing_balance_local_unlock($transaction, $userId)
{
    global $_billing_balance_user_locks;
    global $_billing_balance_transaction_locks;
    
    $lockId = billing_balance_build_lockId($userId);;
    
    unset(
        $_billing_balance_user_locks[$userId], 
        $_billing_balance_transaction_locks[$transaction][$userId]
    );
    
    return $lockId;
}

function billing_balance_lock($transaction, $lockId) {
    lets_use('core_storage_lock');
    return core_storage_lock_get($lockId, 60, $transaction);
}

function billing_balance_unlock($lockId) {
    lets_use('core_storage_lock');
    return core_storage_lock_release($lockId);
}

function billing_balance_release_locks($transactionId) {
    global $_billing_balance_transaction_locks;
    
    if (isset($_billing_balance_transaction_locks[$transactionId])) {
        foreach ($_billing_balance_transaction_locks[$transactionId] as $lockId) {
            billing_balance_unlock($lockId);
        }
    }
}

function billing_balance_transaction_fails($transactionId, $error = '') {
    lets_use('core_storage_db');
    
    $error && billing_balance_set_error($error);    
    
    core_storage_db_transactions_rollback_all();
    billing_balance_release_locks($transactionId);
}

function billing_balance_transaction_success($transactionId) {
    lets_use('core_storage_db');
    
    billing_balance_release_locks($transactionId);
}