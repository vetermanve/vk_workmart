<?php

lets_sure_loaded('web_controller_billing');

function web_controller_billing_precall() {
    lets_use('user_self');
    
    $userId = user_self_id();
    if (!$userId) {
        web_router_redirect('/auth/auth');
        return false;
    }
    
    web_render_add_data('is_auth', $userId);
}

function web_controller_billing_add() {
    lets_use('billing_balance');
    
    $fondMoney = billing_balance_get_money(1);
    
    web_router_render_page('billing', 'add', [
        'fond_money' => number_format($fondMoney, 2, '.', ' '),
    ]);
}

function web_controller_billing_refill() {
    lets_use('billing_balance', 'user_self');
    $count = (float) web_router_get_param('sum');
    $res = billing_balance_process_transaction(1, user_self_id(), 1, 1, $count);
    web_router_render_page('billing', 'refill', [
        'result' => $res,
    ]);
}


function web_controller_billing_balance() {
    lets_use('billing_balance');
    
    $fondMoney = billing_balance_get_money(1);
    
    web_router_render_page('billing', 'add', [
        'fond_money' => number_format($fondMoney, 2, '.', ' '),
    ]);
}

