<?php

namespace Config;

$routes = Services::routes();

$routes->get('social_login', 'Social_Login::index', ['namespace' => 'Social_Login\Controllers']);
$routes->get('social_login/(:any)', 'Social_Login::$1', ['namespace' => 'Social_Login\Controllers']);
$routes->add('social_login/(:any)', 'Social_Login::$1', ['namespace' => 'Social_Login\Controllers']);

$routes->get('social_login_settings', 'Social_Login_settings::index', ['namespace' => 'Social_Login\Controllers']);
$routes->get('social_login_settings/(:any)', 'Social_Login_settings::$1', ['namespace' => 'Social_Login\Controllers']);
$routes->post('social_login_settings/(:any)', 'Social_Login_settings::$1', ['namespace' => 'Social_Login\Controllers']);

$routes->get('social_login_updates', 'Social_Login_Updates::index', ['namespace' => 'Social_Login\Controllers']);
$routes->get('social_login_updates/(:any)', 'Social_Login_Updates::$1', ['namespace' => 'Social_Login\Controllers']);