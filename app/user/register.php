<?php

lets_sure_loaded('user_register');


function user_register_new_user($name, $email, $pass) {
    lets_use('storage_db');
    
    storage_db_transaction_begin('users');
    
    $userId = storage_db_insert_row('users', [
        'name' => $name,
        'email' => $email,
    ]);
    
    if (!$userId) {
        storage_db_transaction_rollback('users');
        core_error('cannot save user data to db table');
        return false;
    }
    
    lets_use('user_session');
    
    $token = user_session_create_token($userId, $pass);
    
    if (!$token) {
        storage_db_transaction_rollback('users');
        core_error('cannot save user token');
        return false;
    }
    
    storage_db_transaction_commit('users');
    
    return $userId;
}

function user_register_get_user_id_by_email($email) {
    lets_use('storage_db');
    
    $authUserId = storage_db_get_value('users', 'id', [
        ['email', $email],
    ]);
    
    return $authUserId;
}
