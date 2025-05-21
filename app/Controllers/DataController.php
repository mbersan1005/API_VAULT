<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\AdministradoresModelo;
use App\Models\DesarrolladoraModelo;
use App\Services\ApiKeyValidator;
use App\Models\VideojuegoModelo;
use CodeIgniter\I18n\Time;
use App\Models\GeneroModelo;
use App\Models\PlataformaModelo;
use App\Models\PublisherModelo;
use App\Models\TiendaModelo;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Cloudinary\Cloudinary;

class DataController extends BaseController
{

    protected $request;
    protected $response;

    public $apiKeyValidator;

    //https://apirest.saicasl.eu/api1/api/public -- https://vault-ci4-api.up.railway.app -- https://api-vault.onrender.com
    private $baseUrlHost;

    public function __construct()
    {
        $this->apiKeyValidator = new ApiKeyValidator();
        $this->VideojuegoModelo = new VideojuegoModelo();
        $this->AdministradoresModelo = new AdministradoresModelo();
        $this->GeneroModelo = new GeneroModelo();
        $this->DesarrolladoraModelo = new DesarrolladoraModelo();
        $this->PlataformaModelo = new PlataformaModelo();
        $this->PublisherModelo = new PublisherModelo();
        $this->TiendaModelo = new TiendaModelo();
        $this->baseUrlHost = "https://api-vault.onrender.com";
    }

    public function setRequest(RequestInterface $request)
    {
        $this->request = $request;
    }

    public function setResponse(ResponseInterface $response)
    {
        $this->response = $response;
    }

    public function recibirJuegos()
    {

        $resultadoValidacion = $this->apiKeyValidator->validar($this->request, $this->response);

        if ($resultadoValidacion !== true) {
            return $resultadoValidacion;
        }

        try {

            $juegos = $this->VideojuegoModelo->findAll();

            if (empty($juegos)) {
                $data = ['mensaje' => 'No se encontraron videojuegos'];
            } else {
                $data = ['juegos' => $juegos];
            }

            return $this->response->setJSON($data)->setStatusCode(200);
        } catch (\Exception $e) {
            log_message('error', 'Error al obtener juegos: ' . $e->getMessage());
            return $this->response->setJSON([
                'error' => 'Ocurrió un error al recuperar los datos'
            ])->setStatusCode(500);
        }
    }

    public function recibirDatosJuego($id)
    {
        $resultadoValidacion = $this->apiKeyValidator->validar($this->request, $this->response);
        if ($resultadoValidacion !== true) {
            return $resultadoValidacion;
        }

        try {
            $juego = $this->VideojuegoModelo->find($id);

            if (!$juego) {
                return $this->response->setJSON([
                    'error' => 'No se encontró videojuego con ese ID'
                ])->setStatusCode(404);
            }

            return $this->response->setJSON(['juego' => $juego])->setStatusCode(200);
        } catch (\Exception $e) {
            log_message('error', 'Error al obtener el juego con ID ' . $id . ': ' . $e->getMessage());

            return $this->response->setJSON([
                'error' => 'Ocurrió un error al recuperar los datos del juego'
            ])->setStatusCode(500);
        }
    }

    public function inicioSesion()
    {
        $resultadoValidacion = $this->apiKeyValidator->validar($this->request, $this->response);
        if ($resultadoValidacion !== true) {
            return $resultadoValidacion;
        }

        try {
            $data = $this->request->getJSON(true) ?? [];

            $nombre = trim($data['nombre'] ?? '');
            $password = trim($data['password'] ?? '');

            $administrador = $this->AdministradoresModelo->where('nombre', $nombre)->first();

            if (!$administrador) {
                return $this->response->setJSON([
                    'error' => 'Usuario no encontrado'
                ])->setStatusCode(404);
            }

            if (!password_verify($password, $administrador['password'])) {
                return $this->response->setJSON([
                    'error' => 'Contraseña incorrecta'
                ])->setStatusCode(401);
            }

            $fechaActual = Time::now('Europe/Madrid')->toDateTimeString();
            $this->AdministradoresModelo->update($administrador['id'], [
                'fecha_ultimo_login' => $fechaActual
            ]);

            return $this->response->setJSON([
                'mensaje' => 'Inicio de sesión exitoso',
                'administrador' => [
                    'id' => $administrador['id'],
                    'nombre' => $administrador['nombre'],
                    'fecha_creacion' => $administrador['fecha_creacion'],
                    'fecha_ultimo_login' => $fechaActual,
                ]
            ])->setStatusCode(200);
        } catch (\Exception $e) {
            log_message('error', 'Error en inicioSesion: ' . $e->getMessage());
            return $this->response->setJSON([
                'error' => 'Ocurrió un error en el inicio de sesión'
            ])->setStatusCode(500);
        }
    }

