<?php

lets_sure_loaded('web_controller_index');

function web_controller_index_index () {
    lets_use('core_storage_db');
    
    $rows = core_storage_db_get_row('users', '*', ['id', '=', 1], ['ORDER BY' => 'id']);
    
    web_render_page('index', 'index', ['say' => 'booom',]);   
}