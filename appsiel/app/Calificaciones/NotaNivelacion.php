<?php

namespace App\Calificaciones;

use Illuminate\Database\Eloquent\Model;

use App\Calificaciones\EscalaValoracion;
use App\Calificaciones\Periodo;
use App\Calificaciones\Calificacion;

class NotaNivelacion extends Model
{
	protected $table = 'sga_notas_nivelaciones';

	protected $fillable = ['colegio_id', 'matricula_id', 'periodo_id', 'curso_id', 'asignatura_id', 'estudiante_id', 'calificacion', 'observacion', 'creado_por', 'modificado_por'];

	public $encabezado_tabla = ['<i style="font-size: 20px;" class="fa fa-check-square-o"></i>', 'Estudiante', 'Año lectivo', 'Curso', 'Periodo', 'Asignatura', 'Calificación de nivelación', 'Observaciones', 'Creada por'];

	public $urls_acciones = '{"show":"no"}';

	public function periodo()
	{
		return $this->belongsTo('App\Calificaciones\Periodo', 'periodo_id');
	}

	public function curso()
	{
		return $this->belongsTo('App\Matriculas\Curso', 'curso_id');
	}

	public function asignatura()
	{
		return $this->belongsTo('App\Calificaciones\Asignatura', 'asignatura_id');
	}

	public function estudiante()
	{
		return $this->belongsTo('App\Matriculas\Estudiante', 'estudiante_id');
	}

	public function escala_valoracion()
	{
		return EscalaValoracion::where('calificacion_minima', '<=', $this->calificacion)
			->where('calificacion_maxima', '>=', $this->calificacion)
			->where('periodo_lectivo_id', '=', $this->periodo->periodo_lectivo_id)
			->get()
			->first();
	}

	public static function consultar_registros($nro_registros, $search)
	{
		return NotaNivelacion::leftJoin('sga_estudiantes', 'sga_estudiantes.id', '=', 'sga_notas_nivelaciones.estudiante_id')
			->leftJoin('core_terceros', 'core_terceros.id', '=', 'sga_estudiantes.core_tercero_id')
			->leftJoin('sga_cursos', 'sga_cursos.id', '=', 'sga_notas_nivelaciones.curso_id')
			->leftJoin('sga_periodos', 'sga_periodos.id', '=', 'sga_notas_nivelaciones.periodo_id')
			->leftJoin('sga_periodos_lectivos', 'sga_periodos_lectivos.id', '=', 'sga_periodos.periodo_lectivo_id')
			->leftJoin('sga_asignaturas', 'sga_asignaturas.id', '=', 'sga_notas_nivelaciones.asignatura_id')
			->select(
				'core_terceros.descripcion AS campo1',
				'sga_periodos_lectivos.descripcion AS campo2',
				'sga_cursos.descripcion AS campo3',
				'sga_periodos.descripcion AS campo4',
				'sga_asignaturas.descripcion AS campo5',
				'sga_notas_nivelaciones.calificacion AS campo6',
				'sga_notas_nivelaciones.observacion AS campo7',
				'sga_notas_nivelaciones.creado_por AS campo8',
				'sga_notas_nivelaciones.id AS campo9'
			)
			->where("core_terceros.descripcion", "LIKE", "%$search%")
			->orWhere("sga_periodos_lectivos.descripcion", "LIKE", "%$search%")
			->orWhere("sga_cursos.descripcion", "LIKE", "%$search%")
			->orWhere("sga_periodos.descripcion", "LIKE", "%$search%")
			->orWhere("sga_asignaturas.descripcion", "LIKE", "%$search%")
			->orWhere("sga_notas_nivelaciones.calificacion", "LIKE", "%$search%")
			->orWhere("sga_notas_nivelaciones.observacion", "LIKE", "%$search%")
			->orWhere("sga_notas_nivelaciones.creado_por", "LIKE", "%$search%")
			->orderBy('sga_notas_nivelaciones.created_at', 'DESC')
			->paginate($nro_registros);
	}

