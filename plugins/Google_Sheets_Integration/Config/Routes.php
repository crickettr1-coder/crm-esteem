<?php

namespace Config;

$routes = Services::routes();

$routes->get('google_sheets', 'Google_Sheets::index', ['namespace' => 'Google_Sheets_Integration\Controllers']);
$routes->get('google_sheets/(:any)', 'Google_Sheets::$1', ['namespace' => 'Google_Sheets_Integration\Controllers']);
$routes->add('google_sheets/(:any)', 'Google_Sheets::$1', ['namespace' => 'Google_Sheets_Integration\Controllers']);
$routes->post('google_sheets/(:any)', 'Google_Sheets::$1', ['namespace' => 'Google_Sheets_Integration\Controllers']);

$routes->get('google_sheets_integration_settings', 'Google_Sheets_Integration_settings::index', ['namespace' => 'Google_Sheets_Integration\Controllers']);
$routes->get('google_sheets_integration_settings/(:any)', 'Google_Sheets_Integration_settings::$1', ['namespace' => 'Google_Sheets_Integration\Controllers']);
$routes->post('google_sheets_integration_settings/(:any)', 'Google_Sheets_Integration_settings::$1', ['namespace' => 'Google_Sheets_Integration\Controllers']);

$routes->get('google_sheets_integration_updates', 'Google_Sheets_Integration_Updates::index', ['namespace' => 'Google_Sheets_Integration\Controllers']);
$routes->get('google_sheets_integration_updates/(:any)', 'Google_Sheets_Integration_Updates::$1', ['namespace' => 'Google_Sheets_Integration\Controllers']);
