<?php

use Silex\Application;

require_once __DIR__.'/vendor/autoload.php';

$app = new Application();

require(__DIR__.'/services.php');

return $app;
