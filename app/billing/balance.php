<?php

lets_sure_loaded('billing_balance');

const BILLING_BALANCE_DB_TABLE = 'balance';

const BILLING_BALANCE_FIELD_ACCOUNT_ID = 'acc_id';
const BILLING_BALANCE_FIELD_AMOUNT     = 'amount';

function billing_balance_check_sum_available($accountFrom, $sum) {
    $amount = billing_balance_get_account_amount($accountFrom);
    return $amount >= $sum;
}

function billing_balance_get_account_amount($accountId) {
    lets_use('storage_db');
    
    $amount = storage_db_get_value(
        BILLING_BALANCE_DB_TABLE, 
        BILLING_BALANCE_FIELD_AMOUNT, 
        [
            [BILLING_BALANCE_FIELD_ACCOUNT_ID, $accountId],
        ]
    );
    
    return $amount ? _billing_balance_unpack_money($amount) : 0;
}

function billing_balance_set_account_amount($accountId, $amount) {
    lets_use('storage_db');
    
    $bind = [
        BILLING_BALANCE_FIELD_ACCOUNT_ID => $accountId,
        BILLING_BALANCE_FIELD_AMOUNT     => _billing_balance_pack_money($amount),
    ];
    
    $res = storage_db_set(BILLING_BALANCE_DB_TABLE, $bind);
    if (!$res) {
        core_error('cant set money amount: '.json_encode($bind));
        return false;
    }
    
    return true;
}

function billing_balance_storage_transaction_start() {
    return storage_db_transaction_begin(BILLING_BALANCE_DB_TABLE);
}

function billing_balance_storage_transaction_commit() {
    return storage_db_transaction_commit(BILLING_BALANCE_DB_TABLE);
}

function billing_balance_storage_transaction_rollback() {
    return storage_db_transaction_rollback(BILLING_BALANCE_DB_TABLE);
}

function billing_balance_process_move ($accountFrom, $accountTo, $sum, $trId) {
    $incomingFromAccountAmount = billing_balance_get_account_amount($accountFrom);
    $incomingToAccountAmount = billing_balance_get_account_amount($accountTo);
    
    $accountFromUpdate = billing_balance_set_account_amount($accountFrom, $incomingFromAccountAmount - $sum);
    if (!$accountFromUpdate) {
        return false;
    }
    
    $accountToUpdate = billing_balance_set_account_amount($accountTo, $incomingToAccountAmount + $sum);
    if (!$accountToUpdate) {
        return false;
    }
    
    return $accountFromUpdate;
}

function _billing_balance_pack_money($amount)
{
    $amount = round($amount, 2, PHP_ROUND_HALF_DOWN) * 100;
    
    if ($amount < 0) {
        core_error('Trying to pack negative money value: ' . $amount); // @todo pass additional info
        $amount = 0;
    }
    
    return $amount;
}

function _billing_balance_unpack_money($amount)
{
    return round($amount / 100, 2, PHP_ROUND_HALF_DOWN);
}