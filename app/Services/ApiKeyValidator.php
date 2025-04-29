<?php
namespace App\Services;

use CodeIgniter\HTTP\ResponsableInterface;

class ApiKeyValidator{

    private $validacionToken = '318be955-c62b-40d7-a7b3-c4de08ceb444';
    private $validacionAuthorization = 'Bearer i8zz9PWQdXr7OpKW2BMZ4LgH8tXE3ms3H2YLuEFmfrGTVkt2Gxm9i3VdJdSCS47A';

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