<?php

namespace App\Nomina;

use Illuminate\Database\Eloquent\Model;

use App\Nomina\NomContrato;
use App\Nomina\NomDocRegistro;

use DB;

class NomDocEncabezado extends Model
{
    //protected $table = 'nom_doc_encabezados';

    // tiempo_a_liquidar: cantidad de horas a liquidar en el documento !!! WARNING, puede haber conflicto cuando una empleado tiene una cantidad de horas_laborales al mes diferente a los demás, puede que todas sus horas se liquiden antes de cumplirse el mes. Ejemplo, si tiene en el contrato 120 horas (medio tiempo) y se hacen dos documentos con un tiempo_a_liquidar de 120 horas cada uno, al empleado se le liquidarán 240 horas !!!!

    /* 
        tipo_liquidacion: cada tipo tiene sus propias formas de liquidar y conceptos 
            normal: automática todos los contratos activos. 
            selectiva: se debe seleccionar a los empleados que se liquidarán (ejemplo, vacaciones, terminación contratos).
            terminacion_contrato: Se liquida todo y se dejan tablas de consolidados en cero.

    */
	protected $fillable = ['core_tipo_transaccion_id', 'core_tipo_doc_app_id', 'consecutivo', 'fecha', 'core_empresa_id', 'descripcion','tiempo_a_liquidar', 'total_devengos', 'total_deducciones', 'estado', 'creado_por', 'modificado_por','tipo_liquidacion'];
	
	public $encabezado_tabla = [ 'Fecha', 'Documento', 'Descripción', 'Total devengos', 'Total deducciones', 'Estado', 'Acción'];

    public $urls_acciones = '{"cambiar_estado":"a_i/id_fila"}';

    public function empleados()
    {
        return $this->belongsToMany(NomContrato::class,'nom_empleados_del_documento','nom_doc_encabezado_id','nom_contrato_id');
    }

	public static function consultar_registros()
	{
	    return NomDocEncabezado::leftJoin('core_tipos_docs_apps', 'core_tipos_docs_apps.id', '=', 'nom_doc_encabezados.core_tipo_doc_app_id')
                            ->select(
                                'nom_doc_encabezados.fecha AS campo1',
                                DB::raw( 'CONCAT(core_tipos_docs_apps.prefijo," ",nom_doc_encabezados.consecutivo) AS campo2' ),
                                'nom_doc_encabezados.descripcion AS campo3',
                                'nom_doc_encabezados.total_devengos AS campo4',
                                'nom_doc_encabezados.total_deducciones AS campo5',
                                'nom_doc_encabezados.estado AS campo6',
                                'nom_doc_encabezados.id AS campo7')
                    	    ->get()
                    	    ->toArray();
	}



    public static function opciones_campo_select()
    {
        $opciones = NomDocEncabezado::where('estado','Activo')
                                ->get();

        $vec['']='';
        foreach ($opciones as $opcion)
        {
            $vec[$opcion->id] = $opcion->descripcion;
        }

        return $vec;
    }

    public static function get_un_registro($id)
    {
        return NomDocEncabezado::where('nom_doc_encabezados.id',$id)
                        ->leftJoin('core_tipos_docs_apps', 'core_tipos_docs_apps.id', '=', 'nom_doc_encabezados.core_tipo_doc_app_id')
                        ->select( DB::raw( 'CONCAT(core_tipos_docs_apps.prefijo," ",nom_doc_encabezados.consecutivo) AS documento_app' ),
                            'nom_doc_encabezados.id',
                            'nom_doc_encabezados.core_empresa_id',
                            'nom_doc_encabezados.fecha',
                            'nom_doc_encabezados.descripcion',
                            'nom_doc_encabezados.core_tipo_transaccion_id',
                            'nom_doc_encabezados.core_tipo_doc_app_id',
                            'nom_doc_encabezados.consecutivo',
                            'nom_doc_encabezados.total_devengos',
                            'nom_doc_encabezados.total_deducciones',
                            'nom_doc_encabezados.creado_por')
                        ->get()
                        ->first();
    }


    public function store_adicional( $datos, $registro )
    {
        // Se agregan todos los contratos al documento
        if ( $registro->tipo_liquidacion == 'normal' )
        {
            $empleados = NomContrato::where( 'estado', 'Activo' )->get();
            foreach ($empleados as $contrato)
            {
                DB::table('nom_empleados_del_documento')->insert( [
                                                                    'nom_doc_encabezado_id' => $registro->id,
                                                                    'nom_contrato_id' => $contrato->id
                                                                ]);
            }
        }
            
    }

    public function get_campos_adicionales_edit($lista_campos, $registro)
    {
        $registro = NomDocRegistro::where( 'nom_doc_encabezado_id', $registro->id )->get()->first();

        // Si hay al menos un registro para el documento de nómina, no se puede editar
        if ( !is_null( $registro ) )
        {
            return [ null, 'No se puede editar este documento. Ya tiene registros asociados.'];
        }

        return $lista_campos;
    }
}