    public function recibirGeneros()
    {
        $resultadoValidacion = $this->apiKeyValidator->validar($this->request, $this->response);

        if ($resultadoValidacion !== true) {
            return $resultadoValidacion;
        }

        try {
            $generos = $this->GeneroModelo->findAll();

            if (empty($generos)) {
                return $this->response->setJSON([
                    'error' => 'No se encontraron géneros'
                ])->setStatusCode(404);
            }

            return $this->response->setJSON([
                'generos' => $generos
            ])->setStatusCode(200);
        } catch (\Exception $e) {
            log_message('error', 'Error al obtener géneros: ' . $e->getMessage());

            return $this->response->setJSON([
                'error' => 'Ocurrió un error al recuperar los géneros'
            ])->setStatusCode(500);
        }
    }

    public function recibirPlataformas()
    {
        $resultadoValidacion = $this->apiKeyValidator->validar($this->request, $this->response);

        if ($resultadoValidacion !== true) {
            return $resultadoValidacion;
        }

        try {
            $plataformas = $this->PlataformaModelo->findAll();

            if (empty($plataformas)) {
                return $this->response->setJSON([
                    'error' => 'No se encontraron plataformas'
                ])->setStatusCode(404);
            }

            return $this->response->setJSON([
                'plataformas' => $plataformas
            ])->setStatusCode(200);
        } catch (\Exception $e) {
            log_message('error', 'Error al obtener plataformas: ' . $e->getMessage());

            return $this->response->setJSON([
                'error' => 'Ocurrió un error al recuperar las plataformas'
            ])->setStatusCode(500);
        }
    }

    public function recibirTiendas()
    {
        $resultadoValidacion = $this->apiKeyValidator->validar($this->request, $this->response);

        if ($resultadoValidacion !== true) {
            return $resultadoValidacion;
        }

        try {
            $tiendas = $this->TiendaModelo->findAll();

            if (empty($tiendas)) {
                return $this->response->setJSON([
                    'error' => 'No se encontraron tiendas'
                ])->setStatusCode(404);
            }

            return $this->response->setJSON([
                'tiendas' => $tiendas
            ])->setStatusCode(200);
        } catch (\Exception $e) {
            log_message('error', 'Error al obtener tiendas: ' . $e->getMessage());

            return $this->response->setJSON([
                'error' => 'Ocurrió un error al recuperar las tiendas'
            ])->setStatusCode(500);
        }
    }

    public function recibirDesarrolladoras()
    {
        $resultadoValidacion = $this->apiKeyValidator->validar($this->request, $this->response);

        if ($resultadoValidacion !== true) {
            return $resultadoValidacion;
        }

        try {
            $desarrolladoras = $this->DesarrolladoraModelo->orderBy('cantidad_juegos', 'DESC')->findAll();

            if (empty($desarrolladoras)) {
                return $this->response->setJSON([
                    'error' => 'No se encontraron desarrolladoras'
                ])->setStatusCode(404);
            }

            return $this->response->setJSON([
                'desarrolladoras' => $desarrolladoras
            ])->setStatusCode(200);
        } catch (\Exception $e) {
            log_message('error', 'Error al obtener desarrolladoras: ' . $e->getMessage());

            return $this->response->setJSON([
                'error' => 'Ocurrió un error al recuperar las desarrolladoras'
            ])->setStatusCode(500);
        }
    }

