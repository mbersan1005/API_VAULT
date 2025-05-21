<?php

use APP\Controllers\ApiController;
use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');
$routes->get('recibirJuegos', 'DataController::recibirJuegos');
$routes->get('recibirDatosJuego/(:num)', 'DataController::recibirDatosJuego/$1');
$routes->post('inicioSesion', 'DataController::inicioSesion');
$routes->get('actualizarDatosAPI','ApiController::actualizarDatosAPI');
$routes->get('recibirGeneros','DataController::recibirGeneros');
$routes->get('recibirPlataformas','DataController::recibirPlataformas');
$routes->get('recibirTiendas','DataController::recibirTiendas');
$routes->get('recibirDesarrolladoras','DataController::recibirDesarrolladoras');
$routes->get('recibirPublishers','DataController::recibirPublishers');
$routes->get('recibirJuegosFiltrados/(:any)/(:any)', 'DataController::recibirJuegosFiltrados/$1/$2');
$routes->post('eliminarJuego', 'DataController::eliminarJuego');
$routes->get('obtenerDatosFormulario', 'DataController::obtenerDatosFormulario');
$routes->post('agregarJuego','DataController::agregarJuego');
$routes->post('crearAdministrador','DataController::crearAdministrador');
$routes->post('editarJuego','DataController::editarJuego');
$routes->get('recibirJuegosAdmin', 'DataController::recibirJuegosAdmin');
$routes->get('purgarDatos','ApiController::purgarDatos');
$routes->post('realizarBusqueda','DataController::realizarBusqueda');
$routes->post('realizarBusquedaDesarrolladoras','DataController::realizarBusquedaDesarrolladoras');
$routes->post('realizarBusquedaPublishers','DataController::realizarBusquedaPublishers');
$routes->get('obtenerAppId', 'ApiController::obtenerAppId');