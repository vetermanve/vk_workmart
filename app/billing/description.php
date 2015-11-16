<?php

lets_sure_loaded('billing_description');

function billing_description_transaction_types() {
    return [
        0  => 'Что-то невероятное',
        BILLING_TRANSACTION_TYPE_LOCK  => 'Блокировка средств',
        BILLING_TRANSACTION_TYPE_UNLOCK => 'Возврат сресств',
        BILLING_TRANSACTION_TYPE_PAY    => 'Уплата',
        BILLING_TRANSACTION_TYPE_REFILL => 'Пополнение',          
    ];
}

function billing_description_account_owner_names($accData) {
    lets_use('billing_account');
    
    $users = $sources = $result = [];
    core_dump($accData);
    
    foreach ($accData as &$accInfo) {
        $id = $accInfo[BILLING_ACCOUNT_FIELD_ID];
        $ownerId = $accInfo[BILLING_ACCOUNT_FIELD_OWNER_ID];
        
        switch($accInfo[BILLING_ACCOUNT_FIELD_TYPE])
        {
            case BILLING_ACCOUNT_TYPE_USER_MAIN:  
            case BILLING_ACCOUNT_TYPE_USER_LOCKED:
                $users[$id] = $ownerId;
                break;
            case BILLING_ACCOUNT_TYPE_INCOMING:
                $sources[$id] = $ownerId;
                break;
            case BILLING_ACCOUNT_TYPE_SYSTEM:
            default:
                $result[$id] = '';
                break;
            
        }
    }
    lets_use('storage_db');
    
    $userNames = storage_db_get_rows('users', ['id', 'name'],
        [['id', $users]],
        [],
        'id'
    );
    
    $sourcesNames = storage_db_get_rows('money_source', ['id', 'name'],
        ['id', $sources],
        [],
        'id'
    );
    
    
    foreach ($users as $accId => $userId) {
        $result[$accId] = isset($userNames[$userId]) ? $userNames[$userId]['name'] : '';
    }
    
    foreach ($sources as $accId => $sourceId) {
        $result[$accId] = isset($sourcesNames[$sourceId]) ? $sourcesNames[$sourceId]['name'] : '';
    }
    
    return $result;
}
