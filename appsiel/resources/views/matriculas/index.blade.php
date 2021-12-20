@extends('layouts.principal')

@section('content')
<style>
	li.botonConfig {
		border-top: 1px solid gray;
		border-left: 1px solid gray;
		border-right: 2px solid gray;
		border-bottom: 2px solid gray;
		margin-left: 50px;
		width: 220px;
		height: 100px;
		text-align: center;  
		-moz-text-align-last: center; /* Code for Firefox */
		text-align-last: center;
		list-style-type: none;
	}
</style>

	{{ Form::bsMigaPan($miga_pan) }}
	<hr>

	@include('layouts.mensajes')

<div class="row">
	<div class="col col-sm-8 col-sm-offset-2">
		<div class="panel panel-success">
			<div class="panel-heading" align="center">
				<b>Estadísticas {{ $periodo_lectivo->descripcion }}</b>
			</div>
			
			<div class="panel-body collapse in" id="demo2">
				<?php 
					//echo Lava::render('PieChart', 'MyStocks', 'stocks-chart');
					echo Lava::render('BarChart', 'MyStocks', 'stocks-chart');
					echo Lava::render('PieChart', 'Generos', 'stocks-chart2');
					echo Lava::render('BarChart', 'antiguedad', 'stocks-chart3');
					//dd($generos);
				?>
				<div class="row">
					<div  class="col-sm-6">
						<b>Cantidad de estudiantes por antiguedad</b>
						<div id="stocks-chart3"></div>
						<table>
							@php $total=0 @endphp
							@foreach($nuevos_matriculados as $fila)
								<tr>
									<td width="100px">{{ $fila[0] }}: </td><td>{{ $fila[1] }}</td>
								</tr>
								@php $total += $fila[1] @endphp
							@endforeach
							<tr><td width="100px">TOTAL</td><td>{{ $total }} Estudiantes</td></tr>
						</table>
					</div>
					<div  class="col-sm-6">
						&nbsp;
					</div>
				</div>
				<hr>
				<div class="row">
					<div  class="col-sm-6">
						<b>Cantidad de estudiantes por curso</b>
						<div id="stocks-chart"></div>
						<table>
							@php $total=0 @endphp
						@foreach($alumnos_por_curso as $curso)
							<tr>
							@if($curso->curso=="")
								<td width="100px">Indefinido: </td><td>{{ $curso->Cantidad }}</td>
							@else
								<td width="100px">{{ $curso->curso }}: </td><td>{{ $curso->Cantidad }}</td>
							@endif
							</tr>
							@php $total = $total + $curso->Cantidad @endphp
						@endforeach
							<tr><td width="100px">TOTAL</td><td>{{ $total }} Estudiantes</td></tr>
						</table>
					</div>
					<div  class="col-sm-6">
						<b>Cantidad por géneros</b>
						<div id="stocks-chart2"></div>
						<table>
							@php $total=0 @endphp
						@foreach($generos as $genero)
							<tr>
							@if($genero->Genero=="")
								<td width="100px">Indefinido: </td><td>{{ $genero->Cantidad }}</td>
							@else
								<td width="100px">{{ $genero->Genero }}: </td><td>{{ $genero->Cantidad }}</td>
							@endif
							</tr>
							@php $total = $total + $genero->Cantidad @endphp
						@endforeach
							<tr><td width="100px">TOTAL</td><td>{{ $total }} Estudiantes</td></tr>
						</table>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
@endsection
