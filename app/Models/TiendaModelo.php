<?php

namespace App\Models;

use CodeIgniter\Model;

class TiendaModelo extends Model{

    protected $table = 'vault.tiendas';

    protected $primaryKey = 'id';

    protected $allowedFields = [
        'nombre', 
        'dominio',
        'cantidad_juegos', 
        'imagen'
    ];

    protected $validationRules = [
        'nombre' => 'required',
        'dominio' => 'required',
        'cantidad_juegos' => 'required|integer',
        'imagen' => 'required'
    ];

    protected $validationMessages = [
        'nombre' => [
            'required' => 'El nombre de la tienda es obligatorio.'
        ],
        'dominio' => [
            'required' => 'El dominio de la tienda es obligatorio.'
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