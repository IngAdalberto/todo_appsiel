<?php  
    $variables_url = '?id='.Input::get('id').'&id_modelo='.Input::get('id_modelo').'&id_transaccion='.$id_transaccion;
?>

@extends('transaccion.show')

@section('botones_acciones')
	{{ Form::bsBtnCreate( 'inventarios/create'.$variables_url ) }}
    <!-- @ if( in_array($id_transaccion, [1, 2, 3, 4, 27, 28, 35]) ) -->
        @if( !in_array( $doc_encabezado->estado, ['Anulado', 'Facturada'] ) )
            <button class="btn-gmail" id="btn_anular" title="Anular"><i class="fa fa-btn fa-close"></i></button>
        @endif
        
        @if( $doc_encabezado->estado == 'Pendiente' && Input::get('id_transaccion') == 35 )
            <a class="btn-gmail" href="{{ url('compras/create') . '?id=9&id_modelo=159&id_transaccion=25' }}" title="Crear Factura de Compras"><i class="fa fa-btn fa-file-text"></i></a>
        @endif
        
        @if( $doc_encabezado->estado == 'Pendiente' && Input::get('id_transaccion') == 24 )
            <a class="btn-gmail" href="{{ url('ventas/create') . '?id=13&id_modelo=139&id_transaccion=23' }}" title="Crear Factura de Ventas"><i class="fa fa-btn fa-file-text"></i></a>
        @endif

    <!-- @ endif -->
@endsection

@section('botones_imprimir_email')
	Formato: {{ Form::select('formato_impresion_id',['1'=>'Movimiento','2'=>'Remisión (estándar)','3'=>'Remisión (POS)'], null, [ 'id' =>'formato_impresion_id' ] ) }}
	{{ Form::bsBtnPrint( 'transaccion_print/'.$id.$variables_url.'&formato_impresion_id=1' ) }}
	{{ Form::bsBtnEmail( 'inventarios_enviar_por_email/'.$id.$variables_url.'&formato_impresion_id=1' ) }}
@endsection

@section('botones_anterior_siguiente')
	{!! $botones_anterior_siguiente->dibujar( 'inventarios/', $variables_url ) !!}
@endsection

@section('datos_adicionales_encabezado')

    {!! $enlace1 !!}
    
    {!! $enlace2 !!}

@endsection

@section('filas_adicionales_encabezado')
    <tr>
        <td colspan="2" style="border: solid 1px #ddd;">
            <b>Estado Doc.:</b> {{ $doc_encabezado->estado }}
            <br/>
            <b>Tercero:</b> {{ $doc_encabezado->tercero_nombre_completo }}
            <br/>
            <b>NIT: &nbsp;&nbsp;</b> {{ number_format( $doc_encabezado->numero_identificacion, 0, ',', '.') }}
        </td>
    </tr>
    <tr>        
        <td colspan="2" style="border: solid 1px #ddd;">
            <b>Detalle: &nbsp;&nbsp;</b> {{ $doc_encabezado->descripcion }}
        </td>
    </tr>
@endsection

@section('div_advertencia_anulacion')
	<div class="alert alert-warning" style="display: none;">
		<a href="#" id="close" class="close">&times;</a>
		<strong>Advertencia!</strong>
		<br>
		Al anular el documento se eliminan los registros del movimiento contable relacionado. La anulación no se puede revertir.
		<br>
		Si realmente quiere anular el documento, haga click en el siguiente enlace: <small> <a href="{{ url('inv_anular_documento/'.$id.$variables_url ) }}"> Anular </a> </small>
	</div>
@endsection

