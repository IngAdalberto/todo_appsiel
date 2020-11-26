<?php

namespace App\Nomina;

use Illuminate\Database\Eloquent\Model;

class NomEntidad extends Model
{
    protected $table = 'nom_entidades';
	protected $fillable = ['core_tercero_id', 'descripcion', 'codigo_nacional', 'tipo_entidad', 'estado'];
	public $encabezado_tabla = [ 'NIT', 'Razón Social', 'Nombre Entidad', 'Código nacional', 'Tipo Entidad', 'Estado', 'Acción'];
	public static function consultar_registros()
	{
        return NomEntidad::leftJoin('core_terceros','core_terceros.id','=','nom_entidades.core_tercero_id')
                                ->select(
                                            'core_terceros.numero_identificacion AS campo1',
                                            'core_terceros.descripcion AS campo2',
                                            'nom_entidades.descripcion AS campo3', 
                                            'nom_entidades.codigo_nacional AS campo4', 
                                            'nom_entidades.tipo_entidad AS campo5', 
                                            'nom_entidades.estado AS campo6', 
                                            'nom_entidades.id AS campo7')
                        	    ->get()
                        	    ->toArray();
	}

    public static function opciones_campo_select()
    {
        $opciones = NomEntidad::where('estado','Activo')->get();

        $vec['']='';
        foreach ($opciones as $opcion)
        {
            $vec[$opcion->id] = $opcion->descripcion;
        }

        return $vec;
    }
}
