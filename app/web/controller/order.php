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
    $curUserId = user_self_id();
    
    if (!$curUserId) {
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
        
        lets_use('order_storage');
        
        $res = order_storage_add_order($title, $desc, $curUserId, $cost);
        
        if (!$res) {
            web_router_render_page('order', 'create', ['msg'   => 'Не удалось сохранить заказ', 'error' => 'core',]);
            return;
        }
        
        web_router_redirect('/order/list');
    }
    
    web_router_render_page('order', 'create');
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
