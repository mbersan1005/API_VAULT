<?php

namespace App\Models;

use CodeIgniter\Model;

class AdministradoresModelo extends Model
{
    protected $table = 'vault.administradores';
    
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'nombre',
        'password',
        'fecha_creacion',
        'fecha_ultimo_login'
    ];

    protected $validationRules = [
        'nombre' => 'required|min_length[3]|max_length[50]',
        'password' => 'required|min_length[8]|max_length[255]',
        'fecha_creacion' => 'required|valid_date',
        'fecha_ultimo_login' => 'required|valid_date'
    ];

    protected $validationMessages = [
        'nombre' => [
            'required' => 'El nombre es requerido.',
            'min_length' => 'El nombre debe tener al menos 3 caracteres.',
            'max_length' => 'El nombre no debe superar los 50 caracteres.'
        ],
        'password' => [
            'required' => 'La contraseña es requerida.',
            'min_length' => 'La contraseña debe tener al menos 8 caracteres.',
            'max_length' => 'La contraseña no debe superar los 255 caracteres.'
        ],
        'fecha_creacion' => [
            'required' => 'La fecha de creación es requerida.',
            'valid_date' => 'La fecha de creación debe ser una fecha válida.'
        ],
        'fecha_ultimo_login' => [
            'required' => 'La fecha de último login es requerida.',
            'valid_date' => 'La fecha de último login debe ser una fecha válida.'
        ]
    ];

    public function __construct()
    {
        parent::__construct();
    }
}

?>