	public static function sqlString($search)
	{
		$string = NotaNivelacion::leftJoin('sga_estudiantes', 'sga_estudiantes.id', '=', 'sga_notas_nivelaciones.estudiante_id')
			->leftJoin('core_terceros', 'core_terceros.id', '=', 'sga_estudiantes.core_tercero_id')
			->leftJoin('sga_cursos', 'sga_cursos.id', '=', 'sga_notas_nivelaciones.curso_id')
			->leftJoin('sga_periodos', 'sga_periodos.id', '=', 'sga_notas_nivelaciones.periodo_id')
			->leftJoin('sga_periodos_lectivos', 'sga_periodos_lectivos.id', '=', 'sga_periodos.periodo_lectivo_id')
			->leftJoin('sga_asignaturas', 'sga_asignaturas.id', '=', 'sga_notas_nivelaciones.asignatura_id')
			->select(
				'core_terceros.descripcion AS ESTUDIANTE',
				'sga_periodos_lectivos.descripcion AS AÑO',
				'sga_cursos.descripcion AS CURSO',
				'sga_periodos.descripcion AS PERÍODO',
				'sga_asignaturas.descripcion AS ASIGNATURA',
				'sga_notas_nivelaciones.calificacion AS CALIFICACIÓN',
				'sga_notas_nivelaciones.observacion AS OBSERVACIÓN',
				'sga_notas_nivelaciones.creado_por AS CREADO_POR'
			)
			->where("core_terceros.descripcion", "LIKE", "%$search%")
			->orWhere("sga_periodos_lectivos.descripcion", "LIKE", "%$search%")
			->orWhere("sga_cursos.descripcion", "LIKE", "%$search%")
			->orWhere("sga_periodos.descripcion", "LIKE", "%$search%")
			->orWhere("sga_asignaturas.descripcion", "LIKE", "%$search%")
			->orWhere("sga_notas_nivelaciones.calificacion", "LIKE", "%$search%")
			->orWhere("sga_notas_nivelaciones.observacion", "LIKE", "%$search%")
			->orWhere("sga_notas_nivelaciones.creado_por", "LIKE", "%$search%")
			->orderBy('sga_notas_nivelaciones.created_at', 'DESC')
			->toSql();
		return str_replace('?', '"%' . $search . '%"', $string);
	}

	//Titulo para la exportación en PDF y EXCEL
	public static function tituloExport()
	{
		return "LISTADO DE NOTAS DE NIVELACIÓN";
	}

	public static function get_registros($curso_id, $asignatura_id, $nro_registros, $search)
	{
		$array_wheres = [];

		if ($curso_id != null) {
			$array_wheres = array_merge($array_wheres, ['sga_notas_nivelaciones.curso_id' => $curso_id]);
		}

		if ($asignatura_id != null) {
			$array_wheres = array_merge($array_wheres, ['sga_notas_nivelaciones.asignatura_id' => $asignatura_id]);
		}

		return NotaNivelacion::where($array_wheres)
			->leftJoin('sga_estudiantes', 'sga_estudiantes.id', '=', 'sga_notas_nivelaciones.estudiante_id')
			->leftJoin('core_terceros', 'core_terceros.id', '=', 'sga_estudiantes.core_tercero_id')
			->leftJoin('sga_cursos', 'sga_cursos.id', '=', 'sga_notas_nivelaciones.curso_id')
			->leftJoin('sga_periodos', 'sga_periodos.id', '=', 'sga_notas_nivelaciones.periodo_id')
			->leftJoin('sga_periodos_lectivos', 'sga_periodos_lectivos.id', '=', 'sga_periodos.periodo_lectivo_id')
			->leftJoin('sga_asignaturas', 'sga_asignaturas.id', '=', 'sga_notas_nivelaciones.asignatura_id')
			->select(
				'core_terceros.descripcion AS campo1',
				'sga_periodos_lectivos.descripcion AS campo2',
				'sga_cursos.descripcion AS campo3',
				'sga_periodos.descripcion AS campo4',
				'sga_asignaturas.descripcion AS campo5',
				'sga_notas_nivelaciones.calificacion AS campo6',
				'sga_notas_nivelaciones.observacion AS campo7',
				'sga_notas_nivelaciones.id AS campo8'
			)
			->orWhere("core_terceros.descripcion", "LIKE", "%$search%")
			->orWhere("sga_periodos_lectivos.descripcion", "LIKE", "%$search%")
			->orWhere("sga_cursos.descripcion", "LIKE", "%$search%")
			->orWhere("sga_periodos.descripcion", "LIKE", "%$search%")
			->orWhere("sga_asignaturas.descripcion", "LIKE", "%$search%")
			->orWhere("sga_notas_nivelaciones.calificacion", "LIKE", "%$search%")
			->orWhere("sga_notas_nivelaciones.observacion", "LIKE", "%$search%")
			->orderBy('sga_notas_nivelaciones.created_at', 'DESC')
			->paginate($nro_registros);
	}

