<?php

namespace App\Http\Controllers\FacturacionElectronica;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Http\Controllers\Ventas\VentaController;
use App\Http\Controllers\Core\TransaccionController;

use Auth;
use View;
use Input;

use App\Core\EncabezadoDocumentoTransaccion;
use App\Core\TipoDocApp;
use App\Sistema\Html\BotonesAnteriorSiguiente;

use App\Inventarios\RemisionVentas;

use App\Ventas\VtasDocEncabezado;
use App\Ventas\NotaCredito;

use App\CxC\CxcAbono;

use App\Tesoreria\TesoMovimiento;

use App\FacturacionElectronica\Factura;
use App\FacturacionElectronica\Services\DocumentHeaderService;

class FacturaController extends TransaccionController
{
    protected $documento_factura;

    public function index()
    {
    	return view('facturacion_electronica.index');
    }

    public function show( $id )
    {
    	$this->set_variables_globales();

        $botones_anterior_siguiente = new BotonesAnteriorSiguiente( $this->transaccion, $id );

        $doc_encabezado = app( $this->transaccion->modelo_encabezados_documentos )->get_registro_impresion( $id );

        $doc_registros = app( $this->transaccion->modelo_registros_documentos )->get_registros_impresion( $doc_encabezado->id );

        $docs_relacionados = VtasDocEncabezado::get_documentos_relacionados( $doc_encabezado );
        $empresa = $this->empresa;
        $id_transaccion = $this->transaccion->id;

        $registros_contabilidad = TransaccionController::get_registros_contabilidad( $doc_encabezado );

        $registros_tesoreria = TesoMovimiento::get_registros_un_documento( $doc_encabezado->core_tipo_transaccion_id, $doc_encabezado->core_tipo_doc_app_id, $doc_encabezado->consecutivo )->first();
        $medios_pago = View::make('tesoreria.incluir.show_medios_pago', compact('registros_tesoreria'))->render();

        // Datos de los abonos aplicados a la factura
        $abonos = CxcAbono::get_abonos_documento( $doc_encabezado );

        // Datos de Notas Crédito aplicadas a la factura
        $notas_credito = NotaCredito::get_notas_aplicadas_factura( $doc_encabezado->id );

        $documento_vista = '';

        $miga_pan = $this->get_array_miga_pan( $this->app, $this->modelo, $doc_encabezado->documento_transaccion_prefijo_consecutivo );

        //$url_crear = $this->modelo->url_crear.$this->variables_url;
        $variables_url = '?id=' . Input::get('id') . '&id_modelo=' . Input::get('id_modelo') . '&id_transaccion=' . $id_transaccion;
        $acciones = $this->acciones_basicas_modelo( $this->modelo, $variables_url );

        $url_crear = $acciones->create;

        return view( 'facturacion_electronica.facturas.show', compact( 'id', 'botones_anterior_siguiente', 'miga_pan', 'documento_vista', 'doc_encabezado', 'registros_contabilidad','abonos','empresa','docs_relacionados','doc_registros','url_crear','id_transaccion','notas_credito','medios_pago') );

    }

    public function store( Request $request )
    {
    	$datos = $request->all();

    	// Paso 1
    	$remision = new RemisionVentas;
    	$documento_remision = $remision->crear_nueva( $datos );

    	// Paso 2
    	$datos['creado_por'] = Auth::user()->email;
    	$datos['remision_doc_encabezado_id'] = $documento_remision->id;
        $datos['estado'] = 'Sin enviar';
        $encabezado_documento = new EncabezadoDocumentoTransaccion( $request->url_id_modelo );
        $encabezado_factura = $encabezado_documento->crear_nuevo( $datos );

        $lineas_registros = json_decode( $request->lineas_registros );
        $encabezado_factura->almacenar_lineas_registros( $lineas_registros );
        
        $encabezado_factura->actualizar_valor_total();
        
        // NOTA: No se crea el movimiento de ventas, ni de tesoreria, ni de contabilidad

        $mensaje = (object)[ 'tipo'=>'flash_message', 'contenido' => 'Documento almacenado creado correctamente.' ];

        // Paso 3: Validar Resolución (secuenciales) del documento
        if ( empty( $encabezado_factura->tipo_documento_app->resolucion_facturacion->toArray() ) )
        {
            $mensaje->tipo = 'mensaje_error';
            $mensaje->contenido .= ' NOTA: El documento de factura no tiene resolución asociada.';
        }

    	return redirect( 'fe_factura/'.$encabezado_factura->id.'?id='.$request->url_id.'&id_modelo='.$request->url_id_modelo.'&id_transaccion='.$request->url_id_transaccion)->with( $mensaje->tipo, $mensaje->contenido );

    }

    // Llamado directamente
    public function enviar_factura_electronica( $id )
    {
        $encabezado_factura = Factura::find( $id );

        if ( empty( $encabezado_factura->tipo_documento_app->resolucion_facturacion->toArray() ) )
        {
            return redirect( 'fe_factura/'.$encabezado_factura->id.'?id=' . Input::get('id') .'&id_modelo='. Input::get('id_modelo') .'&id_transaccion='. Input::get('id_transaccion') )->with( 'mensaje_error', 'Documento no puede ser enviado. El prefijo ' . $encabezado_factura->tipo_documento_app->prefijo . ' no tiene una resolución asociada.');
        }

        $mensaje = $encabezado_factura->enviar_al_proveedor_tecnologico();                

        if ( $mensaje->tipo != 'mensaje_error' )
        {
            if ( $encabezado_factura->estado != 'Contabilizado - Sin enviar')
            {
                $encabezado_factura->crear_movimiento_ventas();

                // Contabilizar
                $encabezado_factura->contabilizar_movimiento_debito();
                $encabezado_factura->contabilizar_movimiento_credito();

                $encabezado_factura->crear_registro_pago();
            }
            
            $encabezado_factura->estado = 'Enviada';
            $encabezado_factura->save();
        }

        return redirect( 'fe_factura/'.$encabezado_factura->id.'?id=' . Input::get('id') .'&id_modelo='. Input::get('id_modelo') .'&id_transaccion='. Input::get('id_transaccion') )->with( $mensaje->tipo, $mensaje->contenido);
    }

    public function convertir_en_factura_electronica( $vtas_doc_encabezado_id, $parent_transaction_id )
    {
        $tipo_doc_fe = TipoDocApp::find(config('facturacion_electronica.document_type_id_default'));
        if ( empty( $tipo_doc_fe->resolucion_facturacion->toArray() ) )
        {
            return back()->with( 'mensaje_error', 'Documento no puede ser enviado. El prefijo ' . $tipo_doc_fe->prefijo . ' no tiene una resolución asociada.');
        }

        $doc_header_serv = new DocumentHeaderService();
        $result = $doc_header_serv->convert_to_electronic_invoice($vtas_doc_encabezado_id, $parent_transaction_id);

        if ( $result->status == 'mensaje_error' )
        {
            return back()->with( $result->status, $result->message);
        }

        return redirect( 'fe_factura/'. $result->new_document_header_id.'?id=21&id_modelo=244&id_transaccion=52' )->with($result->status,$result->message);
    }
    
}
