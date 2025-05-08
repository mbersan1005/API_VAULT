<?php

namespace App\Models;

use CodeIgniter\Model;

class ArticuloModelo extends Model
{
    protected $primaryKey="id";
    
    protected $table = 'articulo';

    protected $allowedFields = ['nombre_producto', 'modelo', 'marca', 'alta', 'precio', 'fotografia'];

    protected $validationRules=[
        'nombre_producto' => 'required',
        'modelo' => 'required',
        'marca' => 'required',
        'alta' => 'date|required',
        'precio' => 'required',
        'fotografia' => 'required'
    ];

    protected $validationMessages=[
        'nombre_producto' => ['required'=>'Se requiere del nombre del producto.'],
        'modelo' => ['required'=>'Se requiere del modelo del producto.'],
        'marca' => ['required'=>'Se requiere la marca del producto.'],
        'alta' =>[
            'date' => 'Fecha de alta del producto inválida.',
            'required' => 'Se requiere el alta del producto.'
        ],
        'precio' => ['required'=>'Se requiere el precio del producto.'],
        'fotografia' => ['required'=>'Se requiere la fotografia del producto.']
        
    ];

    public function __construct()
    {
        parent::__construct();
    }
}