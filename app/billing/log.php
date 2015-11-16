<?php

lets_sure_loaded('billing_log');

function billing_log_get_user_transactions($userId) {
    lets_use('billing_account', 'billing_transaction', 'billing_description');
    
    $userMain = billing_account_get_account($userId, BILLING_ACCOUNT_TYPE_USER_MAIN, false);
    $acc[] = $userMain; 
    $acc[] = billing_account_get_account($userId, BILLING_ACCOUNT_TYPE_USER_LOCKED, false);
    $acc = array_filter($acc); 
    if (!$acc) {
        return [];
    }
    
    $tr = billing_transaction_get_accounts_transactions($acc);
    
    $types = billing_description_transaction_types();
    $accIds = array_unique(array_merge(array_column($tr, BILLING_TRANSACTION_FIELD_ACC_FROM),array_column($tr, BILLING_TRANSACTION_FIELD_ACC_TO)));
    
    $accData = billing_account_get_accounts($accIds);
    $ownersNames = billing_description_account_owner_names($accData);
    
    foreach ($tr as &$transaction) {
        $transaction['str_type'] = isset($types[$transaction[BILLING_TRANSACTION_FIELD_TYPE]]) ? $types[$transaction[BILLING_TRANSACTION_FIELD_TYPE]] : $types[0];
        $transaction['target_action'] = $transaction[BILLING_TRANSACTION_FIELD_ACC_FROM] == $userId ? 'в счет' : 'из';
        $transaction['prefix'] = $transaction[BILLING_TRANSACTION_FIELD_TYPE] == BILLING_TRANSACTION_TYPE_REFILL? 'источника' : '';
        
        if ($transaction[BILLING_TRANSACTION_FIELD_ACC_FROM] == $userMain) {
            $transaction['target_owner'] = $ownersNames[$transaction[BILLING_TRANSACTION_FIELD_ACC_TO]]; 
        } else {
            $transaction['target_owner'] = $ownersNames[$transaction[BILLING_TRANSACTION_FIELD_ACC_FROM]];
        }
        
        $transaction['success'] = $transaction['status'] == BILLING_TRANSACTION_STATUS_SUCCESS;
    }
    
    return $tr;
}

function billing_log_get_accounts_from_transaction($tr) {
}
