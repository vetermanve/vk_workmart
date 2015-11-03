<?php

lets_sure_loaded('web_controller_user');

function web_controller_user_precall () {
    lets_use('user_self');
    
    web_render_add_data('is_auth', user_self_id());
}

function web_controller_user_index () {
    web_router_render_page('index', 'index'); 
}

function web_controller_user_profile () {
    web_router_render_page('index', 'index');
}

function web_controller_user_orders () {
    web_router_render_page('index', 'index');
}

function web_controller_user_oreders () {
    web_router_render_page('index', 'index');
}