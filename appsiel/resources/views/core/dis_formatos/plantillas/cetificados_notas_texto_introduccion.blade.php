
<br>
<div align="center">
	EL SUSCRITO RECTOR Y SECRETARIA DE: {{ $colegio->descripcion }}
</div>		
<div align="center">
	CERTIFICAN QUE:
</div>
<br>

<?php
	if( config('calificaciones.detallar_curso_grado') == 'grado' ){
		$label = 'grado <b>'.$curso->grado->descripcion.'</b>';
	}else {
		$label = 'curso <b>'.$curso->descripcion.'</b>';
	}

	$texto_resultado = 'cursó';
	if( $resultado_academico != '' ){
		$texto_resultado = 'cursó y <b>' . $resultado_academico . '</b>';
	}
?>

<div style="text-align: justify;">
<b>{{ $estudiante->nombre_completo }}</b> {!! $texto_resultado !!} en esta institución educativa los estudios correspondientes al {!! $label !!} de educación en Nivel <b>{{ $curso->nivel->descripcion }}</b>, según pensum oficial. Habiendo obtenido en el periodo lectivo {{ $periodo_lectivo->descripcion }} los resultados que a continuación se registran:
</div>		

<br>
				