	public static function sqlString2($curso_id, $asignatura_id, $search)
	{
		$array_wheres = [];

		if ($curso_id != null) {
			$array_wheres = array_merge($array_wheres, ['sga_notas_nivelaciones.curso_id' => $curso_id]);
		}

		if ($asignatura_id != null) {
			$array_wheres = array_merge($array_wheres, ['sga_notas_nivelaciones.asignatura_id' => $asignatura_id]);
		}

		$string = NotaNivelacion::where($array_wheres)
			->leftJoin('sga_estudiantes', 'sga_estudiantes.id', '=', 'sga_notas_nivelaciones.estudiante_id')
			->leftJoin('core_terceros', 'core_terceros.id', '=', 'sga_estudiantes.core_tercero_id')
			->leftJoin('sga_cursos', 'sga_cursos.id', '=', 'sga_notas_nivelaciones.curso_id')
			->leftJoin('sga_periodos', 'sga_periodos.id', '=', 'sga_notas_nivelaciones.periodo_id')
			->leftJoin('sga_periodos_lectivos', 'sga_periodos_lectivos.id', '=', 'sga_periodos.periodo_lectivo_id')
			->leftJoin('sga_asignaturas', 'sga_asignaturas.id', '=', 'sga_notas_nivelaciones.asignatura_id')
			->select(
				'core_terceros.descripcion AS ESTUDIANTE',
				'sga_periodos_lectivos.descripcion AS AÑO_LECTIVO',
				'sga_cursos.descripcion AS CURSO',
				'sga_periodos.descripcion AS PERIODO',
				'sga_asignaturas.descripcion AS ASIGNATURA',
				'sga_notas_nivelaciones.calificacion AS NOTA_NIVELACIÓN',
				'sga_notas_nivelaciones.observacion AS OBSERVACIONES'
			)
			->orWhere("core_terceros.descripcion", "LIKE", "%$search%")
			->orWhere("sga_periodos_lectivos.descripcion", "LIKE", "%$search%")
			->orWhere("sga_cursos.descripcion", "LIKE", "%$search%")
			->orWhere("sga_periodos.descripcion", "LIKE", "%$search%")
			->orWhere("sga_asignaturas.descripcion", "LIKE", "%$search%")
			->orWhere("sga_notas_nivelaciones.calificacion", "LIKE", "%$search%")
			->orWhere("sga_notas_nivelaciones.observacion", "LIKE", "%$search%")
			->orderBy('sga_notas_nivelaciones.created_at', 'DESC')
			->toSql();
		return str_replace('?', '"%' . $search . '%"', $string);
	}

    public static function get_la_calificacion($periodo_id, $curso_id, $estudiante_id, $asignatura_id)
    {
        $periodo = Periodo::find($periodo_id);

        $calificacion = NotaNivelacion::where([
									            'periodo_id' => $periodo_id,
									            'curso_id' => $curso_id,
									            'estudiante_id' => $estudiante_id,
									            'asignatura_id' => $asignatura_id
									        ])
								            ->get()
								            ->first();

        $la_calificacion = (object)[
                                        'valor' => 0,
                                        'escala_id' => 0,
                                        'escala_descripcion' => '-',
                                        'escala_abreviatura' => '-',
                                        'escala_nacional' => '-',
                                        'logros' => ''
                                    ];

        if (!is_null($calificacion)) {
            $escala = EscalaValoracion::get_escala_segun_calificacion($calificacion->calificacion, $periodo->periodo_lectivo_id);

            if (!is_null($escala)) {
                $la_calificacion = (object)[
                                            'valor' => $calificacion->calificacion,
                                            'escala_id' => $escala->id,
                                            'escala_descripcion' => $escala->nombre_escala,
                                            'escala_abreviatura' => $escala->sigla,
                                            'escala_nacional' => $escala->nombre_escala,
                                            'logros' => $calificacion->logros
                                        ];
            } else {
                $la_calificacion = (object)[
                                            'valor' => $calificacion->calificacion,
                                            'escala_id' => 0,
                                            'escala_descripcion' => '-',
                                            'escala_abreviatura' => '-',
                                            'escala_nacional' => '-',
                                            'logros' => ''
                                        ];
            }
        }

        return $la_calificacion;
    }
}
