<?php

if (PHP_SAPI == 'cli-server') {
    // To help the built-in PHP dev server, check if the request was actually for
    // something which should probably be served as a static file
    $file = __DIR__ . $_SERVER['REQUEST_URI'];
    if (is_file($file)) {
        return false;
    }
}

require __DIR__ . '/../vendor/autoload.php';
require '../config.php';
require 'callback.php';

session_start();

// Instantiate the app
$settings = require __DIR__ . '/../src/settings.php';
$app = new \Slim\App($settings);
$app->get('/', function() use ($app) {
    echo sprintf('<h1>Welcome to findit Sso Application</h1>');
    session_destroy();
});

//User register api.
$app->post('/user/register', function($data) {
    $requestdata = $data->getParsedBody();
    $result = createuseraccount($requestdata);
    return json_encode($result);
});

//User login api.
$app->post('/user/login', function($data) {
    $requestdata = $data->getParsedBody();
    $result = loginuser($requestdata);
    return json_encode($result);
});
$app->run();
