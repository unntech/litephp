<?php

define('IN_LitePhp', true);
require_once "autoload.php";

$cfg  = new LitePhp\Config('/config/');
var_dump($cfg);
$config = $cfg->load('app');

var_dump($config);
