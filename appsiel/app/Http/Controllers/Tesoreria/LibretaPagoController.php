<?php

namespace App\Http\Controllers\Tesoreria;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests;
use Auth;
use DB;
use View;
use Lava;
use Input;


use App\Http\Controllers\Core\ConfiguracionController;
use App\Http\Controllers\Sistema\ModeloController;


// Modelos
use App\Matriculas\Estudiante;
use App\Matriculas\Matricula;
use App\Matriculas\Curso;

use App\Core\Colegio;
use App\Core\Empresa;
use App\Core\TipoDocApp;
use App\Sistema\Modelo;
use App\Core\Tercero;

use App\Tesoreria\TesoLibretasPago;
use App\Tesoreria\TesoRecaudosLibreta;
use App\Tesoreria\TesoCarteraEstudiante;
use App\Tesoreria\TesoCuentaBancaria;
use App\Tesoreria\TesoCaja;
use App\Tesoreria\TesoEntidadFinanciera;
use App\Tesoreria\TesoMotivo;
use App\Tesoreria\TesoMedioRecaudo;
use App\Tesoreria\TesoMovimiento;

use App\Contabilidad\ContabMovimiento;


class LibretaPagoController extends ModeloController
{
    protected $total_valor_movimiento = 0;
    protected $saldo = 0;
    protected $j;


    public function actualizar_estado_cartera()
    {
        // 1ro. PROCESO QUE ACTUALIZA LAS CARTERAS, asignando EL ESTADO Vencida
        // Actualizar las cartera con fechas inferior a hoy y con estado distinto a Pagada
        TesoCarteraEstudiante::where('fecha_vencimiento','<', date('Y-m-d'))
          ->where('estado','<>', 'Pagada')
          ->update(['estado' => 'Vencida']);
    }

    public function cartera_vencida_estudiantes()
    {
        $this->actualizar_estado_cartera();

        $colegio = Colegio::where('empresa_id',Auth::user()->empresa_id)->get();
        $curso_id = '';
        $curso_lbl = 'Todos';
        $cursos = Curso::where('id_colegio',$colegio[0]->id)->where('estado','Activo')->get();
        if ( Input::get('curso_id')!==null ) 
        {
            $curso_id = Input::get('curso_id');
            if ( Input::get('curso_id') != '') {
                $curso_lbl = Curso::find(Input::get('curso_id'))->descripcion;
            }                
        }
        $vec2['']='Todos';
        foreach ($cursos as $opcion){
            $vec2[$opcion->id]=$opcion->descripcion;
        }
        $cursos = $vec2;


        if ( Input::get('curso_id') == '') 
        {
            $curso_id = '%%';
        }else{
            $curso_id = Input::get('curso_id');
        }


        // Creación de gráfico de Torta MATRICULAS
        $stocksTable1 = Lava::DataTable();
        
        $stocksTable1->addStringColumn('Meses')
                    ->addNumberColumn('Valor');

        // Obtención de datos
        $concepto = 'Matrícula';
        $num_mes="01";
        $cartera_matriculas=array();
        for($i=0;$i<12;$i++){
            if (strlen($num_mes)==1) {
                $num_mes="0".$num_mes;
            }
            $cadena="%-".$num_mes."-%";
            $cartera_matriculas[$num_mes] = TesoCarteraEstudiante::leftJoin('sga_matriculas','sga_matriculas.id_estudiante','=','teso_cartera_estudiantes.id_estudiante')
                ->where('curso_id','LIKE', $curso_id)
                ->where('teso_cartera_estudiantes.fecha_vencimiento','LIKE',$cadena)
                ->where('teso_cartera_estudiantes.concepto','=', $concepto)
                ->where('teso_cartera_estudiantes.estado','=','Vencida')
                ->sum('teso_cartera_estudiantes.saldo_pendiente');

            // Agregar campo a la torta
            $stocksTable1->addRow([ConfiguracionController::nombre_mes($num_mes), (float)$cartera_matriculas[$num_mes]]);

            $num_mes++;
            if($num_mes>=13){
                $num_mes='01';
            }
        }

        $chart1 = Lava::PieChart('torta_matriculas', $stocksTable1,[
                'is3D'                  => True,
                'pieSliceText'          => 'value'
            ]);


        // Creación de gráfico de Torta PENSIONES
        $stocksTable = Lava::DataTable();
        
        $stocksTable->addStringColumn('Meses')
                    ->addNumberColumn('Valor');

        // Obtención de datos
        $concepto = 'Pensión';
        $num_mes="01";
        $cartera_pensiones=array();
        for($i=0;$i<12;$i++){
            if (strlen($num_mes)==1) {
                $num_mes="0".$num_mes;
            }
            $cadena="%-".$num_mes."-%";
            $cartera_pensiones[$num_mes] = TesoCarteraEstudiante::leftJoin('sga_matriculas','sga_matriculas.id_estudiante','=','teso_cartera_estudiantes.id_estudiante')
                ->where('curso_id','LIKE', $curso_id)
                ->where('teso_cartera_estudiantes.fecha_vencimiento','LIKE',$cadena)
                ->where('teso_cartera_estudiantes.concepto','=',$concepto)
                ->where('teso_cartera_estudiantes.estado','=','Vencida')
                ->sum('teso_cartera_estudiantes.saldo_pendiente');

            // Agregar campo a la torta
            $stocksTable->addRow([ConfiguracionController::nombre_mes($num_mes), (float)$cartera_pensiones[$num_mes]]);

            $num_mes++;
            if($num_mes>=13){
                $num_mes='01';
            }
        }

        $chart = Lava::PieChart('torta_pensiones', $stocksTable,[
                'is3D'                  => True,
                'pieSliceText'          => 'value'
            ]);

        

        $miga_pan = [
                ['url'=>'NO','etiqueta'=>'Tesoreria']
            ];

        return view('tesoreria.cartera_vencida_estudiantes',compact('cartera_pensiones','cartera_matriculas','miga_pan','cursos','curso_id'));
    }

