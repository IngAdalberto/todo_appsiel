<?php

namespace App\Http\Controllers\Calificaciones;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use Auth;
use DB;
use Input;
use View;

use App\Matriculas\Curso;
use App\Matriculas\Matricula;
use App\Matriculas\PeriodoLectivo;
use App\Matriculas\Estudiante;

use App\Calificaciones\Asignatura;
use App\Calificaciones\AsistenciaClase;

use App\Core\Colegio;

class AsistenciaClaseController extends Controller
{ 
    
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {   
        //
                
    }

    /**
     * 
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $colegio = Colegio::where('empresa_id',Auth::user()->empresa_id)->get()->first();
        
        $registros = Curso::where('estado','Activo')->where('id_colegio',$colegio->id)->get();
        
        $vec['']= '';
        foreach ($registros as $opcion){
            $vec[$opcion->id]=$opcion->descripcion;
        }

        $registros = $vec;

        $miga_pan = [
                        ['url'=>'web?id='.Input::get('id').'&id_modelo='.Input::get('id_modelo'),'etiqueta'=>'Asistencia clases'],
                        ['url'=>'NO','etiqueta'=>'Tomar asistencia']
                    ];

        //print_r($registros);
        return view('calificaciones.asistencia_clases.crear',compact('registros','miga_pan'));
    }

    public function continuar_creacion(Request $request)
    {
        $colegio = Colegio::where('empresa_id',Auth::user()->empresa_id)->get();
        $colegio = $colegio[0];
        
        $registros = Matricula::estudiantes_matriculados( $request->curso_id, PeriodoLectivo::get_actual()->id, 'Activo'  );

        $fecha = $request->fecha;
        $curso = Curso::find($request->curso_id);
        $asignatura = Asignatura::find($request->id_asignatura);
        
        $miga_pan = [
                        ['url'=>'web?id='.Input::get('id').'&id_modelo='.Input::get('id_modelo'),'etiqueta'=>'Asistencia clases'],
                        ['url'=>'NO','etiqueta'=>'Tomar asistencia']
                    ];

        return view('calificaciones.asistencia_clases.create',compact('registros','fecha','curso','asignatura','miga_pan'));
    }
   

    /**
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        // Guardar la asistencia para cada estudiante      
        for($i=0;$i<$request->cantidad_estudiantes;$i++){
            $asistencia = new AsistenciaClase;
            $asistencia->id_estudiante = $request->input('id_estudiante.'.$i);
            $asistencia->curso_id = $request->curso_id;
            $asistencia->asignatura_id = $request->asignatura_id;
            $asistencia->fecha = $request->fecha;
            $asistencia->asistio = $request->input('asistio-'.$i);
            $asistencia->anotacion = $request->input('anotacion.'.$i);
            $asistencia->save();
        }

        return redirect('web?id='.$request->url_id.'&id_modelo='.$request->url_id_modelo)->with('flash_message','Asistencias ingresadas correctamente'); 

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $registro = AsistenciaClase::findOrFail($id);

        $registro->fill($request->all())->save();

        return redirect('web?id='.$request->url_id.'&id_modelo='.$request->url_id_modelo)->with('flash_message','Asistencia MODIFICADA correctamente');
    }

    // SE USARÁ ESTÁ CLASE PARA MOSTRAR LOS REPORTES
    public function reportes()
    {
        $colegio = Colegio::where('empresa_id',Auth::user()->empresa_id)->get()->first();

        $registros = Curso::where('estado','Activo')->where('id_colegio',$colegio->id)->get();

        $vec['']='';
        foreach ($registros as $opcion){
            $vec[$opcion->id]=$opcion->descripcion;
        }

        $registros = $vec;

        $miga_pan = [
                        ['url'=>'web?id='.Input::get('id').'&id_modelo='.Input::get('id_modelo'),'etiqueta'=>'Asistencia clases'],
                        ['url'=>'NO','etiqueta'=>'Reportes de asistencia']
                    ];

        return view('calificaciones.asistencia_clases.reportes',compact('miga_pan','registros'));
    }

    public function generar_reporte($fecha_inicial,$fecha_final,$curso_id,$tipo_reporte)
    {
        $colegio = Colegio::where('empresa_id',Auth::user()->empresa_id)->get()->first();

        $registros = AsistenciaClase::get_inasistencias( $curso_id, $fecha_inicial, $fecha_final, null, null);

        $curso = Curso::find($curso_id);

        return View::make('calificaciones.asistencia_clases.reportes.cantidad_inasistencias', compact('registros', 'fecha_inicial','fecha_final','curso' ));
    }
}