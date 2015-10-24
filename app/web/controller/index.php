<?php

lets_sure_loaded('web_controller_index');

function web_controller_index_index ($request, $response) {
    web_render_page('index', 'index', ['say' => 'booom',]);   
}