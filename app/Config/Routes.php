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

// routes dusun
$routes->get('/dusun', 'DusunController::index');
$routes->post('/dusun/store', 'DusunController::store');
$routes->post('/dusun/update/(:num)', 'DusunController::update/$1');
$routes->get('/dusun/delete/(:num)', 'DusunController::delete/$1');

// routes rt
$routes->get('/rt', 'RtController::index');
$routes->post('/rt/store', 'RtController::store');
$routes->post('/rt/update/(:num)', 'RtController::update/$1');
$routes->get('/rt/delete/(:num)', 'RtController::delete/$1');

// routes laporan agregat
$routes->get('/laporan', 'LaporanAgregatController::index');
$routes->post('/laporan', 'LaporanAgregatController::index');
$routes->get('/laporan/input', 'LaporanAgregatController::create');
$routes->post('/laporan/store', 'LaporanAgregatController::store');
$routes->get('/laporan/edit/(:num)', 'LaporanAgregatController::edit/$1');
$routes->post('/laporan/update/(:num)', 'LaporanAgregatController::update/$1');
$routes->get('/laporan/delete/(:num)', 'LaporanAgregatController::delete/$1');
$routes->get('laporan/export', 'LaporanAgregatController::export');