<?php

lets_sure_loaded('web_controller_auth');

lets_use('web_render');

function web_controller_auth_auth () {
    web_render_page('auth', 'auth', []);
}

function web_controller_auth_register () {
    web_render_page('auth', 'register', []);
}