<?php

namespace App\Calificaciones;

use Illuminate\Database\Eloquent\Model;

use App\Matriculas\PeriodoLectivo;

use Auth;

class EscalaValoracion extends Model
{
    protected $table = 'sga_escala_valoracion';

    protected $fillable = ['periodo_lectivo_id','calificacion_minima','calificacion_maxima','nombre_escala','sigla','escala_nacional','imagen'];

    public $encabezado_tabla = ['Año lectivo','Escala nacional','Nombre escala','Sigla','Calificación mínima','Calificación máxima','Acción'];

    public static function consultar_registros()
    {
    	$registros = EscalaValoracion::leftJoin('sga_periodos_lectivos','sga_periodos_lectivos.id','=','sga_escala_valoracion.periodo_lectivo_id')
                            ->select('sga_periodos_lectivos.descripcion AS campo1',
                                'sga_escala_valoracion.escala_nacional AS campo2',
                                'sga_escala_valoracion.nombre_escala AS campo3',
                                'sga_escala_valoracion.sigla AS campo4',
                                'sga_escala_valoracion.calificacion_minima AS campo5',
                                'sga_escala_valoracion.calificacion_maxima AS campo6',
                                'sga_escala_valoracion.id AS campo7')
                            ->get()
                            ->toArray();

        return $registros;
    }

    public static function opciones_campo_select()
    {
        $opciones = EscalaValoracion::get_escalas_periodo_lectivo_abierto();

        $vec['']='';
        foreach ($opciones as $opcion)
        {
            $vec[$opcion->id] = $opcion->nombre_escala.' ('.$opcion->calificacion_minima.'-'.$opcion->calificacion_maxima.')';
        }
        
        return $vec;
    }

    public static function get_escalas_periodo_lectivo_abierto( $periodo_lectivo_id = null )
    {
        $array_wheres = [ [ 'sga_periodos_lectivos.cerrado', 0 ] ];

        if ( !is_null( $periodo_lectivo_id ) ) 
        {
            $array_wheres = array_merge($array_wheres, [ ['sga_escala_valoracion.periodo_lectivo_id', $periodo_lectivo_id] ]);          
        }
        
        return EscalaValoracion::leftJoin('sga_periodos_lectivos','sga_periodos_lectivos.id','=','sga_escala_valoracion.periodo_lectivo_id')
                                    ->where( $array_wheres )
                                    ->select('sga_escala_valoracion.id','sga_escala_valoracion.nombre_escala','sga_escala_valoracion.calificacion_minima','sga_escala_valoracion.calificacion_maxima')
                                    ->orderBy('sga_escala_valoracion.calificacion_minima','DESC')
                                    ->get();
    }

    // Obtener los valores mínimo y máximo de toda la escala
    public static function get_min_max( $periodo_lectivo_id = null )
    {
        if ( $periodo_lectivo_id == null)
        {
            $periodo_lectivo_id = PeriodoLectivo::get_actual()->id;
        }
        
        $escala_valoracion = EscalaValoracion::where('periodo_lectivo_id', $periodo_lectivo_id )
                                            ->orderBy('calificacion_minima','ASC')
                                            ->get();
                                            
        if ( empty( $escala_valoracion->toArray() ) )
        {
            return [ 0, 0];
        }

        return [ $escala_valoracion->first()->calificacion_minima, $escala_valoracion->last()->calificacion_maxima];
    }

    public static function get_escala_segun_calificacion( $calificacion, $periodo_lectivo_id = null )
    {
        if ( is_null( $periodo_lectivo_id ) )
        {
            $periodo_lectivo_id = PeriodoLectivo::get_actual()->id;
        }

        return EscalaValoracion::where('calificacion_minima','<=',$calificacion)
                                        ->where('calificacion_maxima','>=',$calificacion)
                                        ->where('periodo_lectivo_id','=',$periodo_lectivo_id)
                                        ->get()
                                        ->first();
    }
}
