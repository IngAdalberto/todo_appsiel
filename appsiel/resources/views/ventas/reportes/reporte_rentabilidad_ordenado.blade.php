<h3 style="width: 100%; text-align: center;">
    REPORTE DE RENTABILIDAD EN VENTAS 
    <span style="background: yellow; color: red;">({!! $mensaje !!})</span> 
</h3>
<hr>

<?php 
    switch ( $agrupar_por )
    {
        case 'core_tercero_id':
            $primer_encabezado = 'Clientes';
            break;
        case 'inv_producto_id':
            $primer_encabezado = 'Productos';
            break;        
        default:
            $primer_encabezado = '';
            break;
    }
?>

<div class="table-responsive">
    <table id="myTable" class="table table-striped">
        <thead>
            <tr>
                <th> {{ $primer_encabezado }} </th>
                <th> Cantidad total </th>
                <th> Precio promedio </th>
                <th> Venta total <i class="fa fa-sort-amount-desc"></i></th>
                <th> Costo prom. </th>
                <th> Costo total </th>
                <th> Rentabilidad total </th>
                <th> % Rentabilidad </th>
            </tr>
        </thead>
        <tbody>

            <?php

                $total_cantidad_venta = 0;
                $total_volumen_venta = 0;
                $total_costo = 0; 
                $total_rentabilidad = 0; // Rentabilidad

                $array_lista = [];
                $i = 0;

                foreach( $movimiento as $campo_agrupado => $coleccion_movimiento)
                {
                    $cantidad = $coleccion_movimiento->sum('cantidad');
                    $base_impuesto_total = $coleccion_movimiento->sum('base_impuesto_total');

                    if ( $agrupar_por == 'core_tercero_id' )
                    {
                        $costo_total = $movimiento_inventarios->where( 'core_tercero_id', $coleccion_movimiento->first()->core_tercero_id )->sum('costo_total') * -1; // los movimiento de inventarios de ventas son negativos
                    }else{
                        $costo_total = $movimiento_inventarios->where( 'inv_producto_id', $coleccion_movimiento->first()->inv_producto_id )->sum('costo_total') * -1; // los movimiento de inventarios de ventas son negativos
                    }
                        

                    $array_lista[$i]['descripcion'] = $campo_agrupado;
                    
                    if ( $agrupar_por == 'core_tercero_id' )
                    {
                        $tercero = $coleccion_movimiento->first()->tercero;
                        if ( !is_null($tercero) )
                        {
                            $array_lista[$i]['descripcion'] = $tercero->descripcion;
                        }
                    }

                    $array_lista[$i]['cantidad'] = $cantidad;
                    //$array_lista[$i]['cantidad_inventario'] = $cantidad_inventario;

                    $precio = $base_impuesto_total; // Sin IVA

                    $precio_promedio = 0;
                    $costo_promedio = 0;                    
                    if( $cantidad != 0 )
                    { 
                        $precio_promedio = $precio / $cantidad;
                        $costo_promedio = $costo_total / $cantidad;
                    }

                    $array_lista[$i]['precio_promedio'] = $precio_promedio;
                    $array_lista[$i]['precio'] = $precio;

                    $array_lista[$i]['costo_promedio'] = $costo_promedio;
                    $array_lista[$i]['costo_total'] = $costo_total;

                    $array_lista[$i]['rentabilidad'] = $precio - $costo_total;

                    $total_cantidad_venta += $cantidad;
                    $total_volumen_venta += $precio;
                    $total_costo += $costo_total;
                    $total_rentabilidad += $precio - $costo_total;

                    $i++;
                }

                $columna_precio  = array_column($array_lista, 'precio');
                array_multisort($columna_precio, SORT_DESC, $array_lista);
                $cantidad_elementos = count($columna_precio);

                $j = 1;
            ?>

            @for( $i = 0; $i < $cantidad_elementos; $i++)
                
                <?php
                    $clase = '';
                    if ( $array_lista[$i]['rentabilidad'] < 0)
                    {
                        $clase = 'danger';
                    }
                ?>

                <tr class="fila-{{$j}} {{$clase}}">
                    <td> {{ $array_lista[$i]['descripcion'] }} </td>
                    <td> {{ number_format( $array_lista[$i]['cantidad'], 2, ',', '.') }} </td>
                    <td> ${{ number_format( $array_lista[$i]['precio_promedio'], 2, ',', '.') }} </td>
                    <td> ${{ number_format( $array_lista[$i]['precio'], 2, ',', '.') }} </td>
                    <td> ${{ number_format( $array_lista[$i]['costo_promedio'], 2, ',', '.') }} </td>
                    <td> ${{ number_format( $array_lista[$i]['costo_total'], 2, ',', '.') }} </td>
                    <td> ${{ number_format( $array_lista[$i]['rentabilidad'], 2, ',', '.') }} </td>
                    <?php
                        $margen_rentabilidad = 0;
                        if ( $array_lista[$i]['costo_total'] != 0)
                        {
                            $margen_rentabilidad = $array_lista[$i]['rentabilidad'] / $array_lista[$i]['costo_total'] * 100;
                        }
                    ?>
                    <td> {{ number_format( $margen_rentabilidad, 2, ',', '.') }}% </td>
                </tr>

                <?php

                    $j++;
                    if ($j==3) {
                        $j=1;
                    }
                ?>
            @endfor

            <tr style=" background-color: #67cefb; font-weight: bolder;">
                <td> </td>
                <td> {{ number_format( $total_cantidad_venta, 2, ',', '.') }} </td>
                <td> &nbsp; </td>
                <td> ${{ number_format( $total_volumen_venta, 2, ',', '.') }} </td>
                <td> &nbsp;</td>
                <td> ${{ number_format( $total_costo, 2, ',', '.') }} </td>
                <td> ${{ number_format( $total_rentabilidad, 2, ',', '.') }} </td>
                <?php
                    $margen_rentabilidad = 0;
                    if ( $total_costo != 0)
                    {
                        $margen_rentabilidad = $total_rentabilidad / $total_costo * 100;
                    }
                ?>
                <td> {{ number_format( $margen_rentabilidad, 2, ',', '.') }}% </td>
            </tr>
        </tbody>
    </table>
</div>