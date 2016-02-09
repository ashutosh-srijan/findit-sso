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
$app->post('/user/register', function($request, $response) {
  $headers = $request->getHeaders();
  $requestdata = $request->getParsedBody();
  $result = createuseraccount($requestdata, $headers);
  $response->write($result);
  return $response->withHeader('Content-type', 'application/json');
});

//Get user data based on id.
$app->get('/user/identity', function($request, $response, $id) {
  $headers = $request->getHeaders();
  $userinfo = user_identity($id, $headers);
  return $userinfo;
});

//User login api.
$app->post('/user/login', function($request, $response) {
  $headers = $request->getHeaders();
  $requestdata = $request->getParsedBody();
  $result = loginuser($requestdata, $headers);
  $response->write($result);
  return $response->withHeader('Content-type', 'application/json');
});

//Forgot pasword api
$app->post('/user/forgot/password', function($request, $response) {
  $data = $request->getParsedBody();
  $result = updateUserPassword($data);
  $response->write(json_encode($result));
  return $response->withHeader('Content-type', 'application/json');
});

//User Reset Password
$app->post('/user/reset/password', function($request, $response) {
  $data = $request->getParsedBody();
  $result = updateResetPassword($data);
  $response->write(json_encode($result));
  return $response->withHeader('Content-type', 'application/json');
});

$app->run();
