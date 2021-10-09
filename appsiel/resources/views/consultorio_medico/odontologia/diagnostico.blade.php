<br><br>
diagnostico VA AQUÍ. CIE10.

Maximo 4 Dx (1 principal, 3 adicionales)

<?php
	use App\Http\Controllers\Sistema\VistaController;

	$datos = App\Salud\DiagnosticoCie::where( [
											['consulta_id', '=', $consulta->id]
										] )
									->get();
	//dd($datos);
?>

<br>

<div class="div_spin" style="width: 100%; display: none; text-align: center;">
    <img src="{{ asset( 'img/spinning-wheel.gif') }}" width="64px">
</div>

<div class="alert alert-success alert-dismissible fade in" style="display: none;" id="mensaje_alerta">
</div>

<div id="contenido_seccion_modelo_{{$ID}}" class="contenido_seccion_modelo">
	<table class="table table-bordered table-striped">
		<thead>
			<tr>
				<th>Diagnóstico principal</th>
				<th>Código</th>
				<th>Tipo</th>
				<th>Observaciones</th>
				<th>Acción</th>
			</tr>
		</thead>
		<tbody>
			@foreach( $datos AS $linea )
				<tr>
					<td> {{ $linea->es_diagnostico_principal }} </td>
					<td> {{ $linea->codigo_cie }} </td>
					<td> {{ $linea->tipo_diagnostico_principal }} </td>
					<td> {{ $linea->observaciones }} </td>
					<td> <button type='button' class='btn btn-danger btn-xs btn_eliminar_diagnostico_cie'><i class='glyphicon glyphicon-trash'></i></button> </td>
				</tr>
			@endforeach
		</tbody>
		<tfoot>
            <tr>
                <td colspan="5">
                    <button style="background-color: transparent; color: #3394FF; border: none;"><i class="fa fa-btn fa-plus btn_nuevo_registro_diagnostico_cie"></i> Agregar registro</button>
                </td>
            </tr>
        </tfoot>
	</table>
</div>


@section('scripts8')

	<script type="text/javascript">

		$(document).ready(function(){

			var LineaNum = 0;

			$(".btn_nuevo_registro_endodoncia").click(function(event){
				event.preventDefault();
		        nueva_linea_ingreso_datos();
		    });		    


		    function nueva_linea_ingreso_datos()
		    {
		    	var form = $('#div_linea_form_ingreso').find('linea_form_ingreso');
		    	console.log(form);
		        $(this).parent('table').find('tbody:last').append( form );
		    }

			$(document).on('click', '.btn_confirmar', function(event) {
				event.preventDefault();
				LineaNum++;
				var fila = $(this).closest("tr");
		       	fila.remove();
		       	nueva_linea_ingreso_datos();
            
				// Bajar el Scroll hasta el final de la página
				$("html, body").animate( { scrollTop: $(document).height()+"px"} );

			});

			$(document).on('click', '.btn_eliminar', function(event) {
				event.preventDefault();
				var fila = $(this).closest("tr");
				fila.remove();
				$('#btn_nuevo').show();
				calcular_totales();
			});


			// GUARDAR 
			$('#btn_guardar').click(function(event){
				event.preventDefault();

				if ( !validar_requeridos() )
				{
					return false;	
				}
				
				var valor_total = parseFloat( $('#valor_total').val() );

				var total_valor = parseFloat( $('#total_valor').text().substring(1) );

				if ( valor_total != total_valor) {
					alert('El VALOR TOTAL PAGO no coincide con el valor total de los registros ingresados.');
					return false;
				}

				// Se obtienen todos los datos del formulario y se envían

						// Desactivar el click del botón
						$( this ).off( event );

						// Eliminar fila(s) de ingreso de registro vacia
						$('.linea_ingreso_default').remove();						

						// Se asigna la tabla de ingreso de registros a un campo hidden
						var tabla_registros_documento = $('#ingreso_registros').tableToJSON();
						$('#tabla_registros_documento').val( JSON.stringify(tabla_registros_documento) );

						// Enviar formulario
						habilitar_campos_form_create();
						$('#form_create').submit();	
					
			});

	</script>
@endsection

