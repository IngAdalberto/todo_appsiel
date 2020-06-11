@extends('layouts.principal')

@section('webstyle')
<style>
	.page {
		padding: 50px;
		-webkit-box-shadow: 0px 0px 20px -3px rgba(0, 0, 0, 0.9);
		-moz-box-shadow: 0px 0px 20px -3px rgba(0, 0, 0, 0.9);
		box-shadow: 0px 0px 20px -3px rgba(0, 0, 0, 0.9);
		font-size: 16px;
	}

	.border {
		border: 1px solid;
		padding: 5px;
	}
</style>
@endsection

@section('content')
{{ Form::bsMigaPan($miga_pan) }}
<hr>

@include('layouts.mensajes')

<div class="container-fluid">
	<div class="marco_formulario">
		&nbsp;
		<div class="row" style="padding: 20px;">
			<div class="col-md-12">
				<div class="panel panel-primary">
					<div class="panel-heading">Crear Contrato</div>
					<div class="panel-body">
						<div class="col-md-12" style="padding: 50px;">
							<div class="col-md-12 page">
								{{ Form::open(['route'=>'cte_contratos.store','method'=>'post','class'=>'form-horizontal']) }}
								<input type="hidden" name="variables_url" value="{{$variables_url}}" />
								<table style="width: 100%;">
									<tbody>
										<tr>
											<td class="border" style="width: 20%;"><img style="width: 100%;" src="{{ asset('img/logos/transporcol_back_contrato.jpg') }}"></td>
											<td class="border" style="width: 68%; text-align: center;">
												<div class="col-md-8" style="border-right: 1px solid; font-size: 24px; line-height: 0.9em;">
													<p style="font-weight: bold; color: #000;">{{$e->descripcion}}</p>
													<p style="font-weight: bold; color: #000;">{{$e->razon_social}}</p>
													<p style="font-size: 20px; font-weight: bold; color: #000;">NIT: {{$e->numero_identificacion."-".$e->digito_verificacion}}</p>
												</div>
												<div class="col-md-4" style="text-align: left;">
													<input type="text" class="form-control" name="codigo" required placeholder="Código: ejemplo FR-GA-03" />
													<input type="text" class="form-control" name="version" required placeholder="Versión: ejemplo 2.0" />
													<input type="date" class="form-control" required name="fecha" />
												</div>
												<div class="col-md-12" style="border-top: 1px solid;">
													<p style="font-size: 20px; font-weight: bold;">CONTRATO</p>
												</div>
											</td>
											<td class="border" style="width: 12%;"><img style="max-height: 150px;" src="{{ asset('img/logos/transporcol_rigth.jpg') }}"></td>
										</tr>
									</tbody>
								</table>
								<div class="row" style="margin-top: 20px;">
									<div class="col-md-12" style="text-align: center;">
										<p><b>CONTRATO DE PRESTACION DE SERVICIO DE TRANSPORTE N° <input type="text" required name="numero_contrato" /></b></p>
									</div>
									<div class="col-md-12" style="margin-top: 20px;">
										<p>Entre los suscritos a saber <input type="text" style="width: 300px !important;" name="rep_legal" required value="HUBER PARADA QUINTERO">
											en representación de la empresa <b>{{$e->descripcion}}</b> con Nit. <b>{{$e->numero_identificacion."-".$e->digito_verificacion}}</b>, legalmente constituida
											y habilitada por el ministerio de transporte para la prestación del servicio transporte
											especial, de aquí en adelante el <b>CONTRATISTA</b>, y por otro lado <b>EL CONTRATANTE</b>
											<select class="select2" name="contratante_id" required>
												@if($contratantes!=null)
												@foreach($contratantes as $key=>$value)
												<option value="{{$key}}">{!!$value!!}</option>
												@endforeach
												@endif
											</select>
											en representación de <input type="text" required name="representacion_de" style="width: 800px !important;" value="PARA UN GRUPO ESPECIFICO DE USUARIOS DE TRANSPORTE DE PERSONAL (TRANSPORTE PARTICULAR)" />
										</p>
										<div class="table-responsive col-md-12" id="table_content">
											<h4>DESCRIPCIÓN DEL GRUPO DE USUARIOS</h4>
											<a onclick="addRow('usuarios')" class="btn btn-danger btn-xs"><i class="fa fa-plus"></i> Agregar Usuario</a>
											<table id="usuarios" class="table table-bordered table-striped">
												<thead>
													<tr>
														<th>Identificación</th>
														<th>Persona</th>
														<th>Quitar</th>
													</tr>
												</thead>
												<tbody>

												</tbody>
											</table>
										</div>
										<p>
											El presente contrato será desarrollado por el propietario del vehículo automotor de
											<select class="select2" name="vehiculo_id" required>
												@if($vehiculos!=null)
												@foreach($vehiculos as $key=>$value)
												<option value="{{$key}}">{!!$value!!}</option>
												@endforeach
												@endif
											</select>
											quien cumplirá todas las obligaciones derivadas del mismo. Hemos convenido celebrar el contrato de
											<b>TRANSPORTE DE GRUPO DE USUARIOS</b>, el cual se regirá por las siguientes clausula, y en lo no previsto en ellas, por lo dispuesto en la ley.
											<b>CLAUSULA PRIMERA - OBJETO DEL CONTRATO:</b> <input type="text" style="width: 100%;" required name="objeto" value="el objeto del contrato consiste en el transporte terrestre de pasajeros, mediante un servicio expreso al grupo específico descrito anteriormente" /> <b>CLAUSULA SEGUNDA: CARACTERISTICA DEL SERVICIO.</b>
											<b>ORIGEN </b><input type="text" name="origen" required /> <b>DESTINO </b><input type="text" name="destino" required />
											<b>FECHA DE INICIAL </b><input type="date" name="fecha_inicio" required /> <b>FECHA VENCIMIENTO</b> <input onchange="validar()" type="date" name="fecha_fin" id="fecha_fin" required />
											<b>CLAUSULA TERCERA. OBLIGACION DEL CONTRATANTE:</b> El <b>CONTARTANTE</b> se
											obliga con el <b>CONTARISTA</b>, a lo siguiente: <b>A)</b> Dar aviso de los servicios de transporte
											requerido con la suficiente anticipación, indicando claramente número de pasajeros,
											destino y demás detalles del servicios <b>B)</b> Cumplir con lo establecido en el presente
											contrato en forma oportuna, dentro de los términos establecidos y de conformidad con las
											calidades pactadas. <b>C)</b> Pagar el valor de la contraprestación en los términos y condiciones
											establecidas en este contrato. <b>D)</b> A cancelar los valores pactados para la ejecución del
											contrato de transporte que hace referencia este documento.
										</p>
										<table>
											<tbody>
												<tr>
													<td>Valor del Contrato </td>
													<td>$<input type="text" name="valor_contrato" required /></td>
												</tr>
												<tr>
													<td>Valor cancelado a la empresa </td>
													<td>$<input type="text" name="valor_empresa" required /></td>
												</tr>
												<tr>
													<td>Valor Cancelado al Propietario </td>
													<td>$<input type="text" name="valor_propietario" required /></td>
												</tr>
											</tbody>
										</table>
										<p>
											<b>CLAUSULA CUARTA. OBLIGACION DEL CONTRATISTA:</b> El <b>CONTARTISTA</b> se
											obliga con el <b>CONTARTANTE A)</b> Cumplir con lo establecido en el presente contrato en
											forma oportuna, dentro del término establecido y de conformidad con las calidades
											pactadas <b>B)</b> Prestar el servicios en el vehículo arriba descrito que cumpla con todas las
											exigencias del ministerio de transporte y cumplir con las disposiciones legales
											contempladas en la ley 769 del 6 de agosto del 2002, el decreto 174 de 5 de febrero 2001
											<b>C)</b> Cumplir estrictamente con idoneidad y oportunidad en la ejecución del presente
											contrato. <b>CLAUSULA QUINTA. TERMINACION:</b> El presente contrato podrás darse por
											terminado por mutuo acuerdo entre las partes, sin lugar a indemnización alguna; o en
											forma unilateral por cumplimiento de las obligaciones derivadas del contrato; o bien, por
											que desaparezca las condiciones que le dieron origen sea por parte del <b>CONTRATANTE</b>
											o el <b>CONTARTISTA. CLAUSULA SEXTA. CESION</b> el presente contrato se celebra en
											consideración a la calidad del <b>CONTRATISTA</b>, quien no lo podrá ceder a subcontratar
											total o parcialmente sin consentimiento previo y por escrito del <b>CONTRATANTE.
												CLAUSULA SEPTIMA. INDEPENDENCIA DELA CONTARTISTA:</b> para todos los efectos
											legas, el presente contrato es de carácter civil y, en consecuencia el contratista, actuara
											por su propia cuenta, con absoluta autonomía y no estará sometido a subordinación
											laboral con el <b>CONTRATANTE</b>, para quien, sus derecho se limitaran, de acuerdo con la
											naturaleza del contrato, a exigir el cumplimiento de las obligaciones del <b>CONTRATISTA</b>,
											tendrá plena libertad y autonomía en la ejecución y cumplimiento de este contrato y en
											ningún momento tendrá relación laboral con el <b>CONTRATANTE. CLAUSULA OCTAVA.
												MODIFICACIONES:</b> el presente contrato podrá ser modificado por acuerdo entre las
											partes, mediante la suscripción de documento que indique con claridad y precisión la
											forma acordada. <b>CLAUSULA NOVENA. DOMICILIO CONTRACTUAL;</b> las notificaciones
											serán recibidas por las partes en las siguiente direcciones <b>CONTRATANTE
												<input type="text" name="direccion_notificacion" required /> TELEFONO o CELULAR
												<input type="text" name="telefono_notificacion" required /> CONTRATISTA</b> Carrera 10 # 16B - 29 Local 2 Segundo Piso
											<b>TELEFONO o CELULAR 572269 - 3186128754 – 3223039437.</b>
										</p>
										<p>
											En señal de aceptación, se firma el presente documento a los <input type="number" required name="dia_contrato" /> días del mes de
											<input type="text" name="mes_contrato" placeholder="JUNIO del año 2020" required />.
										</p>
									</div>
								</div>
								<br><br><br>
								<table style="width: 100%;">
									<tbody>
										<tr>
											<td style="width: 40%; text-align: left;">EL CONTRATANTE</td>
											<td style="width: 20%; text-align: left;"></td>
											<td style="width: 40%; text-align: left;">EL CONTRATISTA</td>
										</tr>
										<tr>
											<td style="width: 40%; text-align: left;"><br><br><br><br></td>
											<td style="width: 20%; text-align: left;"><br><br><br><br></td>
											<td style="width: 40%; text-align: left;"><br><br><br><br></td>
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
								<div class="col-md-12" style="margin-top: 50px; border-bottom: 10px solid; border-color: #6cf5ee;"></div>
								<div class="row" style="margin-top: 50px;">
									<div class="col-md-11" style="margin-top: 30px; text-align: center;">
										<p>
											<b><input style="text-align: center;" class="form-control" type="text" required name="pie_uno" value="*Valledupar: Carrera 10 N° 16B – 29 segundo piso Local 2 Sector Centro Teléfono 5722269 - Cel. 3186128754" /></b>
											<b><input style="text-align: center;" class="form-control" type="text" required name="pie_dos" value="*Bucaramanga: Calle 41 N° 32-59 local 203 edificio profesional el prado cel: 3168757905" /></b>
											<b><input style="text-align: center;" class="form-control" type="text" required name="pie_tres" value="*Aguachica Calle 3 N° 14- 57 tel. 5661208" /></b>
											<b><input style="text-align: center;" class="form-control" type="text" required name="pie_cuatro" value="www.transporcol.com* Email: transporcol2009@gmail.com" /></b>
										</p>
									</div>
									<div class="col-md-1" style="margin-top: 50px; text-align: center;">
										<img style="width: 100%;" src="{{ asset('img/logos/super_transporte.png') }}">
									</div>
								</div>
								<div class="form-group">
									<div class="col-md-12" style="margin-top: 50px;">
										<button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Guardar Contrato</button>
									</div>
								</div>
								</form>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
@endsection


@section('scripts')
<script type="text/javascript">
	$(document).ready(function() {
		$('.select2').select2();
	});

	$(document).on('click', '.delete', function(event) {
		event.preventDefault();
		$(this).closest('tr').remove();
	});

	function addRow(tabla) {
		var html = "<tr><td><input type='text' class='form-control' name='identificacion[]' required /></td><td><input type='text' class='form-control' name='persona[]' required /></td><td><a class='btn btn-xs btn-danger delete'><i class='fa fa-trash-o'></i></a></td></tr>";
		$('#' + tabla + ' tr:last').after(html);
	}
	

	function validar() {
		var f = $("#fecha_fin").val();
		var v = f.split("-");
		var hoy = new Date();
		var mes = hoy.getMonth() + 1;
		if (mes != parseInt(v[1])) {
			Swal.fire({
				icon: 'error',
				title: 'Oh no!',
				text: 'La fecha final no puede ser de un mes diferente al actual, si continua sin corregir el contrato no será guardado y perderá los datos',
				footer: '<a href>¿Desea continuar?</a>'
			});
		}
	}
</script>
@endsection