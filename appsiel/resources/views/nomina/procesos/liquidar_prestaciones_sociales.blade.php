@extends('core.procesos.layout')

@section( 'titulo', 'Liquidar prestaciones sociales' )

@section('detalles')
	<p>
		La liquidacónes de la Prima de servicios, las Vacaciones, las Cesantías e Intereses tienen una liquidación especial con base en acumulados y parametrizaciones específicas.
	</p>
	
	Por esta opción puede liquidar todos estos conceptos.
	
	<br>
@endsection

@section('formulario')
	<div class="row" id="div_formulario">

		<div class="row">
			<div class="col-md-6">

				<div class="marco_formulario">
					<div class="container-fluid">
						<h4>
							Parámetros de selección
						</h4>
						<hr>
						{{ Form::open(['url'=>'nom_liquidar_prestaciones_sociales','id'=>'formulario_inicial','files' => true]) }}
							<div class="row" style="padding:5px;">
								<label class="control-label col-sm-4" > <b> *Documento de liquidación: </b> </label>

								<div class="col-sm-8">
									{{ Form::select( 'nom_doc_encabezado_id', App\Nomina\NomDocEncabezado::opciones_campo_select(),null, [ 'class' => 'form-control', 'id' => 'nom_doc_encabezado_id', 'required' => 'required' ]) }}
								</div>					 
							</div>

							<div class="row" style="padding:5px;">
								<h5>Prestaciones a liquidar</h5>
								<hr>
								<label class="checkbox-inline"><input name="prestaciones[]" type="checkbox" value="vacaciones" class="check_prestacion">Vacaciones</label>
								<label class="checkbox-inline"><input name="prestaciones[]" type="checkbox" value="prima_legal" class="check_prestacion">Prima de servicios</label>
								<label class="checkbox-inline"><input name="prestaciones[]" type="checkbox" value="cesantias" class="check_prestacion">Cesantías</label>
								<label class="checkbox-inline"><input name="prestaciones[]" type="checkbox" value="intereses_cesantias" class="check_prestacion">Intereses de cesantías</label>
							</div>

							<br><br>

							<div style="width: 100%; text-align: center;">
								<button class="btn btn-success" id="btn_liquidar"> <i class="fa fa-calculator"></i> Liquidar </button>
								{{ Form::Spin(48) }}
							</div>
							<input type="submit" name="Enviar">
						{{ Form::close() }}
					</div>
						
				</div>
					
			</div>
			<div class="col-md-6">
				<h4>
					Empleados del documento
				</h4>
				<hr>
				<div class="div_lista_empleados_del_documento">
					
				</div>
			</div>
		</div>
				
	</div>

	<div class="row" id="div_resultado">
			
	</div>

@endsection

@section('javascripts')
	<script type="text/javascript">

		$(document).ready(function(){

			var opcion_seleccionada = 0;

			$(".check_prestacion").change(function(){
				if ( $(this).is(':checked') )
				{
					opcion_seleccionada++;
				}else{
					opcion_seleccionada--;
				}
			  });

			$("#btn_liquidar").on('click',function(event){
		    	event.preventDefault();


		    	if ( !validar_requeridos() )
		    	{
		    		return false;
		    	}

		    	if ( opcion_seleccionada == 0) { alert('Debe seleccionar al menos una prestación.'); return false; }


		 		$("#div_spin").show();
		 		$("#div_cargando").show();
				
				var form = $('#formulario_inicial');
				var url = form.attr('action');
				var datos = new FormData(document.getElementById("formulario_inicial"));

				$.ajax({
				    url: url,
				    type: "post",
				    dataType: "html",
				    data: datos,
				    cache: false,
				    contentType: false,
				    processData: false
				})
			    .done(function( respuesta ){
			        $('#div_cargando').hide();
        			$("#div_spin").hide();

        			$("#div_resultado").html( respuesta );
        			$("#div_resultado").fadeIn( 1000 );
			    });
		    });

		});
	</script>
@endsection