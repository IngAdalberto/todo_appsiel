<?php
	$variables_url = '?id=' . Input::get('id') . '&id_modelo=' . Input::get('id_modelo') . '&id_transaccion=' . $id_transaccion;

	//dd( $doc_encabezado->lineas_registros->sum('cantidad_pendiente') );
?>

@extends('transaccion.show')

@section('botones_acciones')

	{{ Form::bsBtnCreate( 'vtas_pedidos/create'.$variables_url ) }}
	
	@if($doc_encabezado->estado != 'Anulado' && $doc_encabezado->estado=='Pendiente')
		<!--{{ Form::bsBtnEdit2(str_replace('id_fila', $id, 'vtas_pedidos/id_fila/edit'.$variables_url ),'Editar') }}-->
		<button class="btn-gmail" id="btn_anular" title="Anular"><i class="fa fa-btn fa-close"></i></button>
	@endif

@endsection

@section('botones_imprimir_email')
Formato: {{ Form::select('formato_impresion_id',['pos'=>'POS','estandar'=>'Estándar','estandar2'=>'Estándar v2'],null, [ 'id' =>'formato_impresion_id' ]) }}
{{ Form::bsBtnPrint( 'vtas_pedidos_imprimir/'.$id.$variables_url.'&formato_impresion_id=pos' ) }}
{{ Form::bsBtnEmail( 'vtas_pedidos_enviar_por_email/'.$id.$variables_url.'&formato_impresion_id=1' ) }}
@endsection

@section('botones_anterior_siguiente')
{!! $botones_anterior_siguiente->dibujar( 'vtas_pedidos/', $variables_url ) !!}
@endsection

@section('cabecera')
	@if( $doc_encabezado->lineas_registros->sum('cantidad_pendiente') != 0 )
		<div class="col-md-12">
			<form class="form-control" method="post" action="{{route('ventas.conexion_procesos')}}">
				<input type="hidden" name="url" value="vtas_pedidos/{{$doc_encabezado->id.$variables_url}}" />
				<input type="hidden" name="modelo" value="{{$doc_encabezado->id}}" />
				<input type="hidden" name="source" value="PEDIDO" />
				{{ csrf_field() }}
				<label class="control-label">Genere de forma automática su remisión o remisión y factura <i class="fa fa-arrow-down" aria-hidden="true"></i></label>
				<div class="row">
					<div class="col-md-3 col-lg-3 col-xl-1">
							{{ Form::bsFecha('fecha',date('Y-m-d'),'Fecha', null,[]) }}
					</div>
					<div class="col-md-2">
						{{ Form::select( 'generar', [ 'remision_desde_pedido' => 'Remisión', 'remision_y_factura_desde_pedido' => 'Remisión y Factura' ], null, ['class'=>'form-control select2','required'=>'required', 'id' =>'generar']) }}
					</div>
					<div class="col-md-1 col-lg-2">
						<button type="submit" class="btn btn-primary btn-block">GENERAR</button>
					</div>
				</div>
					
			</form>
		</div>
		<div id="div_advertencia_factura" style="display: none; color: red;" class="container-fluid">
			Nota: La condición de pago (Crédito o Contado) de la factura será tomada de los datos del cliente.
		</div>
	@endif
@endsection

@section('datos_adicionales_encabezado')
	<br />
	<b>Fecha Entrega: </b> {{ explode(' ', $doc_encabezado->fecha_entrega )[0] }} <!-- -->
	@if( !is_null( $doc_encabezado->documento_ventas_padre() ) )
		<br>
		<b>{{ $doc_encabezado->documento_ventas_padre()->tipo_transaccion->descripcion }}: &nbsp;&nbsp;</b> {!! $doc_encabezado->documento_ventas_padre()->enlace_show_documento() !!}
	@endif
	@if( !is_null( $doc_encabezado->documento_ventas_hijo() ) )
		<br>
		<b>{{ $doc_encabezado->documento_ventas_hijo()->tipo_transaccion->descripcion }}: &nbsp;&nbsp;</b> {!! $doc_encabezado->documento_ventas_hijo()->enlace_show_documento() !!}
	@endif

	<br>
	<b>Remisiones: </b> {!! $doc_encabezado->enlaces_remisiones_hijas() !!}
@endsection

