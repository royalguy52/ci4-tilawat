<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');
$routes->get('api/health', 'Api\Health::index');

$routes->group('api/auth', ['namespace' => 'App\Controllers\Api'], static function($routes) {
    $routes->post('register', 'Auth::register');
    $routes->post('login', 'Auth::login');
    $routes->get('me', 'Auth::me');
});

