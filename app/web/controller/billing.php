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
    lets_use('billing_balance', 'billing_account', 'billing_refill');
    
    $fondAccount = billing_account_get_income_account(BILLING_REFILL_SOURCE_FOND_INCOMING_ID);
    $fondMoney = billing_balance_get_account_amount($fondAccount);
    
    web_router_render_page('billing', 'add', [
        'fond_money' => number_format($fondMoney, 2, '.', ' '),
    ]);
}

function web_controller_billing_refill() {
    lets_use(
        'web_router',
        'billing_balance', 
        'billing_account', 
        'billing_transaction', 
        'billing_locks',
        'user_self'
    );
    
    $incomingSource = 1; // благотоврительный фонд
    
    $sum = (float) web_router_get_param('sum');
    $sum = round($sum, 2);
    
    $accountFrom = billing_account_get_income_account($incomingSource);
    $accountTo = billing_account_get_user_main_account(user_self_id());
    
    $trId = billing_transaction_register($accountFrom, $accountTo, $sum);
    if (!$trId) {
        // cant register transaction
        billing_transaction_fail($trId);
        
        web_router_render_page('billing', 'refill', [
            'result' => false,
            'msg' => 'Ошибка сервера, повторите позже.',
        ]);
        return ;
    }
    
    $lockRes = billing_locks_lock_transaction($trId, [$accountFrom, $accountTo]);
    if (!$lockRes) {
        // cant lock transaction
        billing_transaction_fail($trId);
        
        web_router_render_page('billing', 'refill', [
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
        
        web_router_render_page('billing', 'refill', [
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
        
        web_router_render_page('billing', 'refill', [
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
        
        web_router_render_page('billing', 'refill', [
            'result' => false,
            'msg' => 'Не удалось перевести деньги',
        ]);
        return ;
    }
    
    $transactionCommit = billing_balance_storage_transaction_commit();
    if ($transactionCommit) {
        // cant commit db transaction
        billing_transaction_fail($trId);
        billing_locks_unlock_transaction($trId);
        
        web_router_render_page('billing', 'refill', [
            'result' => false,
            'msg' => 'Не удалось завершить транзакцию',
        ]);
        return ;
    }
    
    billing_transaction_success($trId);
    billing_locks_unlock_transaction($trId);
    
    $moneyNow = billing_balance_get_account_amount($accountTo);
    
    web_router_render_page('billing', 'refill', [
        'result' => true,
        'msg' => $sum.' денежных единиц успешно переведены вам на счет. На вашем счету теперь: '.$moneyNow,
    ]);
}

function web_controller_billing_balance() {
    lets_use('billing_balance');
    
    $fondMoney = billing_balance_get_money(1);
    
    web_router_render_page('billing', 'add', [
        'fond_money' => number_format($fondMoney, 2, '.', ' '),
    ]);
}

