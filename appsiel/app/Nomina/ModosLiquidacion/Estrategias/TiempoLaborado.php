<?php

namespace App\Nomina\ModosLiquidacion\Estrategias;

use App\Nomina\ModosLiquidacion\LiquidacionConcepto;
use App\Nomina\NomDocRegistro;

use Carbon\Carbon;
use Auth;

class TiempoLaborado implements Estrategia
{
	const CANTIDAD_HORAS_DIA_LABORAL = 8;

	public function calcular(LiquidacionConcepto $liquidacion)
	{

		/*$registros_documento = NomDocRegistro::where( 'nom_doc_registros.nom_doc_encabezado_id', $liquidacion['documento_nomina']->id )
													->where( 'nom_doc_registros.core_tercero_id', $liquidacion['empleado']->core_tercero_id )
													->get();*/

		$lapso = $liquidacion['documento_nomina']->lapso();
		$registros_documento = NomDocRegistro::whereBetween( 'nom_doc_registros.fecha', [$lapso->fecha_inicial,$lapso->fecha_final] )
													->where( 'nom_doc_registros.core_tercero_id', $liquidacion['empleado']->core_tercero_id )
													->get();

		$horas_liquidadas_empleado = 0;
        foreach ($registros_documento as $registro )
        {   
            if ( !is_null($registro->concepto) )
            {
                // 7: Tiempo NO Laborado, 1: tiempo laborado
                if ( in_array($registro->concepto->modo_liquidacion_id, [1,7] ) )
                {
                    $horas_liquidadas_empleado += $registro->cantidad_horas;
                }
            }
        }

		// NO se puede liquidar más tiempo del que tiene el documento
		if ( $horas_liquidadas_empleado >= $liquidacion['documento_nomina']->tiempo_a_liquidar )
		{
			return [ 
						[
							'cantidad_horas' => 0,
							'valor_devengo' => 0,
							'valor_deduccion' => 0 
						]
					];
		}

		// Para salario integral
		if ( ( (int)config('nomina.concepto_salario_integral') == $liquidacion['concepto']->id ) && !$liquidacion['empleado']->salario_integral )
		{
			return [ 
						[
							'cantidad_horas' => 0,
							'valor_devengo' => 0,
							'valor_deduccion' => 0 
						]
					];
		}

		// Para salario integral
		if ( ( (int)config('nomina.concepto_salario_integral') != $liquidacion['concepto']->id ) && $liquidacion['empleado']->salario_integral )
		{
			return [ 
						[
							'cantidad_horas' => 0,
							'valor_devengo' => 0,
							'valor_deduccion' => 0 
						]
					];
		}

		// El concepto de aprencies no se liquida automáticamente
		if ( (int)config('nomina.concepto_a_pagar_pasante_sena') == $liquidacion['concepto']->id )
		{
			return [ 
						[
							'cantidad_horas' => 0,
							'valor_devengo' => 0,
							'valor_deduccion' => 0 
						]
					];
		}

		if ( $liquidacion['empleado']->es_pasante_sena )
		{
			$this->liquidacion_pasante_sena( $liquidacion['documento_nomina'], $liquidacion['empleado'] );

			return [ 
						[
							'cantidad_horas' => 0,
							'valor_devengo' => 0,
							'valor_deduccion' => 0 
						]
					];
		}


		$salario_x_hora = $liquidacion['empleado']->salario_x_hora();
		
		$tiempo_a_liquidar = $this->get_tiempo_a_liquidar( $liquidacion['empleado'], $liquidacion['documento_nomina'], $horas_liquidadas_empleado );

		$valores = get_valores_devengo_deduccion( $liquidacion['concepto']->naturaleza, ( $salario_x_hora * $tiempo_a_liquidar ) * $liquidacion['concepto']->porcentaje_sobre_basico / 100 );

		return [ 
					[
						'cantidad_horas' => $tiempo_a_liquidar,
						'valor_devengo' => $valores->devengo,
						'valor_deduccion' => $valores->deduccion 
					]
				];
	}

