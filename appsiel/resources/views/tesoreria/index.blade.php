<?php

	use App\Http\Controllers\Tesoreria\ReporteController;

	$fecha_hoy = date('Y-m-d');

	if ( !is_null( Input::get('fecha_corte') ) )
	{
		$fecha_hoy = Input::get('fecha_corte');
	}

	$tabla = ReporteController::grafica_movimientos_diarios( date("Y-m-d", strtotime($fecha_hoy . "- 30 days") ), $fecha_hoy);
	$cuentas = ReporteController::reporte_cuentas( $fecha_hoy );
	$cajas = ReporteController::reporte_cajas( $fecha_hoy );
?>
@extends('layouts.principal')

@section('content')
{{ Form::bsMigaPan($miga_pan) }}
<hr>

@include('layouts.mensajes')

<div class="container-fluid">
	<div class="marco_formulario">
		<div class="col-md-12">
			<div class="row">
				<div class="col-md-5">
					<h4> Saldos en Cuentas Bancarias y Cajas </h4>
				</div>
				<div class="col-md-7">
					<div class="row">
						<div class="col-md-10">
							{{ Form::bsFecha('fecha_corte', $fecha_hoy, 'Fecha corte', null, ['id'=>'fecha_corte']) }}
						</div>
						<div class="col-md-2" style="text-align: center;">
							<a href="#" class="btn btn-primary btn-sm" id="btn_actualizar" title="Actualizar saldos"> <i class="fa fa-arrow-right"></i> </a>
						</div>
					</div>
				</div>
			</div>
			
		</div>
		<div class="row">
			<div class="col-md-12">
				<div class="col-md-6">
					@if($cuentas['data']!=null)
						<table class="table table-striped table-bordered">
							<thead>
								<tr>
									<th>CUENTA</th>
									<th>SALDO</th>
								</tr>
							</thead>
							<tbody>
								@foreach($cuentas['data'] as $c)
								<tr>
									<td>{{$c['cuenta']}}</td>
									<td align="right">$ {{number_format( $c['saldo'], 0, ',','.') }}</td>
								</tr>
								@endforeach
								<tr>
									<td>TOTAL</td>
									<td align="right">$ {{number_format( $cuentas['total'], 0, ',','.') }}</td>
								</tr>
							</tbody>
						</table>
					@endif
				</div>
				<div class="col-md-6">
					@if($cajas['data']!=null)
						<table class="table table-striped table-bordered">
							<thead>
								<tr>
									<th>CAJA</th>
									<th>SALDO</th>
								</tr>
							</thead>
							<tbody>
								@foreach($cajas['data'] as $ca)
								<tr>
									<td>{{$ca['caja']}}</td>
									<td align="right">$ {{number_format( $ca['saldo'], 0, ',','.') }}</td>
								</tr>
								@endforeach
								<tr>
									<td>TOTAL</td>
									<td align="right">$ {{number_format( $cajas['total'], 0, ',','.' ) }}</td>
								</tr>
							</tbody>
						</table>
					@endif
				</div>
				<div class="col-md-12" style="font-size: 18px;">
					<div style="background-color: #50B794; text-align: center; color:white;">GRAN TOTAL: $ {{number_format($cajas['total']+$cuentas['total'])}}</div>
				</div>
			</div>
		</div>
	</div>
</div>

<div class="container-fluid">
	<div class="marco_formulario">

		<?php
		echo Lava::render('BarChart', 'movimiento_tesoreria', 'grafica1');
		$cant = count($tabla);
		$totales_entradas = 0;
		$totales_salidas = 0;
		?>
		<div class="row">
			<div class="col-md-12">
				<h4> Movimiento de tesorería 30 días hacia atrás de la fecha <span class="small">{{ $fecha_hoy }}</span> </h4>
			</div>
			<hr>
		</div>

		<div id="grafica1"></div>

		<br><br>
		<div class="row">
			<div class="col-md-12">
				<div class="table-responsive">
					<table class="table table-striped table-bordered">
						<thead>
							<tr>
								<th>Fecha</th>
								<th>Recaudos</th>
								<th>Pagos</th>
								<th>Saldo</th>
							</tr>
						</thead>
						<tbody>
							@for($i=0; $i < $cant; $i++) <tr>
								<td> {{ $tabla[$i]['fecha'] }} </td>
								<td style="text-align: right;"> ${{ number_format($tabla[$i]['valor_entradas'], 2, ',', '.') }} </td>
								<td style="text-align: right;"> ${{ number_format($tabla[$i]['valor_salidas'], 2, ',', '.') }} </td>
								<td style="text-align: right;"> ${{ number_format( $tabla[$i]['valor_entradas'] - $tabla[$i]['valor_salidas'], 2, ',', '.') }} </td>
								</tr>
								@php
								$totales_entradas += $tabla[$i]['valor_entradas'];
								$totales_salidas += $tabla[$i]['valor_salidas'];
								@endphp
								@endfor
						</tbody>
						<tfoot>
							<tr>
								<td> </td>
								<td style="text-align: right;"> <b> ${{ number_format($totales_entradas, 2, ',', '.') }} </b> </td>
								<td style="text-align: right;"> <b> ${{ number_format($totales_salidas, 2, ',', '.') }} </b> </td>
								<td style="text-align: right;"> <b> ${{ number_format( $totales_entradas - $totales_salidas, 2, ',', '.') }} </b> </td>
							</tr>
						</tfoot>
					</table>
				</div>
			</div>
		</div>

	</div>
</div>

<br />
@endsection

@section('scripts')
	<script type="text/javascript">
		$(document).ready(function(){

			$('#btn_excel').show();

			$('#fecha_corte').change(function(event){
				var id = getParameterByName('id');
				var fecha_corte = $('#fecha_corte').val();

				$('#btn_actualizar').attr('href',"{{ url('/tesoreria')}}" + "?id=" + id + "&fecha_corte=" + fecha_corte);
			});

			function getParameterByName(name) {
			    name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
			    var regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),
			    results = regex.exec(location.search);
			    return results === null ? "" : decodeURIComponent(results[1].replace(/\+/g, " "));
			}
		});

		
	</script>
@endsection
