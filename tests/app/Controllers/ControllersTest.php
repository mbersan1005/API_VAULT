<?php

namespace App\Controllers;

use App\Base\BaseTestCase;
use App\Models\VideojuegoModelo;
use Config\Services;
use CodeIgniter\HTTP\IncomingRequest;

class ControllersTest extends BaseTestCase
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

        $controller = new DataController();
        $controller->VideojuegoModelo = $videojuegoModelo;
        $controller->apiKeyValidator = $apiKeyValidator;

        $request = \Config\Services::request();
        $response = \Config\Services::response();
        $controller->setRequest($request);
        $controller->setResponse($response);

        $juegosPrueba = [
            [
                'id' => 1,
                'nombre' => 'Juego 1',
                'nota_metacritic' => 85,
                'fecha_lanzamiento' => '2023-01-01',
                'sitio_web' => 'https://juego1.com',
                'imagen' => 'juego1.jpg',
                'plataformas_principales' => 'PC',
                'desarrolladoras' => 'Desarrolladora A',
                'publishers' => 'Publisher A',
                'tiendas' => 'Steam',
                'generos' => 'Acción',
                'descripcion' => 'Descripción del juego 1',
                'creado_por_admin' => 0
            ],
            [
                'id' => 2,
                'nombre' => 'Juego 2',
                'nota_metacritic' => 78,
                'fecha_lanzamiento' => '2023-05-01',
                'sitio_web' => 'https://juego2.com',
                'imagen' => 'juego2.jpg',
                'plataformas_principales' => 'PS5',
                'desarrolladoras' => 'Desarrolladora B',
                'publishers' => 'Publisher B',
                'tiendas' => 'PlayStation Store',
                'generos' => 'Aventura',
                'descripcion' => 'Descripción del juego 2',
                'creado_por_admin' => 0
            ]
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

    public function testRecibirDatosJuego_Exito()
    {
        $videojuegoModelo = $this->createMock(\App\Models\VideojuegoModelo::class);
        $apiKeyValidator = $this->createMock(\App\Services\ApiKeyValidator::class);

        $apiKeyValidator->method('validar')->willReturn(true);

        $juegoSimulado = ['id' => 1, 'nombre' => 'Test Game'];
        $videojuegoModelo->method('find')->willReturn($juegoSimulado);

        $controller = new \App\Controllers\DataController();
        $controller->VideojuegoModelo = $videojuegoModelo;
        $controller->apiKeyValidator = $apiKeyValidator;

        $controller->setRequest(\Config\Services::request());
        $controller->setResponse(\Config\Services::response());

        $response = $controller->recibirDatosJuego(1);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('Test Game', $response->getBody());
    }

    public function testRecibirDatosJuego_NoEncontrado()
    {
        $videojuegoModelo = $this->createMock(\App\Models\VideojuegoModelo::class);
        $apiKeyValidator = $this->createMock(\App\Services\ApiKeyValidator::class);

        $apiKeyValidator->method('validar')->willReturn(true);
        $videojuegoModelo->method('find')->willReturn(null);

        $controller = new \App\Controllers\DataController();
        $controller->VideojuegoModelo = $videojuegoModelo;
        $controller->apiKeyValidator = $apiKeyValidator;

        $controller->setRequest(\Config\Services::request());
        $controller->setResponse(\Config\Services::response());

        $response = $controller->recibirDatosJuego(999);

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertStringContainsString('No se encontró', $response->getBody());
    }

    public function testRecibirDatosJuego_Excepcion()
    {
        $videojuegoModelo = $this->createMock(\App\Models\VideojuegoModelo::class);
        $apiKeyValidator = $this->createMock(\App\Services\ApiKeyValidator::class);

        $apiKeyValidator->method('validar')->willReturn(true);
        $videojuegoModelo->method('find')->willThrowException(new \Exception('DB error'));

        $controller = new \App\Controllers\DataController();
        $controller->VideojuegoModelo = $videojuegoModelo;
        $controller->apiKeyValidator = $apiKeyValidator;

        $controller->setRequest(\Config\Services::request());
        $controller->setResponse(\Config\Services::response());

        $response = $controller->recibirDatosJuego(1);

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertStringContainsString('Ocurrió un error', $response->getBody());
    }

    public function testRecibirGeneros()
    {
        $logger = $this->get_logger("RecibirGeneros_");

        $generoModelo = $this->createMock(\App\Models\GeneroModelo::class);
        $generosPrueba = [
            ['id' => 1, 'nombre' => 'Acción', 'cantidad_juegos' => 25],
            ['id' => 2, 'nombre' => 'Aventura', 'cantidad_juegos' => 18]
        ];
        $generoModelo->method('findAll')->willReturn($generosPrueba);

        $apiKeyValidator = $this->createMock(\App\Services\ApiKeyValidator::class);
        $apiKeyValidator->method('validar')->willReturn(true);

        $controller = new \App\Controllers\DataController();
        $controller->GeneroModelo = $generoModelo;
        $controller->apiKeyValidator = $apiKeyValidator;

        $request = \Config\Services::request();
        $response = \Config\Services::response();
        $controller->setRequest($request);
        $controller->setResponse($response);

        $response = $controller->recibirGeneros();

        try {
            $this->assertEquals(200, $response->getStatusCode());
            $this->assertStringContainsString('Acción', $response->getBody());
            $this->assertStringContainsString('Aventura', $response->getBody());

            $logger->log('info', 'RESULTADO CORRECTO: Se recibieron los géneros correctamente.');
        } catch (\PHPUnit\Framework\AssertionFailedError $e) {
            $logger->log('error', 'ERROR: Falló la recepción de géneros - ' . $e->getMessage());
            throw $e;
        }
    }

    public function testRecibirPlataformas()
    {
        $logger = $this->get_logger("RecibirPlataformas_");

        $plataformaModelo = $this->createMock(\App\Models\PlataformaModelo::class);
        $plataformasPrueba = [
            ['id' => 1, 'nombre' => 'PlayStation 5'],
            ['id' => 2, 'nombre' => 'Xbox Series X']
        ];
        $plataformaModelo->method('findAll')->willReturn($plataformasPrueba);

        $apiKeyValidator = $this->createMock(\App\Services\ApiKeyValidator::class);
        $apiKeyValidator->method('validar')->willReturn(true);

        $controller = new \App\Controllers\DataController();
        $controller->PlataformaModelo = $plataformaModelo;
        $controller->apiKeyValidator = $apiKeyValidator;

        $request = \Config\Services::request();
        $response = \Config\Services::response();
        $controller->setRequest($request);
        $controller->setResponse($response);

        $response = $controller->recibirPlataformas();

        try {
            $this->assertEquals(200, $response->getStatusCode());
            $this->assertStringContainsString('PlayStation 5', $response->getBody());
            $this->assertStringContainsString('Xbox Series X', $response->getBody());

            $logger->log('info', 'RESULTADO CORRECTO: Se recibieron las plataformas correctamente.');
        } catch (\PHPUnit\Framework\AssertionFailedError $e) {
            $logger->log('error', 'ERROR: Falló la recepción de plataformas - ' . $e->getMessage());
            throw $e;
        }
    }

    public function testRecibirTiendas()
    {
        $logger = $this->get_logger("RecibirTiendas_");

        $tiendaModelo = $this->createMock(\App\Models\TiendaModelo::class);
        $tiendasPrueba = [
            ['id' => 1, 'nombre' => 'Steam'],
            ['id' => 2, 'nombre' => 'Epic Games Store']
        ];
        $tiendaModelo->method('findAll')->willReturn($tiendasPrueba);

        $apiKeyValidator = $this->createMock(\App\Services\ApiKeyValidator::class);
        $apiKeyValidator->method('validar')->willReturn(true);

        $controller = new \App\Controllers\DataController();
        $controller->TiendaModelo = $tiendaModelo;
        $controller->apiKeyValidator = $apiKeyValidator;

        $request = \Config\Services::request();
        $response = \Config\Services::response();
        $controller->setRequest($request);
        $controller->setResponse($response);

        $response = $controller->recibirTiendas();

        try {
            $this->assertEquals(200, $response->getStatusCode());
            $this->assertStringContainsString('Steam', $response->getBody());
            $this->assertStringContainsString('Epic Games Store', $response->getBody());

            $logger->log('info', 'RESULTADO CORRECTO: Se recibieron las tiendas correctamente.');
        } catch (\PHPUnit\Framework\AssertionFailedError $e) {
            $logger->log('error', 'ERROR: Falló la recepción de tiendas - ' . $e->getMessage());
            throw $e;
        }
    }

    public function testRecibirDesarrolladoras()
    {
        $logger = $this->get_logger("RecibirDesarrolladoras_");

        $desarrolladoraModelo = $this->createMock(\App\Models\DesarrolladoraModelo::class);
        $desarrolladorasPrueba = [
            ['id' => 1, 'nombre' => 'Naughty Dog'],
            ['id' => 2, 'nombre' => 'CD Projekt RED']
        ];
        $desarrolladoraModelo->method('findAll')->willReturn($desarrolladorasPrueba);

        $apiKeyValidator = $this->createMock(\App\Services\ApiKeyValidator::class);
        $apiKeyValidator->method('validar')->willReturn(true);

        $controller = new \App\Controllers\DataController();
        $controller->DesarrolladoraModelo = $desarrolladoraModelo;
        $controller->apiKeyValidator = $apiKeyValidator;

        $request = \Config\Services::request();
        $response = \Config\Services::response();
        $controller->setRequest($request);
        $controller->setResponse($response);

        $response = $controller->recibirDesarrolladoras();

        try {
            $this->assertEquals(200, $response->getStatusCode());
            $this->assertStringContainsString('Naughty Dog', $response->getBody());
            $this->assertStringContainsString('CD Projekt RED', $response->getBody());

            $logger->log('info', 'RESULTADO CORRECTO: Se recibieron las desarrolladoras correctamente.');
        } catch (\PHPUnit\Framework\AssertionFailedError $e) {
            $logger->log('error', 'ERROR: Falló la recepción de desarrolladoras - ' . $e->getMessage());
            throw $e;
        }
    }

    public function testRecibirPublishers()
    {
        $logger = $this->get_logger("RecibirPublishers_");

        $publisherModelo = $this->createMock(\App\Models\PublisherModelo::class);
        $publishersPrueba = [
            ['id' => 1, 'nombre' => 'Ubisoft'],
            ['id' => 2, 'nombre' => 'Electronic Arts']
        ];
        $publisherModelo->method('findAll')->willReturn($publishersPrueba);

        $apiKeyValidator = $this->createMock(\App\Services\ApiKeyValidator::class);
        $apiKeyValidator->method('validar')->willReturn(true);

        $controller = new \App\Controllers\DataController();
        $controller->PublisherModelo = $publisherModelo;
        $controller->apiKeyValidator = $apiKeyValidator;

        $request = \Config\Services::request();
        $response = \Config\Services::response();
        $controller->setRequest($request);
        $controller->setResponse($response);

        $response = $controller->recibirPublishers();

        try {
            $this->assertEquals(200, $response->getStatusCode());
            $this->assertStringContainsString('Ubisoft', $response->getBody());
            $this->assertStringContainsString('Electronic Arts', $response->getBody());

            $logger->log('info', 'RESULTADO CORRECTO: Se recibieron los publishers correctamente.');
        } catch (\PHPUnit\Framework\AssertionFailedError $e) {
            $logger->log('error', 'ERROR: Falló la recepción de publishers - ' . $e->getMessage());
            throw $e;
        }
    }

    public function testEliminarJuego()
    {
        $logger = $this->get_logger("EliminarJuego_");

        $videojuegoModelo = $this->createMock(\App\Models\VideojuegoModelo::class);

        $juegoSimulado = [
            'id' => 1,
            'nombre' => 'Juego de prueba',
            'imagen' => 'https://res.cloudinary.com/mbersan1005/image/upload/v123456789/juego_prueba.jpg'
        ];

        $videojuegoModelo->method('find')->with(1)->willReturn($juegoSimulado);
        $videojuegoModelo->method('delete')->with(1)->willReturn(true); // Simular delete sin ejecutar

        $apiKeyValidator = $this->createMock(\App\Services\ApiKeyValidator::class);
        $apiKeyValidator->method('validar')->willReturn(true);

        $controller = new \App\Controllers\DataController();
        $controller->VideojuegoModelo = $videojuegoModelo;
        $controller->apiKeyValidator = $apiKeyValidator;

        $request = \Config\Services::request();
        $body = json_encode(['id' => 1]);
        $request->setBody($body);
        $request->setHeader('Content-Type', 'application/json');

        $response = \Config\Services::response();
        $controller->setRequest($request);
        $controller->setResponse($response);

        $response = $controller->eliminarJuego();

        try {
            $this->assertEquals(200, $response->getStatusCode());
            $this->assertStringContainsString('Juego eliminado correctamente', $response->getBody());
            $logger->log('info', 'RESULTADO CORRECTO: Se simuló la eliminación del juego correctamente.');
        } catch (\PHPUnit\Framework\AssertionFailedError $e) {
            $logger->log('error', 'ERROR: Falló la simulación de eliminación - ' . $e->getMessage());
            throw $e;
        }
    }

    public function testObtenerDatosFormulario()
    {
        $logger = $this->get_logger("ObtenerDatosFormulario_");

        $tiendas = [['id' => 1, 'nombre' => 'Steam']];
        $plataformas = [['id' => 1, 'nombre' => 'PC']];
        $generos = [['id' => 1, 'nombre' => 'Acción']];
        $desarrolladoras = [['id' => 1, 'nombre' => 'Ubisoft']];
        $publishers = [['id' => 1, 'nombre' => 'EA']];

        $tiendaModelo = $this->createMock(\App\Models\TiendaModelo::class);
        $tiendaModelo->method('findAll')->willReturn($tiendas);

        $plataformaModelo = $this->createMock(\App\Models\PlataformaModelo::class);
        $plataformaModelo->method('findAll')->willReturn($plataformas);

        $generoModelo = $this->createMock(\App\Models\GeneroModelo::class);
        $generoModelo->method('findAll')->willReturn($generos);

        $desarrolladoraModelo = $this->createMock(\App\Models\DesarrolladoraModelo::class);
        $desarrolladoraModelo->method('findAll')->willReturn($desarrolladoras);

        $publisherModelo = $this->createMock(\App\Models\PublisherModelo::class);
        $publisherModelo->method('findAll')->willReturn($publishers);

        $apiKeyValidator = $this->createMock(\App\Services\ApiKeyValidator::class);
        $apiKeyValidator->method('validar')->willReturn(true);

        $controller = new \App\Controllers\DataController();
        $controller->TiendaModelo = $tiendaModelo;
        $controller->PlataformaModelo = $plataformaModelo;
        $controller->GeneroModelo = $generoModelo;
        $controller->DesarrolladoraModelo = $desarrolladoraModelo;
        $controller->PublisherModelo = $publisherModelo;
        $controller->apiKeyValidator = $apiKeyValidator;

        $request = \Config\Services::request();
        $response = \Config\Services::response();
        $controller->setRequest($request);
        $controller->setResponse($response);

        $response = $controller->obtenerDatosFormulario();
        $body = json_decode($response->getBody(), true);

        try {
            $this->assertEquals(200, $response->getStatusCode());
            $this->assertEquals($tiendas, $body['tiendas']);
            $this->assertEquals($plataformas, $body['plataformas']);
            $this->assertEquals($generos, $body['generos']);
            $this->assertEquals($desarrolladoras, $body['desarrolladoras']);
            $this->assertEquals($publishers, $body['publishers']);

            $logger->log('info', 'RESULTADO CORRECTO: Datos del formulario recibidos correctamente.');
        } catch (\PHPUnit\Framework\AssertionFailedError $e) {
            $logger->log('error', 'ERROR: Falló la recepción de datos del formulario - ' . $e->getMessage());
            throw $e;
        }
    }

    public function testRecibirJuegosAdminDevuelveLista()
    {
        $controller = new \App\Controllers\DataController();

        $request = Services::request();
        $response = Services::response();

        $controller->setRequest($request);
        $controller->setResponse($response);

        $controller->VideojuegoModelo = new \App\Models\VideojuegoModelo();

        $controller->apiKeyValidator = new class {
            public function validar($req, $res)
            {
                return true;
            }
        };

        $resultado = $controller->recibirJuegosAdmin();

        $this->assertInstanceOf(\CodeIgniter\HTTP\ResponseInterface::class, $resultado);
        $this->assertTrue(in_array($resultado->getStatusCode(), [200, 404]));

        $json = json_decode($resultado->getBody(), true);
        $this->assertIsArray($json);

        if ($resultado->getStatusCode() === 200) {
            $this->assertArrayHasKey('juegos', $json);
        } else {
            $this->assertArrayHasKey('error', $json);
        }
    }

}
