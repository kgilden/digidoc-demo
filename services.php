<?php

$app->register(new Silex\Provider\SessionServiceProvider());
$app->register(new Silex\Provider\UrlGeneratorServiceProvider());

$app['digidoc.api'] = $app->share(function ($app) {
    return new \KG\DigiDoc\Api($app['digidoc.client']);
});

$app['digidoc.client'] = $app->share(function ($app) {
    return new \KG\DigiDoc\Soap\Client();
});

return $app;
