<?php

lets_sure_loaded('user_register');


function user_register_new_user($name, $email, $pass) {
    lets_use('core_storage_db');
    
    core_storage_db_transaction_begin('users');
    
    $userId = core_storage_db_insert_row('users', [
        'name' => $name,
        'email' => $email,
    ]);
    
    if (!$userId) {
        core_storage_db_transactions_rollback_all();
        core_error('cannot save user data to db table');
        return false;
    }
    
    lets_use('user_session');
    
    $token = user_session_create_token($userId, $pass);
    
    if (!$token) {
        core_storage_db_transactions_rollback_all();
        core_error('cannot save user token');
        return false;
    }
    
    core_storage_db_transactions_commit_all();
    
    return $userId;
}

function user_register_get_user_id_by_email($email) {
    lets_use('core_storage_db');
    
    $authUserId = core_storage_db_get_value('users', 'id', [
        ['email', $email],
    ]);
    
    return $authUserId;
}
