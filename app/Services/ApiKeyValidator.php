<?php
namespace App\Services;

use CodeIgniter\HTTP\ResponsableInterface;

/**
 * Servicio encargado de validar la autenticación mediante encabezados.
 * Valida si la petición contiene un token válido en el encabezado 'Authorization' o 'Token'.
 */
class ApiKeyValidator{

    //Token válido para validación mediante encabezado 'Token'
    private $validacionToken = '318be955-c62b-40d7-a7b3-c4de08ceb444';

    //Token válido para validación mediante encabezado 'Authorization'
    private $validacionAuthorization = 'Bearer i8zz9PWQdXr7OpKW2BMZ4LgH8tXE3ms3H2YLuEFmfrGTVkt2Gxm9i3VdJdSCS47A';

    /**
     * Valida si la solicitud contiene un token de acceso válido.
     * Se comprueba tanto el encabezado 'Authorization' como 'Token'
     */
    public function validar($request, $response)
    {
        $authorizationHeader = $request->getHeaderLine('Authorization');
        $tokenHeader = $request->getHeaderLine('Token');
        
        if (!empty($authorizationHeader) && $authorizationHeader === $this->validacionAuthorization) {
            return true;
        }elseif (!empty($tokenHeader) && $tokenHeader === $this->validacionToken) {
            return true;
        }else {
            return $response->setStatusCode(401)->setJSON(['error' => 'TOKEN INVALIDO O FALTANTE, ERROR DE VALIDACIÓN']);
        }
    }
}
?>