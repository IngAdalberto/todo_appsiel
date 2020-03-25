<?php

namespace App\Contratotransporte;

use Illuminate\Database\Eloquent\Model;

class Documentosvehiculo extends Model
{
    protected $table = 'cte_documentosvehiculos';
    protected $fillable = ['id', 'vehiculo_id', 'tarjeta_operacion', 'documento', 'recurso', 'nro_documento', 'vigencia_inicio', 'vigencia_fin', 'created_at', 'updated_at'];
}
