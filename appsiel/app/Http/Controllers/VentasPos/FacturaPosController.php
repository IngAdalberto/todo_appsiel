<?php

namespace App\Http\Controllers\VentasPos;

use App\Http\Controllers\Tesoreria\RecaudoController;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests;
use Auth;
use DB;
use View;
use Lava;
use Input;
use Form;


use Spatie\Permission\Models\Permission;

use App\Http\Controllers\Sistema\ModeloController;
use App\Http\Controllers\Sistema\EmailController;
use App\Http\Controllers\Core\TransaccionController;

use App\Http\Controllers\Inventarios\InventarioController;
use App\Http\Controllers\Ventas\VentaController;

use App\Http\Controllers\Contabilidad\ContabilidadController;
use App\Http\Controllers\Ventas\ReportesController;

// Objetos 
use App\Sistema\Html\TablaIngresoLineaRegistros;
use App\Sistema\Html\BotonesAnteriorSiguiente;
use App\Sistema\TipoTransaccion;

// Modelos
use App\Sistema\Modelo;
use App\Sistema\Campo;
use App\Core\Tercero;
use App\Core\TipoDocApp;


use App\Inventarios\InvDocEncabezado;
use App\Inventarios\InvDocRegistro;
use App\Inventarios\InvMovimiento;
use App\Inventarios\InvProducto;
use App\Inventarios\InvCostoPromProducto;
use App\Inventarios\InvMotivo;
use App\Inventarios\ItemDesarmeAutomatico;

use App\VentasPos\PreparaTransaccion;

use App\VentasPos\FacturaPos;
use App\VentasPos\DocRegistro;
use App\VentasPos\Movimiento;

use App\Ventas\VtasPedido;

use App\VentasPos\Pdv;

use App\Ventas\Cliente;
use App\Ventas\ResolucionFacturacion;
use App\Ventas\ListaPrecioDetalle;
use App\Ventas\ListaDctoDetalle;
use App\Ventas\VtasDocEncabezado;
use App\Ventas\NotaCredito;

use App\Ventas\VtasMovimiento;

use App\CxC\DocumentosPendientes;
use App\CxC\CxcMovimiento;
use App\CxC\CxcAbono;

use App\CxP\CxpMovimiento;

use App\Tesoreria\TesoCaja;
use App\Tesoreria\TesoCuentaBancaria;
use App\Tesoreria\TesoMovimiento;
use App\Tesoreria\TesoMotivo;

use App\Contabilidad\ContabMovimiento;
use App\Contabilidad\Impuesto;
use App\Inventarios\InvGrupo;

class FacturaPosController extends TransaccionController
{
    protected $doc_encabezado;

    /* El método index() está en TransaccionController */

    public function create()
    {
        $this->set_variables_globales();

        // Enviar valores predeterminados
        // WARNING!!!! Este motivo es de INVENTARIOS
        $motivos = ['10-salida' => 'Ventas POS'];

        $inv_motivo_id = 10;

        // Dependiendo de la transaccion se genera la tabla de ingreso de lineas de registros
        $tabla = new TablaIngresoLineaRegistros(PreparaTransaccion::get_datos_tabla_ingreso_lineas_registros($this->transaccion, $motivos));

        if (is_null($tabla)) {
            $tabla = '';
        } 

        $lista_campos = ModeloController::get_campos_modelo($this->modelo, '', 'create');
        $cantidad_campos = count($lista_campos);

        $lista_campos = ModeloController::personalizar_campos($this->transaccion->id, $this->transaccion, $lista_campos, $cantidad_campos, 'create', null);

        $modelo_controller = new ModeloController;
        $acciones = $modelo_controller->acciones_basicas_modelo($this->modelo, '');

        $user = Auth::user();

        $pdv = Pdv::find(Input::get('pdv_id'));

        //Personalización de la lista de campos
        for ($i = 0; $i < $cantidad_campos; $i++)
        {
            switch ($lista_campos[$i]['name']) {

                case 'core_tipo_doc_app_id':
                    $lista_campos[$i]['opciones'] = [$pdv->tipo_doc_app_default_id => $pdv->tipo_doc_app->prefijo . " - " . $pdv->tipo_doc_app->descripcion];
                    break;

                case 'cliente_input':
                    $lista_campos[$i]['value'] = $pdv->cliente->tercero->descripcion;
                    break;

                case 'vendedor_id':
                    //array_shift($lista_campos[$i]['opciones']);
                    $lista_campos[$i]['value'] = [$pdv->cliente->vendedor_id];
                    //$lista_campos[$i]['opciones'] = [ $pdv->cliente->vendedor->id => $pdv->cliente->vendedor->tercero->descripcion];
                    break;

                case 'fecha_vencimiento':
                    $lista_campos[$i]['value'] = date('Y-m-d');
                    break;

                case 'inv_bodega_id':
                    $lista_campos[$i]['opciones'] = [$pdv->bodega_default_id => $pdv->bodega->descripcion];
                    break;
                default:
                    # code...
                    break;
            }
        }

        $form_create = [
            'url' => $acciones->store,
            'campos' => $lista_campos
        ];
        $id_transaccion = 8; // 8 = Recaudo cartera
        $motivos = TesoMotivo::opciones_campo_select_tipo_transaccion('Recaudo cartera');
        $medios_recaudo = RecaudoController::get_medios_recaudo();
        $cajas = RecaudoController::get_cajas();
        $cuentas_bancarias = RecaudoController::get_cuentas_bancarias();

        $miga_pan = $this->get_array_miga_pan($this->app, $this->modelo, 'Crear: ' . $this->transaccion->descripcion);

        $productos = InvProducto::get_datos_basicos('', 'Activo');
        $precios = ListaPrecioDetalle::get_precios_productos_de_la_lista($pdv->cliente->lista_precios_id);
        $descuentos = ListaDctoDetalle::get_descuentos_productos_de_la_lista($pdv->cliente->lista_descuentos_id);
        $productosTemp = null;
        foreach ($productos as $pr)
        {
            $pr->categoria = InvGrupo::find($pr->inv_grupo_id)->descripcion;
            $productosTemp[$pr->categoria][] = $pr;
        }
        $vista_categorias_productos = '';//View::make('ventas_pos.lista_items2', compact('productosTemp'))->render();
        $contenido_modal = View::make('ventas_pos.lista_items', compact('productos'))->render();

        $plantilla_factura = $this->generar_plantilla_factura($pdv);


        $redondear_centena = config('ventas_pos.redondear_centena');

        return view('ventas_pos.create', compact('form_create', 'miga_pan', 'tabla', 'pdv', 'productos', 'precios', 'descuentos', 'inv_motivo_id', 'contenido_modal', 'vista_categorias_productos', 'plantilla_factura', 'redondear_centena', 'id_transaccion', 'motivos', 'medios_recaudo', 'cajas', 'cuentas_bancarias'));
    }

