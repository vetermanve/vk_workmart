<?php

lets_sure_loaded('billing_description');

function billing_description_transaction_types() {
    return [
        BILLING_TRANSACTION_TYPE_LOCK  => 'Блокировка средств',
        BILLING_TRANSACTION_TYPE_UNLOCK => 'Возврат сресств',
        BILLING_TRANSACTION_TYPE_PAY    => 'Уплата',
        BILLING_TRANSACTION_TYPE_REFILL => 'Пополнение',          
    ];
}
