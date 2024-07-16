<?php

/*
 * --------------------------------------------------------------------
 * Router Setup
 * --------------------------------------------------------------------
 */
$routes->setDefaultNamespace('App\Controllers');

/*
 * --------------------------------------------------------------------
 * Route Definitions
 * --------------------------------------------------------------------
 */

// We get a performance increase by specifying the default
// route since we don't have to scan directories.

// //WEB ROUTER ------------------------------------------------------
// //------------------------------------------------------------------
// $routes->get('/', 'Home::index');
// $routes->get('lang/{locale}', 'Language::index');

// //API ROUTER ------------------------------------------------------
// //------------------------------------------------------------------
// $routes->get('api/','Api::index');
// $routes->get('api/status','Api::status');
// $routes->post('api/signIn','Api::signIn');

// //API ROUTER USER ------------------------------------------------------
// //------------------------------------------------------------------
// $routes->get('api/user/','Api::user/all');
// $routes->get('api/user/(:segment)','Api::user/id/$1');
// $routes->post('api/user/','Api::user/add');
// $routes->put('api/user/(:segment)','Api::user/edit/$1');
// $routes->delete('api/user/(:segment)','Api::user/delete/$1');
