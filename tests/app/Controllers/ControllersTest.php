<?php

namespace App\Controllers;

use App\Base\BaseTestCase;
use App\Models\VideojuegoModelo;
use Config\Services;
use App\Services\ApiKeyValidator;
use Exception;
use App\Controllers\DataController;

class ControllersTest extends BaseTestCase
{
        
    /**
     * Inicializa el controlador con request, response y logger.
     */
    private function initController(DataController $controller): void
    {
        // Aseguramos que se inicialicen las propiedades necesarias del controlador.
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
    public function testRecibirJuegos_invalidApiKey()
    {
        $controller = new DataController();
        $this->initController($controller);

        // Creamos un fake response usando el servicio de response.
        $fakeResponse = Services::response()
            ->setJSON(['error' => 'API Key inválida'])
            ->setStatusCode(401);

        // Creamos el mock de ApiKeyValidator para forzar una validación fallida.
        $mockValidator = $this->getMockBuilder(ApiKeyValidator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validar'])
            ->getMock();

        $mockValidator->expects($this->once())
            ->method('validar')
            ->with($this->anything(), $this->anything())
            ->willReturn($fakeResponse);

        $controller->apiKeyValidator = $mockValidator;
        // No es necesario configurar el modelo puesto que la validación se detiene antes.

        $result = $controller->recibirJuegos();

        // Obtenemos y decodificamos el contenido JSON
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
    public function testRecibirJuegos_noJuegos()
    {
        $controller = new DataController();
        $this->initController($controller);

        // Configuramos el mock para la validación exitosa.
        $mockValidator = $this->getMockBuilder(ApiKeyValidator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validar'])
            ->getMock();

        $mockValidator->expects($this->once())
            ->method('validar')
            ->willReturn(true);
        $controller->apiKeyValidator = $mockValidator;

        // Simulamos al modelo devolviendo un array vacío.
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
    public function testRecibirJuegos_withJuegos()
    {
        $controller = new DataController();
        $this->initController($controller);

        // Configuramos el mock para la validación exitosa.
        $mockValidator = $this->getMockBuilder(ApiKeyValidator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validar'])
            ->getMock();

        $mockValidator->expects($this->once())
            ->method('validar')
            ->willReturn(true);
        $controller->apiKeyValidator = $mockValidator;

        // Creamos un array simulando videojuegos.
        $juegosArray = [
            ['id' => 1, 'nombre' => 'Juego1'],
            ['id' => 2, 'nombre' => 'Juego2']
        ];

        // Simulamos al modelo devolviendo videojuegos.
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
    public function testRecibirJuegos_exceptionInFindAll()
    {
        $controller = new DataController();
        $this->initController($controller);

        // Configuramos el mock para la validación exitosa.
        $mockValidator = $this->getMockBuilder(ApiKeyValidator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validar'])
            ->getMock();

        $mockValidator->expects($this->once())
            ->method('validar')
            ->willReturn(true);
        $controller->apiKeyValidator = $mockValidator;

        // Configuramos el modelo para que lance una excepción.
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
     *
     * Se simula que la validación falla y se retorna un objeto Response con error 401.
     */
    public function testRecibirDatosJuego_invalidApiKey()
    {
        $controller = new DataController();
        $this->initController($controller);

        // Creamos una respuesta falsa de error por API key inválida.
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
        // No es necesario configurar VideojuegoModelo ya que la validación no continúa.

        $result = $controller->recibirDatosJuego(1);
        $data = json_decode($result->getBody(), true);

        $this->assertEquals(401, $result->getStatusCode());
        $this->assertIsArray($data);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('API Key inválida', $data['error']);
    }

    /**
     * Test: Juego encontrado.
     *
     * Se simula que el modelo retorna un juego existente.
     */
    public function testRecibirDatosJuego_found()
    {
        $controller = new DataController();
        $this->initController($controller);

        // Validación exitosa.
        $mockValidator = $this->getMockBuilder(ApiKeyValidator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validar'])
            ->getMock();
        $mockValidator->expects($this->once())
            ->method('validar')
            ->willReturn(true);
        $controller->apiKeyValidator = $mockValidator;

        // Simulación del juego encontrado.
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
     *
     * Se simula que el modelo no encuentra ningún juego para el ID proporcionado.
     */
    public function testRecibirDatosJuego_notFound()
    {
        $controller = new DataController();
        $this->initController($controller);

        // Validación exitosa.
        $mockValidator = $this->getMockBuilder(ApiKeyValidator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validar'])
            ->getMock();
        $mockValidator->expects($this->once())
            ->method('validar')
            ->willReturn(true);
        $controller->apiKeyValidator = $mockValidator;

        // Configuramos el modelo para que retorne null (no encontrado)
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
     * Test: Excepción al obtener el juego.
     *
     * Se simula que el método find lanza una excepción, por lo que se debe retornar un error 500.
     */
    public function testRecibirDatosJuego_exception()
    {
        $controller = new DataController();
        $this->initController($controller);

        // Validación exitosa.
        $mockValidator = $this->getMockBuilder(ApiKeyValidator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validar'])
            ->getMock();
        $mockValidator->expects($this->once())
            ->method('validar')
            ->willReturn(true);
        $controller->apiKeyValidator = $mockValidator;

        // Configuramos el modelo para que lance una excepción.
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
    public function testInicioSesion_invalidApiKey()
    {
        $controller = new DataController();
        $this->initController($controller);

        // Simulamos una respuesta de error por API key inválida.
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
        // No es necesario configurar AdministradoresModelo en este caso.

        // Ejecutamos el método
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
    public function testInicioSesion_usuarioNoEncontrado()
    {
        $controller = new DataController();
        $this->initController($controller);

        // Datos de inicio de sesión que se enviarán en el request.
        $inputData = ['nombre' => 'nonexistent', 'password' => 'any'];

        // Simulamos el método getJSON() del request con un stub.
        $fakeRequest = $this->getMockBuilder('CodeIgniter\HTTP\IncomingRequest')
            ->disableOriginalConstructor()
            ->onlyMethods(['getJSON'])
            ->getMock();
        $fakeRequest->expects($this->once())
            ->method('getJSON')
            ->with(true)
            ->willReturn($inputData);
        // Inyectamos el fakeRequest en la propiedad protegida 'request'
        $this->setProtectedProperty($controller, 'request', $fakeRequest);

        // Validación exitosa.
        $mockValidator = $this->getMockBuilder(ApiKeyValidator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validar'])
            ->getMock();
        $mockValidator->expects($this->once())
            ->method('validar')
            ->willReturn(true);
        $controller->apiKeyValidator = $mockValidator;

        // Simulamos la consulta en el modelo:
        // Al llamar ->where('nombre', 'nonexistent')->first() se retorna null.
        $mockQuery = $this->getMockBuilder('stdClass')
            ->disableOriginalConstructor()
            ->addMethods(['first'])
            ->getMock();
        $mockQuery->expects($this->once())
            ->method('first')
            ->willReturn(null);

        // En lugar de onlyMethods, usamos addMethods para agregar "where" al mock,
        // ya que AdministradoresModelo no tiene declarado "where".
        $mockAdminModel = $this->getMockBuilder('App\Models\AdministradoresModelo')
            ->disableOriginalConstructor()
            ->addMethods(['where'])
            ->getMock();
        $mockAdminModel->expects($this->once())
            ->method('where')
            ->with('nombre', 'nonexistent')
            ->willReturn($mockQuery);
        $controller->AdministradoresModelo = $mockAdminModel;

        // Ejecutamos el método.
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
    public function testInicioSesion_incorrectPassword()
    {
        $controller = new DataController();
        $this->initController($controller);

        $inputData = ['nombre' => 'user', 'password' => 'wrongPassword'];

        // Stub para getJSON() que retorna los datos de entrada.
        $fakeRequest = $this->getMockBuilder('CodeIgniter\HTTP\IncomingRequest')
            ->disableOriginalConstructor()
            ->onlyMethods(['getJSON'])
            ->getMock();
        $fakeRequest->expects($this->once())
            ->method('getJSON')
            ->with(true)
            ->willReturn($inputData);
        // Inyectamos el fakeRequest en la propiedad protegida 'request'
        $this->setProtectedProperty($controller, 'request', $fakeRequest);

        // Validación exitosa.
        $mockValidator = $this->getMockBuilder(ApiKeyValidator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validar'])
            ->getMock();
        $mockValidator->expects($this->once())
            ->method('validar')
            ->willReturn(true);
        $controller->apiKeyValidator = $mockValidator;

        // Simulamos un administrador encontrado.
        // La contraseña almacenada se genera a partir de 'correctPassword'.
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

        // Usamos addMethods para agregar "where" (ya que no existe explícitamente).
        $mockAdminModel = $this->getMockBuilder('App\Models\AdministradoresModelo')
            ->disableOriginalConstructor()
            ->addMethods(['where'])
            ->getMock();
        $mockAdminModel->expects($this->once())
            ->method('where')
            ->with('nombre', 'user')
            ->willReturn($mockQuery);
        $controller->AdministradoresModelo = $mockAdminModel;

        // Ejecutamos el método y esperamos que la verificación de password falle.
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
    public function testInicioSesion_successful()
    {
        $controller = new DataController();
        $this->initController($controller);

        $inputData = ['nombre' => 'user', 'password' => 'password123'];

        // Simulamos el request que retorna el JSON de entrada.
        $fakeRequest = $this->getMockBuilder('CodeIgniter\HTTP\IncomingRequest')
            ->disableOriginalConstructor()
            ->onlyMethods(['getJSON'])
            ->getMock();
        $fakeRequest->expects($this->once())
            ->method('getJSON')
            ->with(true)
            ->willReturn($inputData);
        // Inyectamos el fakeRequest en la propiedad protegida 'request'
        $this->setProtectedProperty($controller, 'request', $fakeRequest);

        // Validación exitosa.
        $mockValidator = $this->getMockBuilder(ApiKeyValidator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validar'])
            ->getMock();
        $mockValidator->expects($this->once())
            ->method('validar')
            ->willReturn(true);
        $controller->apiKeyValidator = $mockValidator;

        // Preparamos el registro del administrador.
        $adminRecord = [
            'id'             => 1,
            'nombre'         => 'user',
            'password'       => password_hash('password123', PASSWORD_DEFAULT),
            'fecha_creacion' => '2022-01-01 00:00:00'
        ];

        // Simulamos el método where()->first()
        $mockQuery = $this->getMockBuilder('stdClass')
            ->disableOriginalConstructor()
            ->addMethods(['first'])
            ->getMock();
        $mockQuery->expects($this->once())
            ->method('first')
            ->willReturn($adminRecord);

        // En este caso, queremos controlar además el método update.
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

        // Ejecutamos el método.
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
    public function testInicioSesion_exception()
    {
        $controller = new DataController();
        $this->initController($controller);

        $inputData = ['nombre' => 'user', 'password' => 'password123'];

        // Simulamos el request que retorna el JSON de entrada.
        $fakeRequest = $this->getMockBuilder('CodeIgniter\HTTP\IncomingRequest')
            ->disableOriginalConstructor()
            ->onlyMethods(['getJSON'])
            ->getMock();
        $fakeRequest->expects($this->once())
            ->method('getJSON')
            ->with(true)
            ->willReturn($inputData);
        // Inyectamos el fakeRequest en la propiedad protegida 'request'
        $this->setProtectedProperty($controller, 'request', $fakeRequest);

        // Validación exitosa.
        $mockValidator = $this->getMockBuilder(ApiKeyValidator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validar'])
            ->getMock();
        $mockValidator->expects($this->once())
            ->method('validar')
            ->willReturn(true);
        $controller->apiKeyValidator = $mockValidator;

        // Simulamos que ocurre una excepción al llamar a first().
        $mockQuery = $this->getMockBuilder('stdClass')
            ->disableOriginalConstructor()
            ->addMethods(['first'])
            ->getMock();
        $mockQuery->expects($this->once())
            ->method('first')
            ->will($this->throwException(new Exception("DB error")));

        // Aquí usamos addMethods para agregar "where" a AdministradoresModelo.
        $mockAdminModel = $this->getMockBuilder('App\Models\AdministradoresModelo')
            ->disableOriginalConstructor()
            ->addMethods(['where'])
            ->getMock();
        $mockAdminModel->expects($this->once())
            ->method('where')
            ->with('nombre', 'user')
            ->willReturn($mockQuery);
        $controller->AdministradoresModelo = $mockAdminModel;

        // Ejecutamos el método.
        $result = $controller->inicioSesion();
        $data = json_decode($result->getBody(), true);

        $this->assertEquals(500, $result->getStatusCode());
        $this->assertIsArray($data);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Ocurrió un error en el inicio de sesión', $data['error']);
    }

    public function testRecibirGeneros_invalidApiKey()
    {
        $controller = new DataController();
        $this->initController($controller);

        // Simulamos una respuesta de error en la validación (por ejemplo, API Key inválida).
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

        // Al retornar desde la validación fallida, no se requiere configurar GeneroModelo.
        $result = $controller->recibirGeneros();
        $data = json_decode($result->getBody(), true);

        $this->assertEquals(401, $result->getStatusCode());
        $this->assertIsArray($data);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('API Key inválida', $data['error']);
    }

    public function testRecibirGeneros_empty()
    {
        $controller = new DataController();
        $this->initController($controller);

        // Validación exitosa.
        $mockValidator = $this->getMockBuilder(ApiKeyValidator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validar'])
            ->getMock();
        $mockValidator->expects($this->once())
            ->method('validar')
            ->willReturn(true);
        $controller->apiKeyValidator = $mockValidator;

        // Simulamos que el método findAll() del modelo retorna un array vacío.
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

    public function testRecibirGeneros_successful()
    {
        $controller = new DataController();
        $this->initController($controller);

        // Validación exitosa.
        $mockValidator = $this->getMockBuilder(ApiKeyValidator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validar'])
            ->getMock();
        $mockValidator->expects($this->once())
            ->method('validar')
            ->willReturn(true);
        $controller->apiKeyValidator = $mockValidator;

        // Simulamos que el método findAll() retorna una lista de géneros.
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

    public function testRecibirGeneros_exception()
    {
        $controller = new DataController();
        $this->initController($controller);

        // Validación exitosa.
        $mockValidator = $this->getMockBuilder(ApiKeyValidator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validar'])
            ->getMock();
        $mockValidator->expects($this->once())
            ->method('validar')
            ->willReturn(true);
        $controller->apiKeyValidator = $mockValidator;

        // Simulamos que el método findAll() lanza una excepción.
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
    public function testRecibirPlataformas_invalidApiKey()
    {
        $controller = new DataController();
        $this->initController($controller);

        // Simulamos una respuesta de error en la validación.
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
        
        // No es necesario configurar PlataformaModelo porque la validación se detiene.
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
    public function testRecibirPlataformas_empty()
    {
        $controller = new DataController();
        $this->initController($controller);

        // Validación exitosa.
        $mockValidator = $this->getMockBuilder(ApiKeyValidator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validar'])
            ->getMock();
        $mockValidator->expects($this->once())
            ->method('validar')
            ->willReturn(true);
        $controller->apiKeyValidator = $mockValidator;

        // Se simula que el método findAll() del modelo retorna un array vacío.
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
    public function testRecibirPlataformas_successful()
    {
        $controller = new DataController();
        $this->initController($controller);

        // Validación exitosa.
        $mockValidator = $this->getMockBuilder(ApiKeyValidator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validar'])
            ->getMock();
        $mockValidator->expects($this->once())
            ->method('validar')
            ->willReturn(true);
        $controller->apiKeyValidator = $mockValidator;

        // Simulamos que findAll() retorna una lista de plataformas.
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
    public function testRecibirPlataformas_exception()
    {
        $controller = new DataController();
        $this->initController($controller);

        // Validación exitosa.
        $mockValidator = $this->getMockBuilder(ApiKeyValidator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validar'])
            ->getMock();
        $mockValidator->expects($this->once())
            ->method('validar')
            ->willReturn(true);
        $controller->apiKeyValidator = $mockValidator;

        // Simulamos que findAll() lanza una excepción.
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
    public function testRecibirTiendas_invalidApiKey()
    {
        $controller = new DataController();
        $this->initController($controller);
        
        // Simulamos un Response de error (por ejemplo, API Key inválida).
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
        
        // En este caso la validación falla y no se necesita configurar TiendaModelo.
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
    public function testRecibirTiendas_empty()
    {
        $controller = new DataController();
        $this->initController($controller);
        
        // Validación exitosa.
        $mockValidator = $this->getMockBuilder(ApiKeyValidator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validar'])
            ->getMock();
        $mockValidator->expects($this->once())
            ->method('validar')
            ->willReturn(true);
        $controller->apiKeyValidator = $mockValidator;
        
        // Simulamos que el método findAll() retorna un array vacío.
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
    public function testRecibirTiendas_successful()
    {
        $controller = new DataController();
        $this->initController($controller);
        
        // Validación exitosa.
        $mockValidator = $this->getMockBuilder(ApiKeyValidator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validar'])
            ->getMock();
        $mockValidator->expects($this->once())
            ->method('validar')
            ->willReturn(true);
        $controller->apiKeyValidator = $mockValidator;
        
        // Simulamos que findAll() retorna una lista de tiendas.
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
    public function testRecibirTiendas_exception()
    {
        $controller = new DataController();
        $this->initController($controller);
        
        // Validación exitosa.
        $mockValidator = $this->getMockBuilder(ApiKeyValidator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validar'])
            ->getMock();
        $mockValidator->expects($this->once())
            ->method('validar')
            ->willReturn(true);
        $controller->apiKeyValidator = $mockValidator;
        
        // Simulamos que findAll() lanza una excepción.
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
    public function testRecibirDesarrolladoras_invalidApiKey()
    {
        $controller = new DataController();
        $this->initController($controller);
        
        // Simulamos un Response de error (por ejemplo, API Key inválida).
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
        
        // Como la validación falla, el método se detiene en ese punto.
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
    public function testRecibirDesarrolladoras_empty()
    {
        $controller = new DataController();
        $this->initController($controller);
        
        // Validación exitosa.
        $mockValidator = $this->getMockBuilder(ApiKeyValidator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validar'])
            ->getMock();
        $mockValidator->expects($this->once())
            ->method('validar')
            ->willReturn(true);
        $controller->apiKeyValidator = $mockValidator;
        
        // Simulamos que el método findAll() retorna un array vacío.
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
    public function testRecibirDesarrolladoras_successful()
    {
        $controller = new DataController();
        $this->initController($controller);
        
        // Validación exitosa.
        $mockValidator = $this->getMockBuilder(ApiKeyValidator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validar'])
            ->getMock();
        $mockValidator->expects($this->once())
            ->method('validar')
            ->willReturn(true);
        $controller->apiKeyValidator = $mockValidator;
        
        // Simulamos que findAll() retorna una lista de desarrolladoras.
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
    public function testRecibirDesarrolladoras_exception()
    {
        $controller = new DataController();
        $this->initController($controller);
        
        // Validación exitosa.
        $mockValidator = $this->getMockBuilder(ApiKeyValidator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validar'])
            ->getMock();
        $mockValidator->expects($this->once())
            ->method('validar')
            ->willReturn(true);
        $controller->apiKeyValidator = $mockValidator;
        
        // Simulamos que findAll() lanza una excepción.
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
    public function testRecibirPublishers_invalidApiKey()
    {
        $controller = new DataController();
        $this->initController($controller);

        // Simulamos un Response de error (por ejemplo, API Key inválida).
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

        // Al no pasar la validación, el modelo no se invoca.
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
    public function testRecibirPublishers_empty()
    {
        $controller = new DataController();
        $this->initController($controller);

        // Validación exitosa.
        $mockValidator = $this->getMockBuilder(ApiKeyValidator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validar'])
            ->getMock();
        $mockValidator->expects($this->once())
            ->method('validar')
            ->willReturn(true);
        $controller->apiKeyValidator = $mockValidator;

        // Simulamos que el método findAll() del modelo retorna un array vacío.
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
    public function testRecibirPublishers_successful()
    {
        $controller = new DataController();
        $this->initController($controller);

        // Validación exitosa.
        $mockValidator = $this->getMockBuilder(ApiKeyValidator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validar'])
            ->getMock();
        $mockValidator->expects($this->once())
            ->method('validar')
            ->willReturn(true);
        $controller->apiKeyValidator = $mockValidator;

        // Simulamos que findAll() retorna una lista de publishers.
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
    public function testRecibirPublishers_exception()
    {
        $controller = new DataController();
        $this->initController($controller);

        // Validación exitosa.
        $mockValidator = $this->getMockBuilder(ApiKeyValidator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validar'])
            ->getMock();
        $mockValidator->expects($this->once())
            ->method('validar')
            ->willReturn(true);
        $controller->apiKeyValidator = $mockValidator;

        // Simulamos que findAll() lanza una excepción.
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

}
