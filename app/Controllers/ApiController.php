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

class ApiController extends BaseController
{
    protected $request;
    protected $response;

    public $apiKeyValidator;

    //https://apirest.saicasl.eu/api1/api/public -- https://vault-ci4-api.up.railway.app -- https://api-vault.onrender.com
    private $baseUrlHost;

    public function __construct(){
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

    public function recibirJuegos(){
        
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

    public function obtenerIdsJuegos_API(){
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

    public function rellenarTablaVideojuegos($idsJuegos){
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
            }else{
                $notaMetacritic = null;
            }

            if (isset($data['released'])) {
                $fechaLanzamiento =  $data['released'];
            }

            if (isset($data['website'])) {
                $sitioWeb =  $data['website'];
            }else{
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

    public function recibirDatosJuego($id){
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
    
    public function inicioSesion(){
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
    
    //TRUNCATE nombre_de_tu_tabla RESTART IDENTITY; RESETEAR AUTO INCREMENTAR
    public function actualizarDatosAPI(){
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
    
    public function rellenarTablaGeneros(){
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
    
    public function rellenarTablaDesarrolladoras(){
        $apiKey = "a9a117515a694c0fa91d404dd5ede441";
        $baseUrl = "https://api.rawg.io/api/developers";
        $totalPages = 1; //25
        $pageSize = 40; //40

        $db = \Config\Database::connect();
        $db->transStart();

        for ($page=1; $page <= $totalPages; $page++) { 
            
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

    public function rellenarTablaPlataformas(){
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

    public function rellenarTablaPublishers(){
        $apiKey = "a9a117515a694c0fa91d404dd5ede441";
        $baseUrl = "https://api.rawg.io/api/publishers";
        $totalPages = 1; //25
        $pageSize = 40;

        $db = \Config\Database::connect();
        $db->transStart();
        
        for ($page=1; $page <= $totalPages; $page++) { 
            
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

    public function rellenarTablaTiendas(){
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
    
    public function recibirGeneros(){
        $resultadoValidacion = $this->apiKeyValidator->validar($this->request, $this->response);
    
        if ($resultadoValidacion !== true) {
            return $resultadoValidacion;
        }
    
        try {
            $generos = $this->GeneroModelo->orderBy('cantidad_juegos', 'DESC')->findAll();
    
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
    
    public function recibirPlataformas(){
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
    
    public function recibirTiendas(){
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
    
    public function recibirDesarrolladoras(){
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
    
    public function recibirPublishers(){
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
                    'error' => 'No se encontraron videojuegos con esa categoría o nombre'
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
    
    public function eliminarJuego(){
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
    
    public function obtenerDatosFormulario(){
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
    
    public function agregarJuego(){
        $resultadoValidacion = $this->apiKeyValidator->validar($this->request, $this->response);
    
        if ($resultadoValidacion !== true) {
            return $resultadoValidacion;
        }
    
        $datos = $this->request->getPost();
        $imagen = $this->request->getFile('imagen');
    
        try {
            $nombreOriginal = basename($imagen->getClientName());
            $rutaDestino = WRITEPATH . '../public/resources/imagenes/' . $nombreOriginal;
            
            if (is_file($rutaDestino)) {
                unlink($rutaDestino);
            }
    
            $imagen->move(WRITEPATH . '../public/resources/imagenes', $nombreOriginal, true);
            $rutaImagenFinal = $this->baseUrlHost . '/resources/imagenes/' . $nombreOriginal;
    
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
                'creado_por_admin' => 1
            ];
    
            $db = \Config\Database::connect();
            $db->transBegin();
    
            $juegoId = $this->VideojuegoModelo->insert($insertData);
    
            if ($juegoId) {
                $db->transCommit();
                return $this->response->setJSON(['mensaje' => 'Juego agregado correctamente'])
                                      ->setStatusCode(201); 
            } else {
                $db->transRollback();
                return $this->response->setJSON(['error' => 'Error al agregar el juego', 'datos' => $insertData])
                                      ->setStatusCode(500); 
            }
    
        } catch (\Exception $e) {
            $db->transRollback();
            return $this->response->setJSON(['error' => 'Error al agregar el juego'])
                                  ->setStatusCode(500); 
        }
    }
    
    public function crearAdministrador() {
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
    
    public function editarJuego(){
        
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
            if ($imagen && $imagen->isValid() && !$imagen->hasMoved()) {
                $nombreOriginal = basename($imagen->getClientName());
                $rutaDestino = WRITEPATH . '../public/resources/imagenes/' . $nombreOriginal;
    
                if (is_file($rutaDestino)) {
                    unlink($rutaDestino);
                }
    
                if (!$imagen->move(WRITEPATH . '../public/resources/imagenes', $nombreOriginal, true)) {
                    throw new \Exception('Error al mover la imagen al servidor.');
                }
    
                $rutaImagenFinal = $this->baseUrlHost . '/resources/imagenes/' . $nombreOriginal;
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
                'error' => 'Error al actualizar el juego'
            ])->setStatusCode(500); 
        }
    }
    
    public function eliminarDatosActualizar(){
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
    
    
    public function recibirJuegosAdmin(){
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
    
    public function purgarDatos(){
        $resultadoValidacion = $this->apiKeyValidator->validar($this->request, $this->response);
    
        if ($resultadoValidacion !== true) {
            return $resultadoValidacion;
        }
    
        try {
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
    
    public function eliminarDatosPurgar(){
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
    
    public function realizarBusqueda(){
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
    
    public function realizarBusquedaDesarrolladoras(){
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
    
    public function realizarBusquedaPublishers(){
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
    
    public function obtenerAppId($nombreJuego) {
        ini_set("post_max_size",-1);
        ini_set("max_execution_time",-1);
        ini_set("memory_limit",-1);
        ini_set("max_input_time",-1);
        ini_set("max_input_vars",-1);
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
                'error' => 'Juego no encontrado',
                'appid' => null
            ])->setStatusCode(404);
    
        } catch (\Exception $e) {
            log_message('error', 'Error al obtener AppID desde Steam API: ' . $e->getMessage() . ' en línea ' . $e->getLine());
    
            return $this->response->setJSON([
                'error' => 'Ocurrió un error al buscar el AppID.'
            ])->setStatusCode(500);
        }
    }
    
    
    private function normalizarNombre($nombre) {
        
        $nombre = urldecode($nombre);
        $nombre = trim(mb_strtolower($nombre, 'UTF-8'));
        $nombre = iconv('UTF-8', 'ASCII//TRANSLIT', $nombre);
        $nombre = preg_replace('/[^a-z0-9 ]/', '', $nombre);
        $nombre = preg_replace('/\s+/', ' ', $nombre);
    
        return $nombre;
    }
    
}   
?>