<?php

namespace App\Calificaciones;

use Illuminate\Database\Eloquent\Model;

use DB;
use Auth;

class ObservacionesBoletin extends Model
{
    protected $table = 'sga_observaciones_boletines';

    protected $fillable = ['codigo_matricula','id_colegio','id_periodo','curso_id','id_estudiante','observacion','puesto'];

    public static function consultar_registros()
    {
        return ObservacionesBoletin::leftJoin('sga_estudiantes', 'sga_estudiantes.id', '=', 'sga_observaciones_boletines.id_estudiante')
                        ->leftJoin('core_terceros', 'core_terceros.id', '=', 'sga_estudiantes.core_tercero_id')
                        ->leftJoin('sga_cursos', 'sga_cursos.id', '=', 'sga_observaciones_boletines.curso_id')
                        ->leftJoin('sga_periodos', 'sga_periodos.id', '=', 'sga_observaciones_boletines.id_periodo')
                        ->leftJoin('sga_periodos_lectivos', 'sga_periodos_lectivos.id', '=', 'sga_periodos.periodo_lectivo_id')
                        ->select('sga_periodos_lectivos.descripcion AS campo1',
                                'sga_periodos.descripcion AS campo2',
                                'sga_cursos.descripcion AS campo3',
                                DB::raw( 'CONCAT(core_terceros.apellido1," ",core_terceros.apellido2," ",core_terceros.nombre1," ",core_terceros.otros_nombres) AS campo4' ),
                                'sga_observaciones_boletines.puesto AS campo5',
                                'sga_observaciones_boletines.observacion AS campo6',
                                'sga_observaciones_boletines.id AS campo7')
                        ->get()
                        ->toArray();
    }

    public static function consultar_registros_director_grupo( )
    {
        return ObservacionesBoletin::leftJoin('sga_estudiantes', 'sga_estudiantes.id', '=', 'sga_observaciones_boletines.id_estudiante')
                        ->leftJoin('core_terceros', 'core_terceros.id', '=', 'sga_estudiantes.core_tercero_id')
                        ->leftJoin('sga_cursos', 'sga_cursos.id', '=', 'sga_observaciones_boletines.curso_id')
                        ->leftJoin('sga_periodos', 'sga_periodos.id', '=', 'sga_observaciones_boletines.id_periodo')
                        ->leftJoin('sga_periodos_lectivos', 'sga_periodos_lectivos.id', '=', 'sga_periodos.periodo_lectivo_id')
                        ->leftJoin('sga_curso_tiene_director_grupo', 'sga_curso_tiene_director_grupo.curso_id', '=', 'sga_cursos.id')
                        ->where('sga_curso_tiene_director_grupo.user_id', Auth::user()->id )
                        ->select('sga_periodos_lectivos.descripcion AS campo1',
                                'sga_periodos.descripcion AS campo2',
                                'sga_cursos.descripcion AS campo3',
                                DB::raw( 'CONCAT(core_terceros.apellido1," ",core_terceros.apellido2," ",core_terceros.nombre1," ",core_terceros.otros_nombres) AS campo4' ),
                                'sga_observaciones_boletines.puesto AS campo5',
                                'sga_observaciones_boletines.observacion AS campo6',
                                'sga_observaciones_boletines.id AS campo7')
                        ->get()
                        ->toArray();
    }

    public static function get_cantidad_x_matricula( $colegio_id, $codigo_matricula)
    {
        return ObservacionesBoletin::where(
                                    [ 
                                        'id_colegio' => $colegio_id,
                                        'codigo_matricula' => $codigo_matricula
                                    ]
                                )
                            ->count();
    }

    public static function get_observaciones_boletines( $colegio_id, $periodo_id, $curso_id)
    {
        return ObservacionesBoletin::where(
                                            [
                                                'id_colegio' => $colegio_id,
                                                'id_periodo' => $periodo_id,
                                                'curso_id' => $curso_id
                                            ]
                                        )
                                    ->get();
    }

    public static function get_x_estudiante( $periodo_id, $curso_id, $estudiante_id)
    {
        return ObservacionesBoletin::where(
                                            [
                                                'id_periodo' => $periodo_id,
                                                'curso_id' => $curso_id,
                                                'id_estudiante' => $estudiante_id
                                            ]
                                        )
                                    ->get()
                                    ->first();
    }
}
