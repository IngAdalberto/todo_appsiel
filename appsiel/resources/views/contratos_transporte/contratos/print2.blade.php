<?php

use App\Http\Controllers\ContratoTransporte\ContratoTransporteController;
?>

<!DOCTYPE html>
<html lang="es">

<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">

	<title>
		APPSIEL
	</title>

	<link rel="shortcut icon" href="{{ asset('favicon.ico') }}">

	<!-- Fonts -->
	<!-- Styles -->
	<!-- Latest compiled and minified CSS -->
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">

	<!-- Optional theme -->
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap-theme.min.css" integrity="sha384-rHyoN1iRsVXV4nD0JutlnGaslCJuC7uwjduW9SVrLvRYooPp2bWYgmgJQIXwl/Sp" crossorigin="anonymous">
	<link rel="stylesheet" href="https://cdn.datatables.net/1.10.16/css/jquery.dataTables.min.css">

	<link rel="stylesheet" href="{{ asset('assets/css/mis_estilos.css') }}">

	<style>
		body {
			font-family: 'Arial, Helvetica, sans-serif';
			background-color: #FFFFFF !important;
			/*width: 98%;*/
		}
		
		@page { margin: 15px; }

		#suggestions {
			position: absolute;
			z-index: 9999;
		}

		#proveedores_suggestions {
			position: absolute;
			z-index: 9999;
		}

		a.list-group-item-sugerencia {
			cursor: pointer;
		}

		/*
		#existencia_actual, #tasa_impuesto{
			width: 35px;
		}
		*/

		.custom-combobox {
			position: relative;
			display: inline-block;
		}

		.custom-combobox-toggle {
			position: absolute;
			top: 0;
			bottom: 0;
			margin-left: -1px;
			padding: 0;
		}

		.custom-combobox-input {
			margin: 0;
			padding: 5px 10px;
		}

		#div_cargando {
			display: none;
			/**/
			color: #FFFFFF;
			background: #3394FF;
			position: fixed;
			/*El div será ubicado con relación a la pantalla*/
			/*left:0px; A la derecha deje un espacio de 0px*/
			/*right:0px; A la izquierda deje un espacio de 0px*/
			bottom: 0px;
			/*Abajo deje un espacio de 0px*/
			/*height:50px; alto del div*/
			z-index: 999;
			width: 100%;
			text-align: center;
		}

		#popup_alerta_danger {
			display: none;
			/**/
			color: #FFFFFF;
			background: red;
			border-radius: 5px;
			position: fixed;
			/*El div será ubicado con relación a la pantalla*/
			/*left:0px; A la derecha deje un espacio de 0px*/
			right: 10px;
			/*A la izquierda deje un espacio de 0px*/
			bottom: 10px;
			/*Abajo deje un espacio de 0px*/
			/*height:50px; alto del div */
			width: 20%;
			z-index: 999999;
			float: right;
			text-align: center;
			padding: 5px;
			opacity: 0.7;
		}

		#popup_alerta_success {
			display: none;
			/**/
			color: #FFFFFF;
			background: #55b196;
			border-radius: 5px;
			position: fixed;
			/*El div será ubicado con relación a la pantalla*/
			/*left:0px; A la derecha deje un espacio de 0px*/
			right: 10px;
			/*A la izquierda deje un espacio de 0px*/
			bottom: 10px;
			/*Abajo deje un espacio de 0px*/
			/*height:50px; alto del div */
			width: 20%;
			z-index: 999999;
			float: right;
			text-align: center;
			padding: 5px;
			opacity: 0.7;
		}

.border {
	border: 1.2px solid;
	padding: 5px;
}

.border_center {
	border: 1.2px solid;
	padding: 5px;
	text-align: center;
}

		.page-break{
			page-break-after: always;
		}

	</style>
</head>

<body id="app-layout">
	<div class="container-fluid">
		<div class="row" style="font-size: 13px; line-height: 1.1;">
			
			<div class="col-md-12">
				@include('contratos_transporte.contratos.logos_encabezado_print',['emp'=>$empresa])
				@include('contratos_transporte.contratos.planilla_fuec')
			</div>

			<div class="page-break"></div>
			
			<div class="col-md-12" style="font-size: 12px !important; line-height: 1.1;">
				@include('contratos_transporte.contratos.logos_encabezado_print',['emp'=>$empresa])
				<table style="width: 100%;">
					<tbody>
						<tr>
							<td class="border" style="width: 100%; text-align: center; font-weight: bold;">{{$v->titulo}} <br> N° {{$p->nro}}</td>
						</tr>
					</tbody>
				</table>
				<table style="width: 100%; line-height: 0.7;">
					<tbody>
						<tr>
							<td class="border" style="width: 100%; padding: 10px; font-size: 10px">
								<p style=" text-align: center; font-weight: bold; font-size: 16px;">{{$v->titulo_atras}}</p>
								@if(count($v->plantillaarticulos)>0)
									@foreach($v->plantillaarticulos as $a)
										<p style="text-align: justify;"><b>{{$a->titulo}}</b> {{$a->texto}}</p>
										@if(count($a->plantillaarticulonumerals)>0)
											@foreach($a->plantillaarticulonumerals as $pan)
											    <p style="text-align: justify;"><b>{{$pan->numeracion}}</b> {{$pan->texto}}</p>
												@if(count($pan->numeraltablas)>0)
													<?php $total = count($pan->numeraltablas);
														$mitad = 0;
														if ($total % 2 == 0) {
															$mitad = $total / 2;
														} else {
															$mitad = $total / 2;
															$mitad = $mitad + 0.5;
														}
													?>
													<table style="width: 100%;">
														<tbody>
															<tr>
																<td>
																	<table style="width: 100%;">
																		<tbody>
																			<?php $i = 0; ?>
																			@foreach($pan->numeraltablas as $n)
																				<?php $i = $i + 1; ?>
																				@if($i<=$mitad) 
																				<tr>
																					<td class="border">{{$n->campo}}</td>
																					<td class="border">{{$n->valor}}</td>
																				</tr>
																				@endif
																			@endforeach
																		</tbody>
																	</table>
																</td>
																<td>
																	<table style="width: 100%;">
																		<tbody>
																			<?php $i = 0; ?>
																			@foreach($pan->numeraltablas as $n)
																			<?php $i = $i + 1; ?>
																			@if($i>$mitad)
																			<tr>
																				<td class="border">{{$n->campo}}</td>
																				<td class="border">{{$n->valor}}</td>
																			</tr>
																			@endif
																			@endforeach
																		</tbody>
																	</table>
																</td>
															</tr>
														</tbody>
													</table>
													<br>
												@endif
											@endforeach
										@endif
									@endforeach
								@else
									<p>No hay artículos en la plantilla</p>
								@endif
							</td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</body>

</html>