    public function recibirPublishers()
    {
        $resultadoValidacion = $this->apiKeyValidator->validar($this->request, $this->response);

        if ($resultadoValidacion !== true) {
            return $resultadoValidacion;
        }

        try {
            $publishers = $this->PublisherModelo->orderBy('cantidad_juegos', 'DESC')->findAll();

            if (empty($publishers)) {
                return $this->response->setJSON([
                    'error' => 'No se encontraron publishers'
                ])->setStatusCode(404);
            }

            return $this->response->setJSON([
                'publishers' => $publishers
            ])->setStatusCode(200);
        } catch (\Exception $e) {
            log_message('error', 'Error al obtener publishers: ' . $e->getMessage());

            return $this->response->setJSON([
                'error' => 'Ocurrió un error al recuperar los publishers'
            ])->setStatusCode(500);
        }
    }

    /*
    SELECT * 
    FROM vault.videojuegos
    WHERE EXISTS (
        SELECT 1
        FROM jsonb_array_elements(generos) AS item
        WHERE LOWER(item->>'nombre') LIKE LOWER('%Action%')
    );
    */
    public function recibirJuegosFiltrados($categoria, $nombre)
    {
        $resultadoValidacion = $this->apiKeyValidator->validar($this->request, $this->response);

        if ($resultadoValidacion !== true) {
            return $resultadoValidacion;
        }

        $columnasPermitidas = ['generos', 'tiendas', 'publishers', 'desarrolladoras', 'plataformas_principales'];

        if (!in_array($categoria, $columnasPermitidas)) {
            return $this->response->setJSON(['error' => 'Categoría no válida'])
                ->setStatusCode(400);
        }

        try {
            $db = \Config\Database::connect();

            $query = "
                SELECT * 
                FROM vault.videojuegos
                WHERE EXISTS (
                    SELECT 1
                    FROM jsonb_array_elements($categoria) AS item
                    WHERE LOWER(item->>'nombre') LIKE LOWER(?)
                )
                ORDER BY nombre ASC
            ";

            $result = $db->query($query, ['%' . $nombre . '%'])->getResult();

            if (empty($result)) {
                return $this->response->setJSON([
                    'error' => 'No se encontraron videojuegos con esa categoría'
                ])->setStatusCode(404);
            }

            return $this->response->setJSON([
                'juegosFiltrados' => $result
            ])->setStatusCode(200);
        } catch (\Exception $e) {
            log_message('error', 'Error al obtener juegos filtrados: ' . $e->getMessage());

            return $this->response->setJSON([
                'error' => 'Ocurrió un error al recuperar los juegos filtrados'
            ])->setStatusCode(500);
        }
    }

    public function eliminarJuego()
    {
        $resultadoValidacion = $this->apiKeyValidator->validar($this->request, $this->response);

        if ($resultadoValidacion !== true) {
            return $resultadoValidacion;
        }

        $json = $this->request->getJSON();
        $id = $json->id ?? null;

        if (!$id) {
            return $this->response->setJSON(['error' => 'ID del juego no proporcionado'])
                ->setStatusCode(400);
        }

        $juego = $this->VideojuegoModelo->find($id);

        if (!$juego) {
            return $this->response->setJSON(['error' => 'Juego no encontrado'])
                ->setStatusCode(404);
        }

        try {

            if (strpos($juego['imagen'], 'res.cloudinary.com') !== false) {
                $cloudinary = new Cloudinary([
                    'cloud' => [
                        'cloud_name' => 'mbersan1005',
                        'api_key'    => '246324721933957',
                        'api_secret' => 'Ojh3Chu7gOvYJbwzWB-u0jmXF7U',
                    ],
                ]);

                $publicId = $this->extraerPublicIdDesdeUrl($juego['imagen']);
                if ($publicId) {
                    $cloudinary->uploadApi()->destroy($publicId);
                }
            }

            $this->VideojuegoModelo->delete($id);

            return $this->response->setJSON([
                'success' => true,
                'mensaje' => 'Juego eliminado correctamente'
            ])->setStatusCode(200);
        } catch (\Exception $e) {
            log_message('error', 'Error al eliminar juego: ' . $e->getMessage());

            return $this->response->setJSON([
                'error' => 'Ocurrió un error al eliminar el juego'
            ])->setStatusCode(500);
        }
    }

