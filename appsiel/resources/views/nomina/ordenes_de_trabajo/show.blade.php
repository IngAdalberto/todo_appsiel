@extends('layouts.principal')

<?php  
	$variables_url = '?id='.Input::get('id').'&id_modelo='.Input::get('id_modelo').'&id_transaccion='.Input::get('id_transaccion');
?>

@section('content')
	
	{{ Form::bsMigaPan($miga_pan) }}
	
	<div class="row">
		<div class="col-md-4">
			<div class="btn-group">
				
				{{ Form::bsBtnCreate( 'web/create' . $variables_url ) }}
				
				@if( $orden_de_trabajo->estado != 'Anulado' )
				    <button class="btn-gmail" id="btn_anular" title="Anular"><i class="fa fa-btn fa-close"></i></button>
				@endif
				
			</div>
		</div>

		<div class="col-md-4 text-center">
			<div class="btn-group">
				Formato: {{ Form::select('formato_impresion_id',['estandar'=>'Estándar'],null, [ 'id' =>'formato_impresion_id' ]) }}
				{{ Form::bsBtnPrint( 'nom_ordenes_trabajo_imprimir/'.$id.$variables_url.'&formato_impresion_id=estandar' ) }}
			</div>			
		</div>

		<div class="col-md-4">	
			<div class="btn-group pull-right">
				{!! $botones_anterior_siguiente->dibujar( 'nom_ordenes_trabajo/', $variables_url ) !!}
			</div>			
		</div>	

	</div>
	
	<hr>
	<div class="row">
		@yield('cabecera')
	</div>
	<hr>

	@include('layouts.mensajes')

	{{ Form::open(['url'=>'nom_ordenes_trabajo_anular', 'id'=>'form_anular']) }}
		<div class="alert alert-warning" style="display: none;">
			<a href="#" id="close" class="close">&times;</a>
			<strong>Advertencia!</strong>
			<br>
			Al anular el documento se eliminan los registros de empleados e items relacionados.
			<br>
			Si realmente quiere anular el documento, haga click en el siguiente enlace: <small> <a href="#" id="enlace_anular" data-url="{{ url('nom_ordenes_trabajo_anular/' . $variables_url ) }}"> Anular </a> </small>
		</div>

				{{ Form::hidden('url_id', Input::get('id')) }}
				{{ Form::hidden('url_id_modelo', Input::get('id_modelo')) }}
				{{ Form::hidden('url_id_transaccion', Input::get('id_transaccion')) }}

				{{ Form::hidden( 'orden_de_trabajo_id', $id ) }}

	{{ Form::close() }}

	<div class="container-fluid">
		<div class="marco_formulario">

			<br><br>
			<div class="table-responsive">
				@yield('informacion_antes_encabezado')
				<table class="table table-bordered">
			        <tr>
			            <td width="50%" style="border: solid 1px #ddd; margin-top: -40px;">
			                @include( 'core.dis_formatos.plantillas.banner_logo_datos_empresa', [ 'vista' => 'show' ] )
			            </td>
			            <td style="border: solid 1px #ddd; padding-top: -20px;">
			                <div style="vertical-align: center;">
			                    <b style="font-size: 1.6em; text-align: center; display: block;">{{ $orden_de_trabajo->tipo_trasaccion->descripcion }}</b>
			                    <br/>
			                    <b>Documento:</b> {{ $orden_de_trabajo->tipo_documento_app->prefijo . ' ' . $orden_de_trabajo->consecutivo }}
			                    <br/>
			                    <b>Fecha:</b> {{ $orden_de_trabajo->fecha }}

			                    @yield('datos_adicionales_encabezado')
			                    
			                </div>
			                @if($orden_de_trabajo->estado == 'Anulado')
			                    <div class="alert alert-danger" class="center">
			                        <strong>Documento Anulado</strong>
			                    </div>
			                @endif
			            </td>
			        </tr>
			        <tr>
				        <td colspan="2" style="border: solid 1px #ddd;">
				            <b>Tercero:</b> {{ $orden_de_trabajo->tercero->descripcion }}
				             / {{ number_format( $orden_de_trabajo->tercero->numero_identificacion, 0, ',', '.') }}
				            <br/>
				            <b>Ubicación desarollo actividad : &nbsp;&nbsp;</b> {{ $orden_de_trabajo->ubicacion_desarrollo_actividad }}
				        </td>
				    </tr>
				    <tr>        
				        <td colspan="2" style="border: solid 1px #ddd;">
				            <b>Detalle: &nbsp;&nbsp;</b> {{ $orden_de_trabajo->descripcion }}
				        </td>
				    </tr>

			    </table>

			</div>

			{!! $documento_vista !!}
			
			<br>

			<div style="text-align: right;">
			    Creado por: {{ explode('@',$orden_de_trabajo->creado_por)[0] }}, {{ $orden_de_trabajo->created_at }}
			    @if( $orden_de_trabajo->modificado_por != 0)
				    <br>
				    Modificado por: {{ explode('@',$orden_de_trabajo->modificado_por)[0] }}
				@endif
			</div>

		</div>
	</div>
	<br/><br/>

@endsection

@section('scripts')

	@yield('otros_scripts')

	<script type="text/javascript">
		$(document).ready(function(){
			$('#btn_print').focus();

			$('.select2').select2();

			$('#btn_print').animate( {  borderSpacing: 45 }, {
			    step: function(now,fx) {
			      $(this).css('-webkit-transform','rotate('+now+'deg)'); 
			      $(this).css('-moz-transform','rotate('+now+'deg)');
			      $(this).css('transform','rotate('+now+'deg)');
			    },
			    duration:'slow'
			},'linear');

			$('#btn_print').animate({  borderSpacing: 0 }, {
			    step: function(now,fx) {
			      $(this).css('-webkit-transform','rotate('+now+'deg)'); 
			      $(this).css('-moz-transform','rotate('+now+'deg)');
			      $(this).css('transform','rotate('+now+'deg)');
			    },
			    duration:'slow'
			},'linear');

			$('#btn_anular').on('click',function(e){
				e.preventDefault();
				$('.alert.alert-warning').show(1000);
			});

			$('#close').on('click',function(e){
				e.preventDefault();
				$('.alert.alert-warning').hide(1000);
			});

			$('#formato_impresion_id').on('change',function(){
				var btn_print = $('#btn_print').attr('href');

				n = btn_print.search('formato_impresion_id');
				var url_aux = btn_print.substr(0,n);
				var new_url = url_aux + 'formato_impresion_id=' + $(this).val();
				
				$('#btn_print').attr('href', new_url);



				var btn_email = $('#btn_email').attr('href');

				n = btn_email.search('formato_impresion_id');
				var url_aux = btn_email.substr(0,n);
				var new_url = url_aux + 'formato_impresion_id=' + $(this).val();
				
				$('#btn_email').attr('href', new_url);
				
			});

		});
	</script>
@endsection