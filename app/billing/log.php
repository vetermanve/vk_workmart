<?php

lets_sure_loaded('billing_log');

function billing_log_get_user_transactions($userId) {
    lets_use('billing_account', 'billing_transaction');
    
    $acc[] = billing_account_get_account($userId, BILLING_ACCOUNT_TYPE_USER_MAIN, false);
    $acc[] = billing_account_get_account($userId, BILLING_ACCOUNT_TYPE_USER_LOCKED, false);
    $acc = array_filter($acc); 
    if (!$acc) {
        return [];
    }
    
    $tr = billing_transaction_get_accounts_transactions($acc); 
    
    return $tr;
}