    /**
     * ALMACENA FACTURA POS - ES LLAMADO VÍA AJAX
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $lineas_registros = json_decode($request->lineas_registros);

        $this->actualizar_campo_lineas_registros_medios_recaudos_request($request);

        // Crear documento de Ventas
        $doc_encabezado = TransaccionController::crear_encabezado_documento($request, $request->url_id_modelo);

        if ($doc_encabezado->core_tercero_id == 0)
        {
            $pdv = Pdv::find($doc_encabezado->pdv_id);
            $doc_encabezado->core_tercero_id = $pdv->cliente->tercero->id;
            $doc_encabezado->save();
        }

        // Crear Registros del documento de ventas
        $request['creado_por'] = Auth::user()->email;
        FacturaPosController::crear_registros_documento($request, $doc_encabezado, $lineas_registros);

        if (isset($request->pedido_id))
        {
            $pedido = VtasPedido::find($request->pedido_id);
            $pedido->ventas_doc_relacionado_id = $doc_encabezado->id;
            $pedido->estado = 'Facturado';
            $pedido->save();
        }

        return $doc_encabezado->consecutivo;
    }

    public function actualizar_campo_lineas_registros_medios_recaudos_request(&$request_2)
    {
        $lineas_registros_medios_recaudos = json_decode($request_2->lineas_registros_medios_recaudos, true); // true convierte en un array asociativo al JSON

        $aux = array_pop($lineas_registros_medios_recaudos); // eliminar ultimo elemento del array

        $medios_recaudos = json_encode($lineas_registros_medios_recaudos);

        if ($medios_recaudos == "[]") {
            $pdv = Pdv::find($request_2->pdv_id);

            $request_2['lineas_registros_medios_recaudos'] = '[{"teso_medio_recaudo_id":"1-Efectivo","teso_motivo_id":"1-Recaudo clientes","teso_caja_id":"' . $pdv->caja_default_id . '-' . $pdv->caja->descripcion . '","teso_cuenta_bancaria_id":"0-","valor":"$' . ($request_2->total_efectivo_recibido - $request_2->valor_total_cambio) . '"}]';
        } else {

            // CUANDO HAY VARIOS MEDIOS DE RECAUDOS... ¿CÓMO CUADRAR CON EL TOTAL DE LA FACTURA?


            $valor = json_decode($medios_recaudos)[0]->valor;
            $request_2['lineas_registros_medios_recaudos'] = str_replace($valor, "$" . ($request_2->total_efectivo_recibido - $request_2->valor_total_cambio), $medios_recaudos);
        }
    }


    /*
        Crea los registros de un documento.
        No Devuelve nada.
    */
    public static function crear_registros_documento(Request $request, $doc_encabezado, array $lineas_registros)
    {
        // WARNING: Cuidar de no enviar campos en el request que se repitan en las lineas de registros 
        $datos = $request->all();

        $total_documento = 0;

        $cantidad_registros = count($lineas_registros);

        for ($i = 0; $i < $cantidad_registros; $i++)
        {
            if ( (int)$lineas_registros[$i]->inv_producto_id == 0)
            {
                continue; // Evitar guardar registros con productos NO validos
            }
            
            $linea_datos = ['vtas_motivo_id' => (int)$request->inv_motivo_id] +
                ['inv_producto_id' => (int)$lineas_registros[$i]->inv_producto_id] +
                ['precio_unitario' => (float)$lineas_registros[$i]->precio_unitario] +
                ['cantidad' => (float)$lineas_registros[$i]->cantidad] +
                ['precio_total' => (float)$lineas_registros[$i]->precio_total] +
                ['base_impuesto' => (float)$lineas_registros[$i]->base_impuesto] +
                ['tasa_impuesto' => (float)$lineas_registros[$i]->tasa_impuesto] +
                ['valor_impuesto' => (float)$lineas_registros[$i]->valor_impuesto] +
                ['base_impuesto_total' => (float)$lineas_registros[$i]->base_impuesto_total] +
                ['tasa_descuento' => (float)$lineas_registros[$i]->tasa_descuento] +
                ['valor_total_descuento' => (float)$lineas_registros[$i]->valor_total_descuento] +
                ['creado_por' => Auth::user()->email] +
                ['estado' => 'Pendiente'] +
                ['vtas_pos_doc_encabezado_id' => $doc_encabezado->id];

            $registro_creado = DocRegistro::create($linea_datos);

            $datos['consecutivo'] = $doc_encabezado->consecutivo;

            Movimiento::create(
                $datos +
                    $linea_datos
            );

            $total_documento += (float)$lineas_registros[$i]->precio_total;
        } // Fin por cada registro

        $doc_encabezado->valor_total = $total_documento;
        $doc_encabezado->save();

        return 0;
    }

    /**
     *
     */
    public function show($id)
    {
        $this->set_variables_globales();

        $botones_anterior_siguiente = new BotonesAnteriorSiguiente($this->transaccion, $id);

        $doc_encabezado = app($this->transaccion->modelo_encabezados_documentos)->get_registro_impresion($id);
        $doc_registros = app($this->transaccion->modelo_registros_documentos)->get_registros_impresion($doc_encabezado->id);

        $docs_relacionados = VtasDocEncabezado::get_documentos_relacionados($doc_encabezado);
        $empresa = $this->empresa;
        $id_transaccion = $this->transaccion->id;

        $registros_contabilidad = TransaccionController::get_registros_contabilidad($doc_encabezado);

        // Datos de los abonos aplicados a la factura
        $abonos = CxcAbono::get_abonos_documento($doc_encabezado);

        // Datos de Notas Crédito aplicadas a la factura
        $notas_credito = NotaCredito::get_notas_aplicadas_factura($doc_encabezado->id);

        $documento_vista = '';

        $miga_pan = $this->get_array_miga_pan($this->app, $this->modelo, $doc_encabezado->documento_transaccion_prefijo_consecutivo);

        $url_crear = $this->modelo->url_crear . $this->variables_url;

        $vista = 'ventas_pos.show';

        if ( !is_null(Input::get('vista') ) )
        {
            $vista = Input::get('vista');
        }

        return view($vista, compact('id', 'botones_anterior_siguiente', 'miga_pan', 'documento_vista', 'doc_encabezado', 'registros_contabilidad', 'abonos', 'empresa', 'docs_relacionados', 'doc_registros', 'url_crear', 'id_transaccion', 'notas_credito'));
    }


    /*
        Imprimir
    */
    public function imprimir($id)
    {
        $documento_vista = $this->generar_documento_vista($id, 'ventas.formatos_impresion.pos');

        // Se prepara el PDF
        $pdf = \App::make('dompdf.wrapper');
        $pdf->loadHTML($documento_vista); //->setPaper( $tam_hoja, $orientacion );

        return $pdf->stream($this->doc_encabezado->documento_transaccion_descripcion . ' - ' . $this->doc_encabezado->documento_transaccion_prefijo_consecutivo . '.pdf');
    }


    /*
        Generar la vista para los métodos show(), imprimir() o enviar_por_email()
    */
    public function generar_documento_vista($id, $ruta_vista)
    {
        $this->set_variables_globales();

        $this->doc_encabezado = app($this->transaccion->modelo_encabezados_documentos)->get_registro_impresion($id);

        $doc_registros = app($this->transaccion->modelo_registros_documentos)->get_registros_impresion($this->doc_encabezado->id);

        $doc_encabezado = $this->doc_encabezado;
        $empresa = $this->empresa;

        $resolucion = ResolucionFacturacion::where('tipo_doc_app_id', $doc_encabezado->core_tipo_doc_app_id)->where('estado', 'Activo')->get()->last();

        $etiquetas = $this->get_etiquetas();

        return View::make($ruta_vista, compact('doc_encabezado', 'doc_registros', 'empresa', 'resolucion', 'etiquetas'))->render();
    }



