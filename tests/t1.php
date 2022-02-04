<?php

define('IN_LitePhp', true);
require_once dirname(__DIR__ , 4)."/vendor/autoload.php";

$cfg  = new \LitePhp\Config(\LitePhp\Lite::getRootPath().'/config/');
var_dump($cfg);
$config = $cfg->load('app');

var_dump($config);
