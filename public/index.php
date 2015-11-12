<?php

chdir('..');

error_reporting(1);
ini_set("display_errors", 1);

require_once 'letsload.php';

lets_use('core', 'web_router'); 

core_init('web');

web_router_route($_SERVER['REQUEST_URI'], $_GET, $_POST);