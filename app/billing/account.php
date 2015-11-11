<?php

lets_sure_loaded('billing_account');

const BILLING_ACCOUNT_TYPE_USER_MAIN   = 1;
const BILLING_ACCOUNT_TYPE_USER_LOCKED = 2;
const BILLING_ACCOUNT_TYPE_SYSTEM      = 3;
const BILLING_ACCOUNT_TYPE_INCOMING    = 4;

const BILLING_ACCOUNT_DB_TABLE = 'accounts';

const BILLING_ACCOUNT_FIELD_ID       = 'id';
const BILLING_ACCOUNT_FIELD_OWNER_ID = 'owner_id';
const BILLING_ACCOUNT_FIELD_TYPE     = 'type';
const BILLING_ACCOUNT_FIELD_CREATED  = 'created';

function billing_account_get_account($ownerId, $type, $autoCreate) {
    lets_use('storage_db');
    
    $accountId = storage_db_get_value(
        BILLING_ACCOUNT_DB_TABLE, 
        BILLING_ACCOUNT_FIELD_ID,
        [
            [BILLING_ACCOUNT_FIELD_OWNER_ID, $ownerId],
            [BILLING_ACCOUNT_FIELD_TYPE, $type]
        ],
        [
            'LIMIT' => 1,
        ]
    );
    
    if (storage_db_get_last_error(BILLING_ACCOUNT_DB_TABLE)) {
        core_error('cant fetch account '.json_encode(func_get_args()));
        return false;
    }
    
    // create account
    if (!$accountId && $autoCreate) {
        $bind = [
            BILLING_ACCOUNT_FIELD_OWNER_ID => $ownerId,
            BILLING_ACCOUNT_FIELD_TYPE => $type,
            BILLING_ACCOUNT_FIELD_CREATED => time(),
        ];
        
        $accountId = storage_db_insert_row(BILLING_ACCOUNT_DB_TABLE, $bind);
        
        if (!$accountId) {
            core_error('cant create an account '.json_encode(func_get_args()));
            return false;
        }
    }
    
    return $accountId;
}

function billing_account_get_user_main_account($userId) {
    return billing_account_get_account($userId, BILLING_ACCOUNT_TYPE_USER_MAIN, true);
}

function billing_account_get_user_locked_account($userId) {
    return billing_account_get_account($userId, BILLING_ACCOUNT_TYPE_USER_LOCKED, true);
}

function billing_account_get_system_account() {
    $id = mt_rand(1, 200); // one of 200 system accounts
    return billing_account_get_account($id, BILLING_ACCOUNT_TYPE_USER_LOCKED, true);
}

function billing_account_get_income_account($partnerId) {
    return billing_account_get_account($partnerId, BILLING_ACCOUNT_TYPE_INCOMING, true);
}
