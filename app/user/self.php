<?php

lets_sure_loaded('user_self');

function user_self_id () {
    static $id;
    
    if (!$id) {
        lets_use('user_session');
        $id = user_session_get_current_user(); 
    }
    
    return $id;
}

function user_self_balance() {
    lets_use('billing_balance', 'billing_account');
    
    $userId = user_self_id();
    
    core_log('user_id: '.$userId, __FUNCTION__);
    
    if (!$userId) {
        return 0;
    }
    
    $account = billing_account_get_user_main_account($userId);
    return billing_balance_get_account_amount($account);
}