<?php

namespace App\Nomina;

use Illuminate\Database\Eloquent\Model;

use App\Nomina\NomContrato;
use App\Nomina\NomDocRegistro;

use DB;
use Input;

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
	
	public $encabezado_tabla = ['<i style="font-size: 20px;" class="fa fa-check-square-o"></i>', 'Fecha', 'Documento', 'Descripción', 'Total devengos', 'Total deducciones', 'Estado'];

    public $urls_acciones = '{"create":"web/create","edit":"web/id_fila/edit","show":"nomina/id_fila","cambiar_estado":"a_i/id_fila"}';

    public function empresa()
    {
        return $this->belongsTo( 'App\Core\Empresa', 'core_empresa_id' );
    }

    public function empleados()
    {
        return $this->belongsToMany(NomContrato::class,'nom_empleados_del_documento','nom_doc_encabezado_id','nom_contrato_id');
    }

    public function registros_liquidacion()
    {
        return $this->hasMany( NomDocRegistro::class, 'nom_doc_encabezado_id' );
    }

    public function horas_liquidadas_empleado( $core_tercero_id )
    {
        $registros_documento = $this->registros_liquidacion->where( 'core_tercero_id', $core_tercero_id )->all();

        $horas_liquidadas = 0;
        foreach ($registros_documento as $registro )
        {   
            if ( !is_null($registro->concepto) )
            {
                // 7: Tiempo NO Laborado, 1: tiempo laborado
                if ( in_array($registro->concepto->modo_liquidacion_id, [1,7] ) )
                {
                    $horas_liquidadas += $registro->cantidad_horas;
                }
            }                
        }

        return $horas_liquidadas;

    }

    public function get_valor_neto_empleado_concepto( $core_tercero_id, $nom_concepto_id )
    {
        $registros_documento = $this->registros_liquidacion->where( 'core_tercero_id', $core_tercero_id )
                                                            ->where( 'core_tercero_id', $core_tercero_id )
                                                            ->get()
                                                            ->first();

        if ( is_null($registros_documento) )
        {
            return 0;
        }
        
        return ( $registros_documento->valor_devengo - $registros_documento->valor_deduccion );
    }

    public function horas_liquidadas_tiempo_laborado_empleado( $core_tercero_id )
    {
        $registros_documento = $this->registros_liquidacion->where( 'core_tercero_id', $core_tercero_id )->all();

        $horas_liquidadas = 0;
        foreach ($registros_documento as $registro )
        {   
            if ( !is_null($registro->concepto) )
            {
                // 1: tiempo laborado
                if ( in_array($registro->concepto->modo_liquidacion_id, [1] ) )
                {
                    $horas_liquidadas += $registro->cantidad_horas;
                }# code...
            }
        }

        return $horas_liquidadas;
    }

    public function get_valor_neto_empleado_segun_grupo_conceptos( array $conceptos, $core_tercero_id )
    {
        $total_devengos = $this->registros_liquidacion->where( 'core_tercero_id', $core_tercero_id )
                                                    ->whereIn( 'nom_concepto_id', $conceptos )
                                                    ->sum( 'valor_devengo' );

        $total_deducciones = $this->registros_liquidacion->where( 'core_tercero_id', $core_tercero_id )
                                                    ->whereIn( 'nom_concepto_id', $conceptos )
                                                    ->sum( 'valor_deduccion' );

        return ( $total_devengos - $total_deducciones );
    }



    // Se asume liquidaciones quincenales
    public function lapso()
    {
        $array_fecha = explode( '-', $this->fecha );

        $dia_inicio = '01';
        $dia_fin = '15';

        if ( (int)$array_fecha[2] > 16 )
        {
            $dia_inicio = '16';
            $dia_fin = '30';
            // Mes de febrero
            if ( $array_fecha[1] == '02' )
            {
                $dia_fin = '28';
            }
        }

        return (object)[ 
                        'fecha_inicial' => $array_fecha[0] . '-' . $array_fecha[1] . '-' . $dia_inicio,
                        'fecha_final' => $array_fecha[0] . '-' . $array_fecha[1] . '-' . $dia_fin
                    ];
    }

	public static function consultar_registros($nro_registros)
    {
        return NomDocEncabezado::leftJoin('core_tipos_docs_apps', 'core_tipos_docs_apps.id', '=', 'nom_doc_encabezados.core_tipo_doc_app_id')
            ->select(
                'nom_doc_encabezados.fecha AS campo1',
                DB::raw('CONCAT(core_tipos_docs_apps.prefijo," ",nom_doc_encabezados.consecutivo) AS campo2'),
                'nom_doc_encabezados.descripcion AS campo3',
                'nom_doc_encabezados.total_devengos AS campo4',
                'nom_doc_encabezados.total_deducciones AS campo5',
                'nom_doc_encabezados.estado AS campo6',
                'nom_doc_encabezados.id AS campo7'
            )
            ->orderBy('nom_doc_encabezados.created_at', 'DESC')
            ->paginate($nro_registros);
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
                            'nom_doc_encabezados.estado',
                            'nom_doc_encabezados.creado_por')
                        ->get()
                        ->first();
    }


    public function store_adicional( $datos, $registro )
    {
        $fecha_inicial_documento = $registro->lapso()->fecha_inicial;

        // Se agregan todos los contratos al documento
        if ( $registro->tipo_liquidacion == 'normal' )
        {
            $empleados = NomContrato::where( [
                                                [ 'estado', '=', 'Activo'],
                                                [ 'contrato_hasta', '>=', $fecha_inicial_documento ]
                                            ] )
                                    ->get();

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



    /*
        FUNCIONES relativas al modelo relacionado a este modelo, en este caso Tipo de Documento
    */
    public static function get_tabla($registro_modelo_padre,$registros_asignados)
    {
        $tabla = '<div class="table-responsive">
                <table class="table table-bordered table-striped" id="myTable">
                    <thead>';
                        $encabezado_tabla = ['Orden','Cédula','Empleado','Acción'];
                        for($i=0;$i<count($encabezado_tabla);$i++){
                            $tabla.='<th>'.$encabezado_tabla[$i].'</th>';
                        }
                $tabla.='</thead>
                    <tbody>';
//                    dd($registros_asignados);
                        foreach($registros_asignados as $fila)
                        {
                            $orden = DB::table('nom_empleados_del_documento')
                                        ->where('nom_doc_encabezado_id', '=', $registro_modelo_padre->id)
                                        ->where('nom_contrato_id', '=', $fila['id'])
                                        ->value('orden');

                            $empleado = NomContrato::where('core_tercero_id',$fila['core_tercero_id'])->get()->first();
                            $tabla.='<tr>';
                                $tabla.='<td>'.$orden.'</td>';
                                $tabla.='<td>'. $empleado->tercero->numero_identificacion .'</td>';
                                $tabla.='<td>'. $empleado->tercero->descripcion .'</td>';
                                $tabla.='<td>
                                        <a class="btn btn-danger btn-sm" href="'.url('nom_eliminar_asignacion/registro_modelo_hijo_id/'.$fila['id'].'/registro_modelo_padre_id/'.$registro_modelo_padre->id.'/id_app/'.Input::get('id').'/id_modelo_padre/'.Input::get('id_modelo')).'"><i class="fa fa-btn fa-trash"></i> </a>
                                        </td>
                            </tr>';
                        }
                    $tabla.='</tbody>
                </table>
            </div>';
        return $tabla;
    }

    public static function get_opciones_modelo_relacionado($nom_doc_encabezado_id)
    {
        $vec['']='';
        $opciones = NomContrato::where('estado','Activo')->get();
        foreach ($opciones as $opcion){
            $esta = DB::table('nom_empleados_del_documento')->where('nom_doc_encabezado_id',$nom_doc_encabezado_id)->where('nom_contrato_id',$opcion->id)->get();
            if ( empty($esta) )
            {
                $vec[$opcion->id] = $opcion->tercero->numero_identificacion.' '.$opcion->tercero->descripcion;
            }
        }

        return $vec;
    }

    public static function get_datos_asignacion()
    {
        $nombre_tabla = 'nom_empleados_del_documento';
        $nombre_columna1 = 'orden';
        $registro_modelo_padre_id = 'nom_doc_encabezado_id';
        $registro_modelo_hijo_id = 'nom_contrato_id';

        return compact('nombre_tabla','nombre_columna1','registro_modelo_padre_id','registro_modelo_hijo_id');
    }
}