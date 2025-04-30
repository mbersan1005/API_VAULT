<?php

use APP\Controllers\ApiController;
use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');
$routes->get('recibirJuegos', 'ApiController::recibirJuegos');
$routes->get('recibirDatosJuego/(:num)', 'ApiController::recibirDatosJuego/$1');
$routes->post('inicioSesion', 'ApiController::inicioSesion');
$routes->get('actualizarDatosAPI','ApiController::actualizarDatosAPI');
$routes->get('recibirGeneros','ApiController::recibirGeneros');
$routes->get('recibirPlataformas','ApiController::recibirPlataformas');
$routes->get('recibirTiendas','ApiController::recibirTiendas');
$routes->get('recibirDesarrolladoras','ApiController::recibirDesarrolladoras');
$routes->get('recibirPublishers','ApiController::recibirPublishers');
$routes->get('recibirJuegosFiltrados/(:any)/(:any)', 'ApiController::recibirJuegosFiltrados/$1/$2');
$routes->post('eliminarJuego', 'ApiController::eliminarJuego');
$routes->get('obtenerDatosFormulario', 'ApiController::obtenerDatosFormulario');
$routes->post('agregarJuego','ApiController::agregarJuego');
$routes->post('crearAdministrador','ApiController::crearAdministrador');
$routes->post('editarJuego','ApiController::editarJuego');
$routes->get('recibirJuegosAdmin', 'ApiController::recibirJuegosAdmin');
$routes->get('purgarDatos','ApiController::purgarDatos');
$routes->post('realizarBusqueda','ApiController::realizarBusqueda');
$routes->post('realizarBusquedaDesarrolladoras','ApiController::realizarBusquedaDesarrolladoras');
$routes->post('realizarBusquedaPublishers','ApiController::realizarBusquedaPublishers');
$routes->get('obtenerAppId/(:any)', 'ApiController::obtenerAppId/$1');
$routes->get('actualizarGraficasJuegos', 'ApiController::actualizarGraficasJuegos');
