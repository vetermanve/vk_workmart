<?php

lets_sure_loaded('web_controller_order');

function web_controller_order_precall()
{
    lets_use('user_self');
    
    web_render_add_data('is_auth', user_self_id());
}

function web_controller_order_create()
{
    lets_use('user_self');
    $authorId = user_self_id();
    
    if (!$authorId) {
        web_router_redirect('/auth/auth');
        return ;
    }
    
    if (web_router_get_method() === 'POST') {
        $cost = web_router_get_param('cost');
        
        if (!$cost) {
            web_router_render_page('order', 'create', [
                'msg'   => 'Цена должна быть задана',
                'error' => 'cost',
            ]);
            
            return;
        }
        
        $title = web_router_get_param('title');
    
        if (!$title) {
            web_router_render_page('order', 'create', ['msg'   => 'Название должно быть задано', 'error' => 'title',]);
            return;
        }
    
        $desc = web_router_get_param('desc');
    
        if (!$desc) {
            web_router_render_page('order', 'create', ['msg'   => 'Описание должно быть задано', 'error' => 'desc',]);
            return;
        }
        
        lets_use(
            'web_router',
            'order_storage',
            'billing_balance',
            'billing_account',
            'billing_transaction',
            'billing_locks',
            'user_self'
        );
    
        $sum = (float) web_router_get_param('cost');
    
        if (!$sum || $sum < 0) {
            web_router_render_page('order', 'create', ['msg'   => 'Сумма заказа должна быть задана и положительна', 'error' => 'sum',]);
            return;
        }
        
        
        $sum = round($sum, 2);
    
        $accountFrom = billing_account_get_user_main_account($authorId);
        $accountTo = billing_account_get_user_locked_account($authorId);
    
        $trId = billing_transaction_register($accountFrom, $accountTo, $sum);
        if (!$trId) {
            // cant register transaction
            billing_transaction_fail($trId);
        
            web_router_render_page('order', 'create', [
                'result' => false,
                'msg' => 'Ошибка сервера, повторите позже.',
            ]);
            return ;
        }
    
        $lockRes = billing_locks_lock_transaction($trId, [$accountFrom, $accountTo]);
        if (!$lockRes) {
            // cant lock transaction
            billing_transaction_fail($trId);
        
            web_router_render_page('order', 'create', [
                'result' => false,
                'msg' => 'В данный момент операция невозможна, повторите позже',
            ]);
            return ;
        }
    
        $movementPossible = billing_balance_check_sum_available($accountFrom, $sum);
        if (!$movementPossible) {
            // not enough money
            billing_transaction_fail($trId);
            billing_locks_unlock_transaction($trId);
        
            web_router_render_page('order', 'create', [
                'result' => false,
                'msg' => 'На исходящем счете недостаточно денег',
            ]);
            return ;
        }
    
        $dbTransactionLock = billing_balance_storage_transaction_start();
        if(!$dbTransactionLock) {
            // cant begin db transaction
            billing_transaction_fail($trId);
            billing_locks_unlock_transaction($trId);
        
            web_router_render_page('order', 'create', [
                'result' => false,
                'msg' => 'Не удалось начать транзакцию',
            ]);
            return ;
        }
    
        $moveRes = billing_balance_process_move($accountFrom, $accountTo, $sum, $trId);
        if(!$moveRes) {
            // cant move money
            billing_balance_storage_transaction_rollback();
            billing_transaction_fail($trId);
            billing_locks_unlock_transaction($trId);
        
            web_router_render_page('order', 'create', [
                'result' => false,
                'msg' => 'Не удалось перевести деньги',
            ]);
            return ;
        }
    
        $orderId = order_storage_create_order($title, $desc, $authorId, $cost);
    
        if (!$orderId) {
            billing_transaction_fail($trId);
            billing_locks_unlock_transaction($trId);
            
            web_router_render_page('order', 'create', [
                'result' => false,
                'msg' => 'Не удалось сохранить заказ',
            ]);
            return ;
        }
    
        $transactionCommit = billing_balance_storage_transaction_commit();
        if ($transactionCommit) {
            // cant commit db transaction
            billing_transaction_fail($trId);
            billing_locks_unlock_transaction($trId);
        
            web_router_render_page('order', 'create', [
                'result' => false,
                'msg' => 'Не удалось завершить транзакцию',
            ]);
            return ;
        }
    
        order_storage_change_order_status($orderId, ORDER_STORAGE_ORDER_STATUS_OK);
        billing_transaction_success($trId);
        billing_locks_unlock_transaction($trId);
        
        web_router_redirect('/order/success?id='.$orderId);
    }
    
    web_router_render_page('order', 'create');
}

function web_controller_order_success()
{
    lets_use('storage_db', 'order_storage');
    $orderId = web_router_get_param('id');
    
    $order = order_storage_get_order($orderId);
    
    if (!$order) {
        web_router_render_page('order', 'success', [
            'order' => [],
            'msg' => 'Не удалось получить созданный заказ',
        ]);
        return;
    }
    
    web_router_render_page('order', 'success', [
        'order' => $order,
        'msg' => 'Заказ успешно создан',
    ]);
}

function web_controller_order_list()
{
    lets_use('storage_db', 'order_storage');
    
    $posts = order_storage_get_list();
    
    $authors = [];
    
    if ($posts) {
        $authors = storage_db_get_rows('users', '*', [
            ['id' , array_unique(array_column($posts, 'author_id'))],
        ], [], 'id');
    }
    
    web_router_render_page('order', 'list', [
        'posts'   => $posts,
        'authors' => $authors,
    ]);
}

function web_controller_order_mine()
{
    lets_use('storage_db', 'order_storage', 'user_self');
    
    $posts = order_storage_get_by_user(user_self_id());
    
    $authors = [];
    
    if ($posts) {
        $authors = storage_db_get_rows('users', '*', [
            ['id' , array_unique(array_column($posts, 'author_id'))],
        ], [], 'id');
    }
    
    web_router_render_page('order', 'list', [
        'posts'   => $posts,
        'authors' => $authors,
    ]);
}