	public function get_tiempo_a_liquidar( $empleado, $documento_nomina, $horas_liquidadas_empleado )
	{
		if ( $empleado->contrato_hasta < $documento_nomina->lapso()->fecha_inicial )
		{
			return 0;
		}

		// Caso 1: el contrato empieza dentro del lapso del documento
		$tiempo_a_descontar_1 = 0;
		if ( $empleado->fecha_ingreso >= $documento_nomina->lapso()->fecha_inicial && $empleado->fecha_ingreso <= $documento_nomina->lapso()->fecha_final )
		{
			// Se suma 1, pues se debe incluir el mismo día inicial.
			$tiempo_a_descontar_1 = $this->diferencia_en_dias_entre_fechas( $documento_nomina->lapso()->fecha_inicial, $empleado->fecha_ingreso ) * self::CANTIDAD_HORAS_DIA_LABORAL;
		}

		// Caso 2: el contrato termina dentro del lapso del documento
		$tiempo_a_descontar_2 = 0;
		if ( $empleado->contrato_hasta >= $documento_nomina->lapso()->fecha_inicial && $empleado->contrato_hasta <= $documento_nomina->lapso()->fecha_final )
		{
			// Se suma 1, pues se debe incluir el mismo día inicial.
			$tiempo_a_descontar_2 = $this->diferencia_en_dias_entre_fechas( $empleado->contrato_hasta, $documento_nomina->lapso()->fecha_final ) * self::CANTIDAD_HORAS_DIA_LABORAL;
		}
		
        $tiempo_a_liquidar = $documento_nomina->tiempo_a_liquidar - $tiempo_a_descontar_1 - $tiempo_a_descontar_2 - $horas_liquidadas_empleado;

        return $tiempo_a_liquidar;
	}



	public function diferencia_en_dias_entre_fechas( string $fecha_inicial, string $fecha_final )
	{
		$fecha_ini = Carbon::createFromFormat('Y-m-d', $fecha_inicial);
		$fecha_fin = Carbon::createFromFormat('Y-m-d', $fecha_final );

		return abs( $fecha_ini->diffInDays($fecha_fin) );
	}

	public function liquidacion_pasante_sena( $documento_nomina, $empleado)
	{
		$concepto_id = (int)config('nomina.concepto_a_pagar_pasante_sena');

		$cant = NomDocRegistro::where( 'nom_doc_encabezado_id', $documento_nomina->id)
                                        ->where('core_tercero_id', $empleado->core_tercero_id)
                                        ->where('nom_concepto_id', $concepto_id)
                                        ->count();
        if ( $cant != 0 ) 
        {
            return 0;
        }

		// Liquidar concepto de sostenimiento y apoyo
		$valor_devengo_mes = (float)config('nomina.SMMLV') * (float)config('nomina.porcentaje_liquidacon_pasante_sena') / 100;
		$salario_x_hora = $valor_devengo_mes / 240;
		$valor_devengo = $salario_x_hora * $documento_nomina->tiempo_a_liquidar;
		NomDocRegistro::create(
                                    ['nom_doc_encabezado_id' => $documento_nomina->id ] + 
                                    ['fecha' => $documento_nomina->fecha] + 
                                    ['core_empresa_id' => $documento_nomina->core_empresa_id] +  
                                    ['nom_concepto_id' => $concepto_id ] + 
                                    ['core_tercero_id' => $empleado->core_tercero_id ] + 
                                    ['nom_contrato_id' => $empleado->id ] + 
                                    ['estado' => 'Activo'] + 
                                    ['creado_por' => Auth::user()->email] + 
                                    ['valor_devengo' => $valor_devengo ]+  
                                    ['cantidad_horas' => $documento_nomina->tiempo_a_liquidar ]+ 
                                    ['modificado_por' => '']
                                );
	}

	public function retirar( NomDocRegistro $registro )
	{
        $registro->delete();

        return 0;
	}
}