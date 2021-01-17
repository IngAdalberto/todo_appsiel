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
			font-family: 'Lato';
			background-color: #FFFFFF !important;
			/*width: 98%;*/
		}

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
			border: 1px solid;
		}

		.page-break{
			page-break-after: always;
		}
	</style>
</head>

<body id="app-layout">
	<div class="container-fluid">
		<div class="row">
			<table class="table table-bordered table-striped">
				<tbody>
					<tr>
						<td class="border" style="width: 48%;"><img style="width: 380px; height: 70px;" src="{{ asset('img/logos/min_transporte.png') }}"></td>
						<td class="border" style="width: 12%; text-align: center;"><img style="height: 70px;" src="data:image/png;base64,{{DNS2D::getBarcodePNG($url, 'QRCODE')}}" alt="barcode" /></td>
						<td class="border" style="width: 40%;"><img style="width: 220px; height: 70px;" src="{{ asset('img/logos/transporcol_back.jpg') }}"></td>
					</tr>
				</tbody>
			</table>
			<div class="row">
				<div class="col-md-12" style="text-align: center; font-weight: bold; font-size: 14px;">
					<p><b>CONTRATO DE PRESTACION DE SERVICIO DE TRANSPORTE N° {{$c->numero_contrato}}</b><br><b>TRANSPORTE GRUPO ESPECIFICO DE USUARIOS</b></p>
				</div>
				<div class="col-md-12" style="text-align: justify; font-size: 11px;">
					<p>Entre los suscritos a saber <b>{{$c->rep_legal}}</b>
						en representación de la empresa <b>{{$emp->descripcion}}</b> con Nit. <b>{{$emp->numero_identificacion."-".$emp->digito_verificacion}}</b>, legalmente constituida
						y habilitada por el ministerio de transporte para la prestación del servicio transporte
						especial, de aquí en adelante el <b>CONTRATISTA</b>, y por otro lado <b>EL CONTRATANTE</b>
						en representación de <b>{{$c->representacion_de}}</b>
					</p>
					<table style="border: 1px solid; width: 100%; border-collapse: collapse;">
						<tbody>
							<tr>
								<td style="width: 25%; border: 1px solid; padding-left: 5px; font-weight: bold;">NOMBRE/APELLIDO</td>
								<td style="width: 75%; border: 1px solid; padding-left: 5px;">@if($contratante!=null) {{$contratante->tercero->descripcion." ".$contratante->razon_social}} @else {{$c->contratanteText}} @endif</td>
							</tr>
							<tr>
								<td style="width: 25%; border: 1px solid; padding-left: 5px; font-weight: bold;">IDENTIFICACIÓN</td>
								<td style="width: 75%; border: 1px solid; padding-left: 5px;">@if($contratante!=null) {{$contratante->tercero->numero_identificacion}} @else {{$c->contratanteIdentificacion}} @endif</td>
							</tr>
							<tr>
								<td style="width: 25%; border: 1px solid; padding-left: 5px; font-weight: bold;">DIRECCIÓN</td>
								<td style="width: 75%; border: 1px solid; padding-left: 5px;">@if($contratante!=null) {{$contratante->tercero->direccion1}} @else {{$c->contratanteDireccion}} @endif</td>
							</tr>
							<tr>
								<td style="width: 25%; border: 1px solid; padding-left: 5px; font-weight: bold;">TELÉFONO</td>
								<td style="width: 75%; border: 1px solid; padding-left: 5px;">@if($contratante!=null) {{$contratante->tercero->telefono1}} @else {{$c->contratanteTelefono}} @endif</td>
							</tr>
						</tbody>
					</table>
					<p>
						El presente contrato será desarrollado por el propietario del vehículo automotor de <b>PLACA {{$c->vehiculo->placa. ", INTERNO " .$c->vehiculo->int. ", CAPACIDAD " . $c->vehiculo->capacidad}}</b>
						quien cumplirá todas las obligaciones derivadas del mismo. Han decidido celebrar el presente Contrato de Prestación de Servicios de Transporte Especial de Pasajeros bajo los lineamientos del artículo 2.2.1.6.3.1 del decreto 1079 de 2015, modificado por el artículo 6 del Decreto 431 de 2017; que se regirá por las siguientes cláusulas:
						<b>PRIMERA: CONDICIONES DEL CONTRATO:</b> Prestación del Servicio público de Transporte Terrestre Especial de Pasajeros a un grupo específico de Usuarios desde un mismo lugar de origen a un mismo lugar de destino.
						<b>SEGUNDA: CARACTERÍSTICAS DEL SERVICIO: El CONTRATISTA</b> se compromete a prestar el Servicio de Transporte Especial de pasajeros al <b>CONTRATANTE</b>, teniendo en cuenta las siguientes características:
						Servicio Ida y Regreso @if($c->tipo_servicio=='IDA-REGRESO') <b style="text-decoration: underline;">X</b> @else ___ @endif Solo Ida @if($c->tipo_servicio=='IDA') <b style="text-decoration: underline;">X</b> @else ___ @endif Solo Regreso @if($c->tipo_servicio=='REGRESO') <b style="text-decoration: underline;">X</b> @else ___ @endif N° de Personas a Movilizar <b style="text-decoration: underline;">{{$c->nro_personas}}</b> Disponibilidad: SI @if($c->disponibilidad=='SI') <b style="text-decoration: underline;">X</b> @else ___ @endif @if($c->disponibilidad=='NO') NO <b style="text-decoration: underline;">X</b> @else NO ___ @endif
					</p>
					<table style="border: 1px solid; width: 100%; border-collapse: collapse;">
						<tbody>
							<tr>
								<td colspan="2" style="text-align: center; border: 1px solid; font-weight: bold; padding-left: 5px;">DATOS DEL SERVICIO</td>
							</tr>
							<tr>
								<td style="width: 30%; border: 1px solid; padding-left: 5px; font-weight: bold;">FECHA INICIO</td>
								<td style="width: 70%; border: 1px solid; padding-left: 5px;">{{$c->fecha_inicio}}</td>
							</tr>
							<tr>
								<td style="width: 30%; border: 1px solid; padding-left: 5px; font-weight: bold;">FECHA TERMINACIÓN</td>
								<td style="width: 70%; border: 1px solid; padding-left: 5px;">{{$c->fecha_fin}}</td>
							</tr>
							<tr>
								<td style="width: 30%; border: 1px solid; padding-left: 5px; font-weight: bold;">ORIGEN - DESTINO</td>
								<td style="width: 70%; border: 1px solid; padding-left: 5px;">{{$c->origen." - ".$c->destino}}</td>
							</tr>
						</tbody>
					</table>
					<p>
						<b>TERCERA: PARQUE AUTOMOTOR:</b> Los vehículos relacionados a continuación son los asignados para la prestación del servicio y cuentan con las pólizas de Responsabilidad Civil Contractual y Extracontractual vigentes, así como el Seguro Obligatorio, extracto de contrato FUEC y demás documentos exigidos en el Decreto 348 de 2015 y/o normatividad legal vigente y cumpliendo demás reglamentación exigida por el Ministerio de Transporte y Superintendencia de Puerto y Transporte.
						<b>CUARTA: PRECIO Y FORMA DE PAGO:</b> El servicio de Transporte tiene un costo acordado previamente por las partes, por valor _________________ <b>QUINTA: OBLIGACIONES DEL CONTRATANTE. EL CONTRATANTE</b> se obliga para <b>EL CONTRATISTA</b> a lo siguiente: (I) A suministrar previamente un listado con los usuarios a movilizar. (II) A suministrar oportunamente las novedades que surjan en el desarrollo del contrato y que alteren o puedan alterar de manera general o específica la marcha normal de actividades y horarios. ( III) Realizar las actividades turísticas de manera respetuosa y responsable con el medio natural, el patrimonio cultural y los valores de la comunidad; promoviendo el consumo de bienes y de servicios con intercambios económicos.
						<b>SEXTA: OBLIGACIONES DEL CONTRATISTA: EL CONTRATISTA:</b> Se obliga para con el <b>CONTRATANTE</b> a lo siguiente (I) disponer de los vehículos determinados y contratados para la prestación del servicio. ( II) A procurar la armonía y convivencia requeridas entre los usuarios del servicio, <b>EL CONTRATANTE</b> y el personal que está a su cargo. (III) Garantizar la prestación del servicio en los términos convenidos, evitando sobre cupos, a la presencia de personas ajenas. (IV) En los eventos de fuerza mayor o en caso fortuito garantizar en cuanto sea posible la prestación del servicio. ( VIII) En <b>SÉPTIMA: CAUSALES DE TERMINACIÓN:</b> El presente contrato terminará por las siguientes causas: ( I) Por mutuo acuerdo de las partes manifestadas con una antelación de 10 días calendario. ( II) Por incumplimiento de alguna o algunas de las obligaciones que surjan del presente contrato. ( III) Por cancelación por parte del <b>CONTRATANTE</b>, en caso que, el vehículo se ubique en el lugar de origen y no se presta el servicio por causa del contratante se cobrara el 50% del valor del servicio.
					</p>
					<p>En constancia se firma el presente contrato el día <b>({{$c->dia_contrato}})</b> del mes <b>{{$c->mes_contrato}}</b> de <b>{{$c->anio_contrato}}</b> </p>
				</div>
			</div>
			<table style="width: 100%; font-size: 11px;">
				<tbody>
					<tr>
						<td style="width: 40%; text-align: left; font-weight: bold;">EL CONTRATANTE</td>
						<td style="width: 20%; text-align: left;"></td>
						<td style="width: 40%; text-align: left; font-weight: bold;">EL CONTRATISTA</td>
					</tr>
					<tr>
						<td style="width: 40%; text-align: left;"><br><br><br><br></td>
						<td style="width: 20%; text-align: left;"><br><br><br><br></td>
						<td style="width: 40%; text-align: left;"><img src="{{asset('img/logos/sello_transporcol.png')}}"></td>
					</tr>
					<tr>
						<td style="width: 40%; text-align: left; border-bottom: 1px solid;"></td>
						<td style="width: 20%; text-align: left;"></td>
						<td style="width: 40%; text-align: left; border-bottom: 1px solid;"></td>
					</tr>
					<tr>
						<td style="width: 40%; text-align: left;">CC/NIT</td>
						<td style="width: 20%; text-align: left;"></td>
						<td style="width: 40%; text-align: left;">CC/NIT</td>
					</tr>
					<tr>
						<td style="width: 40%; text-align: left;">Firma</td>
						<td style="width: 20%; text-align: left;"></td>
						<td style="width: 40%; text-align: left;">Firma</td>
					</tr>
				</tbody>
			</table>
		</div>


		<div class="page-break"></div>


		<div class="row" style="font-size: 14px; line-height: 1.5;">
			<table class="table table-bordered table-striped">
				<tbody>
					<tr>
						<td class="border" style="width: 48%;"><img style="width: 380px; height: 70px;" src="{{ asset('img/logos/min_transporte.png') }}"></td>
						<td class="border" style="width: 12%; text-align: center;"><img style="height: 70px;" src="data:image/png;base64,{{DNS2D::getBarcodePNG($url, 'QRCODE')}}" alt="barcode" /></td>
						<td class="border" style="width: 40%;"><img style="width: 220px; height: 70px;" src="{{ asset('img/logos/transporcol_back.jpg') }}"></td>
					</tr>
				</tbody>
			</table>
			<table style="width: 100%;">
				<tbody>
					<tr>
						<td class="border" style="width: 100%; text-align: center; font-weight: bold;">{{$v->titulo}} <br> N° {{$p->nro}}</td>
					</tr>
				</tbody>
			</table>
			<table style="width: 100%;">
				<tbody>
					<tr>
						<td class="border" style="width: 20%; font-weight: bold;">RAZÓN SOCIAL</td>
						<td class="border" style="width: 50%;">{{$p->razon_social}}</td>
						<td class="border" style="width: 10%; font-weight: bold;">NIT</td>
						<td class="border" style="width: 20%;">{{$p->nit}}</td>
					</tr>
				</tbody>
			</table>
			<table style="width: 100%;">
				<tbody>
					<tr>
						<td class="border" style="width: 20%; font-weight: bold;">CONTRATO No.</td>
						<td class="border" style="width: 80%;">{{$c->numero_contrato}}</td>
					</tr>
				</tbody>
			</table>
			<table style="width: 100%;">
				<tbody>
					<tr>
						<td class="border" style="width: 20%; font-weight: bold;">CONTRATANTE</td>
						<td class="border" style="width: 50%; font-weight: bold; font-size: 10px;">@if($c->contratante_id==null || $c->contratante_id=='null') {{$c->contratanteText}} @else {{$c->contratante->tercero->descripcion}} @endif</td>
						<td class="border" style="width: 10%; font-weight: bold;">NIT/CC</td>
						<td class="border" style="width: 20%; font-weight: bold;">@if($c->contratante_id==null || $c->contratante_id=='null') {{$c->contratanteIdentificacion}} @else {{$c->contratante->tercero->numero_identificacion}} @if($c->contratante->tercero->tipo!='Persona natural') {{"-".$c->contratante->tercero->digito_verificacion}} @endif @endif</td>
					</tr>
				</tbody>
			</table>
			<table style="width: 100%;">
				<tbody>
					<tr>
						<td class="border" style="width: 20%; font-weight: bold; border-right: none;">OBJETO CONTRATO:</td>
						<td class="border" style="width: 80%; font-size: 12px; border-left: none;">{{strtoupper($c->objeto)}}</td>
					</tr>
					<tr>
						<td class="border" style="width: 20%; font-weight: bold;">ORIGEN - DESTINO</td>
						<td class="border" style="width: 80%; font-weight: bold;">{{$c->origen." - ".$c->destino}}</td>
					</tr>
				</tbody>
			</table>
			<table style="width: 100%;">
				<tbody>
					<tr>
						<td class="border" style="width: 100%; font-weight: bold;">CONVENIO CONSORCIO UNION TEMPORAL CON: {{$p->convenio}}</td>
					</tr>
					<tr>
						<td class="border" style="width: 100%; font-weight: bold; text-align: center;">VIGENCIA DEL CONTRATO</td>
					</tr>
				</tbody>
			</table>
			<table style="width: 100%;">
				<tbody>
					<tr>
						<td class="border" style="width: 30%; border-bottom: none;"></td>
						<td class="border" style="width: 20%; font-weight: bold;">DÍA</td>
						<td class="border" style="width: 20%; font-weight: bold;">MES</td>
						<td class="border" style="width: 20%; font-weight: bold;">AÑO</td>
					</tr>
					<tr>
						<td class="border" style="width: 30%; font-weight: bold; border-top: none;">FECHA INICIAL</td>
						<td class="border" style="width: 20%; font-weight: bold;">{{$fi[2]}}</td>
						<td class="border" style="width: 20%; font-weight: bold;">{{ContratoTransporteController::mes()[$fi[1]]}}</td>
						<td class="border" style="width: 20%; font-weight: bold;">{{$fi[0]}}</td>
					</tr>
					<tr>
						<td class="border" style="width: 30%; font-weight: bold;">FECHA FINAL</td>
						<td class="border" style="width: 20%; font-weight: bold;">{{$ff[2]}}</td>
						<td class="border" style="width: 20%; font-weight: bold;">{{ContratoTransporteController::mes()[$ff[1]]}}</td>
						<td class="border" style="width: 20%; font-weight: bold;">{{$ff[0]}}</td>
					</tr>
				</tbody>
			</table>
			<table style="width: 100%;">
				<tbody>
					<tr>
						<td class="border" style="width: 100%; font-weight: bold; text-align: center;">CARACTERÍSTICAS DEL VEHÍCULO</td>
					</tr>
				</tbody>
			</table>
			<table style="width: 100%;">
				<tbody>
					<tr>
						<td class="border" style="width: 15%; font-weight: bold;">PLACA</td>
						<td class="border" style="width: 25%; font-weight: bold;">MODELO</td>
						<td class="border" style="width: 20%; font-weight: bold;">MARCA</td>
						<td class="border" style="width: 40%; font-weight: bold;">CLASE</td>
					</tr>
					<tr>
						<td class="border" style="width: 15%; font-weight: bold;">{{$c->vehiculo->placa}}</td>
						<td class="border" style="width: 25%; font-weight: bold;">{{$c->vehiculo->modelo}}</td>
						<td class="border" style="width: 20%; font-weight: bold;">{{$c->vehiculo->marca}}</td>
						<td class="border" style="width: 40%; font-weight: bold;">{{$c->vehiculo->clase}}</td>
					</tr>
				</tbody>
			</table>
			<table style="width: 100%;">
				<tbody>
					<tr>
						<td class="border" style="width: 40%; font-weight: bold;">NÚMERO INTERNO</td>
						<td class="border" style="width: 60%; font-weight: bold;">NÚMERO TARJETA DE OPERACIÓN</td>
					</tr>
					<tr>
						<td class="border" style="width: 40%; font-weight: bold;">{{$c->vehiculo->int}}</td>
						<td class="border" style="width: 60%; font-weight: bold;">@if($to!=null) {{$to->nro_documento}} @else --- @endif</td>
					</tr>
				</tbody>
			</table>
			<table style="width: 100%;">
				<tbody>
					<tr>
						<td class="border" style="width: 15%; font-weight: bold; font-size: 12px; border-bottom: none;"></td>
						<td class="border" style="width: 32%; font-weight: bold; font-size: 12px;">NOMBRES Y APELLIDOS</td>
						<td class="border" style="width: 13%; font-weight: bold; font-size: 12px;">No CÉDULA</td>
						<td class="border" style="width: 19%; font-weight: bold; font-size: 12px;">No LICENCIA CONDUCCIÓN</td>
						<td class="border" style="width: 10%; font-weight: bold; font-size: 12px;">VIGENCIA</td>
					</tr>
					<tr>
						<td class="border" style="width: 15%; font-weight: bold; font-size: 12px; text-align: center; border-top: none;">DATOS DEL CONDUCTOR 1</td>
						<td class="border" style="width: 32%; font-weight: bold; font-size: 12px;">@if(isset($conductores[0])){{$conductores[0]->conductor->tercero->descripcion}}@endif</td>
						<td class="border" style="width: 13%; font-weight: bold; font-size: 12px;">@if(isset($conductores[0])){{$conductores[0]->conductor->tercero->numero_identificacion}}@endif</td>
						<td class="border" style="width: 19%; font-weight: bold; font-size: 12px;">@if(isset($conductores[0])) @if($conductores[0]->licencia!=null) {{$conductores[0]->licencia->nro_documento}} @endif @endif</td>
						<td class="border" style="width: 10%; font-weight: bold; font-size: 12px;">@if(isset($conductores[0])) @if($conductores[0]->licencia!=null) {{$conductores[0]->licencia->vigencia_fin}} @endif @endif</td>
					</tr>
					<tr>
						<td class="border" style="width: 15%; font-weight: bold; font-size: 12px; text-align: center;">DATOS DEL CONDUCTOR 2</td>
						<td class="border" style="width: 32%; font-weight: bold; font-size: 12px;">@if(isset($conductores[1])){{$conductores[1]->conductor->tercero->descripcion}}@endif</td>
						<td class="border" style="width: 13%; font-weight: bold; font-size: 12px;">@if(isset($conductores[1])){{$conductores[1]->conductor->tercero->numero_identificacion}}@endif</td>
						<td class="border" style="width: 19%; font-weight: bold; font-size: 12px;">@if(isset($conductores[1])) @if($conductores[1]->licencia!=null) {{$conductores[0]->licencia->nro_documento}} @endif @endif</td>
						<td class="border" style="width: 10%; font-weight: bold; font-size: 12px;">@if(isset($conductores[1])) @if($conductores[1]->licencia!=null) {{$conductores[0]->licencia->vigencia_fin}} @endif @endif</td>
					</tr>
					<tr>
						<td class="border" style="width: 15%; font-weight: bold; font-size: 12px; text-align: center;">DATOS DEL CONDUCTOR 3</td>
						<td class="border" style="width: 32%; font-weight: bold; font-size: 12px;">@if(isset($conductores[2])){{$conductores[2]->conductor->tercero->descripcion}}@endif</td>
						<td class="border" style="width: 13%; font-weight: bold; font-size: 12px;">@if(isset($conductores[2])){{$conductores[2]->conductor->tercero->numero_identificacion}}@endif</td>
						<td class="border" style="width: 19%; font-weight: bold; font-size: 12px;">@if(isset($conductores[2])) @if($conductores[2]->licencia!=null) {{$conductores[0]->licencia->nro_documento}} @endif @endif</td>
						<td class="border" style="width: 10%; font-weight: bold; font-size: 12px;">@if(isset($conductores[2])) @if($conductores[2]->licencia!=null) {{$conductores[0]->licencia->vigencia_fin}} @endif @endif</td>
					</tr>
					<tr>
						<td class="border" style="width: 15%; font-weight: bold; font-size: 12px; border-bottom: none;"></td>
						<td class="border" style="width: 32%; font-weight: bold; font-size: 12px;">NOMBRES Y APELLIDOS</td>
						<td class="border" style="width: 13%; font-weight: bold; font-size: 12px;">No CÉDULA</td>
						<td class="border" style="width: 19%; font-weight: bold; font-size: 12px;">DIRECCIÓN</td>
						<td class="border" style="width: 10%; font-weight: bold; font-size: 12px;">TELÉFONO</td>
					</tr>
					<tr>
						<td class="border" style="width: 15%; font-weight: bold; font-size: 12px; text-align: center; border-top: none;">RESPONSABLE DEL CONTRATANTE</td>
						<td class="border" style="width: 32%; font-weight: bold; font-size: 10px;">@if($c->contratante_id==null || $c->contratante_id=='null') {{$c->contratanteText}} @else {{$c->contratante->tercero->descripcion}} @endif</td>
						<td class="border" style="width: 13%; font-weight: bold; font-size: 12px;">@if($c->contratante_id==null || $c->contratante_id=='null') {{$c->contratanteIdentificacion}} @else {{$c->contratante->tercero->numero_identificacion}} @if($c->contratante->tercero->tipo!='Persona natural') {{"-".$c->contratante->tercero->digito_verificacion}} @endif @endif</td>
						<td class="border" style="width: 19%; font-weight: bold; font-size: 12px;">@if($c->contratante_id==null || $c->contratante_id=='null') {{$c->contratanteDireccion}} @else {{$c->contratante->tercero->direccion1}} @endif</td>
						<td class="border" style="width: 10%; font-weight: bold; font-size: 12px;">@if($c->contratante_id==null || $c->contratante_id=='null') {{$c->contratanteTelefono}} @else {{$c->contratante->tercero->telefono1}} @endif</td>
					</tr>
				</tbody>
			</table>
			<table style="width: 100%;">
				<tbody>
					<tr>
						<td class="border" style="width: 40%; text-align: center; font-weight: bold; margin-top: 10px !important;">@if($emp!=null) {{$emp->direccion1." - "}} @endif {{$v->direccion}}<br> @if($emp!=null) {{$emp->telefono1." - "}} @endif {{$v->telefono}}<br><a> @if($emp!=null) {{$emp->email." - "}} @endif {{$v->correo}}</a></td>
						<td class="border" style="width: 20%; text-align: center; font-weight: bold; margin-top: 10px !important;"><img src="{{asset('img/logos/sello_transporcol.png')}}"><br>Sello</td>
						<td class="border" style="width: 40%; text-align: center; font-weight: bold; margin-top: 10px !important; font-size: 14px;"><img src="{{asset('img/logos/firma_transporcol.png')}}"><br>FIRMA<br><i style="font-size: 9px; text-decoration: none;" valign="bottom">{{$v->firma}}</i></td>
					</tr>
				</tbody>
			</table>
			<table style="width: 100%;">
				<tbody>
					<tr>
						<td class="border" style="width: 100%; text-align: justify; font-size: 10px;">{!!$v->pie_pagina1!!}</a></td>
					</tr>
				</tbody>
			</table>
		</div>

		<div class="page-break"></div>

		<div class="row" style="font-size: 12px !important; line-height: 1.2;">
			<table class="table table-bordered table-striped">
				<tbody>
					<tr>
						<td class="border" style="width: 48%;"><img style="width: 380px; height: 70px;" src="{{ asset('img/logos/min_transporte.png') }}"></td>
						<td class="border" style="width: 12%; text-align: center;"><img style="height: 70px;" src="data:image/png;base64,{{DNS2D::getBarcodePNG($url, 'QRCODE')}}" alt="barcode" /></td>
						<td class="border" style="width: 40%;"><img style="width: 220px; height: 70px;" src="{{ asset('img/logos/transporcol_back.jpg') }}"></td>
					</tr>
				</tbody>
			</table>
				<table style="width: 100%;">
					<tbody>
						<tr>
							<td class="border" style="width: 100%; text-align: center; font-weight: bold;">{{$v->titulo}} <br> N° {{$p->nro}}</td>
						</tr>
					</tbody>
				</table>
				<table style="width: 100%; line-height: 0.9;">
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
</body>

</html>