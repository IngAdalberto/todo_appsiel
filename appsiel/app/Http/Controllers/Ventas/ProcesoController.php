<?php

namespace App\Http\Controllers\Ventas;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Http\Controllers\Inventarios\ProcesoController as InvProcesoController;
use App\Http\Controllers\Ventas\VentaController;
use App\Http\Controllers\Ventas\NotaCreditoController;


use App\Ventas\VtasDocEncabezado;
use App\Ventas\VtasDocRegistro;
use App\Ventas\VtasMovimiento;

use App\Inventarios\InvDocEncabezado;
use App\Inventarios\InvDocRegistro;
use App\Inventarios\InvMovimiento;
use App\Ventas\InvCostoPromProducto;

use App\Compras\ComprasMovimiento;
use App\Contabilidad\ContabMovimiento;
use App\Http\Controllers\Inventarios\InventarioController;
use App\Inventarios\InvProducto;
use App\Inventarios\RemisionVentas;
use App\Tesoreria\TesoMovimiento;
use App\Tesoreria\RegistrosMediosPago;
use App\Ventas\Cliente;
use Input;
use Symfony\Component\HttpFoundation\Request as HttpFoundationRequest;

class ProcesoController extends Controller
{
    //
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function recontabilizar_documento_factura($documento_id)
    {
        ProcesoController::recontabilizar_documento($documento_id);
        return redirect('ventas/' . $documento_id . '?id=' . Input::get('id') . '&id_modelo=' . Input::get('id_modelo') . '&id_transaccion=' . Input::get('id_transaccion'))->with('flash_message', 'Documento Recontabilizado.');
    }

    public function recontabilizar_documento_nota_credito($documento_id)
    {
        ProcesoController::recontabilizar_nota_credito($documento_id);
        return redirect('ventas/' . $documento_id . '?id=' . Input::get('id') . '&id_modelo=' . Input::get('id_modelo') . '&id_transaccion=' . Input::get('id_transaccion') . '&vista=ventas.notas_credito.show')->with('flash_message', 'Documento Recontabilizado.');
    }

    // Recontabilizar un documento dada su ID
    public static function recontabilizar_documento($documento_id)
    {
        $documento = VtasDocEncabezado::find($documento_id);

        // Recontabilizar la remisión
        /* ¿Qué hacer cuando tiene varias remisiones?
        if ( $documento->remision_doc_encabezado_id != 0)
        {
            InvProcesoController::recontabilizar_documento( $documento->remision_doc_encabezado_id );
        }
        */

        // Eliminar registros contables actuales
        ContabMovimiento::where('core_tipo_transaccion_id', $documento->core_tipo_transaccion_id)
            ->where('core_tipo_doc_app_id', $documento->core_tipo_doc_app_id)
            ->where('consecutivo', $documento->consecutivo)
            ->delete();

        // Obtener líneas de registros del documento
        $registros_documento = VtasDocRegistro::where('vtas_doc_encabezado_id', $documento->id)->get();

        $total_documento = 0;
        $n = 1;
        foreach ($registros_documento as $linea) {
            $detalle_operacion = 'Recontabilizado. ' . $linea->descripcion;
            VentaController::contabilizar_movimiento_credito($documento->toArray() + $linea->toArray(), $detalle_operacion);
            $total_documento += $linea->precio_total;
            $n++;
        }

        $forma_pago = $documento->forma_pago;

        $datos = $documento->toArray();
        $datos['registros_medio_pago'] = [];
        if ($forma_pago == 'contado') {
            $datos['registros_medio_pago'] = ProcesoController::get_lineas_medios_recaudos($documento);
        }

        VentaController::contabilizar_movimiento_debito($forma_pago, $datos, $total_documento, $detalle_operacion);/**/
    }

    public static function get_lineas_medios_recaudos($documento)
    {
        $registro = TesoMovimiento::get_registros_un_documento($documento->core_tipo_transaccion_id, $documento->core_tipo_doc_app_id, $documento->consecutivo)->first();

        $medio_recaudo = 'Efectivo'; // MUY MANUAL
        $motivo = '1-Recaudo clientes'; // MUY MANUAL
        $caja = (object)['descripcion' => ''];
        if ($registro->teso_caja_id != 0) {
            $medio_recaudo = 'Cuenta bancaria'; // MUY MANUAL
            $caja = $registro->caja;
        }

        $cuenta_bancaria = (object)['descripcion' => ''];
        if ($registro->teso_cuenta_bancaria_id != 0) {
            $cuenta_bancaria = $registro->cuenta_bancaria;
            $motivo = '5-Pago a proveedores'; // MUY MANUAL
        }

        $campo_lineas_recaudos = json_decode('[{"teso_medio_recaudo_id":"1-' . $medio_recaudo . '","teso_motivo_id":"' . $motivo . '","teso_caja_id":"' . $registro->teso_caja_id . '-' . $caja->descripcion . '","teso_cuenta_bancaria_id":"' . $registro->teso_cuenta_bancaria_id . '-' . $cuenta_bancaria->descripcion . '","valor":"$' . $registro->valor_movimiento . '"}]');

        $registros_medio_pago = new RegistrosMediosPago;
        return $registros_medio_pago->get_datos_ids($campo_lineas_recaudos);
    }


