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

class ApiController extends BaseController
{

    private $apiKeyValidator;

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
    }

    public function recibirJuegos(){
        
        $resultadoValidacion = $this->apiKeyValidator->validar($this->request, $this->response);

        if ($resultadoValidacion !== true) {
            return $resultadoValidacion;
        }
        
        $juegos = $this->VideojuegoModelo->findAll();

        if (empty($juegos)) {
            $data = ['mensaje' => 'No se encontraron videojuegos'];
        } else {
            $data = ['juegos' => $juegos];
        }
        
        return $this->response->setJSON($data);

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
        
        $juego = $this->VideojuegoModelo->find($id);

        if (empty($juego)) {
            $data = ['mensaje' => 'No se encontró videojuego con ese ID'];
        } else {
            $data = ['juego' => $juego];
        }
        
        return $this->response->setJSON($data);

    }
        
    public function inicioSesion()
    {
        $resultadoValidacion = $this->apiKeyValidator->validar($this->request, $this->response);
    
        if ($resultadoValidacion !== true) {
            return $resultadoValidacion;
        }
    
        $data = $this->request->getJSON(true) ?? [];
    
        if (!isset($data['nombre']) || !isset($data['password']) || empty(trim($data['nombre'])) || empty(trim($data['password']))) {
            return $this->response->setJSON(['mensaje' => 'Usuario o contraseña no proporcionados'])
                                  ->setStatusCode(400);
        }
    
        $nombre = trim($data['nombre']);
        $password = trim($data['password']);
    
        $administrador = $this->AdministradoresModelo->where('nombre', $nombre)->first();
    
        if (!$administrador) {
            return $this->response->setJSON(['mensaje' => 'Usuario no encontrado'])
                                  ->setStatusCode(404);
        }
    
        if (!password_verify($password, $administrador['password'])) {
            return $this->response->setJSON(['mensaje' => 'Contraseña incorrecta'])
                                  ->setStatusCode(401);
        }
    
        $fechaActual = Time::now('Europe/Madrid')->toDateTimeString();
        $this->AdministradoresModelo->update($administrador['id'], ['fecha_ultimo_login' => $fechaActual]);
    
        return $this->response->setJSON([
            'mensaje' => 'Inicio de sesión exitoso',
            'administrador' => [
                'id' => $administrador['id'],
                'nombre' => $administrador['nombre'],
                'fecha_creacion' => $administrador['fecha_creacion'],
                'fecha_ultimo_login' => $fechaActual 
            ]
        ]);
    }
    
    //TRUNCATE nombre_de_tu_tabla RESTART IDENTITY; RESETEAR AUTO INCREMENTAR

    public function actualizarDatosAPI(){
        
        $resultadoValidacion = $this->apiKeyValidator->validar($this->request, $this->response);

        if ($resultadoValidacion !== true) {
            return $resultadoValidacion;
        }
        
        $this->eliminarDatosActualizar();

        $idsJuegos = $this->obtenerIdsJuegos_API();
        $this->rellenarTablaVideojuegos($idsJuegos);
        $this->rellenarTablaGeneros();
        $this->rellenarTablaDesarrolladoras();
        $this->rellenarTablaPlataformas();
        $this->rellenarTablaPublishers();
        $this->rellenarTablaTiendas();

        return $this->response->setJSON(['status' => 'ok', 'mensaje' => 'Datos actualizados correctamente.']);

    }
    
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
    
    public function rellenarTablaDesarrolladoras(){
        $apiKey = "a9a117515a694c0fa91d404dd5ede441";
        $baseUrl = "https://api.rawg.io/api/developers";
        $totalPages = 25;
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
        $totalPages = 25;
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
        
        $generos = $this->GeneroModelo->orderBy('cantidad_juegos', 'DESC')->findAll();

        if (empty($generos)) {
            $data = ['mensaje' => 'No se encontraron generos'];
        } else {
            $data = ['generos' => $generos];
        }
        
        return $this->response->setJSON($data);

    }

    public function recibirPlataformas(){
        
        $resultadoValidacion = $this->apiKeyValidator->validar($this->request, $this->response);

        if ($resultadoValidacion !== true) {
            return $resultadoValidacion;
        }
        
        $plataformas = $this->PlataformaModelo->findAll();

        if (empty($plataformas)) {
            $data = ['mensaje' => 'No se encontraron plataformas'];
        } else {
            $data = ['plataformas' => $plataformas];
        }
        
        return $this->response->setJSON($data);

    }

    public function recibirTiendas(){
        
        $resultadoValidacion = $this->apiKeyValidator->validar($this->request, $this->response);

        if ($resultadoValidacion !== true) {
            return $resultadoValidacion;
        }
        
        $tiendas = $this->TiendaModelo->findAll();

        if (empty($tiendas)) {
            $data = ['mensaje' => 'No se encontraron tiendas'];
        } else {
            $data = ['tiendas' => $tiendas];
        }
        
        return $this->response->setJSON($data);

    }

    public function recibirDesarrolladoras(){
        
        $resultadoValidacion = $this->apiKeyValidator->validar($this->request, $this->response);

        if ($resultadoValidacion !== true) {
            return $resultadoValidacion;
        }
        
        $desarrolladoras = $this->DesarrolladoraModelo->orderBy('cantidad_juegos', 'DESC')->findAll();

        if (empty($desarrolladoras)) {
            $data = ['mensaje' => 'No se encontraron desarrolladoras'];
        } else {
            $data = ['desarrolladoras' => $desarrolladoras];
        }
        
        return $this->response->setJSON($data);

    }

    public function recibirPublishers(){
        
        $resultadoValidacion = $this->apiKeyValidator->validar($this->request, $this->response);

        if ($resultadoValidacion !== true) {
            return $resultadoValidacion;
        }
        
        $publishers = $this->PublisherModelo->orderBy('cantidad_juegos', 'DESC')->findAll();

        if (empty($publishers)) {
            $data = ['mensaje' => 'No se encontraron publishers'];
        } else {
            $data = ['publishers' => $publishers];
        }
        
        return $this->response->setJSON($data);

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
            return $this->response->setJSON(['mensaje' => 'Categoría no válida']);
        }
    
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
            return $this->response->setJSON(['mensaje' => 'No se encontraron videojuegos con esa categoria o nombre']);
        } else {
            return $this->response->setJSON(['juegosFiltrados' => $result]);
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
            return $this->response->setJSON(['mensaje' => 'ID del juego no proporcionado']);
        }

        $juego = $this->VideojuegoModelo->find($id);

        if (!$juego) {
            return $this->response->setJSON(['mensaje' => 'Juego no encontrado']);
        }

        $this->VideojuegoModelo->delete($id);

        return $this->response->setJSON([
            'success' => true,
            'mensaje' => 'Juego eliminado correctamente'
        ]);
    }
    
    public function obtenerDatosFormulario(){

        $resultadoValidacion = $this->apiKeyValidator->validar($this->request, $this->response);
    
        if ($resultadoValidacion !== true) {
            return $resultadoValidacion;
        }

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

    }

    public function agregarJuego() {
        
        $resultadoValidacion = $this->apiKeyValidator->validar($this->request, $this->response);
    
        if ($resultadoValidacion !== true) {
            return $resultadoValidacion;
        }
    
        $data = $this->request->getJSON(true); 
    
        if (empty($data['nombre']) || empty($data['descripcion']) || empty($data['fecha_lanzamiento'])) {
            return $this->response->setJSON(['mensaje' => 'Faltan campos obligatorios', 'datos_recibidos' => $data]);
        }
    
        if (empty($data['tiendas']) || empty($data['plataformas']) || empty($data['generos']) || empty($data['desarrolladoras']) || empty($data['publishers'])) {
            return $this->response->setJSON([
                'mensaje' => 'Debe seleccionar al menos una tienda, plataforma, género, desarrolladora y publisher',
                'datos_recibidos' => $data
            ]);
        }
    
        try {
            $data['nota_metacritic'] = isset($data['nota_metacritic']) && $data['nota_metacritic'] !== '' ? $data['nota_metacritic'] : null;
            $data['sitio_web'] = isset($data['sitio_web']) && $data['sitio_web'] !== '' ? $data['sitio_web'] : null;
    
            $data['plataformas_principales'] = json_encode($data['plataformas']);
            $data['tiendas'] = json_encode($data['tiendas']);
            $data['generos'] = json_encode($data['generos']);
            $data['desarrolladoras'] = json_encode($data['desarrolladoras']);
            $data['publishers'] = json_encode($data['publishers']);
    
            unset($data['plataformas']); 
    
            $data['creado_por_admin'] = 1;
    
            $juegoId = $this->VideojuegoModelo->insert($data);
            if ($juegoId) {
                return $this->response->setJSON(['mensaje' => 'Juego agregado correctamente']);
            } else {
                return $this->response->setJSON(['mensaje' => 'Error al agregar el juego', 'datos' => $data]);
            }
        } catch (\Exception $e) {
            return $this->response->setJSON(['mensaje' => 'Error al agregar el juego: ' . $e->getMessage()]);
        }
    }
    
    public function crearAdministrador() {
        $resultadoValidacion = $this->apiKeyValidator->validar($this->request, $this->response);
    
        if ($resultadoValidacion !== true) {
            return $resultadoValidacion;
        }
    
        $nombre = $this->request->getJSON()->nombre;
        $password = $this->request->getJSON()->password;        
        
        if (empty($nombre)) {
            return $this->response->setJSON([
                'mensaje' => 'El nombre no puede estar vacío',
                'datos' => []
            ]);
        }
    
        if (empty($password)) {
            return $this->response->setJSON([
                'mensaje' => 'La contraseña no puede estar vacía',
                'datos' => []
            ]);
        }
    
        $passwordCifrada = password_hash($password, PASSWORD_DEFAULT);
        $fechaActual = Time::now('Europe/Madrid')->toDateTimeString();
    
        $data = [
            'nombre' => $nombre,
            'password' => $passwordCifrada,
            'fecha_creacion' => $fechaActual,
            'fecha_ultimo_login' => $fechaActual
        ];
    
        $inserted = $this->AdministradoresModelo->insert($data);
        
        if (!$inserted) {
            return $this->response->setJSON([
                'mensaje' => 'Error al insertar el administrador en la base de datos',
                'datos' => []
            ]);
        }
    
        return $this->response->setJSON([
            'mensaje' => 'Cuenta de administrador creada correctamente',
            'datos' => $data
        ]);
    }
    
    public function editarJuego(){
        $resultadoValidacion = $this->apiKeyValidator->validar($this->request, $this->response);
        
        if ($resultadoValidacion !== true) {
            return $resultadoValidacion;
        }
    
        $data = $this->request->getJSON();
    
        $updateData = [
            'nombre' => $data->nombre,
            'nota_metacritic' => $data->nota_metacritic,
            'fecha_lanzamiento' => $data->fecha_lanzamiento,
            'sitio_web' => $data->sitio_web,
            'imagen' => $data->imagen,
            'plataformas_principales' => json_encode($data->plataformas_principales),
            'desarrolladoras' => json_encode($data->desarrolladoras),
            'publishers' => json_encode($data->publishers),
            'tiendas' => json_encode($data->tiendas),
            'generos' => json_encode($data->generos),
            'descripcion' => $data->descripcion,
            'creado_por_admin' => 1  
        ];
    
        $this->VideojuegoModelo->update($data->id, $updateData);
    
        return $this->response->setJSON([
            'mensaje' => 'Juego actualizado correctamente.',
            'datos' => $updateData
        ]);
    }
    
    public function eliminarDatosActualizar(){
        $db = \Config\Database::connect();
        $db->transStart();
    
        $this->VideojuegoModelo->where('creado_por_admin !=', 1)->delete();
    
        $db->query('SELECT setval(\'vault."Videojuegos_id_seq"\', 1, false);');
    
        $db->query('TRUNCATE TABLE vault.generos RESTART IDENTITY CASCADE;');
        $db->query('TRUNCATE TABLE vault.desarrolladoras RESTART IDENTITY CASCADE;');
        $db->query('TRUNCATE TABLE vault.plataformas RESTART IDENTITY CASCADE;');
        $db->query('TRUNCATE TABLE vault.publishers RESTART IDENTITY CASCADE;');
        $db->query('TRUNCATE TABLE vault.tiendas RESTART IDENTITY CASCADE;');
    
        $db->transComplete();
    }
    
    public function recibirJuegosAdmin(){
        
        $resultadoValidacion = $this->apiKeyValidator->validar($this->request, $this->response);

        if ($resultadoValidacion !== true) {
            return $resultadoValidacion;
        }
        
        $juegos = $this->VideojuegoModelo->select('nombre')->where('creado_por_admin', 1)->findAll();

        if (empty($juegos)) {
            $data = ['mensaje' => 'No se encontraron videojuegos'];
        } else {
            $data = ['juegos' => $juegos];
        }
        
        return $this->response->setJSON($data);

    }

    public function purgarDatos(){
        
        $resultadoValidacion = $this->apiKeyValidator->validar($this->request, $this->response);

        if ($resultadoValidacion !== true) {
            return $resultadoValidacion;
        }
        
        $this->eliminarDatosPurgar();

        $idsJuegos = $this->obtenerIdsJuegos_API();
        $this->rellenarTablaVideojuegos($idsJuegos);
        
        $this->rellenarTablaGeneros();
        $this->rellenarTablaDesarrolladoras();
        $this->rellenarTablaPlataformas();
        $this->rellenarTablaPublishers();
        $this->rellenarTablaTiendas();
        
        return $this->response->setJSON(['status' => 'ok', 'mensaje' => 'Datos purgados correctamente.']);

    }

    public function eliminarDatosPurgar(){
        $db = \Config\Database::connect();
        $db->transStart();
    
        $db->query('TRUNCATE TABLE vault.videojuegos RESTART IDENTITY CASCADE;');
        
        $db->query('TRUNCATE TABLE vault.generos RESTART IDENTITY CASCADE;');
        $db->query('TRUNCATE TABLE vault.desarrolladoras RESTART IDENTITY CASCADE;');
        $db->query('TRUNCATE TABLE vault.plataformas RESTART IDENTITY CASCADE;');
        $db->query('TRUNCATE TABLE vault.publishers RESTART IDENTITY CASCADE;');
        $db->query('TRUNCATE TABLE vault.tiendas RESTART IDENTITY CASCADE;');
        
        $db->transComplete();
    }

    public function realizarBusqueda(){
        $resultadoValidacion = $this->apiKeyValidator->validar($this->request, $this->response);
    
        if ($resultadoValidacion !== true) {
            return $resultadoValidacion;
        }
    
        $data = $this->request->getJSON(true) ?? [];
        $nombre = $data['nombre'] ?? '';
    
        if (empty($nombre)) {
            return $this->response->setJSON(['juegos' => []]);
        }
    
        $juegos = $this->VideojuegoModelo
            ->like('LOWER(nombre)', strtolower($nombre))
            ->findAll();
    
        return $this->response->setJSON(['juegos' => $juegos]);
    }

    public function realizarBusquedaDesarrolladoras(){
        $resultadoValidacion = $this->apiKeyValidator->validar($this->request, $this->response);
    
        if ($resultadoValidacion !== true) {
            return $resultadoValidacion;
        }
    
        $data = $this->request->getJSON(true) ?? [];
        $nombre = $data['nombre'] ?? '';
    
        if (empty($nombre)) {
            return $this->response->setJSON(['desarrolladoras' => []]);
        }
    
        $juegos = $this->DesarrolladoraModelo
            ->like('LOWER(nombre)', strtolower($nombre))
            ->findAll();
    
        return $this->response->setJSON(['desarrolladoras' => $juegos]);
    }

    public function realizarBusquedaPublishers(){
        $resultadoValidacion = $this->apiKeyValidator->validar($this->request, $this->response);
    
        if ($resultadoValidacion !== true) {
            return $resultadoValidacion;
        }
    
        $data = $this->request->getJSON(true) ?? [];
        $nombre = $data['nombre'] ?? '';
    
        if (empty($nombre)) {
            return $this->response->setJSON(['publishers' => []]);
        }
    
        $juegos = $this->PublisherModelo
            ->like('LOWER(nombre)', strtolower($nombre))
            ->findAll();
    
        return $this->response->setJSON(['publishers' => $juegos]);
    }
    
    public function obtenerAppId($nombreJuego)
    {
        $rutaJson = FCPATH . 'resources/json/juegos_steam.json';
    
        if (!file_exists($rutaJson)) {
            return $this->response->setJSON(['error' => 'Archivo JSON no encontrado.']);
        }
    
        $contenido = file_get_contents($rutaJson);
        $json = json_decode($contenido, true);
    
        if (!$json || !isset($json['applist']['apps'])) {
            return $this->response->setJSON(['error' => 'Estructura del JSON no válida.']);
        }
    
        $apps = $json['applist']['apps'];
        $nombreJuegoBuscado = $this->normalizarNombre($nombreJuego);
    
        foreach ($apps as $app) {
            $nombreJuegoActual = $this->normalizarNombre($app['name'] ?? '');
    
            if ($nombreJuegoBuscado === $nombreJuegoActual) {
                return $this->response->setJSON(['appid' => $app['appid']]);
            }
        }
    
        return $this->response->setJSON([
            'mensaje' => 'Juego no encontrado',
            'appid' => null
        ]);
    }

    private function normalizarNombre($nombre)
    {
        $nombre = urldecode($nombre);
        $nombre = trim(strtolower($nombre));
        $nombre = preg_replace('/\s+/', ' ', $nombre);
        return $nombre;
    }

    public function actualizarGraficasJuegos(){

        $apiUrl = 'https://api.steampowered.com/ISteamApps/GetAppList/v2/';
        $nombreArchivo = 'juegos_steam.json';
        $archivoLocal = WRITEPATH . '../public/resources/json/' . $nombreArchivo;

        helper('filesystem');

        try {
            
            $jsonData = file_get_contents($apiUrl);

            if ($jsonData === false) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'mensaje' => 'No se pudo obtener el JSON desde la API'
                ]);
            }

            write_file($archivoLocal, $jsonData); 

            return $this->response->setJSON([
                'status' => 'ok',
                'mensaje' => 'Archivo actualizado.',
                'archivo' => $nombreArchivo]);

        } catch (\Exception $e) {
            return $this->response->setJSON([
                'status' => 'error',
                'mensaje' => 'Error al actualizar el archivo: ' . $e->getMessage()
            ]);
        }

    }

}   
?>