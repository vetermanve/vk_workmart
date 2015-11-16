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
    
    $result = storage_db_set(ORDER_STORAGE_DB_TABLE, [
        'id' => $orderId,
        'status' => $status,
    ]);
    
    return $result;
}


function order_storage_get_order($order) {
    lets_use('storage_db');
    
    $order = storage_db_get_row(ORDER_STORAGE_DB_TABLE, '*', [
        ['id' , $order],
    ]);
    
    return $order;
}

function order_storage_get_list($status = ORDER_STORAGE_ORDER_STATUS_OK) {
    lets_use('storage_db');
    
    return storage_db_get_rows('orders', '*', [
        ['status', $status]
    ], ['ORDER BY' => 'id DESC',]);
}

function order_storage_get_by_user($userId, $status = ORDER_STORAGE_ORDER_STATUS_OK) {
    lets_use('storage_db');
    
    return storage_db_get_rows('orders', '*', [
        ['author_id', $userId],
        ['status', $status]
    ], ['ORDER BY' => 'id DESC',]);
}

function order_storage_get_author_list($userId) {
    lets_use('storage_db');
    
    return storage_db_get_rows('orders', '*', null, ['ORDER BY' => 'id DESC',]);
}