    public function obtenerDatosFormulario()
    {
        $resultadoValidacion = $this->apiKeyValidator->validar($this->request, $this->response);

        if ($resultadoValidacion !== true) {
            return $resultadoValidacion;
        }

        try {
            $tiendas = $this->TiendaModelo->findAll();
            $plataformas = $this->PlataformaModelo->findAll();
            $generos = $this->GeneroModelo->findAll();
            $desarrolladoras = $this->DesarrolladoraModelo->findAll();
            $publishers = $this->PublisherModelo->findAll();

            $response = [
                'tiendas' => $tiendas,
                'plataformas' => $plataformas,
                'generos' => $generos,
                'desarrolladoras' => $desarrolladoras,
                'publishers' => $publishers
            ];

            return $this->response->setJSON($response);
        } catch (\Exception $e) {
            log_message('error', 'Error al obtener datos del formulario: ' . $e->getMessage());

            return $this->response->setJSON([
                'error' => 'Ocurrió un error al obtener los datos'
            ])->setStatusCode(500);
        }
    }

    public function agregarJuego()
    {
        $resultadoValidacion = $this->apiKeyValidator->validar($this->request, $this->response);

        if ($resultadoValidacion !== true) {
            return $resultadoValidacion;
        }

        $datos = $this->request->getPost();
        $imagen = $this->request->getFile('imagen');

        try {
            $juegoExistente = $this->VideojuegoModelo->where('nombre', $datos['nombre'])->first();
            if ($juegoExistente) {
                return $this->response->setJSON(['error' => 'Ya existe un juego con este nombre.'])
                    ->setStatusCode(400);
            }

            $cloudinary = new Cloudinary([
                'cloud' => [
                    'cloud_name' => 'mbersan1005',
                    'api_key'    => '246324721933957',
                    'api_secret' => 'Ojh3Chu7gOvYJbwzWB-u0jmXF7U',
                ],
                'url' => [
                    'secure' => true
                ]
            ]);

            $nombreJuegoSlug = url_title($datos['nombre'], '-', true);

            $uploadResult = $cloudinary->uploadApi()->upload($imagen->getTempName(), [
                'public_id' => 'videojuegos/' . $nombreJuegoSlug,
                'overwrite' => true
            ]);

            $rutaImagenFinal = $uploadResult['secure_url'] ?? null;

            if (!$rutaImagenFinal) {
                return $this->response->setJSON(['error' => 'Error al obtener URL segura de imagen.'])
                    ->setStatusCode(500);
            }

            $insertData = [
                'nombre' => $datos['nombre'],
                'nota_metacritic' => $datos['nota_metacritic'] ?? null,
                'fecha_lanzamiento' => $datos['fecha_lanzamiento'],
                'sitio_web' => $datos['sitio_web'] ?? null,
                'imagen' => $rutaImagenFinal,
                'plataformas_principales' => $datos['plataformas'],
                'desarrolladoras' => $datos['desarrolladoras'],
                'publishers' => $datos['publishers'],
                'tiendas' => $datos['tiendas'],
                'generos' => $datos['generos'],
                'descripcion' => $datos['descripcion'],
                'creado_por_admin' => 1,
            ];

            $db = \Config\Database::connect();
            $db->transBegin();

            $juegoId = $this->VideojuegoModelo->insert($insertData);

            if ($juegoId) {
                $db->transCommit();
                return $this->response->setJSON(['mensaje' => 'Juego agregado correctamente.'])
                    ->setStatusCode(201);
            } else {
                $db->transRollback();
                return $this->response->setJSON(['error' => 'Error al agregar el juego.'])
                    ->setStatusCode(500);
            }
        } catch (\Exception $e) {
            if (isset($db)) {
                $db->transRollback();
            }
            return $this->response->setJSON(['error' => 'Excepción al agregar juego.', 'detalle' => $e->getMessage()])
                ->setStatusCode(500);
        }
    }