    /**
     * Almacenar Cartera de estudiantes
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store( Request $request )
    {   
        $parametros = config('configuracion.matriculas'); // Llamar al archivo de configuración del core

        $matricula_estudiante = Matricula::get_registro_impresion( $request->matricula_id );
        $request['id_estudiante'] = $matricula_estudiante->id_estudiante;
        $registro = $this->crear_nuevo_registro( $request );

        $cpto_matricula = InvProducto::find( $inv_producto_id_default_matricula );
        $cpto_pension = InvProducto::find( $inv_producto_id_default_pension );

        /*      SE CREAN LOS REGISTROS DE CARTERA DE ESTUDIANTES (Plan de Pagos)    */
        // 1. Se agrega el registro de matrícula por pagar en la cartera de estudiantes
        $cartera = new TesoCarteraEstudiante;
        $cartera->id_libreta = $registro->id;
        $cartera->id_estudiante = $request->id_estudiante;
        $cartera->concepto = $cpto_matricula->descripcion; // Debe haber un registro en "inv_productos" tipo "servicio" con este mismo nombre para generar la factura de ventas
        $cartera->valor_cartera = $request->valor_matricula;
        $cartera->saldo_pendiente = $request->valor_matricula;
        $cartera->fecha_vencimiento = $request->fecha_inicio;
        $cartera->estado = "Pendiente";
        $cartera->save();


        // Contabilización TODA MANUAL
        /*$valor = $request->valor_matricula;
        $core_empresa_id = Auth::user()->empresa_id;

        $core_tipo_transaccion_id = 21; //Recaudo libreta de pago
        $core_tipo_doc_app_id = 11; // FA Cuenta de cobro

        // Obtener y aumentar el consecutivo
        $consecutivo = $this->get_consecutivo($core_empresa_id, $core_tipo_doc_app_id);
        

        $detalle_operacion = 'Generación libreta de pagos. Matrícula. ID_LIBRETA'.$registro->id.'.';
        $valor_operacion = $valor;

        $datos = [ 'core_empresa_id' => $core_empresa_id ] +
                [ 'core_tipo_transaccion_id' => $core_tipo_transaccion_id ] +
                [ 'core_tipo_doc_app_id' => $core_tipo_doc_app_id ] + 
                ['consecutivo' => $consecutivo] +
                ['fecha' => date('Y-m-d')] +
                ['core_tercero_id' => $matricula_estudiante->core_tercero_id] + 
                [ 'codigo_referencia_tercero' => $matricula_estudiante->id_estudiante ];

        // Contabilizar DB = CARTERA vs CR = INGRESOS

        // CxC Clientes
        $cxc_cuenta_id = $parametros['cta_cartera_default'];
        // Ingresos ventas
        $ingresos_cuenta_id = $parametros['cta_ingresos_default'];



        $valor_debito = $valor;
        $valor_credito = 0;

        $reg_contab = ContabMovimiento::create(  $datos +
                                    [ 'contab_cuenta_id' => $cxc_cuenta_id ] +
                                    [ 'detalle_operacion' => $detalle_operacion] + 
                                    [ 'valor_operacion' => $valor_operacion] + 
                                    [ 'valor_debito' => $valor_debito] + 
                                    [ 'valor_credito' => ($valor_credito * -1) ] + 
                                    [ 'valor_saldo' => ( $valor_debito - $valor_credito ) ]
                                );
        
        $valor_debito = 0;
        $valor_credito = $valor;

        ContabMovimiento::create( $datos +
                                    [ 'contab_cuenta_id' => $ingresos_cuenta_id ] +
                                    [ 'detalle_operacion' => $detalle_operacion] + 
                                    [ 'valor_operacion' => $valor_operacion] + 
                                    [ 'valor_debito' => $valor_debito] + 
                                    [ 'valor_credito' => ($valor_credito * -1) ] + 
                                    [ 'valor_saldo' => ( $valor_debito - $valor_credito ) ]
                                );

        */

