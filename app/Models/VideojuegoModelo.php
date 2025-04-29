<?php

namespace App\Models;

use CodeIgniter\Model;

class VideojuegoModelo extends Model
{
    protected $primaryKey = "id";
    
    protected $table = 'vault.videojuegos';

    protected $allowedFields = [
        'nombre',
        'nota_metacritic',
        'fecha_lanzamiento',
        'sitio_web',
        'imagen',
        'plataformas_principales',
        'desarrolladoras',
        'publishers',
        'tiendas',
        'generos',
        'descripcion',
        'creado_por_admin'
    ];

    protected $validationRules = [
        'nombre' => 'required',
        'fecha_lanzamiento' => 'date|required',
        'imagen' => 'required',
        'plataformas_principales' => 'required',
        'desarrolladoras' => 'required',
        'publishers' => 'required',
        'tiendas' => 'required',
        'generos' => 'required',
        'descripcion' => 'required',
        'creado_por_admin' => 'required|in_list[0,1]'
    ];

    protected $validationMessages = [
        'nombre' => ['required' => 'El nombre del videojuego es requerido.'],
        'fecha_lanzamiento' => [
            'date' => 'Fecha inválida.',
            'required' => 'La fecha de lanzamiento es requerida.'
        ],
        'imagen' => ['required' => 'La imagen del videojuego es requerida.'],
        'plataformas_principales' => ['required' => 'Las plataformas principales son requeridas.'],
        'desarrolladoras' => ['required' => 'Las desarrolladoras son requeridas.'],
        'publishers' => ['required' => 'Los publishers son requeridos.'],
        'tiendas' => ['required' => 'Las tiendas son requeridas.'],
        'generos' => ['required' => 'Los géneros son requeridos.'],
        'descripcion' => ['required' => 'La descripción es requerida.'],
        'creado_por_admin' => ['required' => 'El campo "creado por admin" es requerido.']
    ];

    public function __construct()
    {
        parent::__construct();
    }
}
?>