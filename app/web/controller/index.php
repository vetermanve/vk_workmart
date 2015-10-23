<?php

function web_controller_index_index ($request, $response) {
    web_render_page('index', 'index', ['say' => 'booom',]);   
}