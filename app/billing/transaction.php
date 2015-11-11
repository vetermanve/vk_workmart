<?php

lets_sure_loaded('billing_transaction');

const BILLING_TRANSACTION_DB_TABLE = 'transactions';

const BILLING_TRANSACTION_FIELD_ID         = 'id';
const BILLING_TRANSACTION_FIELD_ACC_FROM   = 'acc_from';
const BILLING_TRANSACTION_FIELD_ACC_TO     = 'acc_to';
const BILLING_TRANSACTION_FIELD_AMOUNT     = 'amount';
const BILLING_TRANSACTION_FIELD_TYPE       = 'type';
const BILLING_TRANSACTION_FIELD_RELATED_ID = 'related_id';
const BILLING_TRANSACTION_FIELD_STARTED    = 'started';
const BILLING_TRANSACTION_FIELD_STATUS     = 'status';

const BILLING_TRANSACTION_STATUS_STARTED = 1;
const BILLING_TRANSACTION_STATUS_SUCCESS = 2;
const BILLING_TRANSACTION_STATUS_ERROR   = 3;

function billing_transaction_register($accountFrom, $accountTo, $sum, $type = 0, $relatedId = 0)
{
    lets_use('storage_db');
    
    $transactionId = storage_db_insert_row(BILLING_TRANSACTION_DB_TABLE, [
        BILLING_TRANSACTION_FIELD_ACC_FROM   => $accountFrom,
        BILLING_TRANSACTION_FIELD_ACC_TO     => $accountTo,
        BILLING_TRANSACTION_FIELD_AMOUNT     => $sum,
        BILLING_TRANSACTION_FIELD_TYPE       => $type,
        BILLING_TRANSACTION_FIELD_RELATED_ID => $relatedId,
        BILLING_TRANSACTION_FIELD_STARTED    => time(),
        BILLING_TRANSACTION_FIELD_STATUS     => BILLING_TRANSACTION_STATUS_STARTED,
    ]);
    
    return $transactionId;
}


function billing_transaction_success($transactionId)
{
    return billing_transaction_update_status($transactionId, BILLING_TRANSACTION_STATUS_SUCCESS);
}

function billing_transaction_fail($transactionId)
{
    return billing_transaction_update_status($transactionId, BILLING_TRANSACTION_STATUS_ERROR);
}

function billing_transaction_update_status($transactionId, $status)
{
    lets_use('storage_db');
    
    $transactionId = storage_db_set(BILLING_TRANSACTION_DB_TABLE, [
        BILLING_TRANSACTION_FIELD_ID     => $transactionId,
        BILLING_TRANSACTION_FIELD_STATUS => BILLING_TRANSACTION_STATUS_SUCCESS,
    ]);
    
    return $transactionId;
}