        // 2. Se agregan los registros de pensiones por pagar
        // $detalle_operacion = 'Generación libreta de pagos. Pensión.';
        $fecha = explode("-",$request->fecha_inicio);
        $num_mes = $fecha[1];
        for( $i=0; $i < $request->numero_periodos ; $i++)
        {
            $fecha_vencimiento = $fecha[0].'-'.$num_mes.'-01';
            $cartera = new TesoCarteraEstudiante;
            $cartera->id_libreta = $registro->id;
            $cartera->id_estudiante = $request->id_estudiante;
            $cartera->concepto = $cpto_pension->descripcion; // Debe haber un registro en "inv_productos" tipo "servicio" con este mismo nombre para generar la factura de ventas
            $cartera->valor_cartera = $request->valor_pension_mensual;
            $cartera->saldo_pendiente = $request->valor_pension_mensual;
            $cartera->fecha_vencimiento = $fecha_vencimiento;
            $cartera->estado = "Pendiente";
            $cartera->save();
            $num_mes++;

            /*
            $consecutivo = $this->get_consecutivo($core_empresa_id, $core_tipo_doc_app_id);
            // CONTABLIZACION
            $detalle_operacion = 'Generación libreta de pagos. Pensión. ID_LIBRETA'.$registro->id.'.';
            $valor = $request->valor_pension_mensual;        
            $valor_operacion = $valor;

            $valor_debito = $valor;
            $valor_credito = 0;

            ContabMovimiento::create( $datos +  
                                    [ 'contab_cuenta_id' => $cxc_cuenta_id ] +
                                    [ 'detalle_operacion' => $detalle_operacion] + 
                                    [ 'valor_operacion' => $valor_operacion] + 
                                    [ 'valor_debito' => $valor_debito] + 
                                    [ 'valor_credito' => ($valor_credito * -1) ] + 
                                    [ 'valor_saldo' => ( $valor_debito - $valor_credito ) ]
                                );

            $valor_debito = 0;
            $valor_credito = $valor;

            ContabMovimiento::create( $datos +  
                                    [ 'contab_cuenta_id' => $ingresos_cuenta_id ] +
                                    [ 'detalle_operacion' => $detalle_operacion] + 
                                    [ 'valor_operacion' => $valor_operacion] + 
                                    [ 'valor_debito' => $valor_debito] + 
                                    [ 'valor_credito' => ($valor_credito * -1) ] + 
                                    [ 'valor_saldo' => ( $valor_debito - $valor_credito ) ]
                                );
            */
        }

        return redirect('tesoreria/ver_plan_pagos/'.$registro->id.'?id='.$request->url_id.'&id_modelo='.$request->url_id_modelo)->with('flash_message','Libreta creada correctamente.');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        // Se obtiene el modelo según la variable modelo_id de la url
        $modelo = Modelo::find(Input::get('id_modelo'));

        // Se obtiene el registro a modificar del modelo
        $registro = app($modelo->name_space)->find($id);
        $recaudos_libreta = TesoRecaudosLibreta::where('id_libreta',$id)->get();

        // Si la libreta ya tiene recaudos, no se puede modificar.
        if ( isset($recaudos_libreta[0]) ) 
        {
            return redirect( 'web?id='.Input::get('id').'&id_modelo='.Input::get('id_modelo'))->with('mensaje_error','La Libreta ya tiene pagos aplicados. No puede ser modificada.' );
        }

        $lista_campos = ModeloController::get_campos_modelo($modelo,$registro,'edit');

        /*
            Como el select de estudiantes solo muesta los que no tienen libreta, para la edición se coloca al estudiante de la libreta que se está editando
        */
        $matricula_estudiante = Matricula::get_registro_impresion( $registro->matricula_id );

        $lista_campos[0]['opciones'][$matricula_estudiante->id] = $matricula_estudiante->numero_identificacion.' '.$matricula_estudiante->nombre_estudiante.' ('.$matricula_estudiante->nombre_curso.')';

