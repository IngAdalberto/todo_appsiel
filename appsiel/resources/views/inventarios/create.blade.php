@extends('layouts.principal')

<?php
	use App\Http\Controllers\Sistema\VistaController;
?>

@section('content')
	{{ Form::bsMigaPan($miga_pan) }}
	<hr>

	@include('layouts.mensajes')

	<div class="container-fluid">

		<div class="marco_formulario">
		    <h4>Nuevo registro</h4>
		    <hr>
			{{ Form::open(['url' => $form_create['url'],'id'=>'form_create']) }}
				<?php
				  if (count($form_create['campos'])>0) {
				  	$url = htmlspecialchars($_SERVER['HTTP_REFERER']);
				  	echo '<div class="row" style="margin: 5px;">'.Form::bsButtonsForm2($url).'</div>';
				  }else{
				  	echo "<p>El modelo no tiene campos asociados.</p>";
				  }
				?>

				{{ VistaController::campos_dos_colummnas($form_create['campos']) }}

				{{ Form::hidden('url_id',Input::get('id'))}}
				{{ Form::hidden('url_id_modelo',Input::get('id_modelo'))}}
				{{ Form::hidden('url_id_transaccion',Input::get('id_transaccion'))}}
				{{ Form::hidden('inv_bodega_id_aux',null,['id'=>'inv_bodega_id_aux'])}}
				
			{{ Form::close() }}

			<br/><br/><br/>
		    <h4 style="width: 100%; text-align: center;">Ingreso de productos</h4>
		    <table class="table table-striped" id="ingreso_productos">
		        <thead>
		            <tr>
		                <th data-override="inv_producto_id">Cod.</th>
		                <th width="280px">Producto</th>
		                <th width="200px" data-override="motivo">Motivo</th>
		                <th data-override="costo_unitario"> Costo Unit. </th>
		                <th data-override="cantidad">Cantidad</th>
		                <th data-override="costo_total">Costo Total</th>
		                <th width="10px">&nbsp;</th>
		            </tr>
		        </thead>
		        <tbody>
		            <tr>
		                <td></td>
		                <td class="nom_prod"></td>
		                <td></td>
		                <td></td>
		                <td></td>
		                <td></td>
		                <td></td>
		            </tr>

		            	<!-- Cuando se está haciendo ajuste desde Inventario Físico -->
		            	@if( isset($filas_tabla) )
		            		{!! $filas_tabla !!}
		            		{{ Form::hidden('hay_productos_aux', $cantidad_filas,['id'=>'hay_productos_aux']) }}
		            	@endif

		        </tbody>
		        <tfoot>
		            <tr>
		                <td colspan="4">&nbsp;</td>
		                <td> <div id="total_cantidad"> 0 </div> </td>
		                <td> <div id="total_costo_total"> $0</div> </td>
		                <td> &nbsp;</td>
		            </tr>
		            <tr>
		                <td colspan="5">
		                	<!-- Trigger the modal with a button -->
							<button type="button" class="btn btn-info btn-xs" id="btn_nuevo" style="display:none;"><i class="fa fa-btn fa-plus"></i> Agregar productos </button>
						</td>
		                <td colspan="2">
		                	@if($id_transaccion==4)
		                		<button type="button" class="btn btn-warning btn-xs" id="btn_calcular_costos_finales"><i class="fa fa-btn fa-calculator"></i> Calcular costos</button>
		                	@else
		                		&nbsp;
		                	@endif
						</td>
		            </tr>
		        </tfoot>
		    </table>		    

			<!-- Modal -->
			@include('inventarios.incluir.ingreso_productos_2')
			
		</div>
	</div>
	<br/><br/>
@endsection

