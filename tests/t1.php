<?php

require __DIR__.'/vendor/autoload.php';

$cfg  = new \LitePhp\Config();
$config = $cfg->get('app');

var_dump($config);
