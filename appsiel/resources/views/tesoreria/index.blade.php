<?php

use App\Http\Controllers\Tesoreria\ReporteController;

$fecha_hoy = date('Y-m-d');
$tabla = ReporteController::grafica_movimientos_diarios(date("Y-m-d", strtotime($fecha_hoy . "- 30 days")), $fecha_hoy);
$cuentas = ReporteController::reporte_cuentas();
$cajas = ReporteController::reporte_cajas();
?>
@extends('layouts.principal')

@section('content')
{{ Form::bsMigaPan($miga_pan) }}
<hr>

@include('layouts.mensajes')

<div class="container-fluid">
	<div class="marco_formulario">
		<div class="col-md-12">
			<h3> Saldos en Cuentas Bancarias y Cajas hasta la fecha ({{$fecha_hoy}})</h3>
		</div>
		<div class="row">
			<div class="col-md-12">
				<div class="col-md-6">
					<div style="font-size: 20px; font-weight: bold; padding: 5px; text-align: center;" class="alert alert-success" role="alert">Saldo en cuentas hasta hoy</div>
					@if($cuentas['data']!=null)
					<table class="table table-striped">
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
								<td>$ {{number_format($c['saldo'])}}</td>
							</tr>
							@endforeach
							<tr>
								<th>TOTAL</th>
								<th>$ {{number_format($cuentas['total'])}}</th>
							</tr>
						</tbody>
					</table>
					@endif
				</div>
				<div class="col-md-6">
					<div style="font-size: 20px; font-weight: bold; padding: 5px; text-align: center;" class="alert alert-success" role="alert">Saldo en cajas hasta hoy</div>
					@if($cajas['data']!=null)
					<table class="table table-striped">
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
								<td>$ {{number_format($ca['saldo'])}}</td>
							</tr>
							@endforeach
							<tr>
								<th>TOTAL</th>
								<th>$ {{number_format($cajas['total'])}}</th>
							</tr>
						</tbody>
					</table>
					@endif
				</div>
				<div class="col-md-12" style="font-size: 18px;">
					<table class="table table-striped">
						<thead>
							<tr>
								<th style="background-color: #5cb85c;">GRAN TOTAL</th>
								<th style="background-color: #5cb85c;">$ {{number_format($cajas['total']+$cuentas['total'])}}</th>
							</tr>
						</thead>
					</table>
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
		<div class="col-md-12">
			<h3> Movimiento de tesorería de los últimos 30 días </h3>
		</div>
		<hr>
		<div id="grafica1"></div>

		<br><br>
		<div class="row">
			<div class="col-md-6 col-md-offset-2">
				<table class="table table-striped">
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

<br />
@endsection