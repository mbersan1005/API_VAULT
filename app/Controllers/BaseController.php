<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Class BaseController
 *
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 * Extend this class in any new controllers:
 *     class Home extends BaseController
 *
 * For security be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{
    /**
     * Instance of the main Request object.
     *
     * @var CLIRequest|IncomingRequest
     */
    protected $request;

    /**
     * An array of helpers to be loaded automatically upon
     * class instantiation. These helpers will be available
     * to all other controllers that extend BaseController.
     *
     * @var list<string>
     */
    protected $helpers = [];

    /**
     * Be sure to declare properties for any property fetch you initialized.
     * The creation of dynamic property is deprecated in PHP 8.2.
     */
    public $VideojuegoModelo;
    public $AdministradoresModelo;
    public $GeneroModelo;
    public $DesarrolladoraModelo;
    public $PlataformaModelo;
    public $PublisherModelo;
    public $TiendaModelo;

    /**
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);

        // Preload any models, libraries, etc, here.
        $this->VideojuegoModelo = new \App\Models\VideojuegoModelo();
        // E.g.: $this->session = service('session');
    }

    public function getResponse(array $responseBody, int $code = ResponseInterface::HTTP_OK){
        return $this->response->setStatusCode($code)->setJSON($responseBody);
    }

    /**
     * Extrae el public ID de una URL de Cloudinary.
     */
    protected function extraerPublicIdDesdeUrl($url)
    {
        $parsed = parse_url($url);
        if (!isset($parsed['path'])) return null;
    
        $path = trim($parsed['path'], '/');
        $segments = explode('/', $path);
    
        $uploadIndex = array_search('upload', $segments);
        if ($uploadIndex === false || !isset($segments[$uploadIndex + 1])) {
            return null;
        }
    
        $publicIdParts = array_slice($segments, $uploadIndex + 1);
    
        if (preg_match('/^v\d+$/', $publicIdParts[0])) {
            array_shift($publicIdParts);
        }
    
        $last = array_pop($publicIdParts);
        $last = preg_replace('/\.(jpg|jpeg|png)$/', '', $last);
        $publicIdParts[] = $last;
    
        return implode('/', $publicIdParts);
    }

}
