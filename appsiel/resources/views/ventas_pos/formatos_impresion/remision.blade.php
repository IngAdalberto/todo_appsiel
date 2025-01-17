
    <table border="0" style="margin-top: 0px !important;" width="100%">
        <td>
            <h5 style="text-align: center;">
                <span>--- (Copia Cocina) ---</span>
                <br>
                <b>{{ $pdv->descripcion }}</b>
            </h5>
        </td>
        <tr>
            <td>
                <b>{{ $pdv->tipo_doc_app->descripcion }} No.</b> 
                @if( !is_null( $resolucion ) )
                    {{ $resolucion->prefijo }}
                @else
                    {{ $pdv->tipo_doc_app->prefijo }}
                @endif
                <div class="lbl_consecutivo_doc_encabezado" style="display: inline;"></div>
            </td>
        </tr>
    </table>

    <div class="subheadp" >
        <b>Cliente:</b> <div class="lbl_cliente_descripcion" style="display: inline;"> {{ $pdv->cliente->tercero->descripcion }} </div> 
        <br>
        <b>Atendido por: &nbsp;&nbsp;</b> 
        <div class="lbl_atendido_por" style="display: inline;"> {{ $pdv->cliente->vendedor->tercero->descripcion }} </div>
        <br>
    </div>

    <table style="width: 100%; font-size: {{ $tamanino_fuente_2 }};" id="tabla_productos_facturados2">
        {{ Form::bsTableHeader(['Producto','Cant.']) }}
        <tbody>
        </tbody>
    </table>
    <br>
    <b> Cantidad de items&nbsp;: </b> <div style="display: inline;" id="cantidad_total_productos" ></div>
    <br>
    <b> Despachado por &nbsp;&nbsp;&nbsp;: </b> _____________________    
    <br>
    <b>Detalle: &nbsp;&nbsp;</b> <div class="lbl_descripcion_doc_encabezado" style="display: inline;"> </div>
    <br><br>