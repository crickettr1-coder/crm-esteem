<?php
namespace Config;
use CodeIgniter\Config\Services;
$routes = Services::routes();
$route_options = array('namespace' => 'Esteem_Meta_Lead_Capture\\Controllers');
$routes->get('esteem_meta_lead_capture/webhook', 'Meta_webhook::verify', $route_options);
$routes->post('esteem_meta_lead_capture/webhook', 'Meta_webhook::receive', $route_options);
$routes->get('esteem_meta_lead_capture_settings', 'Meta_settings::index', $route_options);
$routes->post('esteem_meta_lead_capture_settings/save', 'Meta_settings::save', $route_options);
$routes->post('esteem_meta_lead_capture_settings/test_connection', 'Meta_settings::test_connection', $route_options);