@section('scripts')
	<script type="text/javascript">

		function ejecutar_acciones_con_item_sugerencia( item_sugerencia, obj_text_input )
        {
        	reset_form_producto();
			$('#spin').show();

			// Si no se seleccionó un producto, salir
			if ( $('#inv_producto_id').val() === '' || $('#inv_producto_id').val() === undefined )
			{
				$('#spin').hide();
				return false;
			}

			// Preparar datos de los controles para enviar formulario de ingreso de productos
			var form_producto = $('#form_producto');
			var url = form_producto.attr('action');
			$('#id_bodega').val($('#inv_bodega_id').val());
			var datos = form_producto.serialize();


			// Enviar formulario de ingreso de productos vía POST
			$.post(url,datos,function(respuesta){
				
				var mov = $('#motivo').val().split('-');
				$('#spin').hide();
				
				// Se valida la existencia actual
				$('#existencia_actual').val(respuesta.existencia_actual);
				$('#saldo_original').val(respuesta.existencia_actual);

				$('#tipo_producto').val(respuesta.tipo);

				if ( respuesta.existencia_actual >= 0) {
					$('#existencia_actual').attr('style','background-color:#97D897;');
				}else{
					
					$('#existencia_actual').attr('style','background-color:#FF8C8C;');
					
					// Si el tipo de producto es "producto" y el movimiento NO es de entrada, no se permite seguir con existencia 0
					if ( respuesta.tipo == 'producto' && mov[1] != 'entrada' )
					{
						$('#btn_agregar').hide(500);
						return false;
					}
				}
				
				// Asignar datos a los controles
				$('#costo_unitario').val(parseFloat(respuesta.precio_compra).toFixed(2));
				$('#unidad_medida1').val(respuesta.unidad_medida1);

				$('#inv_producto_id_aux').val( respuesta.descripcion );

				
				// Si la TRANSACCIÓN es una Entrada Directa o Entrada por compras o el producto es tipo servicio, se puede modificar el costo unitario			
				if ( $('#id_transaccion').val() == 1 || $('#id_transaccion').val() == 35 || respuesta.tipo == 'servicio' )
				{
					$('#costo_unitario').removeAttr('disabled');
					$('#costo_unitario').attr('style','background-color:white;');
					$('#costo_unitario').select();
				}else{
					// Se pasa a ingresar las cantidades
					$('#cantidad').removeAttr('disabled');
					$('#cantidad').attr('style','background-color:white;');
					$('#cantidad').select();
				}			
			});
        }

        

		function reset_form_producto(){
			$('#form_producto input[type="text"]').val('');
			$('#form_producto input[type="text"]').attr('style','background-color:#ECECE5;');
			$('#form_producto input[type="text"]').attr('disabled','disabled');

			$('#inv_producto_id_aux').removeAttr('disabled');
			$('#inv_producto_id_aux').attr('style','background-color:#ffffff;');

			$('#spin').hide();
			$('#btn_agregar').hide();
			$('#inv_producto_id_aux').focus();
		}

		$(document).ready(function(){
			$('#core_empresa_id').val(1);
			
			$('#fecha').focus();

			/* INVENTARIOS*/
			var respuesta;
			// VARIABLES GLOBALES PAR ENSAMBLES
			var suma_costo_total_prod_salida = 0;
			var suma_cantidades_prod_entrada = 0;
			var celda_costo_unitario;
			var celda_costo_total;
			var costos_finales_correctos = false;

			$('#core_tipo_doc_app_id').change(function(){
				$('#fecha').focus();
			});

			$('#fecha').keyup(function(event){
				var x = event.which || event.keyCode;
				if(x==13){
					$('#core_tercero_id').focus();				
				}		
			});

			$('#core_tercero_id').change(function(){
				$('#inv_bodega_id').focus();
			});

			// Al seleccionar una bodega, se muestra el ingreso de productos
			$('#inv_bodega_id').change(function(){
				if ($('#inv_bodega_id').val()!='') {
					$('#btn_nuevo').show();
					if ($('#id_transaccion').val()==2) { // Si es una transferencia
						$('#bodega_destino_id').focus();
					}else{
						$('#btn_nuevo').focus();
					}
				}else{
					$('#btn_nuevo').hide();
				}
			});	

			$('#bodega_destino_id').change(function(){
				$('#btn_nuevo').focus();
			});

			/*
			**	Abrir formulario de productos
			*/
			$("#btn_nuevo").click(function(event){
				event.preventDefault();
		    	$('#inv_producto_id_aux').val('')
		    	$('#inv_producto_id').val('')
		        reset_form_producto();
		        $("#myModal").modal({backdrop: "static"});
		    });
		    	
		    	// Al mostrar la ventana modal
		    $("#myModal,#myModal2").on('shown.bs.modal', function () {
		    	$('#inv_producto_id_aux').focus();
		    	$('#fecha_aux').val( $('#fecha').val() );
		    });
			// Al OCULTAR la ventana modal
		    $("#myModal,#myModal2").on('hidden.bs.modal', function () {
		       $('#btn_nuevo').focus();
		    });

			/**
				* Al seleccionar un producto
			
			$('#inv_producto_id').on('change',function(){

				reset_form_producto();
				$('#spin').show();

				// Si no se seleccionó un producto, salir
				if ($('#inv_producto_id').val()==='') {
					$('#spin').hide();
					return false;
				}

				// Preparar datos de los controles para enviar formulario de ingreso de productos
				var form_producto = $('#form_producto');
				var url = form_producto.attr('action');
				$('#id_bodega').val($('#inv_bodega_id').val());
				var datos = form_producto.serialize();


				// Enviar formulario de ingreso de productos vía POST
				$.post(url,datos,function(respuesta){
					
					var mov = $('#motivo').val().split('-');
					$('#spin').hide();
					
					// Se valida la existencia actual
					$('#existencia_actual').val(respuesta.existencia_actual);
					$('#saldo_original').val(respuesta.existencia_actual);

					$('#tipo_producto').val(respuesta.tipo);

					if ( respuesta.existencia_actual >= 0) {
						$('#existencia_actual').attr('style','background-color:#97D897;');
					}else{
						
						$('#existencia_actual').attr('style','background-color:#FF8C8C;');
						
						// Si el tipo de producto es "producto" y el movimiento NO es de entrada, no se permite seguir con existencia 0
						if ( respuesta.tipo == 'producto' && mov[1] != 'entrada' )
						{
							$('#btn_agregar').hide(500);
							return false;
						}
					}
					
					// Asignar datos a los controles
					$('#costo_unitario').val(parseFloat(respuesta.precio_compra).toFixed(2));
					$('#unidad_medida1').val(respuesta.unidad_medida1);
					
					// Si la TRANSACCIÓN es una Entrada Directa o Entrada por compras o el producto es tipo servicio, se puede modificar el costo unitario			
					if ( $('#id_transaccion').val() == 1 || $('#id_transaccion').val() == 35 || respuesta.tipo == 'servicio' )
					{
						$('#costo_unitario').removeAttr('disabled');
						$('#costo_unitario').attr('style','background-color:white;');
						$('#costo_unitario').select();
					}else{
						// Se pasa a ingresar las cantidades
						$('#cantidad').removeAttr('disabled');
						$('#cantidad').attr('style','background-color:white;');
						$('#cantidad').select();
					}			
				});
			});
			**/

			/*
			** Al dejar el control del costo unitario, se valida lo ingresado, se inactiva el control
			** y se pasa a las cantidades
			*/
			$('#costo_unitario').keyup(function(event){
				calcula_costo_total();
				var x = event.which || event.keyCode;
				if( x==13 ){
					if (enfocar_el_incorrecto()!='costo_unitario') {
						$('#cantidad').removeAttr('disabled');
						$('#cantidad').select();
						$('#cantidad').attr('style','background-color:white;');
					}				
				}
			});

			/*
			** Al digitar la cantidad, se valida la existencia actual, si es un movimiento de resta
			** luego se calcular el costo total
			*/
			$('#cantidad').keyup(function(event){
				
				if( validar_input_numerico( $(this) ) && $(this).val() > 0 )
		        {
		            calcula_nuevo_saldo_a_la_fecha();
		            
					calcula_costo_total();

		            var x = event.which || event.keyCode;
		            if( x==13 )
		            {
		                if ( !validar_existencia_actual() )
		                {
		                    $('#costo_total').val('');
		                    return false;
		                }
		                if (enfocar_el_incorrecto()!='cantidad')
		                {
							if (enfocar_el_incorrecto()!='costo_unitario')
							{
								validacion_saldo_movimientos_posteriores();						
							}
						}

		            }

		            $('#costo_total').val( parseFloat( $('#costo_unitario').val() ) * parseFloat( $('#cantidad').val() ));
		        }else{
		            $('#costo_total').val('');
		            $(this).focus();
		            return false;
		        }
			});

			/*
			    validar_existencia_actual
			*/
			function validar_existencia_actual()
			{
				var mov = $('#motivo').val().split('-');
			    
			    // PARA ENTRADAS
			    if ( mov[1] == 'entrada' )
			    {
			    	// Para que pueda pasar las validadciones
					if ( $('#costo_unitario').val( ) == 0 || $('#costo_unitario').val( ) == "" ) 
					{
						$('#costo_unitario').val( 0.0000001 );
					}

					/*
			        if ( parseFloat( $('#existencia_actual').val() ) < 0 ) 
			        {
			        	$('#btn_agregar').hide();
			            alert('Saldo negativo a la fecha.');
			            $('#cantidad').val('');
			            $('#cantidad').focus();
			            return false;
			        }
			        */
			    }

			    // PARA SALIDAS
			    if ( mov[1] == 'salida' )
			    {
			        if ( parseFloat( $('#existencia_actual').val() ) < 0 && $('#tipo_producto').val() != 'servicio' ) 
			        {
			        	$('#btn_agregar').hide();
			            alert('Saldo negativo a la fecha.');
			            $('#cantidad').val('');
			            $('#cantidad').focus();
			            return false;
			        }
			    }
			    return true;
			}
			

			// Al cambiar de motivo
			$('#motivo').change(function(){
				reset_form_producto();
		    	$('#inv_producto_id_aux').val('')
				$('#inv_producto_id').val('');
			});

			/*
			** Al presionar el botón agregar
			*/
			$('#btn_agregar').click(function(event){
				event.preventDefault();
				
				if ( !validar_existencia_actual() )
		        {
		            $('#costo_total').val('');
		            return false;
		        }

				var costo_total = $('#costo_total').val(); // ya está asignado con la funcion calcular_costo_total
				var producto = $('#inv_producto_id');
				var nombre_producto = producto.val() + ' ' + $( "#inv_producto_id_aux" ).val();
				var costo_unitario = $('#costo_unitario').val();
				var cantidad = $('#cantidad').val();
				var mov = $('#motivo').val().split('-');
				var motivo = $( "#motivo option:selected" ).text();
				
				switch(mov[1]){
					case 'entrada':
						var estilo = 'style="color:green;"';
						if( costo_total == "" )
						{
							costo_total = 0.00000000001;
						}
						break;
					case 'salida':
						var estilo = 'style="color:red;"';
						break;
					default:
						break;
				}

				var btn_borrar = "<button type='button' class='btn btn-danger btn-xs btn_eliminar'><i class='fa fa-btn fa-trash'></i></button>";

				if($.isNumeric(costo_total) && costo_total>0){
					celda_costo_unitario = '<td>$'+parseFloat(costo_unitario).toFixed(2)+'</td>';
					celda_costo_total = '<td class="costo_total">$'+costo_total+'</td>';
					
					// Si la transaccion es ENSAMBLE, no se muestran los costos para los productos
					// finales (tipo entrada, movimiento suma)
					if ($('#id_transaccion').val()==4) {
						if (mov[1]=='entrada') {
							// para la entradas se suman las cantidades
							suma_cantidades_prod_entrada = suma_cantidades_prod_entrada + parseFloat(cantidad);
							// la class costo_unitario solo la tienen las celdas de los productos de entradas
							celda_costo_unitario = '<td class="costo_unitario">Sin calcular</td>';
							// se usa otra class costo_total2 porque esta es la que se va a rellenar cuando se
							// calcule el costo de los productos de entrada
							celda_costo_total = '<td class="costo_total2">Sin calcular</td>';
						}else{
							// para las salidas se suman los costos
							suma_costo_total_prod_salida = suma_costo_total_prod_salida + parseFloat(costo_total);
						}
					}

					$('#ingreso_productos').find('tbody:last').append('<tr id="'+producto.val()+'">'+
																	'<td>'+producto.val()+'</td>'+
																	'<td class="nom_prod">'+nombre_producto+'</td>'+
																	'<td><span style="color:white;">'+mov[0]+'-</span><span '+estilo+'>'+motivo+'</span><input type="hidden" class="movimiento" value="'+mov[1]+'"></td>'+
																	celda_costo_unitario+
																	'<td class="cantidad">'+cantidad+" "+$('#unidad_medida1').val()+'</td>'+
																	celda_costo_total+
																	'<td>'+btn_borrar+'</td>'+
																	'</tr>');
					
					// Se calculan los totales para la última fila
					calcular_totales();

					// Se retira el producto del select
					$("#inv_producto_id option[value='"+producto.val()+"']").remove();
					reset_form_producto();
					$('#inv_producto_id_aux').val('');
					$('#inv_producto_id').val('');

					// Se incrementa variable auxiliar para llevar control del ingreso 
					// de productos al agregar o elminiar
					var hay_productos = $('#hay_productos').val();
					hay_productos = parseFloat(hay_productos) + 1;
					$('#hay_productos').val(hay_productos);

					// se inactiva la bodega para que ya no se pueda cambiar, pues el motiviemto está amarrado a la bodega
					// 1ro. Se pasa el valor del id de la bodega (select) a un input hidden bodega
					$('#inv_bodega_id_aux').val($('#inv_bodega_id').val());
					// 2do. Intercambio los atributos de los campos, pues cuando está desabilitado, el campo name no es enviado
					$('#inv_bodega_id').attr('name','no_inv_bodega_id');
					$('#inv_bodega_id_aux').attr('name','inv_bodega_id');
					// 3ro. Desabilito el select
					$('#inv_bodega_id').attr('style','background-color:#ECECE5;');
					$('#inv_bodega_id').attr('disabled','disabled');
					$('#fecha').attr('disabled','disabled');

					// Se resetean los costos finales para obligar a que se calculen nuevamente
					if ($('#id_transaccion').val()==4) {
						$('.costo_unitario,.costo_total2').html('Sin Calcular');
						costos_finales_correctos = false;
					}

				}else{
					enfocar_el_incorrecto();
					alert('Datos incorrectos o incompletos. Por favor verifique.');
					$('#btn_agregar').hide();
				}
			});

			/*
			** Al eliminar una fila
			*/
			$(document).on('click', '.btn_eliminar', function(event) {
				event.preventDefault();
				var fila = $(this).closest("tr");

				// Se agrega nuevamente el producto al select
				//var id_producto = fila.attr("id");
				//var nombre_producto = fila.find("td.nom_prod").html();
				//$('#inv_producto_id').append($('<option>', { value: id_producto, text: nombre_producto}));

				fila.remove();
				reset_form_producto();
				$('#inv_producto_id_aux').val('');
				$('#inv_producto_id').val('');

				// Se DECREMENTA variable auxiliar para llevar control del ingreso 
				// de prodcutos al agregar o elminiar
				var hay_productos = $('#hay_productos').val();
				hay_productos = parseFloat(hay_productos) - 1;
				$('#hay_productos').val(hay_productos);

				if (hay_productos==0) {
					// Se ACTIVA el select de la bodegas para que ya no se pueda cambiar, pues el motiviemto está amarrado a la bodega
					// 1ro. Se pasa el valor del id del select bodega a un input hidden bodega
					$('#inv_bodega_id_aux').val($('#inv_bodega_id').val());
					// 2do. Intercambio los traibutos de los campos, pues cuando está desabilitado, el campo name no es enviado
					$('#inv_bodega_id').attr('name','inv_bodega_id');
					$('#inv_bodega_id_aux').attr('name','inv_bodega_id_aux');
					// 3ro. Desabilito el select
					$('#inv_bodega_id').removeAttr('disabled');
					$('#inv_bodega_id').attr('style','background-color:white;');


					$('#fecha').removeAttr('disabled');
				}


				if ($('#id_transaccion').val()==4) {
					var mov = fila.find("input.movimiento").val();
					if (mov=='entrada') {
						var cantidad = fila.find('td.cantidad').html();
						// Se elimina la cadena "UND" del texto de la cantidad
						var pos_espacio = cantidad.search(" ");
						cantidad = cantidad.substring(0,pos_espacio);
						suma_cantidades_prod_entrada = suma_cantidades_prod_entrada - parseFloat(cantidad);
						
					}else{
						var costo_total = fila.find('td.costo_total').html();
						// Se elimina el signo "$" del texto del costo
						costo_total = costo_total.substring(1);
						suma_costo_total_prod_salida = suma_costo_total_prod_salida - parseFloat(costo_total);
					}
					$('.costo_unitario,.costo_total2').html('Sin Calcular');
					costos_finales_correctos = false;
				}

				calcular_totales();
			});

			$('#btn_guardar').click(function(event){
				event.preventDefault();

				var object = $('#ingreso_productos').val();
				
				if( typeof object == typeof undefined){
					// Si no existe la tabla de ingreso_productos, se envía el formulario
					// Esto es para los otros modelos que usan el ModeloController y que no
					// son una transacción

					// Desactivar el click del botón
					$( this ).off( event );

					$('#form_create').submit();
				}else{
					var hay_productos = $('#hay_productos').val();
					if(hay_productos>0) {
						var table = $('#ingreso_productos').tableToJSON();
				 		$('#movimiento').val(JSON.stringify(table));
				 		var count = Object.values($('#movimiento')).length;
			  			var control = 1;
						$( "*[required]" ).each(function() {
							if ( $(this).val() == "" ) {
							  $(this).focus();
							  control = 0;
							  alert( $(this).attr('name') + ': Este campo es requerido.');
							  $(this).removeAttr('disabled');
							  return false;
							}else{
							  control = 1;
							}
						});

						if (control==1) {

							// Si es un ENSAMBLE
							if ($('#id_transaccion').val()==4) {
								// se validan los costos finales
								if (costos_finales_correctos) {


									// Desactivar el click del botón
									$( this ).off( event );

									$('#fecha').removeAttr('disabled');
									$('#form_create').submit();	
								}else{
									alert('NO se han calculado los costos finales.');
								}
							}else{

								// Desactivar el click del botón
								$( this ).off( event );
								
								$('#fecha').removeAttr('disabled');
								$('#form_create').submit();	
							}
							
						}else{
							$(this).removeAttr('disabled');
							alert('Faltan campos por llenar.');
						}
			  			
					}else{
						$(this).removeAttr('disabled');
						alert('No ha ingresado productos.');
						$('#inv_producto_id_aux').focus();
					}
				}
					
			});

			$('#btn_calcular_costos_finales').click(function(event){
				event.preventDefault();
				if (suma_cantidades_prod_entrada>0 && suma_costo_total_prod_salida>0) {
					costo_promedio = suma_costo_total_prod_salida / suma_cantidades_prod_entrada;
					$('.costo_unitario').text('$'+costo_promedio.toFixed(2));

					$('.costo_total2').each(function()
					{
						var costo_total = $(this); // celda costo_total
						var cant = costo_total.prev(); // celda cantidad
						var cost_unit = cant.prev(); // celda costo_unitario

						var cantidad = cant.text();
						// Se elimina la cadena "UND" del texto de la cantidad
						var pos_espacio = cantidad.search(" ");
						cantidad = cantidad.substring(0,pos_espacio);

						cost_unit = cost_unit.text();
						// Se elimina el signo "$" del texto del costo
						cost_unit = cost_unit.substring(1);
						
						valor_costo = parseFloat(cantidad) * parseFloat(cost_unit);
					    costo_total.text('$'+(valor_costo).toFixed(2));
					});
					costos_finales_correctos = true;
					calcular_totales();
				}else{
					costos_finales_correctos = false;
					alert('Datos incompletos para calcular el costo unitario. Recuerde que Costo unitario = costo_total_productos_salidas / cantidades_productos_entradas');
				}
			});

			function calcula_costo_total(){
				var costo_unitario = $('#costo_unitario').val();
				var cantidad = $('#cantidad').val();
				var costo_total = (costo_unitario * cantidad).toFixed(2);
				if(($.isNumeric(costo_total)) && (costo_total>0)){
					$('#costo_total').val(costo_total);
				}else{
					$('#costo_total').val('');
				}
			}

			function calcular_totales(){
				var sum = 0.0;
				$('.cantidad').each(function()
				{
				    var cantidad = $(this).text();
					// Se elimina la cadena "UND" del texto de la cantidad
					var pos_espacio = cantidad.search(" ");
					cantidad = cantidad.substring(0,pos_espacio);
				    sum += parseFloat(cantidad);
				});
				var texto = sum.toFixed(2);
				$('#total_cantidad').text(texto);

				sum = 0.0;
				$('.costo_total,.costo_total2').each(function()
				{
				    var cadena = $(this).text();
				    sum += parseFloat(cadena.substring(1));
				});

				$('#total_costo_total').text("$"+sum.toFixed(2));
			}

			function enfocar_el_incorrecto()
			{
				var costo_unitario = $('#costo_unitario').val();
				var cantidad = $('#cantidad').val();
				var ok;
				
				if($.isNumeric(costo_unitario) && costo_unitario>0){
					$('#costo_unitario').attr('style','background-color:white;');

					if($.isNumeric(cantidad) && cantidad>0){
						$('#cantidad').attr('style','background-color:white;');
						ok = true;
					}else{
						$('#cantidad').attr('style','background-color:#FF8C8C;');
						$('#cantidad').select();
						ok = 'cantidad';
					}
				}else{
					$('#costo_unitario').attr('style','background-color:#FF8C8C;');
					$('#costo_unitario').select();
					ok = 'costo_unitario';
				}
				return ok;
			}
		            
		    function validacion_saldo_movimientos_posteriores()
		    {
		        var url = '../inv_validacion_saldo_movimientos_posteriores/' + $('#inv_bodega_id').val() + '/' + $('#inv_producto_id').val() + '/' + $('#fecha').val() + '/' + $('#cantidad').val() + '/' + $('#existencia_actual').val() + '/' + $('#motivo').val().split('-')[1];

		        $.get( url )
		            .done( function( data ) {
		                if ( data != 0 )
		                {
		                    $('#popup_alerta_danger').show();
		                    $('#popup_alerta_danger').text( data );
		                    $('#btn_agregar').hide();
		                }else{
		                    $('.btn_save_modal').off( 'click' );
		                    $('#popup_alerta_danger').hide();
		                    $('#btn_agregar').show();
							$('#btn_agregar').focus();
		                }
		            });

		    }

		    function calcula_nuevo_saldo_a_la_fecha()
		    {
		    	var mov = $('#motivo').val().split('-');
		        // PARA ENTRADAS
		        if ( mov[1] == 'entrada' )
		        {
		            var saldo_actual = parseFloat( $('#existencia_actual').val() );
		            var cantidad_anterior = parseFloat( $('#cantidad_anterior').val() );
		            var nuevo_saldo = saldo_actual - cantidad_anterior + parseFloat( $('#cantidad').val() );

		            $('#existencia_actual').val( nuevo_saldo );
		            $('#cantidad_anterior').val( $('#cantidad').val() );
		        }

		        // PARA SALIDAS
		        if ( mov[1] == 'salida' )
		        {
		            var nuevo_saldo = parseFloat( $('#saldo_original').val() ) + parseFloat( $('#cantidad_original').val() ) - parseFloat( $('#cantidad').val() );

		            $('#existencia_actual').val( nuevo_saldo );
		        }
		        
		    }
			
			// Para ajustes desde inventario físico
			if( getParameterByName('doc_inv_fisico_id') != '' )
			{
				// se trae la cantidad de productos desde el controller
				$('#hay_productos').val( $('#hay_productos_aux').val() );
				

				//calcular_totales
				var sum = 0.0;
				$('.cantidad').each(function()
				{
				    var cantidad = $(this).text();
					// Se elimina la cadena "UND" del texto de la cantidad
					var pos_espacio = cantidad.search(" ");
					cantidad = cantidad.substring(0,pos_espacio);
				    sum += parseFloat(cantidad);
				});
				var texto = sum.toFixed(2);
				$('#total_cantidad').text(texto);

				sum = 0.0;
				$('.costo_total,.costo_total2').each(function()
				{
				    var cadena = $(this).text();
				    sum += parseFloat(cadena.substring(1));
				});

				$('#total_costo_total').text("$"+sum.toFixed(2));
			}else{
				// Solo se coloca la fecha de hoy si no es un ajuste desde inventarios físico
				$('#fecha').val( get_fecha_hoy() );
			}

		});
	</script>
@endsection