    public function generar_plantilla_factura($pdv)
    {
        $this->set_variables_globales();

        $resolucion = ResolucionFacturacion::where('tipo_doc_app_id', $pdv->tipo_doc_app_default_id)->where('estado', 'Activo')->get()->last();

        $empresa = $this->empresa;

        $etiquetas = $this->get_etiquetas();

        return View::make('ventas_pos.' . config('ventas_pos.plantilla_factura_pos_default'), compact('empresa', 'resolucion', 'etiquetas', 'pdv'))->render();
    }

    /**
     * Prepara la vista para Editar una Factura POS
     */
    public function edit($id)
    {
        $this->set_variables_globales();

        // Se obtiene el registro a modificar del modelo
        $registro = app($this->modelo->name_space)->find($id); // Encabezado FActura POS

        $lista_campos = ModeloController::get_campos_modelo($this->modelo, $registro, 'edit');

        $doc_encabezado = FacturaPos::get_registro_impresion($id);

        $cantidad = count($lista_campos);

        // Agregar al comienzo del documento
        array_unshift($lista_campos, [
            "id" => 201,
            "descripcion" => "Empresa",
            "tipo" => "personalizado",
            "name" => "encabezado",
            "opciones" => "",
            "value" => '<div style="border: solid 1px #ddd; padding-top: -20px;">
                                                            <b style="font-size: 1.6em; text-align: center; display: block;">
                                                                ' . $doc_encabezado->documento_transaccion_descripcion . '
                                                                <br/>
                                                                <b>No.</b> ' . $doc_encabezado->documento_transaccion_prefijo_consecutivo . '
                                                                <br/>
                                                                <b>Fecha:</b> ' . $doc_encabezado->fecha . '
                                                            </b>
                                                            <br/>
                                                            <b>Cliente:</b> ' . $doc_encabezado->tercero_nombre_completo . '
                                                            <br/>
                                                            <b>NIT: &nbsp;&nbsp;</b> ' . number_format($doc_encabezado->numero_identificacion, 0, ',', '.') . '
                                                        </div>',
            "atributos" => [],
            "definicion" => "",
            "requerido" => 0,
            "editable" => 1,
            "unico" => 0
        ]);


        foreach ($lista_campos as $key => $value) {
            if ($value['name'] == 'cliente_input') {
                $lista_campos[$key]['value'] = $doc_encabezado->tercero_nombre_completo;
            }
        }

        $acciones = $this->acciones_basicas_modelo($this->modelo, '?id=' . Input::get('id') . '&id_modelo=' . Input::get('id_modelo') . '&id_transaccion=' . Input::get('id_transaccion'));

        $url_action = str_replace('id_fila', $registro->id, $acciones->update);

        $form_create = [
            'url' => $url_action,
            'campos' => $lista_campos
        ];

        $miga_pan = $this->get_array_miga_pan($this->app, $this->modelo, 'Modificar: ' . $doc_encabezado->documento_transaccion_prefijo_consecutivo);

        $archivo_js = app($this->modelo->name_space)->archivo_js;

        $pdv = Pdv::find($registro->pdv_id);

        $motivos = ['10-salida' => 'Ventas POS'];
        $inv_motivo_id  = 10;

        // Dependiendo de la transaccion se genera la tabla de ingreso de lineas de registros
        $tabla = new TablaIngresoLineaRegistros(PreparaTransaccion::get_datos_tabla_ingreso_lineas_registros($this->transaccion, $motivos));

        if (is_null($tabla)) {
            $tabla = '';
        }


        $id_transaccion = 8; // 8 = Recaudo cartera
        $motivos = TesoMotivo::opciones_campo_select_tipo_transaccion('Recaudo cartera');
        $medios_recaudo = RecaudoController::get_medios_recaudo();
        $cajas = RecaudoController::get_cajas();
        $cuentas_bancarias = RecaudoController::get_cuentas_bancarias();

        $numero_linea = count($registro->lineas_registros) + 1;

        $lineas_registros = $this->armar_cuerpo_tabla_lineas_registros($registro->lineas_registros);

        $cuerpo_tabla_medios_recaudos = $this->armar_cuerpo_tabla_medios_recaudos($registro);

        $vista_medios_recaudo = View::make('tesoreria.incluir.medios_recaudos', compact('id_transaccion', 'motivos', 'medios_recaudo', 'cajas', 'cuentas_bancarias', 'cuerpo_tabla_medios_recaudos'))->render();

        $total_efectivo_recibido = $this->get_total_campo_lineas_registros(json_decode(str_replace("$", "", $registro->lineas_registros_medios_recaudos)), 'valor');

        $productos = InvProducto::get_datos_basicos('', 'Activo');
        $precios = ListaPrecioDetalle::get_precios_productos_de_la_lista($registro->cliente->lista_precios_id);
        $descuentos = ListaDctoDetalle::get_descuentos_productos_de_la_lista($registro->cliente->lista_descuentos_id);
        $productosTemp = null;
        foreach ($productos as $pr) {
            $pr->categoria = InvGrupo::find($pr->inv_grupo_id)->descripcion;
            $productosTemp[$pr->categoria][] = $pr;
        }

        $vista_categorias_productos = '';//View::make('ventas_pos.lista_items2', compact('productosTemp'))->render();
        $contenido_modal = View::make('ventas_pos.lista_items', compact('productos'))->render();

        $plantilla_factura = $this->generar_plantilla_factura($pdv);

        $redondear_centena = config('ventas_pos.redondear_centena');

        return view('ventas_pos.edit', compact('form_create', 'miga_pan', 'registro', 'archivo_js', 'url_action', 'pdv', 'inv_motivo_id', 'tabla', 'productos', 'precios', 'descuentos', 'contenido_modal', 'plantilla_factura', 'redondear_centena', 'numero_linea', 'lineas_registros', 'id_transaccion', 'motivos', 'medios_recaudo', 'cajas', 'cuentas_bancarias', 'vista_medios_recaudo', 'total_efectivo_recibido','vista_categorias_productos'));
    }



    public function armar_cuerpo_tabla_lineas_registros($lineas_registros_documento)
    {

        $cuerpo_tabla_lineas_registros = '<tbody>';
        $i = 1;
        foreach ($lineas_registros_documento as $linea) {

            $cuerpo_tabla_lineas_registros .= '<tr class="linea_registro" data-numero_linea="' . $i . '"><td style="display: none;"><div class="inv_producto_id">' . $linea->inv_producto_id . '</div></td><td style="display: none;"><div class="precio_unitario">' . $linea->precio_unitario . '</div></td><td style="display: none;"><div class="base_impuesto">' . $linea->base_impuesto . '</div></td><td style="display: none;"><div class="tasa_impuesto">' . $linea->tasa_impuesto . '</div></td><td style="display: none;"><div class="valor_impuesto">' . $linea->valor_impuesto . '</div></td><td style="display: none;"><div class="base_impuesto_total">' . $linea->base_impuesto_total . '</div></td><td style="display: none;"><div class="cantidad">' . $linea->cantidad . '</div></td><td style="display: none;"><div class="precio_total">' . $linea->precio_total . '</div></td><td style="display: none;"><div class="tasa_descuento">' . $linea->tasa_descuento . '</div></td><td style="display: none;"><div class="valor_total_descuento">' . $linea->valor_total_descuento . '</div></td><td> &nbsp; </td><td> <span style="background-color:#F7B2A3;">' . $linea->inv_producto_id . '</span> <div class="lbl_producto_descripcion" style="display: inline;"> ' . $linea->item->descripcion . ' </div> </td><td> <div style="display: inline;"> <div class="elemento_modificar" title="Doble click para modificar."> ' . $linea->cantidad . '</div> </div>  (<div class="lbl_producto_unidad_medida" style="display: inline;">' . $linea->item->unidad_medida1 . '</div>) </td><td> <div class="lbl_precio_unitario" style="display: inline;">$ ' . number_format($linea->precio_unitario, '0', ',', '.') . '</div></td><td>' . $linea->tasa_descuento . '% ( $<div class="lbl_valor_total_descuento" style="display: inline;">' . number_format($linea->valor_total_descuento, '0', ',', '.') . '</div> ) </td><td><div class="lbl_tasa_impuesto" style="display: inline;">' . $linea->tasa_impuesto . '%</div></td><td> <div class="lbl_precio_total" style="display: inline;">$ ' . number_format($linea->precio_total, '0', ',', '.') . ' </div> </td> <td><button type="button" class="btn btn-danger btn-xs btn_eliminar"><i class="fa fa-btn fa-trash"></i></button></td></tr>';
            $i++;
        }

        $cuerpo_tabla_lineas_registros .= '</tbody>';

        return $cuerpo_tabla_lineas_registros;
    }


