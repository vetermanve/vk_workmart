<?php

lets_sure_loaded('web_controller_index');

function web_controller_index_precall () {
    lets_use('user_self');
    
    web_render_add_data('is_auth', user_self_id());
}

function web_controller_index_index () {
    lets_use('user_self');
    
    if (user_self_id()) {
        web_router_call('order','list', '');
        return;
    }
    
    web_router_render_page('index', 'index'); 
}