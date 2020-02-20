<?php

namespace App\Http\Controllers\Salud;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Http\Controllers\Sistema\ModeloController;

use Auth;
use DB;
use Input;
use Storage;

use App\Sistema\Aplicacion;
use App\Sistema\Modelo;
use App\Core\Tercero;

use App\Salud\ConsultaMedica;
use App\Salud\ExamenMedico;
use App\Salud\Paciente;

class PacienteController extends Controller
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
        // Se obtiene el modelo según la variable modelo_id  de la url
        $modelo = Modelo::find(Input::get('id_modelo'));

        //echo $modelo->name_space;

        $registros = app($modelo->name_space)->consultar_registros();//->take(20);

        // Se genera la miga de pan

        $app = Aplicacion::find(Input::get('id'));

        //$home = explode(',', $modelo->home_miga_pan);
        $urls = [$app->app.'?id='.Input::get('id'), 'NO'];
        $etiquetas = [$app->descripcion,$modelo->descripcion];

        $miga_pan = [];
        $cant = count($urls);
        for ($i=0; $i < $cant; $i++) { 
            $miga_pan[$i] = ['url'=>$urls[$i],'etiqueta'=>$etiquetas[$i]];
        }
        
        $titulo_tabla = 'Lista de '.$modelo->descripcion;

        $encabezado_tabla = app($modelo->name_space)->encabezado_tabla;

        // Se le asigna a cada variable url, su valor en el modelo correspondiente
        $variables_url = '?id='.Input::get('id').'&id_modelo='.Input::get('id_modelo');
        
        $url_crear = '';
        $url_edit = '';
        $url_print = '';
        $url_ver = '';
        $url_estado = '';
        $url_eliminar = '';
        $botones = [];
        
        // @can('crear_'.$modelo->modelo)
        if ($modelo->url_crear!='') {
            $url_crear = $modelo->url_crear.$variables_url;    
        }
        // @endcan

        if ($modelo->url_edit!='') {
            $url_edit = $modelo->url_edit.$variables_url;
        }
        if ($modelo->url_print!='') {
            $url_print = $modelo->url_print.$variables_url;
        }
        if ($modelo->url_ver!='') {
            $url_ver = $modelo->url_ver.$variables_url;
        }
        if ($modelo->url_custom!='') {
            $url_custom = $modelo->url_custom.$variables_url;
        }
        if ($modelo->url_estado!='') {
            $url_estado = $modelo->url_estado.$variables_url;
        }
        if ($modelo->url_eliminar!='') {
            $url_eliminar = $modelo->url_eliminar.$variables_url;
        }

        // Si el modelo tiene un archivo js particular
        $archivo_js = app($modelo->name_space)->archivo_js;

        return view('consultorio_medico.pacientes_index', compact('registros','miga_pan','url_crear','titulo_tabla','encabezado_tabla','url_edit','url_print','url_ver','url_estado','url_eliminar','archivo_js'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $general = new ModeloController();

        return $general->create();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Almacenar algunos datos del paciente
        $general = new ModeloController();
        $registro_creado = $general->crear_nuevo_registro( $request );

        /* Almacenar datos del Tercero y ... 
            Asignar datos adicionales al Paciente creado */
        $registro_creado->core_tercero_id = Tercero::crear_nuevo_tercero($general, $request)->id;

        // Consecutivo Historia Clínica
        // Se obtiene el consecutivo para actualizar el logro creado
        $registro = DB::table('sys_secuencias_codigos')->where('modulo','historias_clinicas')->value('consecutivo');
        $consecutivo=$registro+1;

        // Actualizar el consecutivo
        DB::table('sys_secuencias_codigos')->where('modulo','historias_clinicas')->increment('consecutivo');
        
        $registro_creado->codigo_historia_clinica = $consecutivo;

        $registro_creado->save();


        return redirect( 'consultorio_medico/pacientes/'.$registro_creado->id.'?id='.$request->url_id.'&id_modelo='.$request->url_id_modelo )->with( 'flash_message','Registro CREADO correctamente.' );
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //return "lallala";
        $secciones_consulta = json_decode( config('consultorio_medico.secciones_consulta') );

        $general = new ModeloController();

        // Se obtiene el modelo según la variable modelo_id de la url
        $modelo = Modelo::find(Input::get('id_modelo'));

        // Se obtiene el registro del modelo indicado y el anterior y siguiente registro
        $registro = app($modelo->name_space)->find($id);
        $reg_anterior = app($modelo->name_space)->where('id', '<', $registro->id)->max('id');
        $reg_siguiente = app($modelo->name_space)->where('id', '>', $registro->id)->min('id');
        
        
        $datos_historia_clinica = Paciente::datos_basicos_historia_clinica( $id );
        
        //$miga_pan = $general->get_miga_pan($modelo,$registro->descripcion);

        $miga_pan = [
                        ['url'=>'consultorio_medico?id='.Input::get('id'),'etiqueta'=>'Consultorio Médico'],
                        ['url'=>'consultorio_medico/pacientes?id='.Input::get('id').'&id_modelo='.Input::get('id_modelo'),'etiqueta'=> $modelo->descripcion],
                        ['url'=>'NO','etiqueta'=> 'Historia Clínica' ]
                    ];

        $url_crear = '';
        $url_edit = '';

        // Se le asigna a cada variable url, su valor en el modelo correspondiente
        $variables_url = '?id='.Input::get('id').'&id_modelo='.Input::get('id_modelo');
        if ($modelo->url_crear!='') {
            $url_crear = $modelo->url_crear.$variables_url;    
        }
        if ($modelo->url_edit!='') {
            $url_edit = $modelo->url_edit.$variables_url;
        }

        // RELATIVO A CONSULTAS
        $modelo_consultas = Modelo::where('modelo','salud_consultas')->first();
        
        $consultas = ConsultaMedica::where('paciente_id', $id)->orderBy('fecha','DESC')->get();

        //dd($consultas);

        $modelo_formulas_opticas = Modelo::where('modelo','salud_formulas_opticas   ')->first();
        
        return view('consultorio_medico.pacientes_show',compact('secciones_consulta','miga_pan','registro','url_crear','url_edit','reg_anterior','reg_siguiente','consultas','modelo_consultas','datos_historia_clinica','modelo_formulas_opticas','id'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $general = new ModeloController();

        $modelo = Modelo::find(Input::get('id_modelo'));

        // Se obtiene el registro a modificar del modelo
        $registro = app($modelo->name_space)->find($id);

        $lista_campos = $general->get_campos_modelo($modelo,$registro,'edit');

        //$paciente = new Paciente;
        $registro->nombre1 = $registro->tercero->nombre1;
        $registro->otros_nombres = $registro->tercero->otros_nombres;
        $registro->apellido1 = $registro->tercero->apellido1;
        $registro->apellido2 = $registro->tercero->apellido2;
        $registro->id_tipo_documento_id = $registro->tercero->id_tipo_documento_id;
        $registro->numero_identificacion = $registro->tercero->numero_identificacion;
        $registro->direccion1 = $registro->tercero->direccion1;
        $registro->telefono1 = $registro->tercero->telefono1;
        $registro->email = $registro->tercero->email;
        
        //
        //dd( array_merge(  $registro->getOriginal(), $registro->tercero->getOriginal() ) );
        
        $form_create = [
                        'url' => $modelo->url_form_create,
                        'campos' => $lista_campos
                    ];

        $url_action = 'web/'.$id;
        if ($modelo->url_form_create != '') {
            $url_action = $modelo->url_form_create.'/'.$id.'?id='.Input::get('id').'&id_modelo='.Input::get('id_modelo');
        }

        //$miga_pan = $general->get_miga_pan($modelo,$registro->descripcion);

        $miga_pan = [
                        ['url'=>'consultorio_medico?'.Input::get('id'),'etiqueta'=>'Consultorio Médico'],
                        ['url'=>'consultorio_medico/pacientes?id='.Input::get('id').'&id_modelo='.Input::get('id_modelo'),'etiqueta'=> $modelo->descripcion],
                        ['url'=>'NO','etiqueta'=> $registro->tercero->nombre1." ".$registro->tercero->otros_nombres." ".$registro->tercero->apellido1." ".$registro->tercero->apellido2 ]
                    ];

        $archivo_js = app($modelo->name_space)->archivo_js;

        return view('layouts.edit',compact('form_create','miga_pan','registro','archivo_js','url_action')); 
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
        $modelo = Modelo::find($request->url_id_modelo);

        // Se obtinene el registro a modificar del modelo
        $registro = app($modelo->name_space)->find($id);

        // Si se envían datos tipo file
        if ( count($request->file()) > 0) 
        {   
            // Para borrar el archivo anterior
            $registro2 = app($modelo->name_space)->find($id);
        }

        // LLamar a los campos del modelo para verificar los que son requeridos
        // y los que son únicos
        $lista_campos = $modelo->campos->toArray();
        $cant = count($lista_campos);
        for ($i=0; $i < $cant; $i++) {
            if ( $lista_campos[$i]['editable'] == 1 ) 
            { 
                    // Se valida solo si el campo pertenece al Modelo directamente
                    if ( in_array( $lista_campos[$i]['name'], $registro->getFillable() )  ) 
                    {
                        if ($lista_campos[$i]['requerido']) 
                        {
                            $this->validate($request,[$lista_campos[$i]['name']=>'required']);
                        }
                        if ($lista_campos[$i]['unico']) 
                        {
                            $this->validate($request,[$lista_campos[$i]['name']=>'unique:'.$registro->getTable().','.$lista_campos[$i]['name'].','.$id]);
                        }
                    }
            }
            // Cuando se edita una transacción
            if ($lista_campos[$i]['name']=='movimiento') {
                $lista_campos[$i]['value']=1;
            }
        }

        $registro->fill( $request->all() );
        $registro->save();

        $archivos_enviados = $request->file();
        foreach ($archivos_enviados as $key => $value) 
        {
            // Si se carga un nuevo archivo, Eliminar el(los) archivo(s) anterior(es)
            if ( $request->file($key) != '' ) 
            {
                // El favicon se almacena en la carpeta public
                if ( isset( $request->favicon ) ) 
                {
                    Storage::disk('publico')->delete($registro2->$key);
                }else{
                    Storage::delete($modelo->ruta_storage_imagen.$registro2->$key);
                }
                
            }
            
            // 2do. Almacenar en disco con su extensión específica
            $archivo = $request->file($key);

            $extension =  $archivo->clientExtension();

            $nuevo_nombre = uniqid().'.'.$extension;


            // El favicon se almacena en la carpeta public
            if ( $key == 'favicon' ) 
            {
                Storage::disk('publico')->put( 
                $nuevo_nombre,
                file_get_contents( $archivo->getRealPath() ) 
                );
            }else{

                Storage::put($modelo->ruta_storage_imagen.$nuevo_nombre,
                file_get_contents( $archivo->getRealPath() ) 
                );
            }                

            // Guardar nombre en la BD
            $registro2->$key = $nuevo_nombre;
            $registro2->save();
        }


        // Actualizar datos del Tercero
        $registro->tercero->fill( $request->all() );
        $registro->tercero->save();

        return redirect('consultorio_medico/pacientes/'.$id.'?id='.$request->url_id.'&id_modelo='.$request->url_id_modelo)->with('flash_message','Registro MODIFICADO correctamente.');
    }


    
    public function eliminar(Request $request)
    {
        Paciente::find($request->recurso_a_eliminar_id)->delete();

        return redirect('web?id='.Input::get('id').'&id_modelo='.Input::get('id_modelo') )->with('mensaje_error','Paciente ELIMINADO correctamente.');
    }
}