    /*
     * RECONTABILIZACION FACTURAS DE VENTAS
     */
    public function recontabilizar_documentos_ventas()
    {
        $fecha_desde = Input::get('fecha_desde'); //'2019-10-28';
        $fecha_hasta = Input::get('fecha_hasta'); //'2019-10-28';

        if (is_null($fecha_desde) || is_null($fecha_hasta)) {
            echo 'Se deben enviar las fechas como parámetros en la url. <br> Ejemplo: <br> recontabilizar_documentos_ventas?fecha_desde=2019-10-28&fecha_hasta=2019-10-28';
            dd('Operación cancelada.');
        }

        // Obtener TODOS los documentos entre las fechas indicadas
        $documentos = VtasDocEncabezado::where('estado', '<>', 'Anulado')
            ->whereIn('core_tipo_transaccion_id', [23]) // 23 = Facturas de ventas
            ->whereBetween('fecha', [$fecha_desde, $fecha_hasta])
            ->get();

        $i = 1;
        foreach ($documentos as $un_documento) {
            ProcesoController::recontabilizar_documento($un_documento->id);
            echo $i . '  ';
            $i++;
        }

        echo '<br>Se Recontabilizaron ' . ($i - 1) . ' documentos de ventas.'; //con sus repectivas remisiones
    }

    /*
     * RECONTABILIZACION NOTAS CRÉDITO DE VENTAS
     */
    public function recontabilizar_notas_creditos_ventas()
    {
        $fecha_desde = Input::get('fecha_desde'); //'2019-10-28';
        $fecha_hasta = Input::get('fecha_hasta'); //'2019-10-28';

        if (is_null($fecha_desde) || is_null($fecha_hasta)) {
            echo 'Se deben enviar las fechas como parámetros en la url. <br> Ejemplo: <br> recontabilizar_documentos_ventas?fecha_desde=2019-10-28&fecha_hasta=2019-10-28';
            dd('Operación cancelada.');
        }

        // Obtener TODOS los documentos entre las fechas indicadas
        $documentos = VtasDocEncabezado::where('estado', '<>', 'Anulado')
            ->whereIn('core_tipo_transaccion_id', [38, 41]) // Nota crédito y NC Directa 
            ->whereBetween('fecha', [$fecha_desde, $fecha_hasta])
            ->get();

        $i = 1;
        foreach ($documentos as $un_documento) {
            ProcesoController::recontabilizar_nota_credito($un_documento->id);
            echo $i . '  ';
            $i++;
        }

        echo '<br>Se Recontabilizaron ' . ($i - 1) . ' documentos de ventas con sus repectivas remisiones.';
    }

    // Recontabilizar una NOTA CRÉDITO dada su ID
    public static function recontabilizar_nota_credito($documento_id)
    {
        $documento = VtasDocEncabezado::find($documento_id);

        // Recontabilizar la devolución
        /* ¿Qué hacer cuando tiene varias devoluciones?
        if ( $documento->remision_doc_encabezado_id != 0)
        {
            InvProcesoController::recontabilizar_documento( $documento->remision_doc_encabezado_id );
        }
        */


        // Eliminar registros contables actuales
        ContabMovimiento::where('core_tipo_transaccion_id', $documento->core_tipo_transaccion_id)
            ->where('core_tipo_doc_app_id', $documento->core_tipo_doc_app_id)
            ->where('consecutivo', $documento->consecutivo)
            ->delete();

        // Obtener líneas de registros del documento
        $registros_documento = VtasDocRegistro::where('vtas_doc_encabezado_id', $documento->id)->get();

        $total_documento = 0;
        $n = 1;
        foreach ($registros_documento as $linea) {
            $detalle_operacion = 'Recontabilizado. ' . $linea->descripcion;
            NotaCreditoController::contabilizar_movimiento_debito($documento->toArray() + $linea->toArray(), $detalle_operacion);
            $total_documento += $linea->precio_total;
            $n++;
        }

        NotaCreditoController::contabilizar_movimiento_credito($documento->toArray(), $total_documento, $detalle_operacion);/**/
    }