    public function armar_cuerpo_tabla_medios_recaudos($doc_encabezado)
    {
        $cuerpo_tabla = '';
        $lineas_recaudos = json_decode($doc_encabezado->lineas_registros_medios_recaudos);

        if (!is_null($lineas_recaudos)) {
            foreach ($lineas_recaudos as $linea) {
                $medio_recaudo = explode('-', $linea->teso_medio_recaudo_id);
                $motivo = explode('-', $linea->teso_motivo_id);
                $caja = explode('-', $linea->teso_caja_id);
                $cuenta_bancaria = explode('-', $linea->teso_cuenta_bancaria_id);

                $cuerpo_tabla .= '<tr> <td> <span style="color:white;">' . $medio_recaudo[0] . '-</span>' . $medio_recaudo[1] . '</td>' .
                    '<td><span style="color:white;">' . $motivo[0] . '-</span>' . $motivo[1] . '</td>' .
                    '<td><span style="color:white;">' . $caja[0] . '-</span>' . $caja[1] . '</td>' .
                    '<td><span style="color:white;">' . $cuenta_bancaria[0] . '-</span>' . $cuenta_bancaria[1] . '</td>' .
                    '<td class="valor_total">' . $linea->valor . '</td>' .
                    '<td> <button type="button" class="btn btn-danger btn-xs btn_eliminar_linea_medio_recaudo"><i class="fa fa-btn fa-trash"></i></button> </td> </tr>';
            }
        }

        return $cuerpo_tabla;
    }


    /**
     * ACTUALIZA FACTURA POS
     *
     */
    public function update(Request $request, $id)
    {
        $lineas_registros = json_decode($request->lineas_registros);

        $this->actualizar_campo_lineas_registros_medios_recaudos_request($request);

        $doc_encabezado = FacturaPos::find($id);
        $doc_encabezado->fecha = $request->fecha;
        $doc_encabezado->descripcion = $request->descripcion;
        $doc_encabezado->vendedor_id = $request->vendedor_id;
        $doc_encabezado->lineas_registros_medios_recaudos = $request->lineas_registros_medios_recaudos;
        $doc_encabezado->valor_total = $this->get_total_campo_lineas_registros($lineas_registros, 'precio_total');
        $doc_encabezado->modificado_por = Auth::user()->email;
        $doc_encabezado->save();

        // Borrar líneas de registros anteriores
        DocRegistro::where('vtas_pos_doc_encabezado_id', $doc_encabezado->id)->delete();

        // Crear nuevamente las líneas de registros
        $request['creado_por'] = Auth::user()->email;
        $request['modificado_por'] = Auth::user()->email;
        FacturaPosController::crear_registros_documento($request, $doc_encabezado, $lineas_registros);

        return $doc_encabezado->consecutivo;
    }


    /*
        Proceso de eliminar FACTURA POS (Antes de acumulación)
    */
    public static function anular_factura_pos($doc_encabezado_id)
    {
        $factura = FacturaPos::find($doc_encabezado_id);

        $modificado_por = Auth::user()->email;

        // Se elimina el Mov. Vtas. POS
        Movimiento::where('core_tipo_transaccion_id', $factura->core_tipo_transaccion_id)
            ->where('core_tipo_doc_app_id', $factura->core_tipo_doc_app_id)
            ->where('consecutivo', $factura->consecutivo)
            ->delete();

        // Se marcan como anulados los registros del documento
        DocRegistro::where('vtas_pos_doc_encabezado_id', $factura->id)->update(['estado' => 'Anulado', 'modificado_por' => $modificado_por]);

        // Se marca como anulado el documento
        $factura->update(['estado' => 'Anulado', 'modificado_por' => $modificado_por]);

        return 1;
    }

    public static function anular_factura_acumulada(Request $request)
    {
        $factura = FacturaPos::find($request->factura_id);

        $array_wheres = [
            'core_empresa_id' => $factura->core_empresa_id,
            'core_tipo_transaccion_id' => $factura->core_tipo_transaccion_id,
            'core_tipo_doc_app_id' => $factura->core_tipo_doc_app_id,
            'consecutivo' => $factura->consecutivo
        ];

        // Verificar si la factura tiene abonos, si tiene no se puede eliminar
        $cantidad = CxcAbono::where('doc_cxc_transacc_id', $factura->core_tipo_transaccion_id)
            ->where('doc_cxc_tipo_doc_id', $factura->core_tipo_doc_app_id)
            ->where('doc_cxc_consecutivo', $factura->consecutivo)
            ->count();

        if ($cantidad != 0) {
            return redirect('pos_factura/' . $request->factura_id . '?id=' . $request->url_id . '&id_modelo=' . $request->url_id_modelo . '&id_transaccion=' . $request->url_id_transaccion)->with('mensaje_error', 'Factura NO puede ser eliminada. Se le han hecho Recaudos de CXC (Tesorería).');
        }

        $modificado_por = Auth::user()->email;

        // 1ro. Anular documento asociado de inventarios
        // Obtener las remisiones relacionadas con la factura y anularlas o dejarlas en estado Pendiente
        $ids_documentos_relacionados = explode(',', $factura->remision_doc_encabezado_id);
        $cant_registros = count($ids_documentos_relacionados);
        for ($i = 0; $i < $cant_registros; $i++) {
            $remision = InvDocEncabezado::find($ids_documentos_relacionados[$i]);
            if (!is_null($remision)) {
                if ($request->anular_remision) // anular_remision es tipo boolean
                {
                    InventarioController::anular_documento_inventarios($remision->id);
                } else {
                    $remision->update(['estado' => 'Pendiente', 'modificado_por' => $modificado_por]);
                }
            }
        }

        // 2do. Borrar registros contables del documento
        ContabMovimiento::where($array_wheres)->delete();

        // 3ro. Se elimina el documento del movimimeto de cuentas por cobrar y de tesorería
        CxcMovimiento::where($array_wheres)->delete();
        TesoMovimiento::where($array_wheres)->delete();

        // 4to. Se elimina el movimiento de ventas POS y Ventas Estándar
        Movimiento::where($array_wheres)->delete();
        VtasMovimiento::where($array_wheres)->delete();

        // 5to. Se marcan como anulados los registros del documento
        DocRegistro::where('vtas_pos_doc_encabezado_id', $factura->id)->update(['estado' => 'Anulado', 'modificado_por' => $modificado_por]);

        // 6to. Se marca como anulado el documento
        $factura->update(['estado' => 'Anulado', 'remision_doc_encabezado_id' => '', 'modificado_por' => $modificado_por]);

        return redirect('pos_factura/' . $request->factura_id . '?id=' . $request->url_id . '&id_modelo=' . $request->url_id_modelo . '&id_transaccion=' . $request->url_id_transaccion)->with('flash_message', 'Factura de ventas ANULADA correctamente.');
    }


