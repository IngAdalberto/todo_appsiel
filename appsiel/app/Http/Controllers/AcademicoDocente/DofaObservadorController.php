<?php

namespace App\Http\Controllers\AcademicoDocente;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests;
use App\Http\Controllers\Sistema\ModeloController;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\User;

use DB;
use Auth;
use Storage;
use Input;

use App\Core\Colegio;
use App\Sistema\Modelo;
use App\Matriculas\FodaEstudiante;

use App\Calificaciones\Periodo;
use App\Calificaciones\Asignatura;
use App\Matriculas\Curso;

class DofaObservadorController extends Controller
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
		$colegio = Colegio::where('empresa_id',Auth::user()->empresa_id)->get()[0];
        
        $registros = FodaEstudiante::get_foda_un_estudiante( Input::get('estudiante_id') );
        
        $miga_pan = [
                    ['url'=>'academico_docente?id='.Input::get('id'),'etiqueta'=>'Académico docente'],
                    ['url'=>'academico_docente/revisar_estudiantes/curso_id/'.Input::get('curso_id').'/id_asignatura/'.Input::get('asignatura_id').'?id='.Input::get('id'),'etiqueta'=>'Estudiantes'],
                    ['url'=>'NO','etiqueta'=>'DOFA Observador']
                ];

        $modelo = Modelo::find( 18 );

        $encabezado_tabla = app($modelo->name_space)->encabezado_tabla;

        // Se le asigna a cada variable url, su valor en el modelo correspondiente
        $variables_url = '?id='.Input::get('id').'&id_modelo='.Input::get('id_modelo').'&curso_id='.Input::get('curso_id').'&asignatura_id='.Input::get('asignatura_id').'&estudiante_id='.Input::get('estudiante_id');

        $url_crear = 'academico_docente/dofa_observador/create'.$variables_url;

        $url_edit = 'academico_docente/dofa_observador/id_fila/edit'.$variables_url;

        $url_print = '';
        $url_ver = '';
        $url_estado = '';

        $url_eliminar = 'academico_docente/dofa_observador/eliminar/id_fila'.$variables_url;

        $sqlString = "";
        $tituloExport = "";
        //determinar la busqueda
        $search = "";

        $id_app = 5;
        $id_modelo = 18; 
        $source = "INDEX1";
        $curso = new Curso();
        $asignatura = new Asignatura();
        $nro_registros = 10;

        return view( 'layouts.index', compact('registros','miga_pan','url_crear','encabezado_tabla','url_edit','url_eliminar','sqlString','tituloExport','search','source','curso','asignatura','id_app','id_modelo','nro_registros','url_ver','url_print','url_estado'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $modelo = Modelo::find(Input::get('id_modelo'));

        $lista_campos = ModeloController::get_campos_modelo($modelo, '', 'create');

        //Personalización de la lista de campos
        $cantida_campos = count($lista_campos);
        for ($i=0; $i <  $cantida_campos; $i++)
        {
          switch ( $lista_campos[$i]['name'] ) {
            case 'id_estudiante':
              $lista_campos[$i]['atributos'] = ['disabled' => 'disabled'];
              $lista_campos[$i]['name'] = 'estudiante_id';
              break;
            case 'descripcion':
              $lista_campos[$i]['atributos'] = ['required' => 'required'];
              break;
            default:
              # code...
              break;
          }
        }

        // Crear un nuevo campo
        $lista_campos[$i]['tipo'] = 'hidden';
        $lista_campos[$i]['name'] = 'id_estudiante';
        $lista_campos[$i]['descripcion'] = '';
        $lista_campos[$i]['opciones'] = '';
        $lista_campos[$i]['value'] = Input::get('estudiante_id');
        $lista_campos[$i]['atributos'] = [];
        $lista_campos[$i]['requerido'] = true;



        $url_action = 'academico_docente/dofa_observador?estudiante_id='.Input::get('estudiante_id').'&curso_id='.Input::get('curso_id').'&asignatura_id='.Input::get('asignatura_id').'&id='.Input::get('id').'&id_modelo='.Input::get('id_modelo');

        $form_create = [
                        'url' => $url_action,
                        'campos' => $lista_campos
                    ];

        $miga_pan = [
                    ['url'=>'academico_docente?id='.Input::get('id'),'etiqueta'=>'Académico docente'],
                    ['url'=>'academico_docente/revisar_estudiantes/curso_id/'.Input::get('curso_id').'/id_asignatura/'.Input::get('asignatura_id').'?id='.Input::get('id'),'etiqueta'=>'Estudiantes'],
                    ['url'=>'academico_docente/dofa_observador?estudiante_id='.Input::get('estudiante_id').'&curso_id='.Input::get('curso_id').'&asignatura_id='.Input::get('asignatura_id').'&id='.Input::get('id').'&id_modelo='.Input::get('id_modelo'),'etiqueta'=>'DOFA Observador'],
                    ['url'=>'NO','etiqueta'=>'Crear nueva']
                ];

        return view('layouts.create',compact('form_create','miga_pan') );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $modelo = Modelo::find($request->url_id_modelo);

        // Obtener la table de ese modelo
        $any_registro = New $modelo->name_space;
        $nombre_tabla = $any_registro->getTable();       

        // LLamar a los campos del modelo para verificar los que son requeridos
        $lista_campos = $modelo->campos->toArray();
        for ($i=0; $i < count($lista_campos); $i++) 
        { 
            if ($lista_campos[$i]['requerido']) 
            {
                $this->validate($request,[$lista_campos[$i]['name']=>'required']);
            }
            if ($lista_campos[$i]['unico']) 
            {
                $this->validate($request,[$lista_campos[$i]['name']=>'unique:'.$nombre_tabla]);
            }
        }

        $datos = $request->all();
        
        $registro = app($modelo->name_space)->create( $datos );

        return redirect( 'academico_docente/dofa_observador?estudiante_id='.Input::get('estudiante_id').'&curso_id='.Input::get('curso_id').'&asignatura_id='.Input::get('asignatura_id').'&id='.Input::get('id').'&id_modelo='.Input::get('id_modelo') )->with( 'flash_message','Registro CREADO correctamente.' );
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
    	$modelo = Modelo::find(Input::get('id_modelo'));

        $registro = app($modelo->name_space)->find($id);

        $lista_campos = ModeloController::get_campos_modelo($modelo, $registro, 'create');

        //Personalización de la lista de campos
        for ($i=0; $i < count($lista_campos) ; $i++) {
          switch ( $lista_campos[$i]['name'] ) {
            case 'id_estudiante':
              $lista_campos[$i]['atributos'] = ['disabled' => 'disabled'];
              break;
            case 'descripcion':
              $lista_campos[$i]['atributos'] = ['required' => 'required'];
              break;
            default:
              # code...
              break;
          }
        }

        $form_create = [
                        'url' => $modelo->url_form_create,
                        'campos' => $lista_campos
                    ];

        $miga_pan = [
                    ['url'=>'academico_docente?id='.Input::get('id'),'etiqueta'=>'Académico docente'],
                    ['url'=>'academico_docente/revisar_estudiantes/curso_id/'.Input::get('curso_id').'/id_asignatura/'.Input::get('asignatura_id').'?id='.Input::get('id'),'etiqueta'=>'Estudiantes'],
                    ['url'=>'academico_docente/dofa_observador?estudiante_id='.Input::get('estudiante_id').'&curso_id='.Input::get('curso_id').'&asignatura_id='.Input::get('asignatura_id').'&id='.Input::get('id').'&id_modelo='.Input::get('id_modelo'),'etiqueta'=>'DOFA Observador'],
                    ['url'=>'NO','etiqueta'=>'Modificar']
                ];

        $url_action = 'academico_docente/dofa_observador/'.$id.'?estudiante_id='.Input::get('estudiante_id').'&curso_id='.Input::get('curso_id').'&asignatura_id='.Input::get('asignatura_id').'&id='.Input::get('id').'&id_modelo='.Input::get('id_modelo');

        return view( 'layouts.edit', compact('form_create','miga_pan','url_action','registro') );
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
        // Se obtiene el modelo según la variable modelo_id de la url
        $modelo = Modelo::find($request->url_id_modelo);

        // Se obtinene el registro a modificar del modelo
        $registro = app($modelo->name_space)->find($id);

        // LLamar a los campos del modelo para verificar los que son requeridos
        // y los que son únicos
        $lista_campos = $modelo->campos->toArray();
        for ($i=0; $i < count($lista_campos); $i++) {
            if ($lista_campos[$i]['editable']!=0) { 
                if ($lista_campos[$i]['requerido']) {
                    $this->validate($request,[$lista_campos[$i]['name']=>'required']);
                }
                if ($lista_campos[$i]['unico']) {
                    $this->validate($request,[$lista_campos[$i]['name']=>'unique:'.$registro->getTable().','.$lista_campos[$i]['name'].','.$id]);
                }
            }
            // Cuando se edita una transacción
            if ($lista_campos[$i]['name']=='movimiento') {
                $lista_campos[$i]['value']=1;
            }
        }

        $registro->fill( $request->all() );

        $registro->save();

        return redirect( 'academico_docente/dofa_observador?estudiante_id='.Input::get('estudiante_id').'&curso_id='.Input::get('curso_id').'&asignatura_id='.Input::get('asignatura_id').'&id='.Input::get('id').'&id_modelo='.Input::get('id_modelo') )->with( 'flash_message','Registro MODIFICADO correctamente.' );
    }

    // ELIMINAR
    public function eliminar($id)
    {
        $registro =  FodaEstudiante::find($id);

        $ruta = 'academico_docente/dofa_observador?estudiante_id='.Input::get('estudiante_id').'&curso_id='.Input::get('curso_id').'&asignatura_id='.Input::get('asignatura_id').'&id='.Input::get('id').'&id_modelo='.Input::get('id_modelo');

        //Borrar registro
        $registro->delete();

        return redirect( $ruta )->with('flash_message','Registro de análisis DOFA ELIMINADO correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
