<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Dashboard::index');
$routes->get('dashboard', 'Dashboard::index');
$routes->get('about', 'About::index');
$routes->get('stock', 'Stock::index');
$routes->get('stock/(:any)', 'Stock::index/$1');
