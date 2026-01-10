<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'AuthController::index');
$routes->get('/dashboard', 'DashboardController::index');
$routes->get('/login', 'AuthController::index');
$routes->get('/logout', 'AuthController::logout');
$routes->post('/proses_login', 'AuthController::proses_login');

// routes desa
$routes->get('/desa', 'DesaController::index');
$routes->post('/desa/store', 'DesaController::store');
$routes->post('/desa/update/(:num)', 'DesaController::update/$1');
$routes->get('/desa/delete/(:num)', 'DesaController::delete/$1');