@section('otros_scripts')
    <script type="text/javascript">
        var validado;
        $(document).ready(function(){

            $(".btn_editar_registro").click(function(event){
                $('#contenido_modal').html('');
                $("#myModal").modal({backdrop: "static"});
                $("#div_spin").show();
                $(".btn_edit_modal").hide();

                var url = '../inv_get_formulario_edit_registro';

                $.get( url, { linea_registro_id: $(this).attr('data-linea_registro_id'), id: getParameterByName('id'), id_modelo: getParameterByName('id_modelo'), id_transaccion: getParameterByName('id_transaccion') } )
                    .done(function( data ) {

                        $('#saldo_original').val( $('#saldo_a_la_fecha').val() );
                        $('#cantidad_original').val( $('#cantidad').val() );

                        $('#contenido_modal').html(data);

                        $("#div_spin").hide();

                        $('#costo_unitario').select();

                    });             
            });

            // Al modificar el precio de compra
            $(document).on('keyup','#costo_unitario',function(event){
                
                if( validar_input_numerico( $(this) ) )
                {   

                    var x = event.which || event.keyCode;
                    if( x==13 )
                    {
                        $('#cantidad').select();                
                    }

                    $('#costo_total').val( parseFloat( $('#costo_unitario').val() ) * parseFloat( $('#cantidad').val() ));

                }else{
                    $(this).focus();
                    return false;
                }

            });

            // Al modificar el precio de compra
            $(document).on('keyup','#cantidad',function(event){
                
                if( validar_input_numerico( $(this) ) && $(this).val() > 0 )
                {
                    calcula_nuevo_saldo_a_la_fecha();

                    var x = event.which || event.keyCode;
                    if( x==13 )
                    {
                        if ( !validar_existencia_actual() )
                        {
                            $('#costo_total').val('');
                            return false;
                        }
                        $('.btn_save_modal').focus();               
                    }

                    $('#costo_total').val( parseFloat( $('#costo_unitario').val() ) * parseFloat( $('#cantidad').val() ));
                }else{
                    $('#costo_total').val('');
                    $(this).focus();
                    return false;
                }

            });

            $('.btn_save_modal').click(function(event){
                if ( $.isNumeric( $('#costo_total').val() ) && $('#costo_total').val() > 0 )
                {
                    if ( !validar_existencia_actual() )
                    {
                        $('#costo_total').val('');
                        return false;
                    }
                    validacion_saldo_movimientos_posteriores();
                }else{
                    alert('El costo total es incorrecto. Verifique lo valores ingresados.');
                }
            });

            $("#myModal").on('hide.bs.modal', function(){
                $('#popup_alerta_danger').hide();
            });


            /*
                validar_existencia_actual
            */
            function validar_existencia_actual()
            {

                // PARA ENTRADAS
                if ( $('#motivo_movimiento').val() == 'entrada' )
                {
                    if ( parseFloat( $('#saldo_a_la_fecha').val() ) < 0 ) 
                    {
                        alert('Saldo negativo a la fecha.');
                        $('#cantidad').val('');
                        $('#cantidad').focus();
                        return false;
                    }
                }

                // PARA SALIDAS
                if ( $('#motivo_movimiento').val() == 'salida' )
                {
                    if ( parseFloat( $('#saldo_a_la_fecha').val() ) < 0 ) 
                    {
                        alert('Saldo negativo a la fecha.');
                        $('#cantidad').val('');
                        $('#cantidad').focus();
                        return false;
                    }
                }
                return true;
            }
            
            function validacion_saldo_movimientos_posteriores()
            {
                var url = '../inv_validacion_saldo_movimientos_posteriores/' + $('#bodega_id').val() + '/' + $('#producto_id').val() + '/' + $('#fecha').val() + '/' + $('#cantidad').val() + '/' + $('#saldo_a_la_fecha2').val() + '/' + $('#motivo_movimiento').val();

                $.get( url )
                    .done( function( data ) {
                        if ( data != 0 )
                        {
                            $('#popup_alerta_danger').show();
                            $('#popup_alerta_danger').text( data );
                        }else{
                            $('.btn_save_modal').off( 'click' );
                            $('#form_edit').submit();
                            $('#popup_alerta_danger').hide();
                        }
                    });

            }

            function calcula_nuevo_saldo_a_la_fecha()
            {
                    
                // PARA ENTRADAS
                if ( $('#motivo_movimiento').val() == 'entrada' )
                {
                    var saldo_actual = parseFloat( $('#saldo_a_la_fecha').val() );
                    var cantidad_anterior = parseFloat( $('#cantidad_anterior').val() );
                    var nuevo_saldo = saldo_actual - cantidad_anterior + parseFloat( $('#cantidad').val() );

                    $('#saldo_a_la_fecha').val( nuevo_saldo );
                    $('#saldo_a_la_fecha2').val( nuevo_saldo );
                    $('#cantidad_anterior').val( $('#cantidad').val() );
                }

                // PARA SALIDAS
                if ( $('#motivo_movimiento').val() == 'salida' )
                {
                    var nuevo_saldo = parseFloat( $('#saldo_original').val() ) + parseFloat( $('#cantidad_original').val() ) - parseFloat( $('#cantidad').val() );

                    $('#saldo_a_la_fecha').val( nuevo_saldo );
                    $('#saldo_a_la_fecha2').val( nuevo_saldo );
                }
                
            }
        });
    </script>
@endsection