    /*
        ACUMULAR
        => Genera movimiento de ventas
        => Genera Documentos de Remisión y movimiento de inventarios
        => Genera Movimiento de Tesorería O CxC
    */
    public function acumular($pdv_id)
    {

        $pdv = Pdv::find($pdv_id);

        $encabezados_documentos = FacturaPos::where('pdv_id', $pdv_id)->where('estado', 'Pendiente')->get();

        if (is_null($encabezados_documentos)) {
            return 1;
        }

        $this->hacer_desarme_automatico($pdv_id, $encabezados_documentos->last()->fecha); // Con la fecha de la última factura

        foreach ($encabezados_documentos as $factura) {
            if ($factura->core_tercero_id == 0) {
                $factura->core_tercero_id = $pdv->cliente->tercero->id;
            }

            $lineas_registros = DocRegistro::where('vtas_pos_doc_encabezado_id', $factura->id)->get();

            foreach ($lineas_registros as $linea) {
                $datos = $factura->toArray() + $linea->toArray();

                // Movimiento de Ventas
                $datos['estado'] = 'Activo';

                VtasMovimiento::create($datos);

                $linea->estado = 'Acumulado';
                $linea->save();
            }

            // Actualiza Movimiento POS
            Movimiento::where('core_tipo_transaccion_id', $factura->core_tipo_transaccion_id)
                ->where('core_tipo_doc_app_id', $factura->core_tipo_doc_app_id)
                ->where('consecutivo', $factura->consecutivo)
                ->update(['estado' => 'Acumulado']);

            // Crear Remisión y Mov. de inventarios
            $datos_remision = $factura->toArray();
            $datos_remision['inv_bodega_id'] = $pdv->bodega_default_id;

            $doc_remision = InventarioController::crear_encabezado_remision_ventas($datos_remision);

            InventarioController::crear_registros_remision_ventas($doc_remision, $lineas_registros);

            // Movimiento de Tesoreria ó CxC
            $datos['estado'] = 'Activo';
            FacturaPosController::crear_registro_pago($factura->forma_pago, $datos, $factura->valor_total, $factura->descripcion);

            $factura->remision_doc_encabezado_id = $doc_remision->id;
            $factura->estado = 'Acumulado';
            $factura->save();
        }

        return 1;
    }

    public function resumen_cantidades_facturadas($pdv_id, $inv_producto_id = null)
    {
        $ids_encabezados_documentos = FacturaPos::where('pdv_id', $pdv_id)
            ->where('estado', 'Pendiente')
            ->select('id')
            ->get()
            ->pluck('id')
            ->all();

        $cantidades_facturadas = DocRegistro::whereIn('vtas_pos_doc_encabezado_id', $ids_encabezados_documentos)
            ->select(DB::raw('sum(cantidad) AS cantidad_facturada'), 'inv_producto_id')
            ->groupBy('inv_producto_id')
            ->get();
        if ($inv_producto_id !== null) {
            return $cantidades_facturadas->where('inv_producto_id', $inv_producto_id)->all();
        }

        return $cantidades_facturadas;
    }

    public function hacer_desarme_automatico($pdv_id, $fecha)
    {
        $bodega_default_id = Pdv::find($pdv_id)->bodega_default_id;

        $cantidades_facturadas = $this->resumen_cantidades_facturadas($pdv_id);

        $ids_items_facturados = $cantidades_facturadas->pluck('inv_producto_id')->all();

        $items_desarme_automatico = ItemDesarmeAutomatico::whereIn('item_producir_id', $ids_items_facturados)->get();

        $parametros = config('inventarios');

        $lineas_desarme = '[{"inv_producto_id":"","Producto":"","motivo":"","costo_unitario":"","cantidad":"","costo_total":""}';

        foreach ($items_desarme_automatico as $parametros_item_desarme)
        {
            $motivo = InvMotivo::find($parametros['motivo_salida_id']);
            $cantidad_proporcional = $parametros_item_desarme->cantidad_proporcional;
            if ($cantidad_proporcional == null && $cantidad_proporcional == 0)
            {
                $cantidad_proporcional = 1;
            }

            $cantidad_facturada = $cantidades_facturadas->where('inv_producto_id', $parametros_item_desarme->item_producir_id)->sum('cantidad_facturada');

            $existencia_item_facturado = InvMovimiento::get_existencia_producto($parametros_item_desarme->item_producir_id, $bodega_default_id, $fecha);

            $cantidad_consumir = intdiv( (int)($cantidad_facturada - $existencia_item_facturado->Cantidad), $cantidad_proporcional) + 1; // La parte entera de la división más 1 unidad adicional

            $existencia_item_consumir = InvMovimiento::get_existencia_producto($parametros_item_desarme->item_consumir_id, $bodega_default_id, $fecha);

            if ($existencia_item_consumir->Cantidad == null && $existencia_item_consumir->Cantidad == 0) {
                continue;
            }

            $costo_unitario_consumir = $existencia_item_consumir->Costo / $existencia_item_consumir->Cantidad;

            $lineas_desarme .= ',{"inv_producto_id":"' . $parametros_item_desarme->item_consumir->id . '","Producto":"' . $parametros_item_desarme->item_consumir->id . ' ' . $parametros_item_desarme->item_consumir->descripcion . ' (' . $parametros_item_desarme->item_consumir->unidad_medida1 . ')","motivo":"' . $motivo->id . '-' . $motivo->descripcion . '","costo_unitario":"$' . $costo_unitario_consumir . '","cantidad":"' . $cantidad_consumir . ' UND","costo_total":"$' . ($cantidad_consumir * $costo_unitario_consumir) . '"}';

            $lineas_desarme .= ',';

            $motivo = InvMotivo::find($parametros['motivo_entrada_id']);
            $cantidad_producir = $cantidad_consumir * $cantidad_proporcional;

            $costo_unitario_producir = $costo_unitario_consumir / $cantidad_proporcional;

            $lineas_desarme .= '{"inv_producto_id":"' . $parametros_item_desarme->item_producir->id . '","Producto":"' . $parametros_item_desarme->item_producir->id . ' ' . $parametros_item_desarme->item_producir->descripcion . ' (' . $parametros_item_desarme->item_producir->unidad_medida1 . '))","motivo":"' . $motivo->id . '-' . $motivo->descripcion . '","costo_unitario":"$' . $costo_unitario_producir . '","cantidad":"' . $cantidad_producir . ' UND","costo_total":"$' . ($cantidad_producir * $costo_unitario_producir) . '"}';
        }

        $lineas_desarme .= ',{"inv_producto_id":"","Producto":"00.00","motivo":"$00.00","costo_unitario":""},{"inv_producto_id":"Agregar productos","Producto":"Agregar productos","motivo":"Agregar productos","costo_unitario":"Agregar productos","cantidad":"Agregar productos","costo_total":"Calcular costos"}]';

        $request = new Request;
        $user = Auth::user();
        $modelo_id = 25;
        $request["core_empresa_id"] = $user->empresa_id;
        $request["core_tipo_transaccion_id"] = $parametros['core_tipo_transaccion_id'];
        $request["core_tipo_doc_app_id"] = $parametros['core_tipo_doc_app_id'];
        $request["fecha"] = $fecha;
        $request["core_tercero_id"] = $parametros['core_tercero_id'];
        $request["descripcion"] = "";
        $request["documento_soporte"] = "";
        $request["inv_bodega_id"] = $bodega_default_id;
        $request["movimiento"] = $lineas_desarme;
        $request["consecutivo"] = "";
        $request["hay_productos"] = "1";
        $request["creado_por"] = $user->email;
        $request["modificado_por"] = "0";
        $request["estado"] = "Activo";
        $request["url_id"] = "8";
        $request["url_id_modelo"] = $modelo_id;
        $request["url_id_transaccion"] = $parametros['core_tipo_transaccion_id'];

        $lineas_registros = InventarioController::preparar_array_lineas_registros($request->movimiento, $request->modo_ajuste);

        InventarioController::crear_documento($request, $lineas_registros, $modelo_id);

        return 1;
    }

