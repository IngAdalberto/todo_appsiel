<?php

namespace App\Nomina;

use Illuminate\Database\Eloquent\Model;

use DB;
use Carbon\Carbon;

class ParametroLiquidacionPrestacionesSociales extends Model
{
    protected $table = 'nom_parametros_liquidacion_prestaciones_sociales';

    /*
        concepto_prestacion = { vacaciones | prima_legal | cesantias | intereses_cesantias }
        base_liquidacion= { 
                            sueldo: solo el sueldo del contrato 
                            sueldo_mas_promedio_agrupacion: sueldo del contrato + promedios de la agrupación
                            promedio_agrupacion: solo promedios de la agrupación (se debe incluir el sueldo en la agrupación para que lo tenga en cuenta)
                        }
    */
    protected $fillable = ['concepto_prestacion', 'grupo_empleado_id', 'nom_agrupacion_id', 'nom_concepto_id', 'nom_agrupacion2_id', 'base_liquidacion', 'cantidad_meses_a_promediar', 'dias_a_liquidar', 'sabado_es_dia_habil'];

    public $encabezado_tabla = ['<i style="font-size: 20px;" class="fa fa-check-square-o"></i>', 'Prestación', 'Grupo empleados', 'Agrupación de conceptos', 'Concepto', 'Base liquidación', 'Cantidad meses a promediar', 'Días a liquidar'];

    public $urls_acciones = '{"create":"web/create","edit":"web/id_fila/edit","eliminar":"web_eliminar/id_fila"}';

    public static function consultar_registros($nro_registros, $search)
    {
        return ParametroLiquidacionPrestacionesSociales::leftJoin('nom_grupos_empleados', 'nom_grupos_empleados.id', '=', 'nom_parametros_liquidacion_prestaciones_sociales.grupo_empleado_id')
            ->leftJoin('nom_agrupaciones_conceptos', 'nom_agrupaciones_conceptos.id', '=', 'nom_parametros_liquidacion_prestaciones_sociales.nom_agrupacion_id')
            ->leftJoin('nom_conceptos', 'nom_conceptos.id', '=', 'nom_parametros_liquidacion_prestaciones_sociales.nom_concepto_id')
            ->select(
                'nom_parametros_liquidacion_prestaciones_sociales.concepto_prestacion AS campo1',
                'nom_grupos_empleados.descripcion AS campo2',
                'nom_agrupaciones_conceptos.descripcion AS campo3',
                DB::raw('CONCAT(nom_conceptos.id," - ",nom_conceptos.descripcion) AS campo4'),
                'nom_parametros_liquidacion_prestaciones_sociales.base_liquidacion AS campo5',
                'nom_parametros_liquidacion_prestaciones_sociales.cantidad_meses_a_promediar AS campo6',
                'nom_parametros_liquidacion_prestaciones_sociales.dias_a_liquidar AS campo7',
                'nom_parametros_liquidacion_prestaciones_sociales.id AS campo8'
            )
            ->where("nom_parametros_liquidacion_prestaciones_sociales.concepto_prestacion", "LIKE", "%$search%")
            ->orWhere("nom_grupos_empleados.descripcion", "LIKE", "%$search%")
            ->orWhere("nom_agrupaciones_conceptos.descripcion", "LIKE", "%$search%")
            ->orWhere(DB::raw('CONCAT(nom_conceptos.id," - ",nom_conceptos.descripcion)'), "LIKE", "%$search%")
            ->orWhere("nom_parametros_liquidacion_prestaciones_sociales.base_liquidacion", "LIKE", "%$search%")
            ->orWhere("nom_parametros_liquidacion_prestaciones_sociales.cantidad_meses_a_promediar", "LIKE", "%$search%")
            ->orWhere("nom_parametros_liquidacion_prestaciones_sociales.dias_a_liquidar", "LIKE", "%$search%")
            ->orderBy('nom_parametros_liquidacion_prestaciones_sociales.created_at', 'DESC')
            ->paginate($nro_registros);
    }
    public static function sqlString($search)
    {
        $string = ParametroLiquidacionPrestacionesSociales::leftJoin('nom_grupos_empleados', 'nom_grupos_empleados.id', '=', 'nom_parametros_liquidacion_prestaciones_sociales.grupo_empleado_id')
            ->leftJoin('nom_agrupaciones_conceptos', 'nom_agrupaciones_conceptos.id', '=', 'nom_parametros_liquidacion_prestaciones_sociales.nom_agrupacion_id')
            ->leftJoin('nom_conceptos', 'nom_conceptos.id', '=', 'nom_parametros_liquidacion_prestaciones_sociales.nom_concepto_id')
            ->select(
                'nom_parametros_liquidacion_prestaciones_sociales.concepto_prestacion AS PRESTACIÓN',
                'nom_grupos_empleados.descripcion AS GRUPO_EMPLEADOS',
                'nom_agrupaciones_conceptos.descripcion AS AGRUPACIÓN_DE_CONCEPTOS',
                DB::raw('CONCAT(nom_conceptos.id," - ",nom_conceptos.descripcion) AS CONCEPTO'),
                'nom_parametros_liquidacion_prestaciones_sociales.base_liquidacion AS BASE_LIQUIDACIÓN',
                'nom_parametros_liquidacion_prestaciones_sociales.cantidad_meses_a_promediar AS CANTIDAD_MESES_A_PROMEDIAR',
                'nom_parametros_liquidacion_prestaciones_sociales.dias_a_liquidar AS DÍAS_A_LIQUIDAR'
            )
            ->where("nom_parametros_liquidacion_prestaciones_sociales.concepto_prestacion", "LIKE", "%$search%")
            ->orWhere("nom_grupos_empleados.descripcion", "LIKE", "%$search%")
            ->orWhere("nom_agrupaciones_conceptos.descripcion", "LIKE", "%$search%")
            ->orWhere(DB::raw('CONCAT(nom_conceptos.id," - ",nom_conceptos.descripcion)'), "LIKE", "%$search%")
            ->orWhere("nom_parametros_liquidacion_prestaciones_sociales.base_liquidacion", "LIKE", "%$search%")
            ->orWhere("nom_parametros_liquidacion_prestaciones_sociales.cantidad_meses_a_promediar", "LIKE", "%$search%")
            ->orWhere("nom_parametros_liquidacion_prestaciones_sociales.dias_a_liquidar", "LIKE", "%$search%")
            ->orderBy('nom_parametros_liquidacion_prestaciones_sociales.created_at', 'DESC')
            ->toSql();
        return str_replace('?', '"%' . $search . '%"', $string);
    }

