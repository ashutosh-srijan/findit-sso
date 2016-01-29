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
  $headers = $data->getHeaders();
  $requestdata = $data->getParsedBody();
  $result = createuseraccount($requestdata, $headers);
  return $result;
});

//User login api.
$app->post('/user/login', function($request) {
  $headers = $request->getHeaders();
  $requestdata = $request->getParsedBody();
  $result = loginuser($requestdata, $headers);
  return $result;
});

//Get user data based on id.
$app->get('/user/identity', function($request, $response, $id) {
  $headers = $request->getHeaders();
  $userinfo = user_identity($id, $headers);
  return $userinfo;
});

$app->run();
