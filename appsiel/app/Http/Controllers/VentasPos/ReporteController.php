<?php

namespace App\Http\Controllers\VentasPos;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests;
use Auth;
use DB;

// Modelos
use App\VentasPos\Pdv;
use App\VentasPos\FacturaPos;


use App\Tesoreria\TesoCaja;

use App\Tesoreria\TesoMovimiento;

use App\Ventas\VtasPedido;
use App\VentasPos\Movimiento;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\View;

class ReporteController extends Controller
{

    public function get_saldos_caja_pdv( $pdv_id, $fecha_desde, $fecha_hasta )
    {
        $pdv = Pdv::find( $pdv_id );

        $encabezados_documentos = FacturaPos::where('pdv_id',$pdv_id)->where('estado','Pendiente')->get();

        $total_contado = $encabezados_documentos->where('forma_pago','contado')->sum('valor_total');
        $total_credito = $encabezados_documentos->where('forma_pago','credito')->sum('valor_total');

        $resumen_ventas = View::make( 'ventas_pos.resumen_ventas', compact( 'total_contado', 'total_credito' ) )->render();
        
        $vista_movimiento = $this->teso_movimiento_caja_pdv( $fecha_desde, $fecha_hasta, $pdv->caja_default_id );

        return $resumen_ventas . '<br><br>' . $vista_movimiento;

    }

    public function consultar_documentos_pendientes( $pdv_id, $fecha_desde, $fecha_hasta )
    {
        $pdv = Pdv::find( $pdv_id );

        $encabezados_documentos = FacturaPos::consultar_encabezados_documentos( $pdv_id, $fecha_desde, $fecha_hasta );

        $encabezados_documentos2 = FacturaPos::where( 'pdv_id', $pdv_id)->where( 'estado', 'Pendiente')->whereBetween( 'fecha', [$fecha_desde, $fecha_hasta] )->get();

        $view = Input::get('view');

        //$this->resumen_por_medios_recaudos( $encabezados_documentos2 );

        $tabla_encabezados_documentos = View::make( 'ventas_pos.tabla_encabezados_documentos', compact( 'encabezados_documentos', 'pdv','view' ) )->render();
        
        return $tabla_encabezados_documentos;

    }


    public function resumen_por_medios_recaudos( $encabezados_documentos )
    {
        foreach ( $encabezados_documentos as $documento )
        {
            $array_totales = $this->get_total_por_medios_recaudos( $documento->lineas_registros_medios_recaudos );
            dd( $array_totales[0] );
        }
    }

    public function get_total_por_medios_recaudos( $lineas_registros_medios_recaudos )
    {
        $array_totales = [];
        $lineas_recaudos = json_decode( $lineas_registros_medios_recaudos );

        if ( !is_null( $lineas_recaudos ) )
        {
            $i = 0;
            foreach( $lineas_recaudos as $linea )
            {
                /*$array_totales[$i]['medio_recaudo'] = explode("-", $linea->teso_medio_recaudo_id)[1];
                $array_totales[$i]['total'] = (float)substr($linea->valor, 1);
                $i++;*/
                $array_totales[] = collect( ['medio_recaudo' => explode("-", $linea->teso_medio_recaudo_id)[1], 'total' => (float)substr($linea->valor, 1) ] );
            }
        }

        return $array_totales;

    }

    public function teso_movimiento_caja_pdv( $fecha_desde, $fecha_hasta, $teso_caja_id )
    {
        $teso_cuenta_bancaria_id = 0;

        $caja = TesoCaja::find( $teso_caja_id );
        $mensaje = $caja->descripcion;

        $saldo_inicial = TesoMovimiento::get_saldo_inicial( $teso_caja_id, $teso_cuenta_bancaria_id, $fecha_desde );

        $movimiento = TesoMovimiento::get_movimiento( $teso_caja_id, $teso_cuenta_bancaria_id, $fecha_desde, $fecha_hasta );

        $vista = View::make('tesoreria.reportes.movimiento_caja_bancos', compact( 'fecha_desde', 'fecha_hasta', 'saldo_inicial', 'movimiento', 'mensaje'))->render();

        return $vista;
    }

    public function revisar_pedidos_ventas( $pdv_id )
    {
        $pedidos = VtasPedido::where( 'estado', 'Pendiente' )
            ->orderBy('fecha','DESC')
            ->orderBy('consecutivo','DESC')->get();

        return View::make( 'ventas_pos.lista_pedidos_pendientes_tabla', compact( 'pedidos', 'pdv_id' ) )->render();

    }

    public function movimientos_ventas(Request $request)
    {
        $fecha_desde = $request->fecha_desde;
        $fecha_hasta  = $request->fecha_hasta;

        $agrupar_por = $request->agrupar_por;
        $detalla_productos  = (int)$request->detalla_productos;
        $detalla_clientes  = (int)$request->detalla_clientes;
        $iva_incluido  = (int)$request->iva_incluido;

        $movimiento = Movimiento::get_movimiento_ventas($fecha_desde, $fecha_hasta, $agrupar_por,$request->estado_facturas);

        // En el movimiento se trae el precio_total con IVA incluido
        $mensaje = 'IVA Incluido en precio';
        if ( !$iva_incluido )
        {
            $mensaje = 'IVA <b>NO</b> incluido en precio';
        }

        $vista = View::make('ventas_pos.reportes.reporte_ventas_ordenado', compact('movimiento','agrupar_por','mensaje','iva_incluido','detalla_productos'))->render();

        Cache::forever('pdf_reporte_' . json_decode($request->reporte_instancia)->id, $vista);

        return $vista;
    }
}