    public function crearAdministrador()
    {
        $resultadoValidacion = $this->apiKeyValidator->validar($this->request, $this->response);

        if ($resultadoValidacion !== true) {
            return $resultadoValidacion;
        }

        $datos = $this->request->getJSON(true);
        $nombre = $datos['nombre'] ?? null;
        $password = $datos['password'] ?? null;

        $adminExistente = $this->AdministradoresModelo->where('nombre', $nombre)->first();
        if ($adminExistente) {
            return $this->response->setJSON([
                'error' => 'Ya existe un administrador con ese nombre',
                'datos' => []
            ])->setStatusCode(409);
        }

        $passwordCifrada = password_hash($password, PASSWORD_DEFAULT);
        $fechaActual = Time::now('Europe/Madrid')->toDateTimeString();

        $data = [
            'nombre' => $nombre,
            'password' => $passwordCifrada,
            'fecha_creacion' => $fechaActual,
            'fecha_ultimo_login' => $fechaActual
        ];

        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            $inserted = $this->AdministradoresModelo->insert($data);

            if (!$inserted) {
                throw new \Exception("Error al insertar el administrador en la base de datos");
            }

            $db->transCommit();

            return $this->response->setJSON([
                'mensaje' => 'Cuenta de administrador creada correctamente',
                'datos' => $data
            ])->setStatusCode(201);
        } catch (\Exception $e) {
            $db->transRollback();

            return $this->response->setJSON([
                'error' => 'Error al crear el administrador'
            ])->setStatusCode(500);
        }
    }

    public function editarJuego()
    {
        $resultadoValidacion = $this->apiKeyValidator->validar($this->request, $this->response);
        if ($resultadoValidacion !== true) {
            return $resultadoValidacion;
        }

        $datos = $this->request->getPost();
        $imagen = $this->request->getFile('imagen');

        $juegoActual = $this->VideojuegoModelo->find($datos['id']);
        if (!$juegoActual) {
            return $this->response->setJSON(['error' => 'Juego no encontrado.'])->setStatusCode(404);
        }

        $db = \Config\Database::connect();
        $db->transBegin();

        try {

            $cloudinary = new Cloudinary([
                'cloud' => [
                    'cloud_name' => 'mbersan1005',
                    'api_key'    => '246324721933957',
                    'api_secret' => 'Ojh3Chu7gOvYJbwzWB-u0jmXF7U',
                ],
            ]);

            if ($imagen && $imagen->isValid() && !$imagen->hasMoved()) {

                if (strpos($juegoActual['imagen'], 'res.cloudinary.com') !== false) {
                    $publicId = $this->extraerPublicIdDesdeUrl($juegoActual['imagen']);
                    if ($publicId) {
                        $cloudinary->uploadApi()->destroy($publicId);
                    }
                }

                $nombreJuegoSlug = url_title($datos['nombre'], '-', true);
                $tempPath = $imagen->getTempName();

                $uploadResult = $cloudinary->uploadApi()->upload($tempPath, [
                    'public_id' => 'videojuegos/' . $nombreJuegoSlug,
                    'overwrite' => true
                ]);

                $rutaImagenFinal = $uploadResult['secure_url'];
            } else {
                $rutaImagenFinal = $juegoActual['imagen'];
            }

            $updateData = [
                'nombre' => $datos['nombre'] ?? $juegoActual['nombre'],
                'nota_metacritic' => $datos['nota_metacritic'] ?? $juegoActual['nota_metacritic'],
                'fecha_lanzamiento' => $datos['fecha_lanzamiento'] ?? $juegoActual['fecha_lanzamiento'],
                'sitio_web' => $datos['sitio_web'] ?? $juegoActual['sitio_web'],
                'imagen' => $rutaImagenFinal,
                'plataformas_principales' => $datos['plataformas'] ?? $juegoActual['plataformas_principales'],
                'desarrolladoras' => $datos['desarrolladoras'] ?? $juegoActual['desarrolladoras'],
                'publishers' => $datos['publishers'] ?? $juegoActual['publishers'],
                'tiendas' => $datos['tiendas'] ?? $juegoActual['tiendas'],
                'generos' => $datos['generos'] ?? $juegoActual['generos'],
                'descripcion' => $datos['descripcion'] ?? $juegoActual['descripcion'],
                'creado_por_admin' => 1
            ];

            $actualizado = $this->VideojuegoModelo->update($datos['id'], $updateData);

            if ($actualizado) {
                $db->transCommit();
                return $this->response->setJSON([
                    'mensaje' => 'Juego actualizado correctamente.',
                    'datos' => $updateData
                ])->setStatusCode(200);
            } else {
                throw new \Exception('Error al actualizar el juego.');
            }
        } catch (\Exception $e) {
            $db->transRollback();
            return $this->response->setJSON([
                'error' => 'Error al actualizar el juego',
                'detalle' => $e->getMessage()
            ])->setStatusCode(500);
        }
    }

    public function recibirJuegosAdmin()
    {
        $resultadoValidacion = $this->apiKeyValidator->validar($this->request, $this->response);

        if ($resultadoValidacion !== true) {
            return $resultadoValidacion;
        }

        try {
            $juegos = $this->VideojuegoModelo->select('nombre')->where('creado_por_admin', 1)->findAll();

            if (empty($juegos)) {
                return $this->response->setJSON([
                    'error' => 'No se encontraron videojuegos creados por administradores'
                ])->setStatusCode(404);
            }

            return $this->response->setJSON([
                'juegos' => $juegos
            ])->setStatusCode(200);
        } catch (\Exception $e) {
            log_message('error', 'Error al obtener juegos administrados: ' . $e->getMessage());

            return $this->response->setJSON([
                'error' => 'Ocurrió un error al recuperar los videojuegos creados por los administradores'
            ])->setStatusCode(500);
        }
    }

    public function realizarBusqueda()
    {
        $resultadoValidacion = $this->apiKeyValidator->validar($this->request, $this->response);

        if ($resultadoValidacion !== true) {
            return $resultadoValidacion;
        }

        try {
            $data = $this->request->getJSON(true) ?? [];
            $nombre = trim($data['nombre'] ?? '');

            $juegos = $this->VideojuegoModelo
                ->like('LOWER(nombre)', strtolower($nombre))
                ->findAll();

            if (empty($juegos)) {
                return $this->response->setJSON([
                    'error' => 'No se encontraron juegos que coincidan',
                    'juegos' => []
                ])->setStatusCode(404);
            }

            return $this->response->setJSON(['juegos' => $juegos])->setStatusCode(200);
        } catch (\Exception $e) {
            log_message('error', 'Error al realizar la búsqueda: ' . $e->getMessage());

            return $this->response->setJSON([
                'error' => 'Ocurrió un error al realizar la búsqueda'
            ])->setStatusCode(500);
        }
    }

    public function realizarBusquedaDesarrolladoras()
    {
        $resultadoValidacion = $this->apiKeyValidator->validar($this->request, $this->response);

        if ($resultadoValidacion !== true) {
            return $resultadoValidacion;
        }

        try {
            $data = $this->request->getJSON(true) ?? [];
            $nombre = trim($data['nombre'] ?? '');

            $desarrolladoras = $this->DesarrolladoraModelo
                ->like('LOWER(nombre)', strtolower($nombre))
                ->findAll();

            if (empty($desarrolladoras)) {
                return $this->response->setJSON([
                    'error' => 'No se encontraron desarrolladoras que coincidan',
                    'desarrolladoras' => []
                ])->setStatusCode(404);
            }

            return $this->response->setJSON([
                'desarrolladoras' => $desarrolladoras
            ])->setStatusCode(200);
        } catch (\Exception $e) {
            log_message('error', 'Error al buscar desarrolladoras: ' . $e->getMessage());

            return $this->response->setJSON([
                'error' => 'Ocurrió un error al realizar la búsqueda'
            ])->setStatusCode(500);
        }
    }

    public function realizarBusquedaPublishers()
    {
        $resultadoValidacion = $this->apiKeyValidator->validar($this->request, $this->response);

        if ($resultadoValidacion !== true) {
            return $resultadoValidacion;
        }

        try {
            $data = $this->request->getJSON(true) ?? [];
            $nombre = trim($data['nombre'] ?? '');

            $publishers = $this->PublisherModelo
                ->like('LOWER(nombre)', strtolower($nombre))
                ->findAll();

            if (empty($publishers)) {
                return $this->response->setJSON([
                    'error' => 'No se encontraron publishers que coincidan',
                    'publishers' => []
                ])->setStatusCode(404);
            }

            return $this->response->setJSON([
                'publishers' => $publishers
            ])->setStatusCode(200);
        } catch (\Exception $e) {
            log_message('error', 'Error al buscar publishers: ' . $e->getMessage());

            return $this->response->setJSON([
                'error' => 'Ocurrió un error al realizar la búsqueda'
            ])->setStatusCode(500);
        }
    }
}
