<?php

namespace App\Controllers;

class Home extends BaseController
{
    public function index(): string
    {
        $db = \Config\Database::connect();
        if ($db->connect()) {
            echo "Conexión exitosa a PostgreSQL";
        } else {
            echo "Error en la conexión a la base de datos";
        }

        return view('welcome_message');
    }

    
}
