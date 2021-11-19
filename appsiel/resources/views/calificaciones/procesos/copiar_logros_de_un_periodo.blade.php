@extends('core.procesos.layout')

@section( 'titulo', 'Copiar logros de un periodo en otro' )

@section('detalles')
	Este proceso copia los logros de un periodo seleccionado en otro periodo que no tenga logros asignados.
					
	<br>
	Normalmente se hace para copiar los logros del Cuarto periodo en el PERIODO FINAL o de un Año Lectivo a otro.
	
	<br>
@endsection

@section('formulario')
	<div class="row">
		<div class="col-md-4">

			<div class="row" style="padding:5px;">
				<label class="control-label col-sm-4" > <b> Periodo origen: </b> </label>

				<div class="col-sm-8">
					{{ Form::select('periodo_origen_id', $periodos, null, ['id' => 'periodo_origen_id', 'class' => 'form-control' ] ) }}
				</div>
			</div>

		</div>

		<div class="col-md-4">

			<div class="row" style="padding:5px;">
				<label class="control-label col-sm-4" > <b> Periodo destino: </b> </label>

				<div class="col-sm-8">
					{{ Form::select('periodo_destino_id', $periodos, null, ['id' => 'periodo_destino_id', 'class' => 'form-control' ] ) }}
				</div>
			</div>

		</div>

		<div class="col-md-4">
			<button class="btn btn-success" id="btn_calcular" disabled="disabled"> <i class="fa fa-calculator"></i> Copiar </button>
		</div>
	</div>
@endsection

@section('javascripts')
	<script type="text/javascript">

		$(document).ready(function(){

			$('#periodo_origen_id').focus();

			$('#periodo_origen_id').on('change',function()
			{
				$("#mensaje_ok").html('');
				$("#div_spin").hide();
				$('#btn_calcular').attr('disabled', 'disabled');
				$('#popup_alerta_danger').hide();

				if ( $(this).val() == '')
				{ 
					$('#div_resultado').html( '' );
					return false;
				}

				$('#div_cargando').show();
				$('#div_advertencia').hide();

				var url = "{{ url('consultar_periodos_periodo_lectivo') }}" + "/" + $('#periodo_origen_id').val();

				$.get( url, function( datos ){

	        		$('#div_cargando').hide();

	        		$('#div_resultado').html( datos[0] );

	        		switch( datos[1] )
	        		{
						case 0: // Incorrecto. No hay periodo final
							$('#popup_alerta_danger').hide();
							$('#btn_calcular').attr('disabled', 'disabled');
							break;

						case 1: // Correcto. Hay un solo periodo final.
							$('#popup_alerta_danger').hide();
							$('#btn_calcular').removeAttr('disabled');
							break;

						default: // Incorrecto.
		        			mostrar_popup('Existe más de un (1) periodo final en el Año Lectivo seleccionado. No se puede continuar.');
		        			$('#btn_calcular').attr('disabled','disabled');
		        			return false;
							break;
					}    				
			    });

			});


			function mostrar_popup( mensaje )
			{
				$('#popup_alerta_danger').show();
				$('#popup_alerta_danger').text( mensaje );
			}



			$("#btn_calcular").on('click',function(event){
		    	event.preventDefault();

		 		$("#div_spin").show();
		 		$("#div_cargando").show();
				$('#btn_calcular').attr('disabled','disabled');

				var url = "{{ url('calcular_promedio_notas_periodo_final') }}" + "/" + $('#periodo_origen_id').val();

				$.get( url, function(datos){

	        		$('#div_cargando').hide();
	        		$("#div_spin").hide();

	        		$("#mensaje_ok").html( '<div class="alert alert-success"><strong>Promedios del periodo final generados correctamente!</strong><br> Se almacenaron '+ datos +' calificaciones. </div>' );
			    });
			
		    });



		});

	</script>
@endsection