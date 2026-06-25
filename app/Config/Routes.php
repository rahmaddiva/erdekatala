<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
// Public Routes (tanpa perlu login)
$routes->get('/', 'PublicController::landingpage');
$routes->get('/public/landingpage', 'PublicController::landingpage');
$routes->get('/getDesaByKecamatan/(:num)', 'PublicController::getDesaByKecamatan/$1');

// Auth Routes
$routes->get('/login', 'AuthController::index');
$routes->post('/proses_login', 'AuthController::proses_login');
$routes->get('/logout', 'AuthController::logout');

// Dashboard Routes (perlu login)
$routes->get('/dashboard', 'DashboardController::index');
$routes->get('/profile', 'AuthController::profile');
$routes->post('/profile/update', 'AuthController::update_profile');
$routes->post('/profile/password', 'AuthController::change_password');

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
$routes->get('laporan/export-options', 'LaporanAgregatController::exportOptions');
$routes->post('laporan/export-options', 'LaporanAgregatController::exportOptions');
$routes->get('laporan/export/(:any)', 'LaporanAgregatController::export/$1');
$routes->get('laporan/getPreviousData', 'LaporanAgregatController::getPreviousData');
$routes->get('laporan/detailDesa/(:num)', 'LaporanAgregatController::detailDesa/$1');

// AJAX Routes untuk Chained Dropdown
$routes->get('dashboard/getDesaByKecamatan/(:num)', 'DashboardController::getDesaByKecamatan/$1');
$routes->get('dashboard/getDusunByDesa/(:num)', 'DashboardController::getDusunByDesa/$1');
$routes->get('dashboard/getRtByDusun/(:num)', 'DashboardController::getRtByDusun/$1');

// User CRUD
$routes->get('/users', 'UserController::index');
$routes->get('/users/create', 'UserController::create');
$routes->post('/users/store', 'UserController::store');
$routes->get('/users/edit/(:num)', 'UserController::edit/$1');
$routes->post('/users/update/(:num)', 'UserController::update/$1');
$routes->get('/users/delete/(:num)', 'UserController::delete/$1');

// -------------------------------------------------------
// API Public Routes (filter: apikeyauth)
// -------------------------------------------------------
$routes->group('api', function ($routes) {
    // Docs & registrasi key (tanpa auth)
    $routes->get('docs', 'ApiDocsController::index');
    $routes->get('guide', 'ApiDocsController::guide');
    $routes->get('register', 'ApiDocsController::register');
    $routes->post('register', 'ApiDocsController::store');

    // Endpoint data (wajib API key)
    $routes->group('v1', ['filter' => 'apikeyauth'], function ($routes) {
        $routes->get('info',                    'ApiController::info');
        $routes->get('kecamatan',               'ApiController::kecamatan');
        $routes->get('desa',                    'ApiController::desa');
        $routes->get('laporan',                 'ApiController::laporan');
        $routes->get('laporan/rekap/kecamatan', 'ApiController::rekapKecamatan');
        $routes->get('laporan/rekap/desa',      'ApiController::rekapDesa');
    });
});

// Manajemen API key oleh admin_dinas (perlu login)
$routes->get('/apikeys',                 'ApiKeyAdminController::index');
$routes->get('/apikeys/create',          'ApiKeyAdminController::create');
$routes->post('/apikeys/store',          'ApiKeyAdminController::store');
$routes->get('/apikeys/revoke/(:num)',    'ApiKeyAdminController::revoke/$1');
$routes->get('/apikeys/activate/(:num)', 'ApiKeyAdminController::activate/$1');
$routes->get('/apikeys/delete/(:num)',   'ApiKeyAdminController::delete/$1');
