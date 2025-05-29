<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\AdministradoresModelo;
use App\Models\DesarrolladoraModelo;
use App\Services\ApiKeyValidator;
use App\Models\VideojuegoModelo;
use App\Models\GeneroModelo;
use App\Models\PlataformaModelo;
use App\Models\PublisherModelo;
use App\Models\TiendaModelo;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Cloudinary\Cloudinary;

//Controlador que gestiona la integración con la API externa RAWG y la base de datos
class ApiController extends BaseController
{
    protected $request;
    protected $response;

    public $apiKeyValidator;

    //https://apirest.saicasl.eu/api1/api/public -- https://vault-ci4-api.up.railway.app -- https://api-vault.onrender.com
    private $baseUrlHost;

    /*CONSTRUCTOR*/
    public function __construct()
    {
        //Inicialización de validación por API Key y modelos usados
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

    /**
     * Obtiene una lista de IDs de videojuegos desde la API RAWG.
     * Se puede configurar el número de páginas y el tamaño por página.
     */
    public function obtenerIdsJuegos_API()
    {
        $apiKey = "a9a117515a694c0fa91d404dd5ede441";
        $baseUrl = "https://api.rawg.io/api/games";
        $pageSize = 20; //20
        $totalPages = 1; //25
        $ids = [];

        for ($page = 1; $page <= $totalPages; $page++) {
            $url = "$baseUrl?key=$apiKey&page=$page&page_size=$pageSize";

            $llamada_API = curl_init();
            curl_setopt($llamada_API, CURLOPT_URL, $url);
            curl_setopt($llamada_API, CURLOPT_RETURNTRANSFER, true);

            $response = curl_exec($llamada_API);

            curl_close($llamada_API);

            $data = json_decode($response, true);

            if (isset($data['results'])) {
                foreach ($data['results'] as $juego) {
                    if (isset($juego['id'])) {
                        $ids[] = $juego['id'];
                    }
                }
            }
        }

        return $ids;
    }

    /**
     * Dado un array de IDs, consulta la API y guarda cada videojuego en la base de datos.
     * También serializa información relacionada como géneros, plataformas, etc
     */
    public function rellenarTablaVideojuegos($idsJuegos)
    {
        $apiKey = "a9a117515a694c0fa91d404dd5ede441";
        $baseUrl = "https://api.rawg.io/api/games";


        $db = \Config\Database::connect();
        $db->transBegin();

        foreach ($idsJuegos as $key => $value) {
            $url = "$baseUrl/$value?key=$apiKey";
            $juegoData = [];

            $llamada_API = curl_init();
            curl_setopt($llamada_API, CURLOPT_URL, $url);
            curl_setopt($llamada_API, CURLOPT_RETURNTRANSFER, true);

            $response = curl_exec($llamada_API);

            curl_close($llamada_API);

            $data = json_decode($response, true);

            if ($this->VideojuegoModelo->where('nombre', $data['name'])->first()) {
                continue;
            }

            if (isset($data['name'])) {
                $nombre = $data['name'];
            }

            if (isset($data['metacritic'])) {
                $notaMetacritic =  $data['metacritic'];
            } else {
                $notaMetacritic = null;
            }

            if (isset($data['released'])) {
                $fechaLanzamiento =  $data['released'];
            }

            if (isset($data['website'])) {
                $sitioWeb =  $data['website'];
            } else {
                $sitioWeb = null;
            }

            if (isset($data['background_image'])) {
                $imagen =  $data['background_image'];
            }

            $plataformas = [];
            if (!empty($data['platforms'])) {
                foreach ($data['platforms'] as $plataforma) {
                    $plataformas[] = [
                        'id' => $plataforma['platform']['id'],
                        'nombre' => $plataforma['platform']['name']
                    ];
                }
            }
            $plataformasJson = json_encode($plataformas, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            $desarrolladoras = [];
            if (!empty($data['developers'])) {
                foreach ($data['developers'] as $dev) {
                    $desarrolladoras[] = [
                        'id' => $dev['id'],
                        'nombre' => $dev['name']
                    ];
                }
            }
            $desarrolladorasJson = json_encode($desarrolladoras, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            $publishers = [];
            if (!empty($data['publishers'])) {
                foreach ($data['publishers'] as $publi) {
                    $publishers[] = [
                        'id' => $publi['id'],
                        'nombre' => $publi['name']
                    ];
                }
            }
            $publishersJson = json_encode($publishers, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            $tiendas = [];
            if (!empty($data['stores'])) {
                foreach ($data['stores'] as $tienda) {
                    $tiendas[] = [
                        'id' => $tienda['store']['id'],
                        'nombre' => $tienda['store']['name']
                    ];
                }
            }
            $tiendasJson = json_encode($tiendas, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            $generos = [];
            if (!empty($data['genres'])) {
                foreach ($data['genres'] as $genero) {
                    $generos[] = [
                        'id' => $genero['id'],
                        'nombre' => $genero['name']
                    ];
                }
            }
            $generosJson = json_encode($generos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            if (isset($data['description'])) {
                $descripcion =  $data['description'];
                $descripcion = strip_tags($data['description']);
            }

            $juegoData = [
                'nombre' => $nombre,
                'nota_metacritic' => $notaMetacritic,
                'fecha_lanzamiento' => $fechaLanzamiento,
                'sitio_web' => $sitioWeb,
                'imagen' => $imagen,
                'plataformas_principales' => $plataformasJson,
                'desarrolladoras' => $desarrolladorasJson,
                'publishers' => $publishersJson,
                'tiendas' => $tiendasJson,
                'generos' => $generosJson,
                'descripcion' => $descripcion,
                'creado_por_admin' => 0
            ];

            $this->VideojuegoModelo->insert($juegoData);
        }

        $db->transCommit();

        return $this->response->setJSON(['mensaje' => 'Datos cargados en la base de datos correctamente']);
    }

    /**
     * Borra datos existentes (salvo los creados por administradores) y carga nuevos datos desde la API
     */
    public function actualizarDatosAPI()
    {
        $resultadoValidacion = $this->apiKeyValidator->validar($this->request, $this->response);
        if ($resultadoValidacion !== true) {
            return $resultadoValidacion;
        }

        try {
            $db = \Config\Database::connect();
            $db->transBegin();

            $this->eliminarDatosActualizar();

            $idsJuegos = $this->obtenerIdsJuegos_API();
            $this->rellenarTablaVideojuegos($idsJuegos);
            $this->rellenarTablaGeneros();
            $this->rellenarTablaDesarrolladoras();
            $this->rellenarTablaPlataformas();
            $this->rellenarTablaPublishers();
            $this->rellenarTablaTiendas();

            $db->transCommit();

            return $this->response->setJSON([
                'mensaje' => 'Datos actualizados correctamente.'
            ])->setStatusCode(200);
        } catch (\Exception $e) {
            if (isset($db)) {
                $db->transRollback();
            }

            log_message('error', 'Error al actualizar datos desde la API externa: ' . $e->getMessage());

            return $this->response->setJSON([
                'error' => 'No se pudo completar la actualización de datos.'
            ])->setStatusCode(500);
        }
    }

    /**
     * Carga y guarda datos de géneros desde RAWG
     */
    public function rellenarTablaGeneros()
    {
        $apiKey = "a9a117515a694c0fa91d404dd5ede441";
        $baseUrl = "https://api.rawg.io/api/genres";

        $db = \Config\Database::connect();
        $db->transStart();

        $url = "$baseUrl?key=$apiKey";
        $llamada_API = curl_init();
        curl_setopt($llamada_API, CURLOPT_URL, $url);
        curl_setopt($llamada_API, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($llamada_API);
        curl_close($llamada_API);

        $data = json_decode($response, true);

        if (empty($data['results'])) {
            return;
        }

        foreach ($data['results'] as $genero) {

            if ($this->GeneroModelo->where('nombre', $genero['name'])->first()) {
                continue;
            }

            $generoData = [
                'nombre'          => $genero['name'],
                'cantidad_juegos' => $genero['games_count'],
                'imagen'          => $genero['image_background']
            ];

            $this->GeneroModelo->insert($generoData);
        }

        $db->transComplete();
    }

    /**
     * Carga y guarda datos de desarrolladoras desde RAWG
     */
    public function rellenarTablaDesarrolladoras()
    {
        $apiKey = "a9a117515a694c0fa91d404dd5ede441";
        $baseUrl = "https://api.rawg.io/api/developers";
        $totalPages = 1; //25
        $pageSize = 40; //40

        $db = \Config\Database::connect();
        $db->transStart();

        for ($page = 1; $page <= $totalPages; $page++) {

            $url = "$baseUrl?key=$apiKey&page=$page&page_size=$pageSize";
            $llamada_API = curl_init();
            curl_setopt($llamada_API, CURLOPT_URL, $url);
            curl_setopt($llamada_API, CURLOPT_RETURNTRANSFER, true);

            $response = curl_exec($llamada_API);
            curl_close($llamada_API);

            $data = json_decode($response, true);

            if (empty($data['results'])) {
                return;
            }

            foreach ($data['results'] as $desarrolladora) {

                if ($this->DesarrolladoraModelo->where('nombre', $desarrolladora['name'])->first()) {
                    continue;
                }

                $desarrolladoraData = [
                    'nombre'          => $desarrolladora['name'],
                    'cantidad_juegos' => $desarrolladora['games_count'],
                    'imagen'          => $desarrolladora['image_background']
                ];

                $this->DesarrolladoraModelo->insert($desarrolladoraData);
            }
        }

        $db->transComplete();
    }

    /**
     * Carga y guarda datos de plataformas desde RAWG
     */
    public function rellenarTablaPlataformas()
    {
        $apiKey = "a9a117515a694c0fa91d404dd5ede441";
        $baseUrl = "https://api.rawg.io/api/platforms";
        $totalPages = 2;

        $db = \Config\Database::connect();
        $db->transStart();

        for ($page = 1; $page <= $totalPages; $page++) {
            $url = "$baseUrl?key=$apiKey&page=$page";
            $llamada_API = curl_init();
            curl_setopt($llamada_API, CURLOPT_URL, $url);
            curl_setopt($llamada_API, CURLOPT_RETURNTRANSFER, true);

            $response = curl_exec($llamada_API);
            curl_close($llamada_API);

            $data = json_decode($response, true);

            if (empty($data['results'])) {
                return;
            }

            foreach ($data['results'] as $plataforma) {

                if ($this->PlataformaModelo->where('nombre', $plataforma['name'])->first()) {
                    continue;
                }

                $plataformaData = [
                    'nombre'          => $plataforma['name'],
                    'cantidad_juegos' => $plataforma['games_count'],
                    'imagen'          => $plataforma['image_background']
                ];

                $this->PlataformaModelo->insert($plataformaData);
            }
        }

        $db->transComplete();
    }

    /**
     * Carga y guarda datos de publishers desde RAWG
     */
    public function rellenarTablaPublishers()
    {
        $apiKey = "a9a117515a694c0fa91d404dd5ede441";
        $baseUrl = "https://api.rawg.io/api/publishers";
        $totalPages = 1; //25
        $pageSize = 40;

        $db = \Config\Database::connect();
        $db->transStart();

        for ($page = 1; $page <= $totalPages; $page++) {

            $url = "$baseUrl?key=$apiKey&page=$page&page_size=$pageSize";

            $llamada_API = curl_init();
            curl_setopt($llamada_API, CURLOPT_URL, $url);
            curl_setopt($llamada_API, CURLOPT_RETURNTRANSFER, true);

            $response = curl_exec($llamada_API);
            curl_close($llamada_API);

            $data = json_decode($response, true);

            if (empty($data['results'])) {
                return;
            }

            foreach ($data['results'] as $publisher) {

                if ($this->PublisherModelo->where('nombre', $publisher['name'])->first()) {
                    continue;
                }

                $publisherData = [
                    'nombre'          => $publisher['name'],
                    'cantidad_juegos' => $publisher['games_count'],
                    'imagen'          => $publisher['image_background']
                ];

                $this->PublisherModelo->insert($publisherData);
            }
        }

        $db->transComplete();
    }

    /**
     * Carga y guarda datos de tiendas desde RAWG
     */
    public function rellenarTablaTiendas()
    {
        $apiKey = "a9a117515a694c0fa91d404dd5ede441";
        $baseUrl = "https://api.rawg.io/api/stores";

        $db = \Config\Database::connect();
        $db->transStart();

        $url = "$baseUrl?key=$apiKey";
        $llamada_API = curl_init();
        curl_setopt($llamada_API, CURLOPT_URL, $url);
        curl_setopt($llamada_API, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($llamada_API);
        curl_close($llamada_API);

        $data = json_decode($response, true);

        if (empty($data['results'])) {
            return;
        }

        foreach ($data['results'] as $tienda) {

            if ($this->TiendaModelo->where('nombre', $tienda['name'])->first()) {
                continue;
            }

            $tiendaData = [
                'nombre'          => $tienda['name'],
                'dominio'         => $tienda['domain'],
                'cantidad_juegos' => $tienda['games_count'],
                'imagen'          => $tienda['image_background']
            ];

            $this->TiendaModelo->insert($tiendaData);
        }

        $db->transComplete();
    }

    /**
     * Elimina videojuegos no creados por administradores y reinicia los ID de las tablas
     */
    public function eliminarDatosActualizar()
    {
        $db = \Config\Database::connect();
        $db->transStart();

        try {
            $this->VideojuegoModelo->where('creado_por_admin !=', 1)->delete();

            $db->query('SELECT setval(\'vault."Videojuegos_id_seq"\', (SELECT MAX(id) FROM vault.videojuegos), true);');

            $db->query('TRUNCATE TABLE vault.generos RESTART IDENTITY CASCADE;');
            $db->query('TRUNCATE TABLE vault.desarrolladoras RESTART IDENTITY CASCADE;');
            $db->query('TRUNCATE TABLE vault.plataformas RESTART IDENTITY CASCADE;');
            $db->query('TRUNCATE TABLE vault.publishers RESTART IDENTITY CASCADE;');
            $db->query('TRUNCATE TABLE vault.tiendas RESTART IDENTITY CASCADE;');
        } catch (\Exception $e) {
            $db->transRollback();
            log_message('error', 'Error al realizar eliminación y actualización: ' . $e->getMessage());
            throw new \Exception('Error en la transacción: ' . $e->getMessage());
        }

        $db->transComplete();

        if ($db->transStatus() === FALSE) {
            log_message('error', 'La transacción falló en eliminarDatosActualizar.');
            throw new \Exception('La transacción falló.');
        }
    }

    /**
     * Resetea todos los datos de la base de datos, eliminando todos los datos e imágenes
     * de Cloudinary para luego reinsertarlo todo
     */
    public function purgarDatos()
    {
        $resultadoValidacion = $this->apiKeyValidator->validar($this->request, $this->response);

        if ($resultadoValidacion !== true) {
            return $resultadoValidacion;
        }

        try {

            $cloudinary = new Cloudinary([
                'cloud' => [
                    'cloud_name' => 'mbersan1005',
                    'api_key'    => '246324721933957',
                    'api_secret' => 'Ojh3Chu7gOvYJbwzWB-u0jmXF7U',
                ]
            ]);

            $juegos = $this->VideojuegoModelo->findAll();

            foreach ($juegos as $juego) {
                if (isset($juego['imagen']) && strpos($juego['imagen'], 'res.cloudinary.com') !== false) {
                    $publicId = $this->extraerPublicIdDesdeUrl($juego['imagen']);
                    if ($publicId) {
                        try {
                            $cloudinary->uploadApi()->destroy($publicId);
                        } catch (\Exception $e) {
                            log_message('error', 'Error al eliminar imagen de Cloudinary (ID: ' . $publicId . '): ' . $e->getMessage());
                        }
                    }
                }
            }

            $this->eliminarDatosPurgar();

            $idsJuegos = $this->obtenerIdsJuegos_API();
            $this->rellenarTablaVideojuegos($idsJuegos);

            $this->rellenarTablaGeneros();
            $this->rellenarTablaDesarrolladoras();
            $this->rellenarTablaPlataformas();
            $this->rellenarTablaPublishers();
            $this->rellenarTablaTiendas();

            return $this->response->setJSON([
                'mensaje' => 'Datos purgados correctamente.'
            ])->setStatusCode(200);
        } catch (\Exception $e) {
            log_message('error', 'Error en purgarDatos: ' . $e->getMessage());

            return $this->response->setJSON([
                'error' => 'Error al purgar y recargar los datos.'
            ])->setStatusCode(500);
        }
    }

    /**
     * Trunca completamente las tablas de la base de datos, reseteando los IDs y
     * eliminando los datos
     */
    public function eliminarDatosPurgar()
    {
        $db = \Config\Database::connect();

        try {
            $db->transStart();

            $db->query('TRUNCATE TABLE vault.videojuegos RESTART IDENTITY CASCADE;');
            $db->query('TRUNCATE TABLE vault.generos RESTART IDENTITY CASCADE;');
            $db->query('TRUNCATE TABLE vault.desarrolladoras RESTART IDENTITY CASCADE;');
            $db->query('TRUNCATE TABLE vault.plataformas RESTART IDENTITY CASCADE;');
            $db->query('TRUNCATE TABLE vault.publishers RESTART IDENTITY CASCADE;');
            $db->query('TRUNCATE TABLE vault.tiendas RESTART IDENTITY CASCADE;');

            $db->transComplete();
        } catch (\Exception $e) {
            $db->transRollback();
            throw $e;
        }
    }

    /**
     * Obtiene el AppID de un juego específico desde la API pública de Steam
     */
    public function obtenerAppId()
    {
        ini_set("post_max_size", -1);
        ini_set("max_execution_time", -1);
        ini_set("memory_limit", -1);
        ini_set("max_input_time", -1);
        ini_set("max_input_vars", -1);

        $nombreJuego = $this->request->getGet('nombreJuego');

        try {
            $apiUrl = 'https://api.steampowered.com/ISteamApps/GetAppList/v2/';

            $ch = curl_init($apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($response === false || $httpCode !== 200) {
                log_message('error', 'Error en cURL al llamar a Steam API: ' . $curlError);
                return $this->response->setJSON([
                    'error' => 'No se pudo obtener la lista de juegos desde Steam.'
                ])->setStatusCode(500);
            }

            $json = json_decode($response, true);

            if (!$json || !isset($json['applist']['apps'])) {
                return $this->response->setJSON([
                    'error' => 'Estructura de respuesta de Steam no válida.'
                ])->setStatusCode(500);
            }

            $apps = $json['applist']['apps'];
            $nombreJuegoBuscado = $this->normalizarNombre($nombreJuego);

            foreach ($apps as $app) {
                $nombreJuegoActual = $this->normalizarNombre($app['name'] ?? '');

                if ($nombreJuegoBuscado === $nombreJuegoActual) {
                    return $this->response->setJSON([
                        'appid' => $app['appid']
                    ])->setStatusCode(200);
                }
            }

            return $this->response->setJSON([
                'error' => 'Gráfica del juego no encontrada'
            ])->setStatusCode(404);
        } catch (\Exception $e) {
            log_message('error', 'Error al obtener AppID desde Steam API: ' . $e->getMessage() . ' en línea ' . $e->getLine());

            return $this->response->setJSON([
                'error' => 'Ocurrió un error al buscar el AppID.'
            ])->setStatusCode(500);
        }
    }

    /**
     * Normaliza el nombre de un juego para facilitar la comparación
     */
    private function normalizarNombre($nombre)
    {

        $nombre = urldecode($nombre);
        $nombre = trim(mb_strtolower($nombre, 'UTF-8'));
        $nombre = iconv('UTF-8', 'ASCII//TRANSLIT', $nombre);
        $nombre = preg_replace('/[^a-z0-9 ]/', '', $nombre);
        $nombre = preg_replace('/\s+/', ' ', $nombre);

        return $nombre;
    }
}
