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