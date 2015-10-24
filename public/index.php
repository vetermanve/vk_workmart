<?php

chdir('..');

error_reporting(1);
ini_set("display_errors", 1);

require_once 'autoload.php';

lets_use('core', 'core_config', 'web_router', 'web_render'); 

core_init('web');

web_router_route($_SERVER['REQUEST_URI'], $_GET, $_POST);
