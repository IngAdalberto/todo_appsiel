<div class="table-responsive">
    <table class="table table-bordered table-striped" style="font-size: 15px; border: 1px solid; border-collapse: collapse;" border="1" width="100%" id="myTable">

            <tr style="background: #ccc; font-weight: bold; text-align: center;">
                <td> Cód. </td>
                <td> Producto </td>
                <td> Bodega </td>
                <td> Cantidad </td>
                <td> Costo Prom. </td>
                <td> Costo Total </td>
            </tr>

            <?php 
            $total_cantidad=0;
            $total_costo_total=0;
            for($i=0;$i<count($productos);$i++){

                    $productos[$i]['Cantidad'] = round($productos[$i]['Cantidad'],2);

                    $costo_promedio = 0;

                    if( $productos[$i]['Cantidad'] != 0)
                    {
                        $costo_promedio = $productos[$i]['Costo'] / $productos[$i]['Cantidad'];
                    }else{
                        $productos[$i]['Costo'] = 0;
                    }

                    $diferencia_costo_prom = 0;//$productos[$i]['costo_promedio_ponderado'] -  $costo_promedio;

                    $alerta = '';
                    if ( 10 <= $diferencia_costo_prom || $diferencia_costo_prom <= -10 )
                    {
                        $alerta = '<i class="fa fa-warning" title="Direfencia de $'.number_format( $diferencia_costo_prom, 2, ',', '.').'"></i>';
                    }
                ?>
                @if( $productos[$i]['id'] != 0 )
    	            <tr>
    	                <td>{{ $productos[$i]['id'] }}</td>
    	                <td>{{ $productos[$i]['descripcion'] }} ({{ $productos[$i]['unidad_medida1'] }})</td>
                        <td> {{ $productos[$i]['bodega'] }} </td>
    	                <td>{{ number_format($productos[$i]['Cantidad'], 2, ',', '.') }} </td>
                        <td>${{ number_format( $costo_promedio, 2, ',', '.') }}</td>
                        <td>${{ number_format( $productos[$i]['Costo'], 2, ',', '.') }}</td>
    	            </tr>
                @else
                    <tr style="background: #4a4a4a; color: white;">
                        <td colspan="3"> &nbsp; </td>
                        <td>{{ number_format($productos[$i]['Cantidad'], 2, ',', '.') }} </td>
                        <td>${{ number_format( $costo_promedio, 2, ',', '.') }}</td>
                        <td>${{ number_format( $productos[$i]['Costo'], 2, ',', '.') }}</td>
                    </tr>
                @endif
            <?php 
                if( $productos[$i]['id'] != 0 )
                {
                    $total_cantidad+= $productos[$i]['Cantidad'];
                    $total_costo_total+= $productos[$i]['Costo'];
                }
            } ?>
            <tr>
                <td colspan="3"> &nbsp; </td>
                <td> {{ number_format($total_cantidad, 2, ',', '.') }} </td>
                <td> &nbsp; </td>
                <td> {{ number_format($total_costo_total, 2, ',', '.') }} </td>
            </tr>
    </table>
</div>