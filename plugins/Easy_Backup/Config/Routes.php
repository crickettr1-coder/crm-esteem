<?php

namespace Config;

$routes = Services::routes();

$routes->add('easy_backup', 'Easy_Backup::index', ['namespace' => 'Easy_Backup\Controllers']);

$routes->get('easy_backup_updates', 'Easy_Backup_Updates::index', ['namespace' => 'Easy_Backup\Controllers']);
$routes->get('easy_backup_updates/(:any)', 'Easy_Backup_Updates::$1', ['namespace' => 'Easy_Backup\Controllers']);

$routes->get('easy_backup_settings', 'Easy_Backup_settings::index', ['namespace' => 'Easy_Backup\Controllers']);
$routes->get('easy_backup_settings/(:any)', 'Easy_Backup_settings::$1', ['namespace' => 'Easy_Backup\Controllers']);
$routes->post('easy_backup_settings/(:any)', 'Easy_Backup_settings::$1', ['namespace' => 'Easy_Backup\Controllers']);