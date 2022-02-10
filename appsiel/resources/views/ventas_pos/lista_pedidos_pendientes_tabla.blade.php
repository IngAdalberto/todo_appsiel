<h3> Lista de pedidos pendientes</h3>
<hr>

<div class="table-responsive">
    <table class="table table-striped table-bordered">
        {{ Form::bsTableHeader( ['Fecha', 'Documento', 'Cliente', 'Vendedor', 'Total', 'Acción'] ) }}
        <tbody>
            @foreach ($pedidos as $pedido)
                <tr>
                    <td> {{ $pedido->fecha }} </td>
                    <td class="text-center"> {{ $pedido->tipo_documento_app->prefijo }} {{ $pedido->consecutivo }} </td>
                    <td> {{ $pedido->tercero->descripcion }} </td>
                    <td> {{ $pedido->vendedor->tercero->descripcion }} </td>
                    <td> ${{ number_format($pedido->valor_total,0,',','.') }} </td>
                    <td>
                        <a class="btn btn-default btn-xs btn-detail" href="{{ url( 'pos_factura_crear_desde_pedido/' . $pedido->id . '?id=20&id_modelo=230&id_transaccion=47&pdv_id=' . $pdv_id . '&action=create_from_order' ) }}" title="Facturar" ><i class="fa fa-file"></i>&nbsp;</a>
                        &nbsp;&nbsp;&nbsp;
                        <a class="btn btn-default btn-xs btn-detail" href="{{ url( 'vtas_pedidos_imprimir/' . $pedido->id . '?id=20&id_modelo=175&id_transaccion=42&formato_impresion_id=pos' ) }}" title="Imprimir" id="btn_print" target="_blank"><i class="fa fa-btn fa-print"></i>&nbsp;</a>
                        &nbsp;&nbsp;&nbsp;
                        <a class="btn btn-default btn-xs btn-detail" href="{{ url( 'vtas_pedidos/' . $pedido->id . '?id=20&id_modelo=175&id_transaccion=42' ) }}" title="Consultar" id="btn_print" target="_blank"><i class="fa fa-btn fa-eye"></i>&nbsp;</a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>