    public function actualizar_valor_total_vtas_encabezados_doc()
    {
        $documentos = VtasDocEncabezado::all();

        $i = 1;
        foreach ($documentos as $un_documento) {
            $valor_total = VtasDocRegistro::where('vtas_doc_encabezado_id', $un_documento->id)->sum('precio_total');
            $un_documento->valor_total = $valor_total;
            $un_documento->save();
            echo $i . '  ';
            $i++;
        }

        echo '<br>Se actualizaron ' . ($i - 1) . ' documentos.';
    }

    //Conecta los procesos de cotizacion, pedidos, remisiones y facturas
    public function conexion_procesos(Request $request)
    {
        // Desde cotizacion ==> 1: Pedido - 2: Pedido y Remisión - 3: Solo Remisión
        $response = null;
        $encabezado = VtasDocEncabezado::find($request->modelo); //el documento desde donde se inicia el proceso
        $source = $request->source; //de donde viene la transaccion
        $url = $request->url; //URL origen de la transaccion
        switch ($request->generar) {
            case '1':
                $response = $response . $this->soloPedido($encabezado, $request);
                break;
            case '2':
                $response = $response . $this->pedido_remision($encabezado, $request);
                break;
            case '3':
                $response = $response . $this->solo_remision($encabezado, $request);
                break;
        }
        return redirect($url)->with('flash_message', $response);
    }

    //crea solo pedido
    public function soloPedido($cotizacion, $request)
    {
        $pedido_id = $this->pedidoStore($cotizacion, $request);
        if ($pedido_id > 0) {
            $cotizacion->estado = 'Cumplido';
            $cotizacion->save();
            $pedido = VtasDocEncabezado::find($pedido_id);
            $pedido->ventas_doc_relacionado_id = $cotizacion->id;
            $pedido->save();
            return "<br>[OK] Pedido almacenado con exito";
        } else {
            return "<br>[XX] El pedido no pudo ser almacenado";
        }
    }

    //pedido y remision
    public function pedido_remision($cotizacion, $request)
    {
        $pedido_id = $this->pedidoStore($cotizacion, $request);
        if ($pedido_id > 0) 
        {
            $cotizacion->estado = 'Cumplido';
            $cotizacion->save();
            $pedido = VtasDocEncabezado::find($pedido_id);
            $pedido->ventas_doc_relacionado_id = $cotizacion->id;
            $pedido->save();
            //remision
            $remision_id = $this->remisionStore($pedido, $request);
            if ($remision_id > 0)
            {
                $pedido->estado = 'Cumplido';
                $pedido->save();
                return "<br>[OK] Pedido y remisión almacenados con exito";
            } else {
                return "<br>[XX] El pedido fue creado con exito, sin embargo la remisión no pudo ser almacenada. Proceda a crearla desde el pedido o manualmente";
            }
        } else {
            return "<br>[XX] El pedido no pudo ser almacenado, el proceso fue interrumpido";
        }
    }

    // Solo remision
    public function solo_remision( $encabezado_cotizacion )
    {
        $datos_remision = $encabezado_cotizacion->toArray();
        $datos_remision['inv_bodega_id'] = $encabezado_cotizacion->cliente->inv_bodega_id;

        $doc_remision = InventarioController::crear_encabezado_remision_ventas($datos_remision, 'Pendiente');

        $lineas_registros = VtasDocRegistro::where( 'vtas_doc_encabezado_id', $encabezado_cotizacion->id )->get();
        InventarioController::crear_registros_remision_ventas($doc_remision, $lineas_registros);

        InventarioController::contabilizar_documento_inventario( $doc_remision->id, '' );

        if ( $doc_remision->id > 0 )
        {
            $encabezado_cotizacion->estado = 'Remisionada';
            //$encabezado_cotizacion->remision_doc_encabezado_id = $doc_remision->id;
            $encabezado_cotizacion->save();
            return "<br>[OK] Remisión almacenada con exito.";
        } else {
            return "<br>[XX] La remisión no pudo ser almacenada. Proceda a crearla desde el pedido o manualmente";
        }
    }

