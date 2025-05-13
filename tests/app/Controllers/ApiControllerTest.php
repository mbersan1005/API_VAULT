<?php

namespace App\Controllers;

use App\Base\BaseTestCase;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\HTTP\Request;
use CodeIgniter\HTTP\Response;
use App\Models\VideojuegoModelo;
use App\Controllers\Services;


class ApiControllerTest extends BaseTestCase
{

    public function testObtenerIdsJuegos_API()
    {
        $logger = $this->get_logger("ObtenerIdsJuegos_API_");

        $controller = new \App\Controllers\ApiController();
        $ids = $controller->obtenerIdsJuegos_API();

        if (is_array($ids) && !empty($ids) && is_int($ids[0])) {
            $logger->log('info', "RESULTADO CORRECTO: Se obtuvo una lista de IDs de juegos correctamente.");
            $this->assertTrue(true, "La función devolverá un array no vacío de IDs enteros.");
        } else {
            $logger->log('error', "ERROR: No se obtuvo la lista de IDs correctamente.");
            $this->fail('La función no devolvió la lista de IDs correctamente.');
        }
    }
    
    public function testRecibirJuegos()
    {
        $logger = $this->get_logger("RecibirJuegos_");
    
        $videojuegoModelo = $this->createMock(VideojuegoModelo::class);
    
        $apiKeyValidator = $this->createMock(\App\Services\ApiKeyValidator::class);
        $apiKeyValidator->method('validar')->willReturn(true);
    
        $controller = new ApiController();
        $controller->VideojuegoModelo = $videojuegoModelo;
        $controller->apiKeyValidator = $apiKeyValidator;
    
        $request = \Config\Services::request();
        $response = \Config\Services::response();
        $controller->setRequest($request);
        $controller->setResponse($response);
    
        $juegosPrueba = [
            ['id' => 1, 'nombre' => 'Juego 1', 'nota_metacritic' => 85, 
            'fecha_lanzamiento' => '2023-01-01', 'sitio_web' => 'https://juego1.com',
            'imagen' => 'juego1.jpg', 'plataformas_principales' => 'PC', 'desarrolladoras' => 'Desarrolladora A',
            'publishers' => 'Publisher A', 'tiendas' => 'Steam', 'generos' => 'Acción', 
            'descripcion' => 'Descripción del juego 1', 'creado_por_admin' => 0],
            ['id' => 2, 'nombre' => 'Juego 2', 'nota_metacritic' => 78,
            'fecha_lanzamiento' => '2023-05-01', 'sitio_web' => 'https://juego2.com',
            'imagen' => 'juego2.jpg', 'plataformas_principales' => 'PS5', 'desarrolladoras' => 'Desarrolladora B', 
            'publishers' => 'Publisher B', 'tiendas' => 'PlayStation Store', 'generos' => 'Aventura', 
            'descripcion' => 'Descripción del juego 2', 'creado_por_admin' => 0]
        ];
        $videojuegoModelo->method('findAll')->willReturn($juegosPrueba);
    
        $response = $controller->recibirJuegos();
    
        try {
            $this->assertEquals(200, $response->getStatusCode());
            $this->assertStringContainsString('Juego 1', $response->getBody());
            $this->assertStringContainsString('Juego 2', $response->getBody());
    
            $logger->log('info', 'RESULTADO CORRECTO: Se recibieron los juegos correctamente.');
        } catch (\PHPUnit\Framework\AssertionFailedError $e) {
            $logger->log('error', 'ERROR: Falló la recepción de juegos - ' . $e->getMessage());
            throw $e;
        }
    }
    
}