@section('filas_adicionales_encabezado')
	<tr>
		<td style="border: solid 1px #ddd;">
			<b>Cliente: </b> {{ $doc_encabezado->tercero_nombre_completo }}
			<br>
			<b>{{ config("configuracion.tipo_identificador") }}: &nbsp;&nbsp;</b>
			
			@if( config("configuracion.tipo_identificador") == 'NIT') 
				{{ number_format( $doc_encabezado->numero_identificacion, 0, ',', '.') }}	
			@else 
				{{ $doc_encabezado->numero_identificacion}} 
			@endif
		</td>
		<td style="border: solid 1px #ddd;">
			@if( !is_null($doc_encabezado->contacto_cliente) )
				<b>Contacto: </b> {{ $doc_encabezado->contacto_cliente->tercero->descripcion }}
				<br>
				<b>Tel: </b> {{ $doc_encabezado->contacto_cliente->tercero->telefono1 }}
				<br>
				<b>Email: </b> {{ $doc_encabezado->contacto_cliente->tercero->email }}
			@endif
		</td>
	</tr>
@endsection

@section('div_advertencia_anulacion')
<div class="alert alert-warning" style="display: none;">
	<a href="#" id="close" class="close">&times;</a>
	<strong>Advertencia!</strong>
	<br>
	La anulación no se puede revertire.
	<br>
	Si realmente quiere anular el documento, haga click en el siguiente enlace: <span style="text-decoration-line: underline"> <a href="{{ url( 'vtas_pedidos_anular/'.$id.$variables_url ) }}"> Anular </a> </span>
</div>
@endsection

@section('documento_vista')
	<p style="color: red;">
		Nota: Las cantidades pendientes se van actualizando a medida que se hagan la remisiones.
	</p>
	<div class="table-responsive">
		<table class="table table-bordered table-striped">
			{{ Form::bsTableHeader(['Item','Producto','Cant.','Cant. Pend.','Vr. unitario','IVA','Total Bruto','Total','']) }}
			<tbody>
				<?php
				$i = 1;
				$total_cantidad = 0;
				$subtotal = 0;
				$total_impuestos = 0;
				$total_factura = 0;
				$array_tasas = [];
				?>
				@foreach($doc_registros as $linea )
				<tr>
					<td> {{ $i }} </td>
					<td width="250px"> {{ $linea->producto_descripcion }} </td>
					<td> {{ number_format( $linea->cantidad, 0, ',', '.') }} </td>
					<td> {{ number_format( $linea->cantidad_pendiente, 0, ',', '.') }} </td>
					<td> {{ '$ '.number_format( $linea->precio_unitario / (1+$linea->tasa_impuesto/100) , 0, ',', '.') }} </td>
					<td> {{ number_format( $linea->tasa_impuesto, 0, ',', '.').'%' }} </td>
					<td> {{ '$ '.number_format( $linea->precio_unitario / (1+$linea->tasa_impuesto/100) * $linea->cantidad, 0, ',', '.') }} </td>
					<td> {{ '$ '.number_format( $linea->precio_total, 0, ',', '.') }} </td>
                    <td>
                        @if( $doc_encabezado->estado = 'Pendiente' )
                            <button class="btn btn-warning btn-xs btn-detail btn_editar_registro" type="button" title="Modificar" data-linea_registro_id="{{$linea->id}}"><i class="fa fa-btn fa-edit"></i>&nbsp; </button>

                            @include('components.design.ventana_modal',['titulo'=>'Editar registro','texto_mensaje'=>''])
                        @endif
                    </td>
				</tr>
				<?php
				$i++;
				$total_cantidad += $linea->cantidad;
				$subtotal += (float) $linea->base_impuesto * (float) $linea->cantidad;
				$total_impuestos += (float) $linea->valor_impuesto * (float) $linea->cantidad;
				$total_factura += $linea->precio_total;

				// Si la tasa no está en el array, se agregan sus valores por primera vez
				if (!isset($array_tasas[$linea->tasa_impuesto])) {
					// Clasificar el impuesto
					$array_tasas[$linea->tasa_impuesto]['tipo'] = 'IVA ' . $linea->tasa_impuesto . '%';
					if ($linea->tasa_impuesto == 0) {
						$array_tasas[$linea->tasa_impuesto]['tipo'] = 'IVA 0%';
					}
					// Guardar la tasa en el array
					$array_tasas[$linea->tasa_impuesto]['tasa'] = $linea->tasa_impuesto;


					// Guardar el primer valor del impuesto y base en el array
					$array_tasas[$linea->tasa_impuesto]['precio_total'] = (float) $linea->precio_total;
					$array_tasas[$linea->tasa_impuesto]['base_impuesto'] = (float) $linea->base_impuesto * (float) $linea->cantidad;
					$array_tasas[$linea->tasa_impuesto]['valor_impuesto'] = (float) $linea->valor_impuesto * (float) $linea->cantidad;
				} else {
					// Si ya está la tasa creada en el array
					// Acumular los siguientes valores del valor base y valor de impuesto según el tipo
					$precio_total_antes = $array_tasas[$linea->tasa_impuesto]['precio_total'];
					$array_tasas[$linea->tasa_impuesto]['precio_total'] = $precio_total_antes + (float) $linea->precio_total;
					$array_tasas[$linea->tasa_impuesto]['base_impuesto'] += (float) $linea->base_impuesto * (float) $linea->cantidad;
					$array_tasas[$linea->tasa_impuesto]['valor_impuesto'] += (float) $linea->valor_impuesto * (float) $linea->cantidad;
				}
				?>
				@endforeach
			</tbody>
		</table>
	</div>
		
	<div class="table-responsive">
		<table class="table table-bordered">
			<tr>
				<td width="75%"> <b> &nbsp; </b> <br> </td>
				<td style="text-align: right; font-weight: bold;"> Subtotal: &nbsp; </td>
				<td style="text-align: right; font-weight: bold;" id="tbstotal"> $ {{ round($subtotal,2,PHP_ROUND_HALF_UP) }} </td>
			</tr>

			@foreach( $array_tasas as $key => $value )
				<tr>
					<td width="75%"> <b> &nbsp; </b> <br> </td>
					<td style="text-align: right; font-weight: bold;"> {{ $value['tipo'] }} </td>
					<td style="text-align: right; font-weight: bold;" id="tbimpuesto"> ${{ round($value['valor_impuesto'],2,PHP_ROUND_HALF_UP) }} </td>
				</tr>
			@endforeach
			<tr>
				<td width="75%"> <b> &nbsp; </b> <br> </td>
				<td style="text-align: right; font-weight: bold;"> Total Pedido: &nbsp; </td>
				<td style="text-align: right; font-weight: bold;" id="tbtotal"> $ {{ round($total_factura,2,PHP_ROUND_HALF_UP) }} </td>
			</tr>
		</table>
	</div>