    /*
        CONTABILIZAR
        => Genera Movimiento Contable para:
            * Movimiento de Ventas (Ingresos e Impuestos)
            * Movimiento de Inventarios (Inventarios y Costos)
            * Movimiento de Tesorería (Caja y Bancos)
            * Movimiento de CxC (Cartera de clientes)
    */
    public function contabilizar($pdv_id)
    {
        $pdv = Pdv::find($pdv_id);

        $encabezados_documentos = FacturaPos::where('pdv_id', $pdv_id)->where('estado', 'Acumulado')->get();

        $detalle_operacion = 'Acumulación PDV: ' . $pdv->descripcion;

        foreach ($encabezados_documentos as $factura) {
            $lineas_registros = DocRegistro::where('vtas_pos_doc_encabezado_id', $factura->id)->get();

            foreach ($lineas_registros as $linea) {
                $una_linea_registro = $factura->toArray() + $linea->toArray();

                $una_linea_registro['estado'] = 'Activo';

                // Contabilizar Ventas (Ingresos e Impuestos)
                VentaController::contabilizar_movimiento_credito($una_linea_registro, $detalle_operacion);

                $linea->estado = 'Contabilizado';
                $linea->save();
            }

            // Actualiza Movimiento POS
            Movimiento::where('core_tipo_transaccion_id', $factura->core_tipo_transaccion_id)
                ->where('core_tipo_doc_app_id', $factura->core_tipo_doc_app_id)
                ->where('consecutivo', $factura->consecutivo)
                ->update(['estado' => 'Contabilizado']);

            // Contabilizar Caja y Bancos ó Cartera de clientes
            $forma_pago = $factura->forma_pago;
            $datos = $factura->toArray();
            $datos['estado'] = 'Activo';
            FacturaPosController::contabilizar_movimiento_debito($forma_pago, $datos, $datos['valor_total'], $detalle_operacion, $pdv->caja_default_id);

            // Inventarios (Inventarios y Costos)
            InventarioController::contabilizar_documento_inventario($factura->remision_doc_encabezado_id, $detalle_operacion);

            $factura->estado = 'Contabilizado';
            $factura->save();
        }

        return 1;
    }

    public static function crear_registro_pago($forma_pago, $datos, $total_documento, $detalle_operacion)
    {
        // Cargar la cuenta por cobrar (CxC)
        if ($forma_pago == 'credito') {
            $datos['modelo_referencia_tercero_index'] = 'App\Ventas\Cliente';
            $datos['referencia_tercero_id'] = $datos['cliente_id'];
            $datos['valor_documento'] = $total_documento;
            $datos['valor_pagado'] = 0;
            $datos['saldo_pendiente'] = $total_documento;
            $datos['estado'] = 'Pendiente';
            DocumentosPendientes::create($datos);
        }

        // Agregar el movimiento a tesorería
        if ($forma_pago == 'contado') {
            $lineas_recaudos = json_decode($datos['lineas_registros_medios_recaudos']);

            if (!is_null($lineas_recaudos)) //&& $datos['lineas_registros_medios_recaudos'] != '' )
            {
                foreach ($lineas_recaudos as $linea) {
                    $datos['teso_motivo_id'] = explode("-", $linea->teso_motivo_id)[0];
                    $datos['teso_caja_id'] = explode("-", $linea->teso_caja_id)[0];
                    $datos['teso_cuenta_bancaria_id'] = explode("-", $linea->teso_cuenta_bancaria_id)[0];
                    $datos['valor_movimiento'] = (float)substr($linea->valor, 1);
                    TesoMovimiento::create($datos);
                }
            } else {
                // Para viejas versiones
                $pdv = Pdv::find($datos['pdv_id']);

                $caja = TesoCaja::find($pdv->caja_default_id);
                // El motivo lo debe traer de unparÃ¡metro de la configuraciÃ³n
                $datos['teso_motivo_id'] = TesoMotivo::where('movimiento', 'entrada')->get()->first()->id;
                $datos['teso_caja_id'] = $caja->id;
                $datos['teso_cuenta_bancaria_id'] = 0;
                $datos['valor_movimiento'] = $total_documento;
                TesoMovimiento::create($datos);
            }
        }
    }

    public static function contabilizar_movimiento_debito($forma_pago, $datos, $total_documento, $detalle_operacion, $caja_banco_id = null)
    {
        /*
            WARNING. Esto debe ser un parámetro de la configuración. Si se quiere llevar la factura contado a la caja directamente o si se causa una cuenta por cobrar
        */

        if ($forma_pago == 'credito') {
            // Se resetean estos campos del registro
            $datos['inv_producto_id'] = 0;
            $datos['cantidad '] = 0;
            $datos['tasa_impuesto'] = 0;
            $datos['base_impuesto'] = 0;
            $datos['valor_impuesto'] = 0;
            $datos['inv_bodega_id'] = 0;

            // La cuenta de CARTERA se toma de la clase del cliente
            $cta_x_cobrar_id = Cliente::get_cuenta_cartera($datos['cliente_id']);
            ContabilidadController::contabilizar_registro2($datos, $cta_x_cobrar_id, $detalle_operacion, $total_documento, 0);
        }

        // Agregar el movimiento a tesorería
        if ($forma_pago == 'contado') {
            $lineas_recaudos = json_decode($datos['lineas_registros_medios_recaudos']);

            if (!is_null($lineas_recaudos)) //&& $datos['lineas_registros_medios_recaudos'] != '' )
            {
                foreach ($lineas_recaudos as $linea) {
                    $contab_cuenta_id = TesoCaja::find(1)->contab_cuenta_id;

                    $teso_caja_id = explode("-", $linea->teso_caja_id)[0];
                    if ($teso_caja_id != 0) {
                        $contab_cuenta_id = TesoCaja::find($teso_caja_id)->contab_cuenta_id;
                    }

                    $teso_cuenta_bancaria_id = explode("-", $linea->teso_cuenta_bancaria_id)[0];
                    if ($teso_cuenta_bancaria_id != 0) {
                        $contab_cuenta_id = TesoCuentaBancaria::find($teso_cuenta_bancaria_id)->contab_cuenta_id;
                    }

                    ContabilidadController::contabilizar_registro2($datos, $contab_cuenta_id, $detalle_operacion, (float)substr($linea->valor, 1), 0, $teso_caja_id, $teso_cuenta_bancaria_id);
                }
            } else {
                // Para viejas versiones
                $pdv = Pdv::find($datos['pdv_id']);

                $caja = TesoCaja::find($pdv->caja_default_id);

                $cta_caja_id = $caja->contab_cuenta_id;

                ContabilidadController::contabilizar_registro2($datos, $cta_caja_id, $detalle_operacion, $total_documento, 0, $caja->id, 0);
            }
        }
    }

