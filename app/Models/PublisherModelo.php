<?php

namespace App\Models;

use CodeIgniter\Model;

class PublisherModelo extends Model{

    protected $table = 'vault.publishers';

    protected $primaryKey = 'id';

    protected $allowedFields = [
        'nombre', 
        'cantidad_juegos', 
        'imagen'
    ];

    protected $validationRules = [
        'nombre' => 'required',
        'cantidad_juegos' => 'required|integer',
        'imagen' => 'required'
    ];

    protected $validationMessages = [
        'nombre' => [
            'required' => 'El nombre del publishers es obligatorio.'
        ],
        'cantidad_juegos' => [
            'required' => 'La cantidad de juegos es obligatoria.',
            'integer' => 'La cantidad de juegos debe ser un número entero.'
        ],
        'imagen' => [
            'required' => 'La imagen es obligatoria.'
        ]
    ];

}

?>