    //Titulo para la exportación en PDF y EXCEL
    public static function tituloExport()
    {
        return "LISTADO DE LIQUIDACION PRESTACIONES SOCIALES";
    }

    public static function opciones_campo_select()
    {
        $opciones = ParametroLiquidacionPrestacionesSociales::where('nom_parametros_liquidacion_prestaciones_sociales.estado', 'Activo')
            ->select('nom_parametros_liquidacion_prestaciones_sociales.id', 'nom_parametros_liquidacion_prestaciones_sociales.descripcion')
            ->get();

        $vec[''] = '';
        foreach ($opciones as $opcion) {
            $vec[$opcion->id] = $opcion->descripcion;
        }

        return $vec;
    }

    public function get_fecha_inicial_promedios( $fecha_final, $empleado )
    {
        $vec_fecha_documento = explode("-", $fecha_final);
        
        $anio_final = (int)$vec_fecha_documento[0];
        $mes_final = (int)$vec_fecha_documento[1];
        $dia_final = $vec_fecha_documento[2];

        $anio_inicial = $anio_final;
        $mes_inicial = 0;
        $dia_inicial = $this->formatear_numero_a_texto_dos_digitos( $dia_final + 1 );
        if ( $dia_final >= 30 )
        {
            $dia_inicial = '01';
        }

        if ( ($mes_final == 2) && ($dia_final >= 28) ) // Febrero
        {
            $dia_inicial = '01';
        }

        $mes_anterior = $mes_final;// + 1;
        for ( $i = $this->cantidad_meses_a_promediar; $i > 0; $i--)
        {
            $mes_iteracion = $mes_anterior - 1;
            if ( $mes_iteracion <= 0 )
            {
                $mes_inicial = 12 + $mes_iteracion;
                $anio_inicial = $anio_final - 1;
            }else{
                $mes_inicial = $mes_iteracion;
            }
            $mes_anterior = $mes_iteracion;
        }

        $mes_inicial = $this->formatear_numero_a_texto_dos_digitos( $mes_inicial );

        $fecha_inicial = $anio_inicial . '-' . $mes_inicial . '-' . $dia_inicial;

        $diferencia = $this->diferencia_en_dias_entre_fechas( $fecha_inicial, $empleado->fecha_ingreso );
        
        // Si la fecha_inicial es menor que la fecha_ingreso del empleado, la fecha inicial debe ser la del contrato
        if ( $diferencia > 0 )
        {
            return $empleado->fecha_ingreso;
        }

        // Si la diferencia es negativa, quiere decir que la fecha_final es superior a la fecha_ingreso
        return $fecha_inicial;
    }

    public function formatear_numero_a_texto_dos_digitos( $numero )
    {
        if ( strlen($numero) == 1 )
        {
            return "0" . $numero;
        }

        return $numero;
    }

    public function diferencia_en_dias_entre_fechas( string $fecha_inicial, string $fecha_final )
    {
        $fecha_ini = Carbon::createFromFormat('Y-m-d', $fecha_inicial);
        $fecha_fin = Carbon::createFromFormat('Y-m-d', $fecha_final );

        return $fecha_ini->diffInDays( $fecha_fin, false); // false activa el calculo de diferencias negativas
    }


    public function validar_eliminacion($id)
    {
        $tablas_relacionadas = '{}';
        $tablas = json_decode($tablas_relacionadas);
        foreach ($tablas as $una_tabla) {
            $registro = DB::table($una_tabla->tabla)->where($una_tabla->llave_foranea, $id)->get();

            if (!empty($registro)) {
                return $una_tabla->mensaje;
            }
        }

        return 'ok';
    }
}