    //crea remision
    public function remisionStore($pedido, $request)
    {
        $registros = VtasDocRegistro::where('vtas_doc_encabezado_id', $pedido->id)->get();
        $url_id = explode("=", explode("&", $request->url)[0])[1];
        $cliente = Cliente::find($pedido->cliente_id);
        $lineas = null;
        foreach ($registros as $r) {
            $prod = InvProducto::find($r->inv_producto_id);
            $lineas[] = [
                'inv_motivo_id' => 17,
                'inv_producto_id' => $r->inv_producto_id,
                'Producto' => $prod->id . " - " . $prod->descripcion . " (" . $prod->unidad_medida1 . ")",
                'motivo' => '17-Remisión de ventas',
                'cantidad' => $r->cantidad,
                'costo_unitario' => $r->precio_unitario,
                'costo_total' => $r->precio_total
            ];
        }
        $data = [
            'core_empresa_id' => $pedido->core_empresa_id,
            'core_tipo_doc_app_id' => 7,
            'fecha' => $pedido->fecha,
            'core_tercero_id' => $pedido->core_tercero_id,
            'descripcion' => $pedido->descripcion . " - (REMISIÓN GENERADA)",
            'documento_soporte' => '',
            'estado' => 'Pendiente',
            'modificado_por' => '0',
            'consecutivo' => '',
            'hay_productos' => count($registros),
            'core_tipo_transaccion_id' => 24,
            'creado_por' => $pedido->creado_por,
            'url_id' => $url_id,
            'url_id_modelo' => 164,
            'url_id_transaccion' => 24,
            'inv_bodega_id' => $cliente->inv_bodega_id,
            'movimiento' => json_encode($lineas)
        ];
        $request->request->add($data);
        $lineas_registros = InventarioController::preparar_array_lineas_registros($request->movimiento, null);
        return InventarioController::crear_documento($request, $lineas_registros, 164);
    }

    //crea un pedido
    public function pedidoStore($cotizacion, $request)
    {
        $url_id = explode("=", explode("&", $request->url)[0])[1];
        $url_id_modelo = explode("=", explode("&", $request->url)[1])[1];
        $cliente = Cliente::find($cotizacion->cliente_id);
        $registros = VtasDocRegistro::where('vtas_doc_encabezado_id', $cotizacion->id)->get();
        $lineas_registros = null;
        foreach ($registros as $r) {
            $lineas_registros[] = [
                'inv_motivo_id' => $r->vtas_motivo_id,
                'inv_bodega_id' => $cliente->inv_bodega_id,
                'inv_producto_id' => $r->inv_producto_id,
                'costo_unitario' => '0', //pendiente
                'precio_unitario' => $r->precio_unitario,
                'base_impuesto' => $r->base_impuesto,
                'tasa_impuesto' => $r->tasa_impuesto,
                'valor_impuesto' => $r->valor_impuesto,
                'base_impuesto_total' => $r->base_impuesto_total,
                'cantidad' => $r->cantidad,
                'costo_total' => '0', //pendiente
                'precio_total' => $r->precio_total,
                'tasa_descuento' => $r->tasa_descuento,
                'valor_total_descuento' => $r->valor_total_descuento
            ];
        }
        $data = [
            'core_tipo_doc_app_id' => 41,
            'core_empresa_id' => $cotizacion->core_empresa_id,
            'fecha' => $cotizacion->fecha,
            'fecha_entrega' => $cotizacion->fecha_entrega,
            'cliente_input' => '',
            'descripcion' => $cotizacion->descripcion . " (PEDIDO GENERADO)",
            'core_tipo_transaccion_id' => 42,
            'consecutivo' => '',
            'url_id' => $url_id,
            'url_id_modelo' => 175,
            'url_id_transaccion' => 42,
            'inv_bodega_id_aux' => '',
            'vendedor_id' => $cotizacion->vendedor_id,
            'forma_pago' => $cotizacion->forma_pago,
            'fecha_vencimiento' => $cotizacion->fecha_vencimiento,
            'inv_bodega_id' => $cliente->inv_bodega_id,
            'cliente_id' => $cotizacion->cliente_id,
            'zona_id' => $cliente->zona_id,
            'clase_cliente_id' => $cliente->clase_cliente_id,
            'equipo_ventas_id' => '', //pendiente
            'core_tercero_id' => $cotizacion->core_tercero_id,
            'lista_precios_id' => $cliente->lista_precios_id,
            'lista_descuentos_id' => $cliente->lista_descuentos_id,
            'liquida_impuestos' => $cliente->liquida_impuestos,
            'lineas_registros' => json_encode($lineas_registros),
            'estado' => 'Pendiente',
            'tipo_transaccion' => 'factura_directa',
            'rm_tipo_transaccion_id' => config('ventas.rm_tipo_transaccion_id'),
            'dvc_tipo_transaccion_id' => config('ventas.dvc_tipo_transaccion_id')
        ];
        $request->request->add($data);
        $lineas_registros2 = json_decode($request->lineas_registros);
        // 2do. Crear documento de Ventas
        $pc = new PedidoController();
        return $pc->crear_documento($request, $lineas_registros2, $url_id_modelo);
    }

    //valida si un valor se encuentra en el arreglo
    public function valueInArray($array, $value)
    {
        $esta = false;
        foreach ($array as $a) {
            if ($a == $value) {
                $esta = true;
            }
        }
        return $esta;
    }
}
