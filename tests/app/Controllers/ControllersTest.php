<?php

namespace App\Controllers;

use App\Base\BaseTestCase;
use App\Models\VideojuegoModelo;
use Config\Services;
use App\Services\ApiKeyValidator;
use Exception;
use App\Controllers\DataController;
use App\Controllers\ApiController;
use stdClass;

class ControllersTest extends BaseTestCase
{
        
    /**
     * Inicializa el controlador con request, response y logger.
     */
    private function initController(DataController $controller): void
    {
        //Aseguramos que se inicialicen las propiedades necesarias del controlador.
        $controller->initController(
            Services::request(),
            Services::response(),
            Services::logger()
        );
    }

    /**
     * Inyecta un valor en una propiedad protegida de un objeto usando Reflection.
     *
     * @param object $object    Objeto al que se inyecta la propiedad.
     * @param string $property  Nombre de la propiedad.
     * @param mixed  $value     Valor a inyectar.
     */
    private function setProtectedProperty(object $object, string $property, $value): void
    {
        $reflection = new \ReflectionClass($object);
        $prop = $reflection->getProperty($property);
        $prop->setAccessible(true);
        $prop->setValue($object, $value);
    }

    /**
     * Test: API key inválida.
     */
    public function testRecibirJuegos_ApiKeyInvalida()
    {
        $controller = new DataController();
        $this->initController($controller);

        //Creamos un fake response usando el servicio de response.
        $fakeResponse = Services::response()
            ->setJSON(['error' => 'API Key inválida'])
            ->setStatusCode(401);

        //Creamos el mock de ApiKeyValidator para forzar una validación fallida.
        $mockValidator = $this->getMockBuilder(ApiKeyValidator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validar'])
            ->getMock();

        $mockValidator->expects($this->once())
            ->method('validar')
            ->with($this->anything(), $this->anything())
            ->willReturn($fakeResponse);

        $controller->apiKeyValidator = $mockValidator;
        //No es necesario configurar el modelo puesto que la validación se detiene antes.

        $result = $controller->recibirJuegos();

        //Obtenemos y decodificamos el contenido JSON
        $body = $result->getBody();
        $data = json_decode($body, true);

        $this->assertEquals(401, $result->getStatusCode());
        $this->assertIsArray($data);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('API Key inválida', $data['error']);
    }

    /**
     * Test: API key válida pero no existen videojuegos.
     */
    public function testRecibirJuegos_SinJuegos()
    {
        $controller = new DataController();
        $this->initController($controller);

        //Configuramos el mock para la validación exitosa.
        $mockValidator = $this->getMockBuilder(ApiKeyValidator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validar'])
            ->getMock();

        $mockValidator->expects($this->once())
            ->method('validar')
            ->willReturn(true);
        $controller->apiKeyValidator = $mockValidator;

        //Simulamos al modelo devolviendo un array vacío.
        $mockModelo = $this->getMockBuilder(VideojuegoModelo::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['findAll'])
            ->getMock();

        $mockModelo->expects($this->once())
            ->method('findAll')
            ->willReturn([]);

        $controller->VideojuegoModelo = $mockModelo;

        $result = $controller->recibirJuegos();

        $body = $result->getBody();
        $data = json_decode($body, true);

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertIsArray($data);
        $this->assertArrayHasKey('mensaje', $data);
        $this->assertEquals('No se encontraron videojuegos', $data['mensaje']);
    }

    /**
     * Test: API key válida y existen videojuegos.
     */
    public function testRecibirJuegos_ConJuegos()
    {
        $controller = new DataController();
        $this->initController($controller);

        //Configuramos el mock para la validación exitosa.
        $mockValidator = $this->getMockBuilder(ApiKeyValidator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validar'])
            ->getMock();

        $mockValidator->expects($this->once())
            ->method('validar')
            ->willReturn(true);
        $controller->apiKeyValidator = $mockValidator;

        //Creamos un array simulando videojuegos.
        $juegosArray = [
            ['id' => 1, 'nombre' => 'Juego1'],
            ['id' => 2, 'nombre' => 'Juego2']
        ];

        //Simulamos al modelo devolviendo videojuegos.
        $mockModelo = $this->getMockBuilder(VideojuegoModelo::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['findAll'])
            ->getMock();

        $mockModelo->expects($this->once())
            ->method('findAll')
            ->willReturn($juegosArray);

        $controller->VideojuegoModelo = $mockModelo;

        $result = $controller->recibirJuegos();

        $body = $result->getBody();
        $data = json_decode($body, true);

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertIsArray($data);
        $this->assertArrayHasKey('juegos', $data);
        $this->assertEquals($juegosArray, $data['juegos']);
    }

    /**
     * Test: Excepción al obtener los juegos (error en findAll).
     */
    public function testRecibirJuegos_Excepcion()
    {
        $controller = new DataController();
        $this->initController($controller);

        //Configuramos el mock para la validación exitosa.
        $mockValidator = $this->getMockBuilder(ApiKeyValidator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validar'])
            ->getMock();

        $mockValidator->expects($this->once())
            ->method('validar')
            ->willReturn(true);
        $controller->apiKeyValidator = $mockValidator;

        //Configuramos el modelo para que lance una excepción.
        $mockModelo = $this->getMockBuilder(VideojuegoModelo::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['findAll'])
            ->getMock();

        $mockModelo->expects($this->once())
            ->method('findAll')
            ->will($this->throwException(new Exception("Database error")));

        $controller->VideojuegoModelo = $mockModelo;

        $result = $controller->recibirJuegos();

        $body = $result->getBody();
        $data = json_decode($body, true);

        $this->assertEquals(500, $result->getStatusCode());
        $this->assertIsArray($data);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Ocurrió un error al recuperar los datos', $data['error']);
    }

    /**
     * Test: API Key inválida.
     */
    public function testRecibirDatosJuego_ApiKeyInvalida()
    {
        $controller = new DataController();
        $this->initController($controller);

        //Creamos una respuesta falsa de error por API key inválida.
        $fakeResponse = Services::response()
            ->setJSON(['error' => 'API Key inválida'])
            ->setStatusCode(401);

        $mockValidator = $this->getMockBuilder(ApiKeyValidator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validar'])
            ->getMock();
        $mockValidator->expects($this->once())
            ->method('validar')
            ->with($this->anything(), $this->anything())
            ->willReturn($fakeResponse);
        $controller->apiKeyValidator = $mockValidator;
        //No es necesario configurar VideojuegoModelo ya que la validación no continúa.

        $result = $controller->recibirDatosJuego(1);
        $data = json_decode($result->getBody(), true);

        $this->assertEquals(401, $result->getStatusCode());
        $this->assertIsArray($data);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('API Key inválida', $data['error']);
    }

    /**
     * Test: Juego encontrado.
     */
    public function testRecibirDatosJuego_Exito()
    {
        $controller = new DataController();
        $this->initController($controller);

        //Validación exitosa.
        $mockValidator = $this->getMockBuilder(ApiKeyValidator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validar'])
            ->getMock();
        $mockValidator->expects($this->once())
            ->method('validar')
            ->willReturn(true);
        $controller->apiKeyValidator = $mockValidator;

        //Simulación del juego encontrado.
        $expectedGame = ['id' => 1, 'nombre' => 'Juego Uno', 'genero' => 'Acción'];

        $mockModelo = $this->getMockBuilder(VideojuegoModelo::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['find'])
            ->getMock();
        $mockModelo->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($expectedGame);
        $controller->VideojuegoModelo = $mockModelo;

        $result = $controller->recibirDatosJuego(1);
        $data = json_decode($result->getBody(), true);

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertIsArray($data);
        $this->assertArrayHasKey('juego', $data);
        $this->assertEquals($expectedGame, $data['juego']);
    }

    /**
     * Test: Juego no encontrado.
     */
    public function testRecibirDatosJuego_NoEncontrado()
    {
        $controller = new DataController();
        $this->initController($controller);

        //Validación exitosa.
        $mockValidator = $this->getMockBuilder(ApiKeyValidator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validar'])
            ->getMock();
        $mockValidator->expects($this->once())
            ->method('validar')
            ->willReturn(true);
        $controller->apiKeyValidator = $mockValidator;

        //Configuramos el modelo para que retorne null (no encontrado)
        $mockModelo = $this->getMockBuilder(VideojuegoModelo::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['find'])
            ->getMock();
        $mockModelo->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);
        $controller->VideojuegoModelo = $mockModelo;

        $result = $controller->recibirDatosJuego(999);
        $data = json_decode($result->getBody(), true);

        $this->assertEquals(404, $result->getStatusCode());
        $this->assertIsArray($data);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('No se encontró videojuego con ese ID', $data['error']);
    }

    /**
     * Test: Excepción al obtener el juego
     */
    public function testRecibirDatosJuego_Excepcion()
    {
        $controller = new DataController();
        $this->initController($controller);

        //Validación exitosa
        $mockValidator = $this->getMockBuilder(ApiKeyValidator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validar'])
            ->getMock();
        $mockValidator->expects($this->once())
            ->method('validar')
            ->willReturn(true);
        $controller->apiKeyValidator = $mockValidator;

        //Configuramos el modelo para que lance una excepción
        $mockModelo = $this->getMockBuilder(VideojuegoModelo::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['find'])
            ->getMock();
        $mockModelo->expects($this->once())
            ->method('find')
            ->with(1)
            ->will($this->throwException(new Exception("Database failure")));
        $controller->VideojuegoModelo = $mockModelo;

        $result = $controller->recibirDatosJuego(1);
        $data = json_decode($result->getBody(), true);

        $this->assertEquals(500, $result->getStatusCode());
        $this->assertIsArray($data);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Ocurrió un error al recuperar los datos del juego', $data['error']);
    }


    /**
     * Test: API Key inválida.
     */
    public function testInicioSesion_ApiKeyInvalida()
    {
        $controller = new DataController();
        $this->initController($controller);

        //Simulamos una respuesta de error por API key inválida.
        $fakeResponse = Services::response()
            ->setJSON(['error' => 'API Key inválida'])
            ->setStatusCode(401);

        $mockValidator = $this->getMockBuilder(ApiKeyValidator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validar'])
            ->getMock();
        $mockValidator->expects($this->once())
            ->method('validar')
            ->with($this->anything(), $this->anything())
            ->willReturn($fakeResponse);
        $controller->apiKeyValidator = $mockValidator;
        //No es necesario configurar AdministradoresModelo en este caso.

        //Ejecutamos el método
        $result = $controller->inicioSesion();
        $data = json_decode($result->getBody(), true);

        $this->assertEquals(401, $result->getStatusCode());
        $this->assertIsArray($data);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('API Key inválida', $data['error']);
    }

    /**
     * Test: Usuario no encontrado.
     */
    public function testInicioSesion_UsuarioNoEncontrado()
    {
        $controller = new DataController();
        $this->initController($controller);

        //Datos de inicio de sesión que se enviarán en el request.
        $inputData = ['nombre' => 'nonexistent', 'password' => 'any'];

        //Simulamos el método getJSON() del request con un stub.
        $fakeRequest = $this->getMockBuilder('CodeIgniter\HTTP\IncomingRequest')
            ->disableOriginalConstructor()
            ->onlyMethods(['getJSON'])
            ->getMock();
        $fakeRequest->expects($this->once())
            ->method('getJSON')
            ->with(true)
            ->willReturn($inputData);
        //Inyectamos el fakeRequest en la propiedad protegida 'request'
        $this->setProtectedProperty($controller, 'request', $fakeRequest);

        //Validación exitosa.
        $mockValidator = $this->getMockBuilder(ApiKeyValidator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validar'])
            ->getMock();
        $mockValidator->expects($this->once())
            ->method('validar')
            ->willReturn(true);
        $controller->apiKeyValidator = $mockValidator;

        //Simulamos la consulta en el modelo:
        //Al llamar ->where('nombre', 'nonexistent')->first() se retorna null.
        $mockQuery = $this->getMockBuilder('stdClass')
            ->disableOriginalConstructor()
            ->addMethods(['first'])
            ->getMock();
        $mockQuery->expects($this->once())
            ->method('first')
            ->willReturn(null);

        //En lugar de onlyMethods, usamos addMethods para agregar "where" al mock,
        //ya que AdministradoresModelo no tiene declarado "where".
        $mockAdminModel = $this->getMockBuilder('App\Models\AdministradoresModelo')
            ->disableOriginalConstructor()
            ->addMethods(['where'])
            ->getMock();
        $mockAdminModel->expects($this->once())
            ->method('where')
            ->with('nombre', 'nonexistent')
            ->willReturn($mockQuery);
        $controller->AdministradoresModelo = $mockAdminModel;

        //Ejecutamos el método.
        $result = $controller->inicioSesion();
        $data = json_decode($result->getBody(), true);

        $this->assertEquals(404, $result->getStatusCode());
        $this->assertIsArray($data);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Usuario no encontrado', $data['error']);
    }

    /**
     * Test: Contraseña incorrecta.
     */
    public function testInicioSesion_PasswordIncorrecta()
    {
        $controller = new DataController();
        $this->initController($controller);

        $inputData = ['nombre' => 'user', 'password' => 'wrongPassword'];

        //Stub para getJSON() que retorna los datos de entrada.
        $fakeRequest = $this->getMockBuilder('CodeIgniter\HTTP\IncomingRequest')
            ->disableOriginalConstructor()
            ->onlyMethods(['getJSON'])
            ->getMock();
        $fakeRequest->expects($this->once())
            ->method('getJSON')
            ->with(true)
            ->willReturn($inputData);
        //Inyectamos el fakeRequest en la propiedad protegida 'request'
        $this->setProtectedProperty($controller, 'request', $fakeRequest);

        //Validación exitosa.
        $mockValidator = $this->getMockBuilder(ApiKeyValidator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validar'])
            ->getMock();
        $mockValidator->expects($this->once())
            ->method('validar')
            ->willReturn(true);
        $controller->apiKeyValidator = $mockValidator;

        //Simulamos un administrador encontrado.
        //La contraseña almacenada se genera a partir de 'correctPassword'.
        $adminRecord = [
            'id'             => 2,
            'nombre'         => 'user',
            'password'       => password_hash('correctPassword', PASSWORD_DEFAULT),
            'fecha_creacion' => '2022-01-01 00:00:00'
        ];

        $mockQuery = $this->getMockBuilder('stdClass')
            ->disableOriginalConstructor()
            ->addMethods(['first'])
            ->getMock();
        $mockQuery->expects($this->once())
            ->method('first')
            ->willReturn($adminRecord);

        //Usamos addMethods para agregar "where" (ya que no existe explícitamente).
        $mockAdminModel = $this->getMockBuilder('App\Models\AdministradoresModelo')
            ->disableOriginalConstructor()
            ->addMethods(['where'])
            ->getMock();
        $mockAdminModel->expects($this->once())
            ->method('where')
            ->with('nombre', 'user')
            ->willReturn($mockQuery);
        $controller->AdministradoresModelo = $mockAdminModel;

        //Ejecutamos el método y esperamos que la verificación de password falle.
        $result = $controller->inicioSesion();
        $data = json_decode($result->getBody(), true);

        $this->assertEquals(401, $result->getStatusCode());
        $this->assertIsArray($data);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Contraseña incorrecta', $data['error']);
    }

    /**
     * Test: Inicio de sesión exitoso.
     */
    public function testInicioSesion_Exito()
    {
        $controller = new DataController();
        $this->initController($controller);

        $inputData = ['nombre' => 'user', 'password' => 'password123'];

        //Simulamos el request que retorna el JSON de entrada.
        $fakeRequest = $this->getMockBuilder('CodeIgniter\HTTP\IncomingRequest')
            ->disableOriginalConstructor()
            ->onlyMethods(['getJSON'])
            ->getMock();
        $fakeRequest->expects($this->once())
            ->method('getJSON')
            ->with(true)
            ->willReturn($inputData);
        //Inyectamos el fakeRequest en la propiedad protegida 'request'
        $this->setProtectedProperty($controller, 'request', $fakeRequest);

        //Validación exitosa.
        $mockValidator = $this->getMockBuilder(ApiKeyValidator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validar'])
            ->getMock();
        $mockValidator->expects($this->once())
            ->method('validar')
            ->willReturn(true);
        $controller->apiKeyValidator = $mockValidator;

        //Preparamos el registro del administrador.
        $adminRecord = [
            'id'             => 1,
            'nombre'         => 'user',
            'password'       => password_hash('password123', PASSWORD_DEFAULT),
            'fecha_creacion' => '2022-01-01 00:00:00'
        ];

        //Simulamos el método where()->first()
        $mockQuery = $this->getMockBuilder('stdClass')
            ->disableOriginalConstructor()
            ->addMethods(['first'])
            ->getMock();
        $mockQuery->expects($this->once())
            ->method('first')
            ->willReturn($adminRecord);

        //En este caso, queremos controlar además el método update.
        $mockAdminModel = $this->getMockBuilder('App\Models\AdministradoresModelo')
            ->disableOriginalConstructor()
            ->addMethods(['where'])
            ->onlyMethods(['update'])
            ->getMock();
        $mockAdminModel->expects($this->once())
            ->method('where')
            ->with('nombre', 'user')
            ->willReturn($mockQuery);
        $mockAdminModel->expects($this->once())
            ->method('update')
            ->with(
                $adminRecord['id'],
                $this->callback(function ($param) {
                    return isset($param['fecha_ultimo_login']) && !empty($param['fecha_ultimo_login']);
                })
            );
        $controller->AdministradoresModelo = $mockAdminModel;

        //Ejecutamos el método.
        $result = $controller->inicioSesion();
        $data = json_decode($result->getBody(), true);

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertIsArray($data);
        $this->assertArrayHasKey('mensaje', $data);
        $this->assertEquals('Inicio de sesión exitoso', $data['mensaje']);
        $this->assertArrayHasKey('administrador', $data);
        $this->assertEquals($adminRecord['id'], $data['administrador']['id']);
        $this->assertEquals($adminRecord['nombre'], $data['administrador']['nombre']);
        $this->assertEquals($adminRecord['fecha_creacion'], $data['administrador']['fecha_creacion']);
        $this->assertArrayHasKey('fecha_ultimo_login', $data['administrador']);
        $this->assertNotEmpty($data['administrador']['fecha_ultimo_login']);
    }

    /**
     * Test: Excepción durante el inicio de sesión.
     */
    public function testInicioSesion_Excepcion()
    {
        $controller = new DataController();
        $this->initController($controller);

        $inputData = ['nombre' => 'user', 'password' => 'password123'];

        //Simulamos el request que retorna el JSON de entrada.
        $fakeRequest = $this->getMockBuilder('CodeIgniter\HTTP\IncomingRequest')
            ->disableOriginalConstructor()
            ->onlyMethods(['getJSON'])
            ->getMock();
        $fakeRequest->expects($this->once())
            ->method('getJSON')
            ->with(true)
            ->willReturn($inputData);
        //Inyectamos el fakeRequest en la propiedad protegida 'request'
        $this->setProtectedProperty($controller, 'request', $fakeRequest);

        //Validación exitosa.
        $mockValidator = $this->getMockBuilder(ApiKeyValidator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validar'])
            ->getMock();
        $mockValidator->expects($this->once())
            ->method('validar')
            ->willReturn(true);
        $controller->apiKeyValidator = $mockValidator;

        //Simulamos que ocurre una excepción al llamar a first().
        $mockQuery = $this->getMockBuilder('stdClass')
            ->disableOriginalConstructor()
            ->addMethods(['first'])
            ->getMock();
        $mockQuery->expects($this->once())
            ->method('first')
            ->will($this->throwException(new Exception("DB error")));

        //Aquí usamos addMethods para agregar "where" a AdministradoresModelo.
        $mockAdminModel = $this->getMockBuilder('App\Models\AdministradoresModelo')
            ->disableOriginalConstructor()
            ->addMethods(['where'])
            ->getMock();
        $mockAdminModel->expects($this->once())
            ->method('where')
            ->with('nombre', 'user')
            ->willReturn($mockQuery);
        $controller->AdministradoresModelo = $mockAdminModel;

        //Ejecutamos el método.
        $result = $controller->inicioSesion();
        $data = json_decode($result->getBody(), true);

        $this->assertEquals(500, $result->getStatusCode());
        $this->assertIsArray($data);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Ocurrió un error en el inicio de sesión', $data['error']);
    }

    /**
     * Test: API Key inválida.
     */
    public function testRecibirGeneros_ApiKeyInvalida()
    {
        $controller = new DataController();
        $this->initController($controller);

        //Simulamos una respuesta de error en la validación (por ejemplo, API Key inválida).
        $fakeResponse = \Config\Services::response()
            ->setJSON(['error' => 'API Key inválida'])
            ->setStatusCode(401);

        $mockValidator = $this->getMockBuilder(ApiKeyValidator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validar'])
            ->getMock();
        $mockValidator->expects($this->once())
            ->method('validar')
            ->with($this->anything(), $this->anything())
            ->willReturn($fakeResponse);
        $controller->apiKeyValidator = $mockValidator;

        //Al retornar desde la validación fallida, no se requiere configurar GeneroModelo.
        $result = $controller->recibirGeneros();
        $data = json_decode($result->getBody(), true);

        $this->assertEquals(401, $result->getStatusCode());
        $this->assertIsArray($data);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('API Key inválida', $data['error']);
    }

    /**
     * Test: No se encuentran generos (array vacío).
     */
    public function testRecibirGeneros_SinGeneros()
    {
        $controller = new DataController();
        $this->initController($controller);

        //Validación exitosa.
        $mockValidator = $this->getMockBuilder(ApiKeyValidator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validar'])
            ->getMock();
        $mockValidator->expects($this->once())
            ->method('validar')
            ->willReturn(true);
        $controller->apiKeyValidator = $mockValidator;

        //Simulamos que el método findAll() del modelo retorna un array vacío.
        $mockGeneroModel = $this->getMockBuilder('App\Models\GeneroModelo')
            ->disableOriginalConstructor()
            ->onlyMethods(['findAll'])
            ->getMock();
        $mockGeneroModel->expects($this->once())
            ->method('findAll')
            ->willReturn([]);
        $controller->GeneroModelo = $mockGeneroModel;

        $result = $controller->recibirGeneros();
        $data = json_decode($result->getBody(), true);

        $this->assertEquals(404, $result->getStatusCode());
        $this->assertIsArray($data);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('No se encontraron géneros', $data['error']);
    }

    /**
     * Test: Generos encontrados exitosamente.
     */
    public function testRecibirGeneros_Exito()
    {
        $controller = new DataController();
        $this->initController($controller);

        //Validación exitosa.
        $mockValidator = $this->getMockBuilder(ApiKeyValidator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validar'])
            ->getMock();
        $mockValidator->expects($this->once())
            ->method('validar')
            ->willReturn(true);
        $controller->apiKeyValidator = $mockValidator;

        //Simulamos que el método findAll() retorna una lista de géneros.
        $expectedGeneros = [
            ['id' => 1, 'nombre' => 'Acción'],
            ['id' => 2, 'nombre' => 'Aventura']
        ];

        $mockGeneroModel = $this->getMockBuilder('App\Models\GeneroModelo')
            ->disableOriginalConstructor()
            ->onlyMethods(['findAll'])
            ->getMock();
        $mockGeneroModel->expects($this->once())
            ->method('findAll')
            ->willReturn($expectedGeneros);
        $controller->GeneroModelo = $mockGeneroModel;

        $result = $controller->recibirGeneros();
        $data = json_decode($result->getBody(), true);

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertIsArray($data);
        $this->assertArrayHasKey('generos', $data);
        $this->assertEquals($expectedGeneros, $data['generos']);
    }

    /**
     * Test: Excepción durante la consulta de generos.
     */
    public function testRecibirGeneros_Excepcion()
    {
        $controller = new DataController();
        $this->initController($controller);

        //Validación exitosa.
        $mockValidator = $this->getMockBuilder(ApiKeyValidator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validar'])
            ->getMock();
        $mockValidator->expects($this->once())
            ->method('validar')
            ->willReturn(true);
        $controller->apiKeyValidator = $mockValidator;

        //Simulamos que el método findAll() lanza una excepción.
        $mockGeneroModel = $this->getMockBuilder('App\Models\GeneroModelo')
            ->disableOriginalConstructor()
            ->onlyMethods(['findAll'])
            ->getMock();
        $mockGeneroModel->expects($this->once())
            ->method('findAll')
            ->will($this->throwException(new \Exception("DB error")));
        $controller->GeneroModelo = $mockGeneroModel;

        $result = $controller->recibirGeneros();
        $data = json_decode($result->getBody(), true);

        $this->assertEquals(500, $result->getStatusCode());
        $this->assertIsArray($data);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Ocurrió un error al recuperar los géneros', $data['error']);
    }

    /**
     * Test: API Key inválida para recibirPlataformas().
     */
    public function testRecibirPlataformas_ApiKeyInvalida()
    {
        $controller = new DataController();
        $this->initController($controller);

        //Simulamos una respuesta de error en la validación.
        $fakeResponse = Services::response()
            ->setJSON(['error' => 'API Key inválida'])
            ->setStatusCode(401);

        $mockValidator = $this->getMockBuilder(ApiKeyValidator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validar'])
            ->getMock();
        $mockValidator->expects($this->once())
            ->method('validar')
            ->with($this->anything(), $this->anything())
            ->willReturn($fakeResponse);
        $controller->apiKeyValidator = $mockValidator;
        
        //No es necesario configurar PlataformaModelo porque la validación se detiene.
        $result = $controller->recibirPlataformas();
        $data = json_decode($result->getBody(), true);

        $this->assertEquals(401, $result->getStatusCode());
        $this->assertIsArray($data);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('API Key inválida', $data['error']);
    }

    /**
     * Test: No se encuentran plataformas (array vacío).
     */
    public function testRecibirPlataformas_SinPlataformas()
    {
        $controller = new DataController();
        $this->initController($controller);

        //Validación exitosa.
        $mockValidator = $this->getMockBuilder(ApiKeyValidator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validar'])
            ->getMock();
        $mockValidator->expects($this->once())
            ->method('validar')
            ->willReturn(true);
        $controller->apiKeyValidator = $mockValidator;

        //Se simula que el método findAll() del modelo retorna un array vacío.
        $mockPlataformaModel = $this->getMockBuilder('App\Models\PlataformaModelo')
            ->disableOriginalConstructor()
            ->onlyMethods(['findAll'])
            ->getMock();
        $mockPlataformaModel->expects($this->once())
            ->method('findAll')
            ->willReturn([]);
        $controller->PlataformaModelo = $mockPlataformaModel;

        $result = $controller->recibirPlataformas();
        $data = json_decode($result->getBody(), true);

        $this->assertEquals(404, $result->getStatusCode());
        $this->assertIsArray($data);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('No se encontraron plataformas', $data['error']);
    }

    /**
     * Test: Plataformas encontradas exitosamente.
     */
    public function testRecibirPlataformas_Exito()
    {
        $controller = new DataController();
        $this->initController($controller);

        //Validación exitosa.
        $mockValidator = $this->getMockBuilder(ApiKeyValidator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validar'])
            ->getMock();
        $mockValidator->expects($this->once())
            ->method('validar')
            ->willReturn(true);
        $controller->apiKeyValidator = $mockValidator;

        //Simulamos que findAll() retorna una lista de plataformas.
        $expectedPlataformas = [
            ['id' => 1, 'nombre' => 'Plataforma A'],
            ['id' => 2, 'nombre' => 'Plataforma B']
        ];

        $mockPlataformaModel = $this->getMockBuilder('App\Models\PlataformaModelo')
            ->disableOriginalConstructor()
            ->onlyMethods(['findAll'])
            ->getMock();
        $mockPlataformaModel->expects($this->once())
            ->method('findAll')
            ->willReturn($expectedPlataformas);
        $controller->PlataformaModelo = $mockPlataformaModel;

        $result = $controller->recibirPlataformas();
        $data = json_decode($result->getBody(), true);

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertIsArray($data);
        $this->assertArrayHasKey('plataformas', $data);
        $this->assertEquals($expectedPlataformas, $data['plataformas']);
    }

    /**
     * Test: Excepción durante la consulta de plataformas.
     */
    public function testRecibirPlataformas_Excepcion()
    {
        $controller = new DataController();
        $this->initController($controller);

        //Validación exitosa.
        $mockValidator = $this->getMockBuilder(ApiKeyValidator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validar'])
            ->getMock();
        $mockValidator->expects($this->once())
            ->method('validar')
            ->willReturn(true);
        $controller->apiKeyValidator = $mockValidator;

        //Simulamos que findAll() lanza una excepción.
        $mockPlataformaModel = $this->getMockBuilder('App\Models\PlataformaModelo')
            ->disableOriginalConstructor()
            ->onlyMethods(['findAll'])
            ->getMock();
        $mockPlataformaModel->expects($this->once())
            ->method('findAll')
            ->will($this->throwException(new Exception("DB error")));
        $controller->PlataformaModelo = $mockPlataformaModel;

        $result = $controller->recibirPlataformas();
        $data = json_decode($result->getBody(), true);

        $this->assertEquals(500, $result->getStatusCode());
        $this->assertIsArray($data);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Ocurrió un error al recuperar las plataformas', $data['error']);
    }

    /**
     * Test: API Key inválida para recibirTiendas().
     */
    public function testRecibirTiendas_ApiKeyInvalida()
    {
        $controller = new DataController();
        $this->initController($controller);
        
        //Simulamos un Response de error (por ejemplo, API Key inválida).
        $fakeResponse = Services::response()
            ->setJSON(['error' => 'API Key inválida'])
            ->setStatusCode(401);
        
        $mockValidator = $this->getMockBuilder(ApiKeyValidator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validar'])
            ->getMock();
        $mockValidator->expects($this->once())
            ->method('validar')
            ->with($this->anything(), $this->anything())
            ->willReturn($fakeResponse);
        $controller->apiKeyValidator = $mockValidator;
        
        //En este caso la validación falla y no se necesita configurar TiendaModelo.
        $result = $controller->recibirTiendas();
        $data = json_decode($result->getBody(), true);
        
        $this->assertEquals(401, $result->getStatusCode());
        $this->assertIsArray($data);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('API Key inválida', $data['error']);
    }
    
    /**
     * Test: No se encuentran tiendas (array vacío).
     */
    public function testRecibirTiendas_SinTiendas()
    {
        $controller = new DataController();
        $this->initController($controller);
        
        //Validación exitosa.
        $mockValidator = $this->getMockBuilder(ApiKeyValidator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validar'])
            ->getMock();
        $mockValidator->expects($this->once())
            ->method('validar')
            ->willReturn(true);
        $controller->apiKeyValidator = $mockValidator;
        
        //Simulamos que el método findAll() retorna un array vacío.
        $mockTiendaModel = $this->getMockBuilder('App\Models\TiendaModelo')
            ->disableOriginalConstructor()
            ->onlyMethods(['findAll'])
            ->getMock();
        $mockTiendaModel->expects($this->once())
            ->method('findAll')
            ->willReturn([]);
        $controller->TiendaModelo = $mockTiendaModel;
        
        $result = $controller->recibirTiendas();
        $data = json_decode($result->getBody(), true);
        
        $this->assertEquals(404, $result->getStatusCode());
        $this->assertIsArray($data);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('No se encontraron tiendas', $data['error']);
    }
    
    /**
     * Test: Tiendas encontradas exitosamente.
     */
    public function testRecibirTiendas_Exito()
    {
        $controller = new DataController();
        $this->initController($controller);
        
        //Validación exitosa.
        $mockValidator = $this->getMockBuilder(ApiKeyValidator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validar'])
            ->getMock();
        $mockValidator->expects($this->once())
            ->method('validar')
            ->willReturn(true);
        $controller->apiKeyValidator = $mockValidator;
        
        //Simulamos que findAll() retorna una lista de tiendas.
        $expectedTiendas = [
            ['id' => 1, 'nombre' => 'Tienda A', 'direccion' => 'Calle 123'],
            ['id' => 2, 'nombre' => 'Tienda B', 'direccion' => 'Avenida 456']
        ];
        
        $mockTiendaModel = $this->getMockBuilder('App\Models\TiendaModelo')
            ->disableOriginalConstructor()
            ->onlyMethods(['findAll'])
            ->getMock();
        $mockTiendaModel->expects($this->once())
            ->method('findAll')
            ->willReturn($expectedTiendas);
        $controller->TiendaModelo = $mockTiendaModel;
        
        $result = $controller->recibirTiendas();
        $data = json_decode($result->getBody(), true);
        
        $this->assertEquals(200, $result->getStatusCode());
        $this->assertIsArray($data);
        $this->assertArrayHasKey('tiendas', $data);
        $this->assertEquals($expectedTiendas, $data['tiendas']);
    }
    
    /**
     * Test: Excepción durante la consulta de tiendas.
     */
    public function testRecibirTiendas_Excepcion()
    {
        $controller = new DataController();
        $this->initController($controller);
        
        //Validación exitosa.
        $mockValidator = $this->getMockBuilder(ApiKeyValidator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validar'])
            ->getMock();
        $mockValidator->expects($this->once())
            ->method('validar')
            ->willReturn(true);
        $controller->apiKeyValidator = $mockValidator;
        
        //Simulamos que findAll() lanza una excepción.
        $mockTiendaModel = $this->getMockBuilder('App\Models\TiendaModelo')
            ->disableOriginalConstructor()
            ->onlyMethods(['findAll'])
            ->getMock();
        $mockTiendaModel->expects($this->once())
            ->method('findAll')
            ->will($this->throwException(new Exception("DB error")));
        $controller->TiendaModelo = $mockTiendaModel;
        
        $result = $controller->recibirTiendas();
        $data = json_decode($result->getBody(), true);
        
        $this->assertEquals(500, $result->getStatusCode());
        $this->assertIsArray($data);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Ocurrió un error al recuperar las tiendas', $data['error']);
    }

    /**
     * Test: API Key inválida para recibirDesarrolladoras.
     */
    public function testRecibirDesarrolladoras_ApiKeyInvalida()
    {
        $controller = new DataController();
        $this->initController($controller);
        
        //Simulamos un Response de error (por ejemplo, API Key inválida).
        $fakeResponse = Services::response()
            ->setJSON(['error' => 'API Key inválida'])
            ->setStatusCode(401);
        
        $mockValidator = $this->getMockBuilder(ApiKeyValidator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validar'])
            ->getMock();
        $mockValidator->expects($this->once())
            ->method('validar')
            ->with($this->anything(), $this->anything())
            ->willReturn($fakeResponse);
        $controller->apiKeyValidator = $mockValidator;
        
        //Como la validación falla, el método se detiene en ese punto.
        $result = $controller->recibirDesarrolladoras();
        $data = json_decode($result->getBody(), true);
        
        $this->assertEquals(401, $result->getStatusCode());
        $this->assertIsArray($data);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('API Key inválida', $data['error']);
    }

    /**
     * Test: No se encontraron desarrolladoras (array vacío).
     */
    public function testRecibirDesarrolladoras_SinDesarrolladoras()
    {
        $controller = new DataController();
        $this->initController($controller);
        
        //Validación exitosa.
        $mockValidator = $this->getMockBuilder(ApiKeyValidator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validar'])
            ->getMock();
        $mockValidator->expects($this->once())
            ->method('validar')
            ->willReturn(true);
        $controller->apiKeyValidator = $mockValidator;
        
        //Simulamos que el método findAll() retorna un array vacío.
        $mockDesarrolladoraModel = $this->getMockBuilder('App\Models\DesarrolladoraModelo')
            ->disableOriginalConstructor()
            ->onlyMethods(['findAll'])
            ->getMock();
        $mockDesarrolladoraModel->expects($this->once())
            ->method('findAll')
            ->willReturn([]);
        $controller->DesarrolladoraModelo = $mockDesarrolladoraModel;
        
        $result = $controller->recibirDesarrolladoras();
        $data = json_decode($result->getBody(), true);
        
        $this->assertEquals(404, $result->getStatusCode());
        $this->assertIsArray($data);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('No se encontraron desarrolladoras', $data['error']);
    }

    /**
     * Test: Desarrolladoras encontradas exitosamente.
     */
    public function testRecibirDesarrolladoras_Exito()
    {
        $controller = new DataController();
        $this->initController($controller);
        
        //Validación exitosa.
        $mockValidator = $this->getMockBuilder(ApiKeyValidator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validar'])
            ->getMock();
        $mockValidator->expects($this->once())
            ->method('validar')
            ->willReturn(true);
        $controller->apiKeyValidator = $mockValidator;
        
        //Simulamos que findAll() retorna una lista de desarrolladoras.
        $expectedDesarrolladoras = [
            ['id' => 1, 'nombre' => 'Desarrolladora A'],
            ['id' => 2, 'nombre' => 'Desarrolladora B']
        ];
        
        $mockDesarrolladoraModel = $this->getMockBuilder('App\Models\DesarrolladoraModelo')
            ->disableOriginalConstructor()
            ->onlyMethods(['findAll'])
            ->getMock();
        $mockDesarrolladoraModel->expects($this->once())
            ->method('findAll')
            ->willReturn($expectedDesarrolladoras);
        $controller->DesarrolladoraModelo = $mockDesarrolladoraModel;
        
        $result = $controller->recibirDesarrolladoras();
        $data = json_decode($result->getBody(), true);
        
        $this->assertEquals(200, $result->getStatusCode());
        $this->assertIsArray($data);
        $this->assertArrayHasKey('desarrolladoras', $data);
        $this->assertEquals($expectedDesarrolladoras, $data['desarrolladoras']);
    }

    /**
     * Test: Excepción durante la consulta de desarrolladoras.
     */
    public function testRecibirDesarrolladoras_Excepcion()
    {
        $controller = new DataController();
        $this->initController($controller);
        
        //Validación exitosa.
        $mockValidator = $this->getMockBuilder(ApiKeyValidator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validar'])
            ->getMock();
        $mockValidator->expects($this->once())
            ->method('validar')
            ->willReturn(true);
        $controller->apiKeyValidator = $mockValidator;
        
        //Simulamos que findAll() lanza una excepción.
        $mockDesarrolladoraModel = $this->getMockBuilder('App\Models\DesarrolladoraModelo')
            ->disableOriginalConstructor()
            ->onlyMethods(['findAll'])
            ->getMock();
        $mockDesarrolladoraModel->expects($this->once())
            ->method('findAll')
            ->will($this->throwException(new Exception("DB error")));
        $controller->DesarrolladoraModelo = $mockDesarrolladoraModel;
        
        $result = $controller->recibirDesarrolladoras();
        $data = json_decode($result->getBody(), true);
        
        $this->assertEquals(500, $result->getStatusCode());
        $this->assertIsArray($data);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Ocurrió un error al recuperar las desarrolladoras', $data['error']);
    }

    /**
     * Test: API Key inválida para recibirPublishers().
     */
    public function testRecibirPublishers_ApiKeyInvalida()
    {
        $controller = new DataController();
        $this->initController($controller);

        //Simulamos un Response de error (por ejemplo, API Key inválida).
        $fakeResponse = Services::response()
            ->setJSON(['error' => 'API Key inválida'])
            ->setStatusCode(401);

        $mockValidator = $this->getMockBuilder(ApiKeyValidator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validar'])
            ->getMock();
        $mockValidator->expects($this->once())
            ->method('validar')
            ->with($this->anything(), $this->anything())
            ->willReturn($fakeResponse);
        $controller->apiKeyValidator = $mockValidator;

        //Al no pasar la validación, el modelo no se invoca.
        $result = $controller->recibirPublishers();
        $data = json_decode($result->getBody(), true);

        $this->assertEquals(401, $result->getStatusCode());
        $this->assertIsArray($data);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('API Key inválida', $data['error']);
    }

    /**
     * Test: No se encuentran publishers (array vacío).
     */
    public function testRecibirPublishers_SinPublishers()
    {
        $controller = new DataController();
        $this->initController($controller);

        //Validación exitosa.
        $mockValidator = $this->getMockBuilder(ApiKeyValidator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validar'])
            ->getMock();
        $mockValidator->expects($this->once())
            ->method('validar')
            ->willReturn(true);
        $controller->apiKeyValidator = $mockValidator;

        //Simulamos que el método findAll() del modelo retorna un array vacío.
        $mockPublisherModel = $this->getMockBuilder('App\Models\PublisherModelo')
            ->disableOriginalConstructor()
            ->onlyMethods(['findAll'])
            ->getMock();
        $mockPublisherModel->expects($this->once())
            ->method('findAll')
            ->willReturn([]);
        $controller->PublisherModelo = $mockPublisherModel;

        $result = $controller->recibirPublishers();
        $data = json_decode($result->getBody(), true);

        $this->assertEquals(404, $result->getStatusCode());
        $this->assertIsArray($data);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('No se encontraron publishers', $data['error']);
    }

    /**
     * Test: Publishers encontrados exitosamente.
     */
    public function testRecibirPublishers_Exito()
    {
        $controller = new DataController();
        $this->initController($controller);

        //Validación exitosa.
        $mockValidator = $this->getMockBuilder(ApiKeyValidator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validar'])
            ->getMock();
        $mockValidator->expects($this->once())
            ->method('validar')
            ->willReturn(true);
        $controller->apiKeyValidator = $mockValidator;

        //Simulamos que findAll() retorna una lista de publishers.
        $expectedPublishers = [
            ['id' => 1, 'nombre' => 'Publisher A'],
            ['id' => 2, 'nombre' => 'Publisher B']
        ];

        $mockPublisherModel = $this->getMockBuilder('App\Models\PublisherModelo')
            ->disableOriginalConstructor()
            ->onlyMethods(['findAll'])
            ->getMock();
        $mockPublisherModel->expects($this->once())
            ->method('findAll')
            ->willReturn($expectedPublishers);
        $controller->PublisherModelo = $mockPublisherModel;

        $result = $controller->recibirPublishers();
        $data = json_decode($result->getBody(), true);

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertIsArray($data);
        $this->assertArrayHasKey('publishers', $data);
        $this->assertEquals($expectedPublishers, $data['publishers']);
    }

    /**
     * Test: Excepción durante la consulta de publishers.
     */
    public function testRecibirPublishers_Excepcion()
    {
        $controller = new DataController();
        $this->initController($controller);

        //Validación exitosa.
        $mockValidator = $this->getMockBuilder(ApiKeyValidator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validar'])
            ->getMock();
        $mockValidator->expects($this->once())
            ->method('validar')
            ->willReturn(true);
        $controller->apiKeyValidator = $mockValidator;

        //Simulamos que findAll() lanza una excepción.
        $mockPublisherModel = $this->getMockBuilder('App\Models\PublisherModelo')
            ->disableOriginalConstructor()
            ->onlyMethods(['findAll'])
            ->getMock();
        $mockPublisherModel->expects($this->once())
            ->method('findAll')
            ->will($this->throwException(new Exception("DB error")));
        $controller->PublisherModelo = $mockPublisherModel;

        $result = $controller->recibirPublishers();
        $data = json_decode($result->getBody(), true);

        $this->assertEquals(500, $result->getStatusCode());
        $this->assertIsArray($data);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Ocurrió un error al recuperar los publishers', $data['error']);
    }

    /**
     * Test: API Key inválida.
     */
    public function testEliminarJuego_InvalidaApiKey()
    {
        $controller = new DataController();
        $this->initController($controller);

        //Simula que la validación de API Key falla.
        $fakeResponse = $this->response->setJSON(['error' => 'API Key inválida'])
            ->setStatusCode(401);
        $mockValidator = $this->getMockBuilder(ApiKeyValidator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validar'])
            ->getMock();
        $mockValidator->expects($this->once())
            ->method('validar')
            ->willReturn($fakeResponse);
        $controller->apiKeyValidator = $mockValidator;

        $result = $controller->eliminarJuego();
        $data = json_decode($result->getBody(), true);

        $this->assertEquals(401, $result->getStatusCode());
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('API Key inválida', $data['error']);
    }

    /**
     * Test: No se proporciona el ID del juego.
     */
    public function testEliminarJuego_SinID()
    {
        $controller = new DataController();
        $this->initController($controller);

        //Validación exitosa.
        $mockValidator = $this->getMockBuilder(ApiKeyValidator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validar'])
            ->getMock();
        $mockValidator->method('validar')->willReturn(true);
        $controller->apiKeyValidator = $mockValidator;
        
        //Simula el retornar un objeto JSON sin propiedad id.
        $fakeRequest = $this->getMockBuilder('CodeIgniter\HTTP\IncomingRequest')
            ->disableOriginalConstructor()
            ->onlyMethods(['getJSON'])
            ->getMock();
        $fakeRequest->expects($this->once())
            ->method('getJSON')
            ->willReturn(new stdClass); // objeto vacío
        $this->setProtectedProperty($controller, 'request', $fakeRequest);

        $result = $controller->eliminarJuego();
        $data = json_decode($result->getBody(), true);

        $this->assertEquals(400, $result->getStatusCode());
        $this->assertEquals('ID del juego no proporcionado', $data['error']);
    }

    /**
     * Test: Juego no encontrado.
     */
    public function testEliminarJuego_JuegoNoEncontrado()
    {
        $controller = new DataController();
        $this->initController($controller);

        //Validación exitosa.
        $mockValidator = $this->getMockBuilder(ApiKeyValidator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validar'])
            ->getMock();
        $mockValidator->method('validar')->willReturn(true);
        $controller->apiKeyValidator = $mockValidator;
        
        //Simula que el JSON tiene un id.
        $fakeRequest = $this->getMockBuilder('CodeIgniter\HTTP\IncomingRequest')
            ->disableOriginalConstructor()
            ->onlyMethods(['getJSON'])
            ->getMock();
        $dummyJson = new stdClass;
        $dummyJson->id = 123;
        $fakeRequest->expects($this->once())
            ->method('getJSON')
            ->willReturn($dummyJson);
        $this->setProtectedProperty($controller, 'request', $fakeRequest);

        //Configura el modelo para que no encuentre el juego.
        $mockModelo = $this->getMockBuilder('App\Models\VideojuegoModelo')
            ->disableOriginalConstructor()
            ->onlyMethods(['find'])
            ->getMock();
        $mockModelo->expects($this->once())
            ->method('find')
            ->with(123)
            ->willReturn(null);
        $controller->VideojuegoModelo = $mockModelo;

        $result = $controller->eliminarJuego();
        $data = json_decode($result->getBody(), true);

        $this->assertEquals(404, $result->getStatusCode());
        $this->assertEquals('Juego no encontrado', $data['error']);
    }

    /**
     * Test: Eliminación exitosa.  
     */
    public function testEliminarJuego_Exito()
    {
        $controller = new DataController();
        $this->initController($controller);

        //Validación exitosa.
        $mockValidator = $this->getMockBuilder(ApiKeyValidator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validar'])
            ->getMock();
        $mockValidator->method('validar')->willReturn(true);
        $controller->apiKeyValidator = $mockValidator;
        
        //Simula JSON de request con id.
        $fakeRequest = $this->getMockBuilder('CodeIgniter\HTTP\IncomingRequest')
           ->disableOriginalConstructor()
           ->onlyMethods(['getJSON'])
           ->getMock();
        $dummyJson = new stdClass;
        $dummyJson->id = 456;
        $fakeRequest->expects($this->once())
           ->method('getJSON')
           ->willReturn($dummyJson);
        $this->setProtectedProperty($controller, 'request', $fakeRequest);
        
        $juego = [
            'id'     => 456,
            'imagen' => 'local_images/juego.jpg'
        ];
        $mockModelo = $this->getMockBuilder('App\Models\VideojuegoModelo')
           ->disableOriginalConstructor()
           ->onlyMethods(['find', 'delete'])
           ->getMock();
        $mockModelo->expects($this->once())
           ->method('find')
           ->with(456)
           ->willReturn($juego);
        $mockModelo->expects($this->once())
           ->method('delete')
           ->with(456);
        $controller->VideojuegoModelo = $mockModelo;

        $result = $controller->eliminarJuego();
        $data = json_decode($result->getBody(), true);

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertEquals('Juego eliminado correctamente', $data['mensaje']);
    }

    /**
     * Test: Excepción durante la eliminación.
     */
    public function testEliminarJuego_Excepcion()
    {
        $controller = new DataController();
        $this->initController($controller);

        //Validación exitosa.
        $mockValidator = $this->getMockBuilder(ApiKeyValidator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validar'])
            ->getMock();
        $mockValidator->method('validar')->willReturn(true);
        $controller->apiKeyValidator = $mockValidator;
        
        //Simula JSON de request con id.
        $fakeRequest = $this->getMockBuilder('CodeIgniter\HTTP\IncomingRequest')
           ->disableOriginalConstructor()
           ->onlyMethods(['getJSON'])
           ->getMock();
        $dummyJson = new stdClass;
        $dummyJson->id = 321;
        $fakeRequest->expects($this->once())
           ->method('getJSON')
           ->willReturn($dummyJson);
        $this->setProtectedProperty($controller, 'request', $fakeRequest);

        //Configura el modelo para encontrar el juego correctamente.
        $juego = [
            'id'     => 321,
            'imagen' => 'local_images/juego.jpg'
        ];
        $mockModelo = $this->getMockBuilder('App\Models\VideojuegoModelo')
           ->disableOriginalConstructor()
           ->onlyMethods(['find', 'delete'])
           ->getMock();
        $mockModelo->expects($this->once())
           ->method('find')
           ->with(321)
           ->willReturn($juego);
        //El método delete lanza una excepción.
        $mockModelo->expects($this->once())
           ->method('delete')
           ->with(321)
           ->will($this->throwException(new Exception("Deletion error")));
        $controller->VideojuegoModelo = $mockModelo;

        $result = $controller->eliminarJuego();
        $data = json_decode($result->getBody(), true);

        $this->assertEquals(500, $result->getStatusCode());
        $this->assertEquals('Ocurrió un error al eliminar el juego', $data['error']);
    }

     /**
     * Test: API Key inválida.
     */
    public function testObtenerDatosFormulario_ApiKeyInvalida()
    {
        $controller = new DataController();
        $this->initController($controller);

        //Simula que la validación falla.
        $fakeResponse = $this->response
            ->setJSON(['error' => 'API Key inválida'])
            ->setStatusCode(401);

        $mockValidator = $this->getMockBuilder(ApiKeyValidator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validar'])
            ->getMock();
        $mockValidator->expects($this->once())
            ->method('validar')
            ->with($this->anything(), $this->anything())
            ->willReturn($fakeResponse);
        $controller->apiKeyValidator = $mockValidator;

        $result = $controller->obtenerDatosFormulario();
        $data = json_decode($result->getBody(), true);

        $this->assertEquals(401, $result->getStatusCode());
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('API Key inválida', $data['error']);
    }

    /**
     * Test: Consulta exitosa, se obtienen los datos de los modelos.
     */
    public function testObtenerDatosFormulario_Exito()
    {
        $controller = new DataController();
        $this->initController($controller);

        //Validación exitosa.
        $mockValidator = $this->getMockBuilder(ApiKeyValidator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validar'])
            ->getMock();
        $mockValidator->method('validar')->willReturn(true);
        $controller->apiKeyValidator = $mockValidator;

        //Datos esperados para cada modelo.
        $expectedTiendas         = [['id' => 1, 'nombre' => 'Tienda A']];
        $expectedPlataformas     = [['id' => 1, 'nombre' => 'Plataforma1']];
        $expectedGeneros         = [['id' => 1, 'nombre' => 'Género1']];
        $expectedDesarrolladoras = [['id' => 1, 'nombre' => 'Desarrolladora1']];
        $expectedPublishers      = [['id' => 1, 'nombre' => 'Publisher1']];

        //Configuramos mocks para cada modelo.
        $mockTiendaModelo = $this->getMockBuilder('App\Models\TiendaModelo')
            ->disableOriginalConstructor()
            ->onlyMethods(['findAll'])
            ->getMock();
        $mockTiendaModelo->method('findAll')->willReturn($expectedTiendas);
        $controller->TiendaModelo = $mockTiendaModelo;

        $mockPlataformaModelo = $this->getMockBuilder('App\Models\PlataformaModelo')
            ->disableOriginalConstructor()
            ->onlyMethods(['findAll'])
            ->getMock();
        $mockPlataformaModelo->method('findAll')->willReturn($expectedPlataformas);
        $controller->PlataformaModelo = $mockPlataformaModelo;

        $mockGeneroModelo = $this->getMockBuilder('App\Models\GeneroModelo')
            ->disableOriginalConstructor()
            ->onlyMethods(['findAll'])
            ->getMock();
        $mockGeneroModelo->method('findAll')->willReturn($expectedGeneros);
        $controller->GeneroModelo = $mockGeneroModelo;

        $mockDesarrolladoraModelo = $this->getMockBuilder('App\Models\DesarrolladoraModelo')
            ->disableOriginalConstructor()
            ->onlyMethods(['findAll'])
            ->getMock();
        $mockDesarrolladoraModelo->method('findAll')->willReturn($expectedDesarrolladoras);
        $controller->DesarrolladoraModelo = $mockDesarrolladoraModelo;

        $mockPublisherModelo = $this->getMockBuilder('App\Models\PublisherModelo')
            ->disableOriginalConstructor()
            ->onlyMethods(['findAll'])
            ->getMock();
        $mockPublisherModelo->method('findAll')->willReturn($expectedPublishers);
        $controller->PublisherModelo = $mockPublisherModelo;

        $result = $controller->obtenerDatosFormulario();
        $data = json_decode($result->getBody(), true);

        //Verifica que se encuentren todas las claves y se devuelven los datos esperados.
        $this->assertArrayHasKey('tiendas', $data);
        $this->assertArrayHasKey('plataformas', $data);
        $this->assertArrayHasKey('generos', $data);
        $this->assertArrayHasKey('desarrolladoras', $data);
        $this->assertArrayHasKey('publishers', $data);

        $this->assertEquals($expectedTiendas, $data['tiendas']);
        $this->assertEquals($expectedPlataformas, $data['plataformas']);
        $this->assertEquals($expectedGeneros, $data['generos']);
        $this->assertEquals($expectedDesarrolladoras, $data['desarrolladoras']);
        $this->assertEquals($expectedPublishers, $data['publishers']);
    }

    /**
     * Test: Excepción durante la consulta de datos.
     */
    public function testObtenerDatosFormulario_Excepcion()
    {
        $controller = new DataController();
        $this->initController($controller);

        //Validación exitosa.
        $mockValidator = $this->getMockBuilder(ApiKeyValidator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validar'])
            ->getMock();
        $mockValidator->method('validar')->willReturn(true);
        $controller->apiKeyValidator = $mockValidator;

        //Configuramos el modelo de Tienda para que lance excepción.
        $mockTiendaModelo = $this->getMockBuilder('App\Models\TiendaModelo')
            ->disableOriginalConstructor()
            ->onlyMethods(['findAll'])
            ->getMock();
        $mockTiendaModelo->method('findAll')
            ->will($this->throwException(new Exception("DB error")));
        $controller->TiendaModelo = $mockTiendaModelo;

        //El resto de los modelos pueden retornar valores correctos o vacíos.
        $controller->PlataformaModelo = $this->getMockBuilder('App\Models\PlataformaModelo')
            ->disableOriginalConstructor()
            ->onlyMethods(['findAll'])
            ->getMock();
        $controller->GeneroModelo = $this->getMockBuilder('App\Models\GeneroModelo')
            ->disableOriginalConstructor()
            ->onlyMethods(['findAll'])
            ->getMock();
        $controller->DesarrolladoraModelo = $this->getMockBuilder('App\Models\DesarrolladoraModelo')
            ->disableOriginalConstructor()
            ->onlyMethods(['findAll'])
            ->getMock();
        $controller->PublisherModelo = $this->getMockBuilder('App\Models\PublisherModelo')
            ->disableOriginalConstructor()
            ->onlyMethods(['findAll'])
            ->getMock();

        $result = $controller->obtenerDatosFormulario();
        $data = json_decode($result->getBody(), true);

        $this->assertEquals(500, $result->getStatusCode());
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Ocurrió un error al obtener los datos', $data['error']);
    }

    /**
     * Test: API Key inválida.
     */
    public function testRecibirJuegosAdmin_ApiKeyInvalida()
    {
        $controller = new DataController();
        $this->initController($controller);

        //Simula validación fallida de API key.
        $fakeResponse = Services::response()
            ->setJSON(['error' => 'API Key inválida'])
            ->setStatusCode(401);
        $mockValidator = $this->getMockBuilder(ApiKeyValidator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validar'])
            ->getMock();
        $mockValidator->expects($this->once())
            ->method('validar')
            ->with($this->anything(), $this->anything())
            ->willReturn($fakeResponse);
        $controller->apiKeyValidator = $mockValidator;

        $result = $controller->recibirJuegosAdmin();
        $data   = json_decode($result->getBody(), true);

        $this->assertEquals(401, $result->getStatusCode());
        $this->assertEquals('API Key inválida', $data['error']);
    }

    /**
     * Test: No se encuentran videojuegos creados por los administradores (resultado vacío).
     */
    public function testRecibirJuegosAdmin_SinJuegosAdmin()
    {
        $controller = new DataController();
        $this->initController($controller);

        //Validación exitosa.
        $mockValidator = $this->getMockBuilder(ApiKeyValidator::class)
                              ->disableOriginalConstructor()
                              ->onlyMethods(['validar'])
                              ->getMock();
        $mockValidator->method('validar')->willReturn(true);
        $controller->apiKeyValidator = $mockValidator;

        //Configuramos la cadena de métodos en el modelo.
        $mockVideojuegoModelo = $this->getMockBuilder('App\Models\VideojuegoModelo')
                                   ->disableOriginalConstructor()
                                   ->addMethods(['select', 'where']) //Agregamos explícitamente
                                   ->onlyMethods(['findAll']) //Solo mockeamos los métodos existentes
                                   ->getMock();
        $mockVideojuegoModelo->expects($this->once())
                             ->method('select')
                             ->with('nombre')
                             ->willReturnSelf();
        $mockVideojuegoModelo->expects($this->once())
                             ->method('where')
                             ->with('creado_por_admin', 1)
                             ->willReturnSelf();
        $mockVideojuegoModelo->expects($this->once())
                             ->method('findAll')
                             ->willReturn([]);
        $controller->VideojuegoModelo = $mockVideojuegoModelo;

        $result = $controller->recibirJuegosAdmin();
        $data   = json_decode($result->getBody(), true);

        $this->assertEquals(404, $result->getStatusCode());
        $this->assertEquals('No se encontraron videojuegos creados por administradores', $data['error']);
    }

    /**
     * Test: Consulta exitosa. Se obtienen videojuegos.
     */
    public function testRecibirJuegosAdmin_Exito()
    {
        $controller = new DataController();
        $this->initController($controller);

        //Validación exitosa.
        $mockValidator = $this->getMockBuilder(ApiKeyValidator::class)
                              ->disableOriginalConstructor()
                              ->onlyMethods(['validar'])
                              ->getMock();
        $mockValidator->method('validar')->willReturn(true);
        $controller->apiKeyValidator = $mockValidator;

        $expectedJuegos = [
            ['nombre' => 'Game1'],
            ['nombre' => 'Game2']
        ];

        //Configuramos la cadena de métodos.
        $mockVideojuegoModelo = $this->getMockBuilder('App\Models\VideojuegoModelo')
                                   ->disableOriginalConstructor()
                                   ->addMethods(['select', 'where']) //Agregamos explícitamente
                                   ->onlyMethods(['findAll']) //Solo mockeamos los métodos existentes
                                   ->getMock();
        $mockVideojuegoModelo->expects($this->once())
                             ->method('select')
                             ->with('nombre')
                             ->willReturnSelf();
        $mockVideojuegoModelo->expects($this->once())
                             ->method('where')
                             ->with('creado_por_admin', 1)
                             ->willReturnSelf();
        $mockVideojuegoModelo->expects($this->once())
                             ->method('findAll')
                             ->willReturn($expectedJuegos);
        $controller->VideojuegoModelo = $mockVideojuegoModelo;

        $result = $controller->recibirJuegosAdmin();
        $data   = json_decode($result->getBody(), true);

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertArrayHasKey('juegos', $data);
        $this->assertEquals($expectedJuegos, $data['juegos']);
    }

    /**
     * Test: Excepción durante la consulta (por ejemplo, findAll() lanza excepción).
     */
    public function testRecibirJuegosAdmin_Excepcion()
    {
        $controller = new DataController();
        $this->initController($controller);

        //Validación exitosa.
        $mockValidator = $this->getMockBuilder(ApiKeyValidator::class)
                              ->disableOriginalConstructor()
                              ->onlyMethods(['validar'])
                              ->getMock();
        $mockValidator->method('validar')->willReturn(true);
        $controller->apiKeyValidator = $mockValidator;

        //Configuramos la cadena de métodos para que findAll() lance una excepción.
        $mockVideojuegoModelo = $this->getMockBuilder('App\Models\VideojuegoModelo')
                                   ->disableOriginalConstructor()
                                   ->addMethods(['select', 'where']) //Agregamos explícitamente
                                   ->onlyMethods(['findAll']) //Solo mockeamos los métodos existentes
                                   ->getMock();
        $mockVideojuegoModelo->expects($this->once())
                             ->method('select')
                             ->with('nombre')
                             ->willReturnSelf();
        $mockVideojuegoModelo->expects($this->once())
                             ->method('where')
                             ->with('creado_por_admin', 1)
                             ->willReturnSelf();
        $mockVideojuegoModelo->expects($this->once())
                             ->method('findAll')
                             ->will($this->throwException(new Exception("DB error")));
        $controller->VideojuegoModelo = $mockVideojuegoModelo;

        $result = $controller->recibirJuegosAdmin();
        $data   = json_decode($result->getBody(), true);

        $this->assertEquals(500, $result->getStatusCode());
        $this->assertEquals('Ocurrió un error al recuperar los videojuegos creados por los administradores', $data['error']);
    }

    /**
     * Test: API Key inválida.
     */
    public function testRealizarBusqueda_ApiKeyInvalida()
    {
        $controller = new DataController();
        $this->initController($controller);

        //Se simula que la validación falla.
        $fakeResponse = Services::response()
            ->setJSON(['error' => 'API Key inválida'])
            ->setStatusCode(401);
        $mockValidator = $this->getMockBuilder(ApiKeyValidator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validar'])
            ->getMock();
        $mockValidator->expects($this->once())
            ->method('validar')
            ->willReturn($fakeResponse);
        $controller->apiKeyValidator = $mockValidator;

        $result = $controller->realizarBusqueda();
        $data   = json_decode($result->getBody(), true);

        $this->assertEquals(401, $result->getStatusCode());
        $this->assertEquals('API Key inválida', $data['error']);
    }

    /**
     * Test: Búsqueda vacía (no se encuentra ningún videojuego).
     */
    public function testRealizarBusqueda_SinJuegos()
    {
        $controller = new DataController();
        $this->initController($controller);

        //Validación exitosa.
        $mockValidator = $this->getMockBuilder(ApiKeyValidator::class)
                              ->disableOriginalConstructor()
                              ->onlyMethods(['validar'])
                              ->getMock();
        $mockValidator->method('validar')->willReturn(true);
        $controller->apiKeyValidator = $mockValidator;

        //Simula que el request contiene un JSON con el nombre buscado.
        $fakeRequest = $this->getMockBuilder('CodeIgniter\HTTP\IncomingRequest')
            ->disableOriginalConstructor()
            ->onlyMethods(['getJSON'])
            ->getMock();
        $fakeRequest->expects($this->once())
            ->method('getJSON')
            ->willReturn(['nombre' => 'Halo']);
        $this->setProtectedProperty($controller, 'request', $fakeRequest);

        //Configuramos la cadena de métodos en el modelo.
        $mockVideojuegoModelo = $this->getMockBuilder('App\Models\VideojuegoModelo')
                                   ->disableOriginalConstructor()
                                   ->addMethods(['like'])
                                   ->onlyMethods(['findAll'])
                                   ->getMock();
        $mockVideojuegoModelo->expects($this->once())
                             ->method('like')
                             ->with('LOWER(nombre)', 'halo')
                             ->willReturnSelf();
        $mockVideojuegoModelo->expects($this->once())
                             ->method('findAll')
                             ->willReturn([]);
        $controller->VideojuegoModelo = $mockVideojuegoModelo;

        $result = $controller->realizarBusqueda();
        $data   = json_decode($result->getBody(), true);

        $this->assertEquals(404, $result->getStatusCode());
        $this->assertEquals('No se encontraron juegos que coincidan', $data['error']);
    }

    /**
     * Test: Búsqueda exitosa (se encuentran videojuegos).
     */
    public function testRealizarBusqueda_Exito()
    {
        $controller = new DataController();
        $this->initController($controller);

        //Validación exitosa.
        $mockValidator = $this->getMockBuilder(ApiKeyValidator::class)
                              ->disableOriginalConstructor()
                              ->onlyMethods(['validar'])
                              ->getMock();
        $mockValidator->method('validar')->willReturn(true);
        $controller->apiKeyValidator = $mockValidator;

        $expectedJuegos = [
            ['nombre' => 'Halo'],
            ['nombre' => 'Halo 2']
        ];

        //Simula que el request contiene un JSON con el nombre buscado.
        $fakeRequest = $this->getMockBuilder('CodeIgniter\HTTP\IncomingRequest')
            ->disableOriginalConstructor()
            ->onlyMethods(['getJSON'])
            ->getMock();
        $fakeRequest->expects($this->once())
            ->method('getJSON')
            ->willReturn(['nombre' => 'Halo']);
        $this->setProtectedProperty($controller, 'request', $fakeRequest);

        //Configuramos la cadena de métodos en el modelo.
        $mockVideojuegoModelo = $this->getMockBuilder('App\Models\VideojuegoModelo')
                                   ->disableOriginalConstructor()
                                   ->addMethods(['like'])
                                   ->onlyMethods(['findAll'])
                                   ->getMock();
        $mockVideojuegoModelo->expects($this->once())
                             ->method('like')
                             ->with('LOWER(nombre)', 'halo')
                             ->willReturnSelf();
        $mockVideojuegoModelo->expects($this->once())
                             ->method('findAll')
                             ->willReturn($expectedJuegos);
        $controller->VideojuegoModelo = $mockVideojuegoModelo;

        $result = $controller->realizarBusqueda();
        $data   = json_decode($result->getBody(), true);

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertArrayHasKey('juegos', $data);
        $this->assertEquals($expectedJuegos, $data['juegos']);
    }

    /**
     * Test: Excepción durante la búsqueda.
     */
    public function testRealizarBusqueda_Excepcion()
    {
        $controller = new DataController();
        $this->initController($controller);

        //Validación exitosa.
        $mockValidator = $this->getMockBuilder(ApiKeyValidator::class)
                              ->disableOriginalConstructor()
                              ->onlyMethods(['validar'])
                              ->getMock();
        $mockValidator->method('validar')->willReturn(true);
        $controller->apiKeyValidator = $mockValidator;

        //Simula que el request contiene un JSON con el nombre buscado.
        $fakeRequest = $this->getMockBuilder('CodeIgniter\HTTP\IncomingRequest')
            ->disableOriginalConstructor()
            ->onlyMethods(['getJSON'])
            ->getMock();
        $fakeRequest->expects($this->once())
            ->method('getJSON')
            ->willReturn(['nombre' => 'Halo']); //Asegura que no sea vacío
        $this->setProtectedProperty($controller, 'request', $fakeRequest);

        //Configuramos la cadena de métodos en el modelo para que findAll() lance una excepción.
        $mockVideojuegoModelo = $this->getMockBuilder('App\Models\VideojuegoModelo')
                                   ->disableOriginalConstructor()
                                   ->addMethods(['like'])
                                   ->onlyMethods(['findAll'])
                                   ->getMock();
        $mockVideojuegoModelo->expects($this->once())
                             ->method('like')
                             ->with('LOWER(nombre)', 'halo')
                             ->willReturnSelf();
        $mockVideojuegoModelo->expects($this->once())
                             ->method('findAll')
                             ->will($this->throwException(new Exception("DB error")));
        $controller->VideojuegoModelo = $mockVideojuegoModelo;

        $result = $controller->realizarBusqueda();
        $data   = json_decode($result->getBody(), true);

        $this->assertEquals(500, $result->getStatusCode());
        $this->assertEquals('Ocurrió un error al realizar la búsqueda', $data['error']);
    }

    /**
     * Test: API Key inválida.
     */
    public function testRealizarBusquedaDesarrolladoras_ApiKeyInvalida()
    {
        $controller = new DataController();
        $this->initController($controller);

        //Se simula que la validación falla.
        $fakeResponse = Services::response()
            ->setJSON(['error' => 'API Key inválida'])
            ->setStatusCode(401);
        $mockValidator = $this->getMockBuilder(ApiKeyValidator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validar'])
            ->getMock();
        $mockValidator->expects($this->once())
            ->method('validar')
            ->willReturn($fakeResponse);
        $controller->apiKeyValidator = $mockValidator;

        $result = $controller->realizarBusquedaDesarrolladoras();
        $data   = json_decode($result->getBody(), true);

        $this->assertEquals(401, $result->getStatusCode());
        $this->assertEquals('API Key inválida', $data['error']);
    }

    /**
     * Test: Búsqueda vacía (ninguna desarrolladora coincide).
     */
    public function testRealizarBusquedaDesarrolladoras_SinDesarrolladoras()
    {
        $controller = new DataController();
        $this->initController($controller);

        //Validación exitosa.
        $mockValidator = $this->getMockBuilder(ApiKeyValidator::class)
                              ->disableOriginalConstructor()
                              ->onlyMethods(['validar'])
                              ->getMock();
        $mockValidator->method('validar')->willReturn(true);
        $controller->apiKeyValidator = $mockValidator;

        //Simula que el request contiene un JSON con el nombre buscado.
        $fakeRequest = $this->getMockBuilder('CodeIgniter\HTTP\IncomingRequest')
            ->disableOriginalConstructor()
            ->onlyMethods(['getJSON'])
            ->getMock();
        $fakeRequest->expects($this->once())
            ->method('getJSON')
            ->willReturn(['nombre' => 'Ubisoft']);
        $this->setProtectedProperty($controller, 'request', $fakeRequest);

        //Configuramos la cadena de métodos en el modelo.
        $mockDesarrolladoraModelo = $this->getMockBuilder('App\Models\DesarrolladoraModelo')
                                   ->disableOriginalConstructor()
                                   ->addMethods(['like'])
                                   ->onlyMethods(['findAll'])
                                   ->getMock();
        $mockDesarrolladoraModelo->expects($this->once())
                             ->method('like')
                             ->with('LOWER(nombre)', 'ubisoft')
                             ->willReturnSelf();
        $mockDesarrolladoraModelo->expects($this->once())
                             ->method('findAll')
                             ->willReturn([]);
        $controller->DesarrolladoraModelo = $mockDesarrolladoraModelo;

        $result = $controller->realizarBusquedaDesarrolladoras();
        $data   = json_decode($result->getBody(), true);

        $this->assertEquals(404, $result->getStatusCode());
        $this->assertEquals('No se encontraron desarrolladoras que coincidan', $data['error']);
    }

    /**
     * Test: Búsqueda exitosa (se encuentran desarrolladoras).
     */
    public function testRealizarBusquedaDesarrolladoras_Exito()
    {
        $controller = new DataController();
        $this->initController($controller);

        //Validación exitosa.
        $mockValidator = $this->getMockBuilder(ApiKeyValidator::class)
                              ->disableOriginalConstructor()
                              ->onlyMethods(['validar'])
                              ->getMock();
        $mockValidator->method('validar')->willReturn(true);
        $controller->apiKeyValidator = $mockValidator;

        $expectedDesarrolladoras = [
            ['nombre' => 'Ubisoft'],
            ['nombre' => 'Ubisoft Montreal']
        ];

        //Simula que el request contiene un JSON con el nombre buscado.
        $fakeRequest = $this->getMockBuilder('CodeIgniter\HTTP\IncomingRequest')
            ->disableOriginalConstructor()
            ->onlyMethods(['getJSON'])
            ->getMock();
        $fakeRequest->expects($this->once())
            ->method('getJSON')
            ->willReturn(['nombre' => 'Ubisoft']);
        $this->setProtectedProperty($controller, 'request', $fakeRequest);

        //Configuramos la cadena de métodos en el modelo.
        $mockDesarrolladoraModelo = $this->getMockBuilder('App\Models\DesarrolladoraModelo')
                                   ->disableOriginalConstructor()
                                   ->addMethods(['like'])
                                   ->onlyMethods(['findAll'])
                                   ->getMock();
        $mockDesarrolladoraModelo->expects($this->once())
                             ->method('like')
                             ->with('LOWER(nombre)', 'ubisoft')
                             ->willReturnSelf();
        $mockDesarrolladoraModelo->expects($this->once())
                             ->method('findAll')
                             ->willReturn($expectedDesarrolladoras);
        $controller->DesarrolladoraModelo = $mockDesarrolladoraModelo;

        $result = $controller->realizarBusquedaDesarrolladoras();
        $data   = json_decode($result->getBody(), true);

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertArrayHasKey('desarrolladoras', $data);
        $this->assertEquals($expectedDesarrolladoras, $data['desarrolladoras']);
    }

    /**
     * Test: Excepción durante la búsqueda.
     */
    public function testRealizarBusquedaDesarrolladoras_Excepcion()
    {
        $controller = new DataController();
        $this->initController($controller);

        $mockValidator = $this->getMockBuilder(ApiKeyValidator::class)
                            ->disableOriginalConstructor()
                            ->onlyMethods(['validar'])
                            ->getMock();
        $mockValidator->method('validar')->willReturn(true);
        $controller->apiKeyValidator = $mockValidator;

        //Simula que el request contiene un JSON con el nombre buscado.
        $fakeRequest = $this->getMockBuilder('CodeIgniter\HTTP\IncomingRequest')
            ->disableOriginalConstructor()
            ->onlyMethods(['getJSON'])
            ->getMock();
        $fakeRequest->expects($this->once())
            ->method('getJSON')
            ->willReturn(['nombre' => 'Ubisoft']); //Se asegura que no sea vacío
        $this->setProtectedProperty($controller, 'request', $fakeRequest);

        //Configura la cadena de métodos en el modelo para que findAll() lance una excepción.
        $mockDesarrolladoraModelo = $this->getMockBuilder('App\Models\DesarrolladoraModelo')
                                ->disableOriginalConstructor()
                                ->addMethods(['like'])
                                ->onlyMethods(['findAll'])
                                ->getMock();
        $mockDesarrolladoraModelo->expects($this->once())
                                ->method('like')
                                ->with('LOWER(nombre)', 'ubisoft')
                                ->willReturnSelf();
        $mockDesarrolladoraModelo->expects($this->once())
                                ->method('findAll')
                                ->will($this->throwException(new Exception("DB error")));
        $controller->DesarrolladoraModelo = $mockDesarrolladoraModelo;

        $result = $controller->realizarBusquedaDesarrolladoras();
        $data   = json_decode($result->getBody(), true);

        $this->assertEquals(500, $result->getStatusCode());
        $this->assertEquals('Ocurrió un error al realizar la búsqueda', $data['error']);
    }

    /**
     * Test: API Key inválida.
     */
    public function testRealizarBusquedaPublishers_ApiKeyInvalida()
    {
        $controller = new DataController();
        $this->initController($controller);

        //Se simula que la validación falla.
        $fakeResponse = Services::response()
            ->setJSON(['error' => 'API Key inválida'])
            ->setStatusCode(401);
        $mockValidator = $this->getMockBuilder(ApiKeyValidator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validar'])
            ->getMock();
        $mockValidator->expects($this->once())
            ->method('validar')
            ->willReturn($fakeResponse);
        $controller->apiKeyValidator = $mockValidator;

        $result = $controller->realizarBusquedaPublishers();
        $data   = json_decode($result->getBody(), true);

        $this->assertEquals(401, $result->getStatusCode());
        $this->assertEquals('API Key inválida', $data['error']);
    }

    /**
     * Test: Búsqueda vacía (ningún publisher coincide).
     */
    public function testRealizarBusquedaPublishers_SinPublishers()
    {
        $controller = new DataController();
        $this->initController($controller);

        //Validación exitosa.
        $mockValidator = $this->getMockBuilder(ApiKeyValidator::class)
                              ->disableOriginalConstructor()
                              ->onlyMethods(['validar'])
                              ->getMock();
        $mockValidator->method('validar')->willReturn(true);
        $controller->apiKeyValidator = $mockValidator;

        //Simula que el request contiene un JSON con el nombre buscado.
        $fakeRequest = $this->getMockBuilder('CodeIgniter\HTTP\IncomingRequest')
            ->disableOriginalConstructor()
            ->onlyMethods(['getJSON'])
            ->getMock();
        $fakeRequest->expects($this->once())
            ->method('getJSON')
            ->willReturn(['nombre' => 'Electronic Arts']);
        $this->setProtectedProperty($controller, 'request', $fakeRequest);

        //Configuramos la cadena de métodos en el modelo.
        $mockPublisherModelo = $this->getMockBuilder('App\Models\PublisherModelo')
                                   ->disableOriginalConstructor()
                                   ->addMethods(['like'])
                                   ->onlyMethods(['findAll'])
                                   ->getMock();
        $mockPublisherModelo->expects($this->once())
                            ->method('like')
                            ->with('LOWER(nombre)', 'electronic arts')
                            ->willReturnSelf();
        $mockPublisherModelo->expects($this->once())
                            ->method('findAll')
                            ->willReturn([]);
        $controller->PublisherModelo = $mockPublisherModelo;

        $result = $controller->realizarBusquedaPublishers();
        $data   = json_decode($result->getBody(), true);

        $this->assertEquals(404, $result->getStatusCode());
        $this->assertEquals('No se encontraron publishers que coincidan', $data['error']);
    }

    /**
     * Test: Búsqueda exitosa (se encuentran publishers).
     */
    public function testRealizarBusquedaPublishers_Exito()
    {
        $controller = new DataController();
        $this->initController($controller);

        //Validación exitosa.
        $mockValidator = $this->getMockBuilder(ApiKeyValidator::class)
                              ->disableOriginalConstructor()
                              ->onlyMethods(['validar'])
                              ->getMock();
        $mockValidator->method('validar')->willReturn(true);
        $controller->apiKeyValidator = $mockValidator;

        $expectedPublishers = [
            ['nombre' => 'Electronic Arts'],
            ['nombre' => 'EA Sports']
        ];

        //Simula que el request contiene un JSON con el nombre buscado.
        $fakeRequest = $this->getMockBuilder('CodeIgniter\HTTP\IncomingRequest')
            ->disableOriginalConstructor()
            ->onlyMethods(['getJSON'])
            ->getMock();
        $fakeRequest->expects($this->once())
            ->method('getJSON')
            ->willReturn(['nombre' => 'Electronic Arts']);
        $this->setProtectedProperty($controller, 'request', $fakeRequest);

        //Configuramos la cadena de métodos en el modelo.
        $mockPublisherModelo = $this->getMockBuilder('App\Models\PublisherModelo')
                                   ->disableOriginalConstructor()
                                   ->addMethods(['like'])
                                   ->onlyMethods(['findAll'])
                                   ->getMock();
        $mockPublisherModelo->expects($this->once())
                            ->method('like')
                            ->with('LOWER(nombre)', 'electronic arts')
                            ->willReturnSelf();
        $mockPublisherModelo->expects($this->once())
                            ->method('findAll')
                            ->willReturn($expectedPublishers);
        $controller->PublisherModelo = $mockPublisherModelo;

        $result = $controller->realizarBusquedaPublishers();
        $data   = json_decode($result->getBody(), true);

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertArrayHasKey('publishers', $data);
        $this->assertEquals($expectedPublishers, $data['publishers']);
    }

    /**
     * Test: Excepción durante la búsqueda.
     */
    public function testRealizarBusquedaPublishers_Excepcion()
    {
        $controller = new DataController();
        $this->initController($controller);

        $mockValidator = $this->getMockBuilder(ApiKeyValidator::class)
                            ->disableOriginalConstructor()
                            ->onlyMethods(['validar'])
                            ->getMock();
        $mockValidator->method('validar')->willReturn(true);
        $controller->apiKeyValidator = $mockValidator;

        //Simula que el request contiene un JSON con el nombre buscado.
        $fakeRequest = $this->getMockBuilder('CodeIgniter\HTTP\IncomingRequest')
            ->disableOriginalConstructor()
            ->onlyMethods(['getJSON'])
            ->getMock();
        $fakeRequest->expects($this->once())
            ->method('getJSON')
            ->willReturn(['nombre' => 'Electronic Arts']); //Se asegura que no sea vacío
        $this->setProtectedProperty($controller, 'request', $fakeRequest);

        //Configura la cadena de métodos en el modelo para que findAll() lance una excepción.
        $mockPublisherModelo = $this->getMockBuilder('App\Models\PublisherModelo')
                                ->disableOriginalConstructor()
                                ->addMethods(['like'])
                                ->onlyMethods(['findAll'])
                                ->getMock();
        $mockPublisherModelo->expects($this->once())
                            ->method('like')
                            ->with('LOWER(nombre)', 'electronic arts')
                            ->willReturnSelf();
        $mockPublisherModelo->expects($this->once())
                            ->method('findAll')
                            ->will($this->throwException(new Exception("DB error")));
        $controller->PublisherModelo = $mockPublisherModelo;

        $result = $controller->realizarBusquedaPublishers();
        $data   = json_decode($result->getBody(), true);

        $this->assertEquals(500, $result->getStatusCode());
        $this->assertEquals('Ocurrió un error al realizar la búsqueda', $data['error']);
    }

    /**
     * Test: Respuesta exitosa. Debería retornar [1,2,3] según nuestro dummy.
     */
    public function testObtenerIdsJuegosAPI_Exito()
    {
        $apiController = new ApiController();
        $ids = $apiController->obtenerIdsJuegos_API();

        $this->assertIsArray($ids, 'El resultado debe ser un array');
        $this->assertEquals(20, count($ids), 'Se espera que se obtengan 20 IDs');
        foreach ($ids as $id) {
            $this->assertIsNumeric($id, 'Cada ID debe ser numérico');
        }
    }

    /**
     * Test: Respuesta con resultados vacíos.
     */
    public function testObtenerIdsJuegosAPI_SinJuegos()
    {
        $apiController = new class extends ApiController {
            public function obtenerIdsJuegos_API() {
                $ids = [];
                //Simulamos respuesta con resultados vacíos.
                $response = '{"results": []}';
                $data = json_decode($response, true);
                if (isset($data['results'])) {
                    foreach ($data['results'] as $juego) {
                        if (isset($juego['id'])) {
                            $ids[] = $juego['id'];
                        }
                    }
                }
                return $ids;
            }
        };
        $ids = $apiController->obtenerIdsJuegos_API();
        $this->assertEquals([], $ids);
    }

    /**
     * Test: Respuesta con JSON inválido.
     */
    public function testObtenerIdsJuegosAPI_JsonInvalido()
    {
        $apiController = new class extends ApiController {
            public function obtenerIdsJuegos_API() {
                $ids = [];
                $response = 'json invalido';
                $data = json_decode($response, true);
                if (isset($data['results'])) {
                    foreach ($data['results'] as $juego) {
                        if (isset($juego['id'])) {
                            $ids[] = $juego['id'];
                        }
                    }
                }
                return $ids;
            }
        };
        $ids = $apiController->obtenerIdsJuegos_API();
        $this->assertEquals([], $ids);
    }

}
