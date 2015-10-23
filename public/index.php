<?php

chdir('..');

require_once 'autoload.php';

lets_use('core_main', 'core_config', 'web_router', 'web_render');

core_main_init(core_config_data());

web_render_page('index', 'index', []);