    public function form_registro_ingresos_gastos($pdv_id, $id_modelo, $id_transaccion)
    {
        $pdv = Pdv::find((int)$pdv_id);

        $tipo_transaccion = TipoTransaccion::find($id_transaccion);

        $tipo_docs_app_id = $tipo_transaccion->tipos_documentos->first()->id;

        $campos = (object)[
            'core_tipo_transaccion_id' => $id_transaccion,
            'core_tipo_doc_app_id' => $tipo_docs_app_id,
            'consecutivo' => 0,
            'fecha' => date('Y-m-d'),
            'core_empresa_id' => Auth::user()->empresa_id,
            'teso_medio_recaudo_id' => 1,
            'teso_caja_id' => $pdv->caja_default_id,
            'teso_cuenta_bancaria_id' => 0,
            'estado' => 'Activo',
            'creado_por' => Auth::user()->email,
            'id_modelo' => $id_modelo
        ];

        return View::make('ventas_pos.form_registro_ingresos_gastos', compact('pdv', 'campos'))->render();
    }



    /*
        Proceso especial para generar remisiones de documentos YA acumulados
    */
    public function generar_remisiones($pdv_id)
    {
        $pdv = Pdv::find($pdv_id);

        $encabezados_documentos = FacturaPos::where('pdv_id', $pdv_id)->where('estado', 'Acumulado')->get();

        foreach ($encabezados_documentos as $factura) {
            $lineas_registros = DocRegistro::where('vtas_pos_doc_encabezado_id', $factura->id)->get();

            // Crear Remisión y Mov. de inventarios
            $datos_remision = $factura->toArray();
            $datos_remision['inv_bodega_id'] = $pdv->bodega_default_id;

            $doc_remision = InventarioController::crear_encabezado_remision_ventas($datos_remision);

            InventarioController::crear_registros_remision_ventas($doc_remision, $lineas_registros);

            $factura->remision_doc_encabezado_id = $doc_remision->id;
            $factura->save();
        }

        return 1;
    }


    public function store_registro_ingresos_gastos(Request $request)
    {

        $this->datos = $request->all();
        $this->datos['core_tercero_id'] = $request->cliente_proveedor_id;
        $this->datos['descripcion'] = $request->detalle_operacion;

        $modelo = Modelo::find($request->id_modelo);

        // Guardar encabezado del documento
        $doc_encabezado = app($modelo->name_space)->create($this->datos);

        $valor_movimiento = $request->col_valor;

        // Si se está almacenando una transacción que maneja consecutivo
        if (isset($request->consecutivo) and isset($request->core_tipo_doc_app_id)) {
            // Seleccionamos el consecutivo actual (si no existe, se crea) y le sumamos 1
            $consecutivo = TipoDocApp::get_consecutivo_actual($request->core_empresa_id, $request->core_tipo_doc_app_id) + 1;

            // Se incementa el consecutivo para ese tipo de documento y la empresa
            TipoDocApp::aumentar_consecutivo($request->core_empresa_id, $request->core_tipo_doc_app_id);

            $doc_encabezado->consecutivo = $consecutivo;
            $doc_encabezado->valor_total = $valor_movimiento;
            $doc_encabezado->save();
        }

        // Guardar registro del documentos
        $tipo_transaccion = TipoTransaccion::find($request->core_tipo_transaccion_id);
        app($tipo_transaccion->modelo_registros_documentos)->create(
            ['teso_encabezado_id' => $doc_encabezado->id] +
                ['teso_motivo_id' => $request->campo_motivos] +
                ['teso_caja_id' => $request->teso_caja_id] +
                ['core_tercero_id' => $request->cliente_proveedor_id] +
                ['detalle_operacion' => $request->detalle_operacion] +
                ['valor' => $valor_movimiento] +
                ['estado' => 'Activo']
        );

        // Guardar movimiento de tesorería
        $motivo = TesoMotivo::find($request->campo_motivos);
        $valor_movimiento_teso = $valor_movimiento;
        if ($motivo->movimiento == 'salida') {
            $valor_movimiento_teso = $valor_movimiento * -1;
        }

        $this->datos['consecutivo'] = $doc_encabezado->consecutivo;
        app($tipo_transaccion->modelo_movimientos)->create(
            $this->datos +
                ['teso_motivo_id' => $motivo->id] +
                ['teso_caja_id' => $request->teso_caja_id] +
                ['teso_cuenta_bancaria_id' => 0] +
                ['valor_movimiento' => $valor_movimiento_teso] +
                ['estado' => 'Activo']
        );


        // Guardar contabilización de tesorería, siempre CAJA
        $contab_cuenta_id = TesoCaja::find($request->teso_caja_id)->contab_cuenta_id;
        $valor_debito = $valor_movimiento;
        $valor_credito = 0;
        if ($motivo->movimiento == 'salida') {
            $valor_debito = 0;
            $valor_credito = $valor_movimiento;
        }
        $this->contabilizar_registro($contab_cuenta_id, $request->detalle_operacion, $valor_debito, $valor_credito, $request->teso_caja_id, 0);

        // Guardar contabiización contrapartida
        $contab_cuenta_id = $motivo->contab_cuenta_id;
        $valor_debito = 0;
        $valor_credito = $valor_movimiento;
        if ($motivo->movimiento == 'salida') {
            $valor_debito = $valor_movimiento;
            $valor_credito = 0;
        }
        $this->contabilizar_registro($contab_cuenta_id, $request->detalle_operacion, $valor_debito, $valor_credito);

        // Guardar otros movimientos según motivo

        // Generar CxP a favor. Saldo negativo por pagar (a favor de la empresa)
        if ($motivo->teso_tipo_motivo == 'Anticipo proveedor') {
            $this->datos['valor_documento'] = $valor_movimiento * -1;
            $this->datos['valor_pagado'] = 0;
            $this->datos['saldo_pendiente'] = $valor_movimiento * -1;
            $this->datos['fecha_vencimiento'] = $this->datos['fecha'];
            $this->datos['estado'] = 'Pendiente';
            CxpMovimiento::create($this->datos);
        }

        // Generar CxP porque se utilizó dinero de un agente externo (banco, coopertaiva, tarjeta de crédito).
        if ($motivo->teso_tipo_motivo == 'Prestamo financiero') {
            $this->datos['valor_documento'] = $valor_movimiento;
            $this->datos['valor_pagado'] = 0;
            $this->datos['saldo_pendiente'] = $valor_movimiento;
            $this->datos['fecha_vencimiento'] = $this->datos['fecha'];
            $this->datos['estado'] = 'Pendiente';
            CxpMovimiento::create($this->datos);
        }

        // Generar CxC por algún dinero prestado o anticipado a trabajadores o clientes.
        if ($motivo->teso_tipo_motivo == 'Pago anticipado') {
            $this->datos['valor_documento'] = $valor_movimiento;
            $this->datos['valor_pagado'] = 0;
            $this->datos['saldo_pendiente'] = $valor_movimiento;
            $this->datos['fecha_vencimiento'] = $this->datos['fecha'];
            $this->datos['estado'] = 'Pendiente';
            CxcMovimiento::create($this->datos);
        }

        // Generar CxC: movimiento de cartera de clientes
        if ($motivo->teso_tipo_motivo == 'Anticipo') {
            $this->datos['valor_documento'] = $valor_movimiento * -1;
            $this->datos['valor_pagado'] = 0;
            $this->datos['saldo_pendiente'] = $valor_movimiento * -1;
            $this->datos['fecha_vencimiento'] = $this->datos['fecha'];
            $this->datos['estado'] = 'Pendiente';
            CxcMovimiento::create($this->datos);
        }

        return '<h4>Registro almacenado correctamente<br><span class="text-info">Documento: ' . $doc_encabezado->tipo_documento_app->prefijo . ' ' . $doc_encabezado->consecutivo . '</span></h4><hr><a class="btn-gmail" href="' . url('/') . '/tesoreria/pagos_imprimir/' . $doc_encabezado->id . '?id=3&id_modelo=' . $request->id_modelo . '&id_transaccion=' . $request->id_transaccion . '" title="Imprimir" id="btn_print" target="_blank"><i class="fa fa-btn fa-print"></i></a>';
    }

