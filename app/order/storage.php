<?php

lets_sure_loaded('order_storage');

const ORDER_STORAGE_DB_TABLE = 'orders';

const ORDER_STORAGE_ORDER_STATUS_CREATING   = 1;
const ORDER_STORAGE_ORDER_STATUS_OK         = 2;
const ORDER_STORAGE_ORDER_STATUS_IN_BILLING = 3;
const ORDER_STORAGE_ORDER_STATUS_CLOSED     = 4;

global $_order_storage_error;

function order_storage_get_error() {
    global $_order_storage_error;
    return $_order_storage_error;
}

function order_storage_set_error($error) {
    global $_order_storage_error;
    return $_order_storage_error = $error;
}
 
function order_storage_add_order($title, $desc, $authorId, $cost) {
    lets_use('billing_balance');
    
    $money = billing_balance_get_money($authorId);
    
    if ($money < $cost) {
        order_storage_set_error('less_money');
        return false;
    }
    
    $orderId = order_storage_create_order($title, $desc, $authorId, $cost);
    
    if (!$orderId) {
        order_storage_set_error('cant_create_order');
        return false;
    }
    
    $transactionId = billing_transaction_start_transaction($authorId, 0, $cost);
    
    $lockRes = billing_balance_lock_money($transactionId, $authorId, $cost);
    if (!$lockRes) {
        order_storage_set_error('cant_lock_money');
        return false; 
    }
    
    $changeStatusRes = order_storage_change_order_status($orderId, ORDER_STORAGE_ORDER_STATUS_OK);
    if (!$changeStatusRes) {
        order_storage_set_error('cant_make_order_public');
        core_error('Order paid but cant be published; OrderId: '.$orderId);
        $transactionId = billing_transaction_start_transaction($authorId, 0, -$cost);
        billing_balance_lock_money($transactionId, $authorId, -$cost);
        return false; 
    }
    
    return $orderId;
}


function order_storage_create_order($title, $desc, $author, $cost) {
    lets_use('storage_db');
    
    $orderId = storage_db_insert_row(ORDER_STORAGE_DB_TABLE, [
        'title' => $title,
        'description' => $desc,
        'author_id' => $author,
        'cost' => $cost,
        'created_at' => time(),
        'status' => ORDER_STORAGE_ORDER_STATUS_CREATING,
    ]);
    
    return $orderId;
}

function order_storage_change_order_status($orderId, $status) {
    lets_use('storage_db');
    
    $orderId = storage_db_set(ORDER_STORAGE_DB_TABLE, [
        'id' => $orderId,
        'status' => $status,
    ]);
    
    return $orderId;
}

function order_storage_get_list() {
    lets_use('storage_db');
    
    return storage_db_get_rows('orders', '*', null, ['ORDER BY' => 'id DESC',]);
}

function order_storage_get_author_list($userId) {
    lets_use('storage_db');
    
    return storage_db_get_rows('orders', '*', null, ['ORDER BY' => 'id DESC',]);
}