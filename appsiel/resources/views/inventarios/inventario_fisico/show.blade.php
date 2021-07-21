<?php  
    $variables_url = '?id='.Input::get('id').'&id_modelo='.Input::get('id_modelo').'&id_transaccion='.$id_transaccion;
?>

@extends('transaccion.show')

@section('botones_acciones')
	{{ Form::bsBtnCreate( 'inv_fisico/create'.'?id='.Input::get('id').'&id_modelo='.Input::get('id_modelo').'&id_transaccion='.$id_transaccion ) }}
    
    {{ Form::bsBtnEdit2(str_replace('id_fila', $id, 'inv_fisico/id_fila/edit'.$variables_url ),'Editar') }}

	<a class="btn-gmail" href="{{ url('inv_fisico_hacer_ajuste?id=8&id_modelo=25&id_transaccion=28&doc_inv_fisico_id='.$id) }}" target="_blank" title="Hacer Ajuste"><i class="fa fa-btn fa-cog"></i></a>
@endsection

@section('botones_imprimir_email')
	{{ Form::bsBtnPrint( 'inv_fisico_imprimir/'.$id.$variables_url ) }}
@endsection

@section('botones_anterior_siguiente')
	{!! $botones_anterior_siguiente->dibujar( 'inv_fisico/', $variables_url ) !!}
@endsection

@section('datos_adicionales_encabezado')
    &nbsp;
@endsection

@section('filas_adicionales_encabezado')
    <tr> 
        <td style="border: solid 1px #ddd;">
            <b>Tercero:</b> {{ $doc_encabezado->tercero_nombre_completo }}
            <br/>
            <b>{{ config("configuracion.tipo_identificador") }}: &nbsp;&nbsp;</b>
			@if( config("configuracion.tipo_identificador") == 'NIT') {{ number_format( $doc_encabezado->numero_identificacion, 0, ',', '.') }}	@else {{ $doc_encabezado->numero_identificacion}} @endif
            <br/>
            <b>Detalle: &nbsp;&nbsp;</b> {{ $doc_encabezado->descripcion }}
        </td>
        <td style="border: solid 1px #ddd;">
            <b>Hora inicio:</b> {{ $doc_encabezado->hora_incio }}
            <br/>
            <b>Hora finalización:</b> {{ $doc_encabezado->hora_finalizacion }}
        </td>
    </tr>
@endsection

@section('div_advertencia_anulacion')
	<div class="alert alert-warning" style="display: none;">
		<a href="#" id="close" class="close">&times;</a>
		<strong>¡ADVERTENCIA!</strong>
		La anulación no puede revertirse. Si quieres confirmar, hacer click en: <a class="btn btn-danger btn-sm" href="{{ url('inv_fisico_anular/'.$id.$variables_url ) }}"><i class="fa fa-arrow-right" aria-hidden="true"></i> Anular </a>
	</div>
@endsection