@endsection

@section('otros_scripts')
	<script type="text/javascript">
		var array_registros = [];
		var cliente = <?php echo $cliente; ?>;
		
		

		$(document).ready(function() {

			$(".btn_editar_registro").click(function(event){

		        $("#myModal").modal({backdrop: "static"});
		        $("#div_spin").show();
		        $(".btn_edit_modal").hide();

		        var url = '../vtas_pedidos_get_formulario_edit_registro';

				$.get( url, { 
								linea_registro_id: $(this).attr('data-linea_registro_id'),  
								modelo_editar: $(this).attr('data-modelo_editar'),
								id: getParameterByName('id'), 
								id_modelo: getParameterByName('id_modelo'), 
								id_transaccion: getParameterByName('id_transaccion')
							} )
					.done(function( data ) {

						$('#saldo_original').val( $('#saldo_a_la_fecha').val() );
						$('#cantidad_original').val( $('#cantidad').val() );

		                $('#contenido_modal').html(data);

		                $("#div_spin").hide();

		                $('#precio_unitario').select();

					});		        
		    });

		    // Al modificar el precio 
	        $(document).on('keyup','#precio_unitario',function(event){
				
				if( validar_input_numerico( $(this) ) )
				{	

					var x = event.which || event.keyCode;
					if( x==13 )
					{
						$('#cantidad').select();				
					}

					calcular_valor_descuento();

					calcular_precio_total();

				}else{
					$(this).focus();
					return false;
				}

			});

		    // Al modificar la cantidad
	        $(document).on('keyup','#cantidad',function(event){
				
				if( validar_input_numerico( $(this) ) )
				{
					if ( !validar_cantidad_pendiente() )
					{
						return false;
					}

					var x = event.which || event.keyCode;
					if( x==13 )
					{
						$('#tasa_descuento').select();
					}

					calcular_valor_descuento();

					calcular_precio_total();
					
				}else{
					$(this).focus();
					return false;
				}

			});


	        $(document).on('keyup','#tasa_descuento',function(event){
	        	if( validar_input_numerico( $(this) ) )
				{	
					// máximo valor de 100
					if ( $(this).val() > 100 )
					{ 
						$(this).val(100);
					}

					var x = event.which || event.keyCode;
					if( x == 13 )
					{
						$('.btn_save_modal').focus();
						return true;
					}
					
					calcular_valor_descuento();

					calcular_precio_total();

				}else{

					$(this).focus();
					return false;
				}
			});

			function calcular_valor_descuento()
			{
				var valor_total_descuento = $('#precio_unitario').val() * $('#tasa_descuento').val() / 100 * $('#cantidad').val();

				$('#valor_total_descuento_no').val( valor_total_descuento );
				$('#valor_total_descuento').val( valor_total_descuento );
			}

			function calcular_precio_total()
			{
				var valor_total_descuento = parseFloat( $('#valor_total_descuento').val() );

				var precio_unitario = parseFloat( $('#precio_unitario').val() );

				var cantidad = parseFloat( $('#cantidad').val() );
				
				var precio_total = precio_unitario * cantidad - valor_total_descuento;

				$('#precio_total').val( precio_total );
			}


	        $('.btn_save_modal').click(function(event){

	        	if ( !validar_cantidad_pendiente() )
				{
					return false;
				}

	        	if ( $.isNumeric( $('#precio_total').val() ) )
	        	{
	                validacion_saldo_movimientos_posteriores();
	        	}else{
	        		alert('El precio total es incorrecto. Verifique lo valores ingresados.');
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
				if ( $('#tipo').val() == 'servicio' ) { return true; }

				if ( parseFloat( $('#saldo_a_la_fecha').val() ) < 0 ) 
				{
					alert('Nueva EXISTENCIA negativa.');
					$('#cantidad').val('');
					$('#cantidad').focus();
					return false;
				}
				return true;
			}

			function validar_cantidad_pendiente()
			{
				if ( parseFloat( $('#cantidad').val() ) > parseFloat( $('#cantidad_pendiente').val() ) ) 
				{
					alert('Cantidad no puede ser mayor a la cantidad pendiente.');
					$('#cantidad').val('');
					$('#cantidad').focus();
					return false;
				}

				return true;
			}


            
            function validacion_saldo_movimientos_posteriores()
            {
            	$('.btn_save_modal').off( 'click' );
                $('#popup_alerta_danger').hide();
                $('#form_edit').submit();

                /*var url = '../inv_validacion_saldo_movimientos_posteriores/' + $('#bodega_id').val() + '/' + $('#producto_id').val() + '/' + $('#fecha').val() + '/' + $('#cantidad').val() + '/' + $('#saldo_a_la_fecha2').val() + '/salida';

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
                */
            }
	            
			array_registros = <?php echo json_encode($doc_registros); ?>;
		});


		function calcular(id) {
			var arraytotal = [];
			var arrayimp = [];
			var arrayc = [];
			var arraytotalbruto = [];
			var nuevoimp = 0;
			var sbtotal = 0;
			var totalc = 0;
			var totalt = 0;
			var vu = $("input:text[name=dpreciounitario_" + id + "]").val();
			var cant = $("input:text[name=dcantidad_" + id + "]").val();
			var bruto = Math.round(parseFloat(vu) * parseFloat(cant));
			$("input:text[name=dprecio_bruto_" + id + "]").val(bruto);
			var iva = $("input:text[name=dimpuesto_" + id + "]").val();
			var total = Math.round(bruto + (bruto * (iva / 100)));
			$("input:text[name=dpreciototal_" + id + "]").val(total);
			$(".cant").each(function() {
				arrayc.push($(this).val());
				totalc = totalc + parseFloat($(this).val());
			});
			$(".total").each(function() {
				arraytotal.push($(this).val());
			});
			$(".imp").each(function() {
				arrayimp.push($(this).val());
			});
			$(".valor_bruto").each(function() {
				arraytotalbruto.push($(this).val());
			});
			arraytotal.forEach(function(value, index) {
				totalt = totalt + parseFloat(value);
				sbtotal = sbtotal + parseFloat(arraytotalbruto[index]);
				nuevoimp = nuevoimp + (arraytotalbruto[index] * (arrayimp[index] / 100));
			});
			$("#tbtotal").html("$ " + Math.round(totalt));
			$("#tbcant").html(totalc);
			$("#tbstotal").html("$ " + Math.round(sbtotal));
			$("#tbimpuesto").html("$ " + Math.round(nuevoimp));
		}


		function enviar() {
			var linea_reg = [];
			$(".total").each(function() {
				var prod = $(this).parent('td').prev().children('input').attr('id');
				linea_reg.push(llenar_objeto(prod));
			});
			$('#lineas_registros').val(JSON.stringify(linea_reg));
			$("#remision").submit();
		}

		function llenar_objeto(id) {
			var o = new Object();
			array_registros.forEach(function(value, index) {
				if (id == value.id) {
					o['inv_motivo_id'] = value.vtas_motivo_id;
					o['inv_bodega_id'] = cliente.inv_bodega_id;
					o['inv_producto_id'] = value.producto_id;
					var precio_unitario = $("input:text[name=dpreciounitario_" + id + "]").val();
					var cantidad = $("input:text[name=dcantidad_" + id + "]").val();
					var costo_unitario = parseFloat(precio_unitario) / (1 + (parseFloat(value.tasa_impuesto) / 100));
					o['costo_unitario'] = costo_unitario;
					o['cantidad'] = cantidad;
					o['costo_total'] = Math.round($("input:text[name=dpreciototal_" + id + "]").val());
				}
			});
			return o;
		}
	</script>
@endsection