        $form_create = [
                        'url' => $modelo->url_form_create,
                        'campos' => $lista_campos
                    ];

        $miga_pan = $this->get_miga_pan($modelo,$registro->descripcion);

        $url_action = 'web/'.$id;
        if ($modelo->url_form_create != '') {
            $url_action = $modelo->url_form_create.'/'.$id.'?id='.Input::get('id').'&id_modelo='.Input::get('id_modelo').'&id_transaccion='.Input::get('id_transaccion');
        }


        return view('layouts.edit',compact('form_create','miga_pan','registro', 'url_action'));
    }

    /**
     * Actualizar cartera de estudiantes.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $parametros = config('configuracion'); // Llamar al archivo de configuración del core

        $matricula_estudiante = Matricula::get_registro_impresion( $request->matricula_id );

        $registro = TesoLibretasPago::find($id);

        //Borrar los registros anteriores de cartera asociados a la libreta, para luego crearlos otra vez
        TesoCarteraEstudiante::where('id_libreta',$id)->delete();

        // Borrar registros contables asociados a la libreta 
        //ContabMovimiento::where('detalle_operacion','LIKE','%ID_LIBRETA'.$id.'.%')->delete();

        // Se agrega el registro de matrícula por pagar en la cartera de estudiantes
        $cartera = new TesoCarteraEstudiante;
        $cartera->id_libreta = $id;
        $cartera->id_estudiante = $matricula_estudiante->id_estudiante;
        $cartera->concepto = "Matrícula";
        $cartera->valor_cartera = $request->valor_matricula;
        $cartera->saldo_pendiente = $request->valor_matricula;
        $cartera->fecha_vencimiento = $request->fecha_inicio;
        $cartera->estado = "Pendiente";
        $cartera->save();


        // Contabilización TODA MANUAL
        /*$valor = $request->valor_matricula;
        $core_empresa_id = Auth::user()->empresa_id;
        $core_tipo_transaccion_id = 21;
        $core_tipo_doc_app_id = 11;

        $consecutivo = $this->get_consecutivo($core_empresa_id, $core_tipo_doc_app_id);

        $datos = [ 'core_empresa_id' => $core_empresa_id ] +
                [ 'core_tipo_transaccion_id' => $core_tipo_transaccion_id ] +
                [ 'core_tipo_doc_app_id' => $core_tipo_doc_app_id ] + 
                ['consecutivo' => $consecutivo] +
                ['fecha' => date('Y-m-d')] +
                ['core_tercero_id' => $matricula_estudiante->core_tercero_id] + 
                [ 'codigo_referencia_tercero' => $matricula_estudiante->id_estudiante ];

        // Contabilizar DB = CARTERA vs CR = INGRESOS

        $cxc_cuenta_id = $parametros['cta_cartera_default']; // CxC Clientes
        $ingresos_cuenta_id = $parametros['cta_ingresos_default']; // Ingresos ventas
        

        $detalle_operacion = 'Generación libreta de pagos. Matrícula. ID_LIBRETA'.$id.'.';
        $valor_operacion = $valor;

        $valor_debito = $valor;
        $valor_credito = 0;

        ContabMovimiento::create( $datos + 
                                        [ 'contab_cuenta_id' => $cxc_cuenta_id ] +
                                        [ 'detalle_operacion' => $detalle_operacion] + 
                                        [ 'valor_operacion' => $valor_operacion] + 
                                        [ 'valor_debito' => $valor_debito] + 
                                        [ 'valor_credito' => ($valor_credito * -1) ] + 
                                        [ 'valor_saldo' => ( $valor_debito - $valor_credito ) ]
                                    );

            $valor_debito = 0;
            $valor_credito = $valor;

            ContabMovimiento::create( $datos + 
                                        [ 'contab_cuenta_id' => $ingresos_cuenta_id ] +
                                        [ 'detalle_operacion' => $detalle_operacion] + 
                                        [ 'valor_operacion' => $valor_operacion] + 
                                        [ 'valor_debito' => $valor_debito] + 
                                        [ 'valor_credito' => ($valor_credito * -1) ] + 
                                        [ 'valor_saldo' => ( $valor_debito - $valor_credito ) ]
                                    );

        */


        // Se agregan los registros de pensiones por pagar
        $fecha = explode("-",$request->fecha_inicio);
        $num_mes = $fecha[1];
        for($i=0;$i<$request->numero_periodos;$i++){
            $cartera = new TesoCarteraEstudiante;
            $cartera->id_libreta = $id;
            $cartera->id_estudiante = $matricula_estudiante->id_estudiante;
            $cartera->concepto = "Pensión";
            $cartera->valor_cartera = $request->valor_pension_mensual;
            $cartera->saldo_pendiente = $request->valor_pension_mensual;
            $cartera->fecha_vencimiento = $fecha[0].'-'.$num_mes.'-01';
            $cartera->estado = "Pendiente";
            $cartera->save();
            $num_mes++;

            // CONTABLIZACION
            /*
            $detalle_operacion = 'Generación libreta de pagos. Pensión. ID_LIBRETA'.$id.'.';
            $valor = $request->valor_pension_mensual;
        
            $valor_operacion = $valor;

            $valor_debito = $valor;
            $valor_credito = 0;
            ContabMovimiento::create( $datos + 
                                        [ 'contab_cuenta_id' => $cxc_cuenta_id ] +
                                        [ 'detalle_operacion' => $detalle_operacion] + 
                                        [ 'valor_operacion' => $valor_operacion] + 
                                        [ 'valor_debito' => $valor_debito] + 
                                        [ 'valor_credito' => ($valor_credito * -1) ] + 
                                        [ 'valor_saldo' => ( $valor_debito - $valor_credito ) ]
                                    );

        
            $valor_debito = 0;
            $valor_credito = $valor;

            ContabMovimiento::create( $datos + 
                                        [ 'contab_cuenta_id' => $ingresos_cuenta_id ] +
                                        [ 'detalle_operacion' => $detalle_operacion] + 
                                        [ 'valor_operacion' => $valor_operacion] + 
                                        [ 'valor_debito' => $valor_debito] + 
                                        [ 'valor_credito' => ($valor_credito * -1) ] + 
                                        [ 'valor_saldo' => ( $valor_debito - $valor_credito ) ]
                                    );
            */
        }

        $registro->fill( $request->all() );
        $registro->id_estudiante = $matricula_estudiante->id_estudiante;
        $registro->save();

        return redirect('tesoreria/ver_plan_pagos/'.$registro->id.'?id='.$request->url_id.'&id_modelo='.$request->url_id_modelo)->with('flash_message','Libreta modificada correctamente.');
    }


    public function imprimir_libreta($id_libreta)
    {
        
        if ($id_libreta!=0) {
            $libreta = TesoLibretasPago::find($id_libreta);
            $sql_registro = $libreta->consultar_un_registro($id_libreta);
            $registro = $sql_registro[0];    
        }else{
            $registro = 0;
        }

        $colegio = Colegio::where('empresa_id',Auth::user()->empresa_id)->get();
        $colegio = $colegio[0]; 

        $empresa = Empresa::find(Auth::user()->empresa_id);
        
        $cuenta = TesoCuentaBancaria::get_cuenta_por_defecto();

        $formato = config('tesoreria.formato_libreta_pago_defecto');

        if( is_null($formato) )
        {
            $formato = 'pdf_libreta';
        }
        
        $view =  View::make('tesoreria.'.$formato, compact('registro','colegio','empresa','cuenta'))->render();

        //crear PDF   echo $view;
        $pdf = \App::make('dompdf.wrapper');
        $pdf->loadHTML($view);
        return $pdf->stream('libreta_pagos.pdf');
        /**/
    }

    public function hacer_recaudo_cartera($id_cartera)
    {        
        $cartera = TesoCarteraEstudiante::find($id_cartera);
        $libreta = TesoLibretasPago::find($cartera->id_libreta);
        $estudiante = Estudiante::find($libreta->id_estudiante);
        $colegio = Colegio::where('empresa_id',Auth::user()->empresa_id)->get()->first();

        $matricula = Matricula::find( $libreta->matricula_id );

        $curso = Curso::find($matricula->curso_id);

        $codigo_matricula = $matricula->codigo;

        $miga_pan = [
                ['url'=>'tesoreria?id='.Input::get('id'),'etiqueta'=>'Tesorería'],
                ['url'=>'web?id='.Input::get('id').'&id_modelo='.Input::get('id_modelo'),'etiqueta'=>'Libretas de pagos'],
                ['url'=>'tesoreria/ver_plan_pagos/'.$libreta->id.'?id='.Input::get('id').'&id_modelo='.Input::get('id_modelo'),'etiqueta'=>'Plan de pagos'],
                ['url'=>'NO','etiqueta'=>'Hacer Recaudo']
            ];

        return view('tesoreria.hacer_recaudo_cartera',compact('cartera','libreta','estudiante','colegio','miga_pan','codigo_matricula','curso'));
    }

    // Almacenar documentos de RECAUDO DE CARTERA DE ESTUDANTE
    public function guardar_recaudo_cartera(Request $request)
    {
        // Se crea un token único para identificar el rgistro en la BD y evitar duplicados
        $mi_token = "";
        /*$mi_token = $request->core_tipo_transaccion_id.$request->core_tipo_doc_app_id.$request->consecutivo.$request->id_libreta.$request->id_cartera.$request->concepto.$request->fecha_recaudo.$request->teso_medio_recaudo_id.$request->cantidad_cuotas.$request->valor_recaudo;

        // Se busca el registro con el token
        $recaudo = TesoRecaudosLibreta::where('mi_token',$mi_token)->get();

        
        // Si ya está creado, no se hace nada
        if ( count( $recaudo) > 0 ) {
            # code...
        }else{
    */
            $fecha = $request->fecha_recaudo;

            $consecutivo = $this->get_consecutivo($request->core_empresa_id, $request->core_tipo_doc_app_id);

            // Se REEMPLAZA el conscutivo en los datos del request
            $datos = array_merge($request->all(),['consecutivo' => $consecutivo,'fecha' => $fecha, 'mi_token'=>$mi_token]);

            // Se guarda el recaudo
            TesoRecaudosLibreta::create( $datos );

            // Se Actualiza la cartera del estudiante
            $cartera = TesoCarteraEstudiante::find($request->id_cartera);
            $valor_pagado = $cartera->valor_pagado + $request->valor_recaudo;
            $saldo_pendiente = $cartera->saldo_pendiente - $request->valor_recaudo;
            $estado = $cartera->estado;
            if($valor_pagado==$cartera->valor_cartera){
                $estado="Pagada";
            }
            $cartera->valor_pagado=$valor_pagado;
            $cartera->saldo_pendiente=$saldo_pendiente;
            $cartera->estado=$estado;
            $cartera->save();

            // Se verifica si la libreta no tiene cartera pendiente y se inactiva
            $suma_matriculas = TesoCarteraEstudiante::where('id_libreta',$request->id_libreta)->where('concepto','Matrícula')->sum('valor_pagado');
            $suma_pensiones = TesoCarteraEstudiante::where('id_libreta',$request->id_libreta)->where('concepto','Pensión')->sum('valor_pagado');
            $total_pagado = $suma_matriculas + $suma_pensiones ;
            $libreta = TesoLibretasPago::find($request->id_libreta);
            $total_libreta = $libreta->valor_matricula + $libreta->valor_pension_anual;
            if ($total_pagado==$total_libreta) {
                $libreta->estado = "Inactivo";
                $libreta->save();
            }

            // MOVIMIENTO DE TESORERIA
            // Datos la caja o el la cuenta bancaria
            // Tambien se asigna el ID de la cuenta contable para el movimiento CREDITO

            $medio_recaudo = TesoMedioRecaudo::find($request->teso_medio_recaudo_id);
            if ( $medio_recaudo->comportamiento == 'Tarjeta bancaria' ) {
                $banco = TesoCuentaBancaria::find($request->teso_cuenta_bancaria_id);
                $contab_cuenta_id = $banco->contab_cuenta_id;
                $teso_caja_id = 0;
                $teso_cuenta_bancaria_id = $banco->id;
            }else{
                $caja = TesoCaja::find($request->teso_caja_id);
                $contab_cuenta_id = $caja->contab_cuenta_id;
                $teso_caja_id = $caja->id;
                $teso_cuenta_bancaria_id = 0;
            }

            TesoMovimiento::create( $datos +  
                            [ 'teso_caja_id' => $teso_caja_id ] + 
                            [ 'teso_cuenta_bancaria_id' => $teso_cuenta_bancaria_id ] + 
                            [ 'valor_movimiento' => $request->valor_recaudo ] +
                            [ 'estado' => 'Activo' ]
                        );


            /*
                **  Determinar la cuenta contable (CAJA O BANCOS)
            */
            

            if ($request->teso_caja_id != '') {
                $sql_contab_cuenta_id = TesoCaja::find($request->teso_caja_id);
                $contab_cuenta_id = $sql_contab_cuenta_id->contab_cuenta_id;
            }
            if ($request->teso_cuenta_bancaria_id != '') {
                $sql_contab_cuenta_id = TesoCuentaBancaria::find($request->teso_cuenta_bancaria_id);
                $contab_cuenta_id = $sql_contab_cuenta_id->contab_cuenta_id;
            }

            $detalle_operacion = 'Recaudo libreta de pago.';
            $valor_operacion = $request->valor_recaudo;

            $valor_debito = $request->valor_recaudo;
            $valor_credito = 0;

            //$datos = array_merge($request->all(),['consecutivo' => $consecutivo]);

            ContabMovimiento::create( $datos + 
                            [ 'contab_cuenta_id' => $contab_cuenta_id ] +
                            [ 'detalle_operacion' => $detalle_operacion] + 
                            [ 'valor_operacion' => $valor_operacion] + 
                            [ 'valor_debito' => $valor_debito] + 
                            [ 'valor_credito' => ($valor_credito * -1) ] + 
                            [ 'valor_saldo' => ( $valor_debito - $valor_credito ) ] + 
                            [ 'teso_caja_id' => $request->teso_caja_id] + 
                            [ 'teso_cuenta_bancaria_id' => $request->teso_cuenta_bancaria_id]
                        );

            
                $motivo = TesoMotivo::find( 1 ); // Recaudo cartera
                $valor_debito = 0;
                $valor_credito = $request->valor_recaudo;

                ContabMovimiento::create( $datos + 
                                [ 'contab_cuenta_id' => $motivo->contab_cuenta_id ] +
                                [ 'detalle_operacion' => $detalle_operacion ] + 
                                [ 'valor_operacion' => $valor_operacion] + 
                                [ 'valor_debito' => $valor_debito ] + 
                                [ 'valor_credito' => ($valor_credito * -1) ] + 
                                [ 'valor_saldo' => ( $valor_debito - $valor_credito ) ]
                            );
       /* }*/
        

        return redirect('tesoreria/ver_plan_pagos/'.$request->id_libreta.'?id='.$request->url_id.'&id_modelo='.$request->url_id_modelo)->with('flash_message','Recaudo realizado correctamente.');
    }


    public function ver_recaudos($id_libreta){
        $libreta = TesoLibretasPago::find($id_libreta);
        $estudiante = Estudiante::get_datos_basicos( $libreta->id_estudiante );

        $recaudos = TesoRecaudosLibreta::where('id_libreta',$id_libreta)->get();

        $miga_pan = [
                ['url'=>'tesoreria?id='.Input::get('id'),'etiqueta'=>'Tesorería'],
                ['url'=>'web?id='.Input::get('id').'&id_modelo='.Input::get('id_modelo'),'etiqueta'=>'Libretas de pagos'],
                ['url'=>'tesoreria/ver_plan_pagos/'.$id_libreta.'?id='.Input::get('id').'&id_modelo='.Input::get('id_modelo'),'etiqueta'=>'Plan de pagos'],
                ['url'=>'NO','etiqueta'=>'Consulta Recaudos']
            ];

        $matricula = Matricula::where('estado','Activo')->where('id_estudiante',$estudiante->id)->get()[0];

        $curso = Curso::find($matricula->curso_id);

        $codigo_matricula = $matricula->codigo;

        return view('tesoreria.ver_recaudos',compact('libreta','estudiante','recaudos','miga_pan','codigo_matricula','curso'));
    }

    public function ver_plan_pagos($id_libreta)
    {
        TesoCarteraEstudiante::where('fecha_vencimiento','<', date('Y-m-d'))
                                  ->where('estado','<>', 'Pagada')
                                  ->update(['estado' => 'Vencida']);

        $libreta = TesoLibretasPago::find($id_libreta);

        $matricula_estudiante = Matricula::get_registro_impresion( $libreta->matricula_id );

        $cartera = TesoCarteraEstudiante::where('id_libreta',$id_libreta)->get();

        $miga_pan = [
                ['url'=>'tesoreria?id='.Input::get('id'),'etiqueta'=>'Tesorería'],
                ['url'=>'web?id='.Input::get('id').'&id_modelo='.Input::get('id_modelo'),'etiqueta'=>'Libretas de pagos'],
                ['url'=>'NO','etiqueta'=>'Plan de pagos']
            ];

        return view('tesoreria.ver_plan_pagos', compact('matricula_estudiante', 'libreta', 'cartera', 'miga_pan') );
    }

    public function imprimir_comprobante_recaudo($id_cartera){
        //echo $id;
        $cartera = TesoCarteraEstudiante::find($id_cartera);
        $recaudos = TesoRecaudosLibreta::where('id_cartera',$id_cartera)->get();
        //$empresa = Empresa::find(Auth::user()->empresa_id);
        $colegio = Colegio::where('empresa_id',Auth::user()->empresa_id)->get();
        $colegio = $colegio[0]; 

        $view =  View::make('tesoreria.pdf_comprobante_recaudo', compact('cartera','recaudos','colegio'))->render();
        $tam_hoja = 'Letter';
        $orientacion='portrait';

        //crear PDF
        $pdf = \App::make('dompdf.wrapper');
        $pdf->loadHTML(($view))->setPaper($tam_hoja,$orientacion);
        return $pdf->stream('comprobante_recaudo.pdf');
    }


    public function eliminar_recaudo_libreta($recaudo_id)
    {
        $recaudo = TesoRecaudosLibreta::find($recaudo_id);

        // 1ro. Borrar registros contables
        ContabMovimiento::where('core_empresa_id',Auth::user()->empresa_id)
            ->where('core_tipo_transaccion_id', $recaudo->core_tipo_transaccion_id)
            ->where('core_tipo_doc_app_id', $recaudo->core_tipo_doc_app_id)
            ->where('consecutivo', $recaudo->consecutivo)
            ->delete();

        // 2do. Eliminar movimiento de tesorería
        TesoMovimiento::where('core_empresa_id',Auth::user()->empresa_id)
            ->where('core_tipo_transaccion_id', $recaudo->core_tipo_transaccion_id)
            ->where('core_tipo_doc_app_id', $recaudo->core_tipo_doc_app_id)
            ->where('consecutivo', $recaudo->consecutivo)
            ->delete();

        // 3ro. Reversar valor que el recaudo descontó en cartera
        // Se Actualiza la cartera del estudiante
        $cartera = TesoCarteraEstudiante::find($recaudo->id_cartera);
        $nuevo_valor_pagado = $cartera->valor_pagado - $recaudo->valor_recaudo;
        $saldo_pendiente = $cartera->saldo_pendiente + $recaudo->valor_recaudo;
        $estado = $cartera->estado;
        
        if($nuevo_valor_pagado == $cartera->valor_cartera)
        {
            $estado="Pagada";
        }else{
            $estado="Pendiente";
        }
        
        $cartera->valor_pagado = $nuevo_valor_pagado;
        $cartera->saldo_pendiente = $saldo_pendiente;
        $cartera->estado = $estado;
        $cartera->save();

        // Se verifica si la libreta no tiene cartera pendiente y se inactiva
        $suma_matriculas = TesoCarteraEstudiante::where('id_libreta',$recaudo->id_libreta)->where('concepto','Matrícula')->sum('valor_pagado');
        $suma_pensiones = TesoCarteraEstudiante::where('id_libreta',$recaudo->id_libreta)->where('concepto','Pensión')->sum('valor_pagado');
        $total_pagado = $suma_matriculas + $suma_pensiones ;
        $libreta = TesoLibretasPago::find($recaudo->id_libreta);
        $total_libreta = $libreta->valor_matricula + $libreta->valor_pension_anual;
        if ($total_pagado==$total_libreta) 
        {
            $libreta->estado = "Inactivo";
        }else{
            $libreta->estado = "Activo";
        }
        $libreta->save();


        // 4to. Se elimina el recaudo
        $recaudo->delete();

        return redirect('tesoreria/ver_recaudos/'.$recaudo->id_libreta.'?id='.Input::get('id').'&id_modelo='.Input::get('id_modelo'))->with('flash_message','Recaudo Eliminado correctamente.');

   }


    // ELIMINAR LIBRETA
    public function eliminar_libreta_pagos($id)
    {
        // Verificación 1: Recaudos
        $cantidad = TesoRecaudosLibreta::where('id_libreta', $id)->count();
        if($cantidad != 0){
            return redirect( 'web?id='.Input::get('id').'&id_modelo='.Input::get('id_modelo') )->with('mensaje_error','Libreta de pagos NO puede ser eliminada. Tiene recaudos.');
        }

        // Borrar registros contables
        ContabMovimiento::where('detalle_operacion', 'LIKE', '% ID_LIBRETA'.$id.'.%')->delete();
        //Borrar Cartera asociada a la libreta
        TesoCarteraEstudiante::where('id_libreta',$id)->delete();
        //Borrar Libreta
        TesoLibretasPago::find($id)->delete();

        return redirect( 'web?id='.Input::get('id').'&id_modelo='.Input::get('id_modelo') )->with('flash_message','Libreta ELIMINADA correctamente.');
    }

    // AUMENTAR EL CONSECUTIVO Y OBTENERLO AUMENTADO
    public function get_consecutivo($core_empresa_id, $core_tipo_doc_app_id)
    {
        // Seleccionamos el consecutivo actual (si no existe, se crea) y le sumamos 1
        $consecutivo = TipoDocApp::get_consecutivo_actual($core_empresa_id, $core_tipo_doc_app_id) + 1;

        // Se incementa el consecutivo para ese tipo de documento y la empresa
        TipoDocApp::aumentar_consecutivo($core_empresa_id, $core_tipo_doc_app_id);

        return $consecutivo;
    }
}