    public function get_etiquetas()
    {
        $parametros = config('ventas');

        $encabezado = '';

        if ($parametros['encabezado_linea_1'] != '') {
            $encabezado .= $parametros['encabezado_linea_1'];
        }

        if ($parametros['encabezado_linea_2'] != '') {
            $encabezado .= '<br>' . $parametros['encabezado_linea_2'];
        }

        if ($parametros['encabezado_linea_3'] != '') {
            $encabezado .= '<br>' . $parametros['encabezado_linea_3'];
        }


        $pie_pagina = '';

        if ($parametros['pie_pagina_linea_1'] != '') {
            $pie_pagina .= $parametros['pie_pagina_linea_1'];
        }

        if ($parametros['pie_pagina_linea_2'] != '') {
            $pie_pagina .= '<br>' . $parametros['pie_pagina_linea_2'];
        }

        if ($parametros['pie_pagina_linea_3'] != '') {
            $pie_pagina .= '<br>' . $parametros['pie_pagina_linea_3'];
        }

        return ['encabezado' => $encabezado, 'pie_pagina' => $pie_pagina];
    }


    public function crear_desde_pedido($pedido_id)
    {
        $this->set_variables_globales();

        // DATOS DE LINEAS DE REGISTROS DEL PEDIDO
        $pedido = VtasPedido::find($pedido_id);
        $numero_linea = count($pedido->lineas_registros) + 1;


        // Enviar valores predeterminados
        // WARNING!!!! Este motivo es de INVENTARIOS
        $motivos = ['10-salida' => 'Ventas POS'];

        $inv_motivo_id = 10;

        // Dependiendo de la transaccion se genera la tabla de ingreso de lineas de registros
        $tabla = new TablaIngresoLineaRegistros(PreparaTransaccion::get_datos_tabla_ingreso_lineas_registros($this->transaccion, $motivos));

        if (is_null($tabla)) {
            $tabla = '';
        }

        $lista_campos = ModeloController::get_campos_modelo($this->modelo, '', 'create');
        $cantidad_campos = count($lista_campos);

        $lista_campos = ModeloController::personalizar_campos($this->transaccion->id, $this->transaccion, $lista_campos, $cantidad_campos, 'create', null);

        $modelo_controller = new ModeloController;
        $acciones = $modelo_controller->acciones_basicas_modelo($this->modelo, '');

        $user = Auth::user();

        $pdv = Pdv::find(Input::get('pdv_id'));
        $cliente = $pedido->cliente;
        //Personalización de la lista de campos
        for ($i = 0; $i < $cantidad_campos; $i++) {
            switch ($lista_campos[$i]['name']) {

                case 'core_tipo_doc_app_id':
                    $lista_campos[$i]['opciones'] = [$pdv->tipo_doc_app_default_id => $pdv->tipo_doc_app->prefijo . " - " . $pdv->tipo_doc_app->descripcion];
                    break;

                case 'cliente_input':
                    $lista_campos[$i]['value'] = $cliente->tercero->descripcion;
                    break;

                case 'vendedor_id':
                    //array_shift($lista_campos[$i]['opciones']);
                    $lista_campos[$i]['value'] = [$pedido->vendedor->id];
                    //$lista_campos[$i]['opciones'] = [ $pdv->cliente->vendedor->id => $pdv->cliente->vendedor->tercero->descripcion];
                    break;

                case 'fecha_vencimiento':
                    $lista_campos[$i]['value'] = date('Y-m-d');
                    break;

                case 'inv_bodega_id':
                    $lista_campos[$i]['opciones'] = [$pdv->bodega_default_id => $pdv->bodega->descripcion];
                    break;
                default:
                    # code...
                    break;
            }
        }

        $form_create = [
            'url' => $acciones->store,
            'campos' => $lista_campos
        ];
        $id_transaccion = 8; // 8 = Recaudo cartera
        $motivos = TesoMotivo::opciones_campo_select_tipo_transaccion('Recaudo cartera');
        $medios_recaudo = RecaudoController::get_medios_recaudo();
        $cajas = RecaudoController::get_cajas();
        $cuentas_bancarias = RecaudoController::get_cuentas_bancarias();
 
        $miga_pan = $this->get_array_miga_pan($this->app, $this->modelo, 'Crear: ' . $this->transaccion->descripcion);

        $productos = InvProducto::get_datos_basicos('', 'Activo');
        $precios = ListaPrecioDetalle::get_precios_productos_de_la_lista($cliente->lista_precios_id);
        $descuentos = ListaDctoDetalle::get_descuentos_productos_de_la_lista($cliente->lista_descuentos_id);

        $productosTemp = null;
        foreach ($productos as $pr){
            $pr->categoria = InvGrupo::find($pr->inv_grupo_id)->descripcion;
            $productosTemp[$pr->categoria][] = $pr;
        }
        $vista_categorias_productos = '';//View::make('ventas_pos.lista_items2', compact('productosTemp'))->render();
        $contenido_modal = View::make('ventas_pos.lista_items', compact('productos'))->render();

        $plantilla_factura = $this->generar_plantilla_factura($pdv);

        $redondear_centena = config('ventas_pos.redondear_centena');

        $lineas_registros = $this->armar_cuerpo_tabla_lineas_registros($pedido->lineas_registros);

        return view('ventas_pos.crear_desde_pedido', compact('form_create', 'miga_pan', 'tabla', 'pdv', 'productos', 'precios', 'descuentos', 'inv_motivo_id', 'contenido_modal', 'plantilla_factura', 'redondear_centena', 'id_transaccion', 'motivos', 'medios_recaudo', 'cajas', 'cuentas_bancarias', 'lineas_registros', 'numero_linea', 'pedido_id', 'cliente','vista_categorias_productos'));
    }
}
