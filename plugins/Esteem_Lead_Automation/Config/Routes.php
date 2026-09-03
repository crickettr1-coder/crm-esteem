<?php

namespace Config;

$routes = Services::routes();

$routes->get('esteem_lead_automation_settings', 'Esteem_Lead_Automation_settings::index', ['namespace' => 'Esteem_Lead_Automation\Controllers']);
$routes->post('esteem_lead_automation_settings/(:any)', 'Esteem_Lead_Automation_settings::$1', ['namespace' => 'Esteem_Lead_Automation\Controllers']);
$routes->get('esteem_lead_automation_settings/(:any)', 'Esteem_Lead_Automation_settings::$1', ['namespace' => 'Esteem_Lead_Automation\Controllers']);

