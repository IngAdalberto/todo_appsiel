<?php

namespace App\Nomina\ModosLiquidacion\Estrategias;

use App\Nomina\ModosLiquidacion\LiquidacionConcepto;

use Auth;
use Carbon\Carbon;

use App\Nomina\NovedadTnl;
use App\Nomina\NomDocRegistro;

class PrestacionSocial implements Estrategia
{

	protected $valor_a_pagar_eps;
	protected $valor_a_pagar_arl;
	protected $valor_a_pagar_afp;
	protected $valor_a_pagar_empresa;
	
	/*
		tipo_novedad_tnl: { incapacidad | permiso_remunerado | permiso_no_remunerado | suspencion }
		origen_incapacidad: { comun | laboral }
		clase_incapacidad: { enfermedad_general | licencia_maternidad | licencia_paternidad | accidente_trabajo | enfermedad_profesional}
	*/

	const CANTIDAD_HORAS_DIA_LABORAL = 8;

	public function calcular(LiquidacionConcepto $liquidacion)
	{
		$lapso_documento = $liquidacion['documento_nomina']->lapso();

		$novedades = NovedadTnl::where( [
											[ 'nom_concepto_id', '=', $liquidacion['concepto']->id ],
											[ 'nom_contrato_id', '=', $liquidacion['empleado']->id ],
											[ 'cantidad_dias_pendientes_amortizar', '>', 0 ],
											[ 'fecha_inicial_tnl', '<=', $lapso_documento->fecha_final ],
											[ 'tipo_novedad_tnl', '=', 'vacaciones' ],
											[ 'estado', '=', 'Activo' ] 
										] )
								->get();

		$valores_novedades = [];
        foreach( $novedades as $novedad )
        {			
			// NO se puede liquidar más tiempo del que tiene el documento
			if ( $liquidacion['documento_nomina']->horas_liquidadas_empleado( $liquidacion['empleado']->core_tercero_id ) >= $liquidacion['documento_nomina']->tiempo_a_liquidar )
			{
				continue;
			}

			$cantidad_horas_a_liquidar = abs( $this->calcular_cantidad_horas_liquidar_novedad( $novedad, $lapso_documento ) );

        	$valor_real_novedad = 0;    		

			$novedad->cantidad_dias_amortizados += ($cantidad_horas_a_liquidar / self::CANTIDAD_HORAS_DIA_LABORAL);
			$novedad->cantidad_dias_pendientes_amortizar -= ($cantidad_horas_a_liquidar / self::CANTIDAD_HORAS_DIA_LABORAL);
        	$novedad->save();

            $valores = get_valores_devengo_deduccion( $liquidacion['concepto']->naturaleza, $valor_real_novedad );

            $valores_novedades[] = [
	                                    'cantidad_horas' => $cantidad_horas_a_liquidar,
										'valor_devengo' => $valores->devengo,
										'valor_deduccion' => $valores->deduccion,
										'novedad_tnl_id' => $novedad->id
                                	];
        }
        return $valores_novedades;
	}

	public function calcular_valores_liquidar_novedad( &$novedad, $empleado, $documento_nomina, $cantidad_horas_a_liquidar, $salario_x_hora )
	{
		switch ( $novedad->tipo_novedad_tnl )
		{
			case 'incapacidad':
				
				$this->calcular_valores_liquidar_incapacidad( $novedad, $empleado, $cantidad_horas_a_liquidar );

				$novedad->valor_a_pagar_eps += $this->valor_a_pagar_eps;
				$novedad->valor_a_pagar_arl += $this->valor_a_pagar_arl;
				$novedad->valor_a_pagar_afp += $this->valor_a_pagar_afp;
				$novedad->valor_a_pagar_empresa += $this->valor_a_pagar_empresa;

				$valor_registro = $this->valor_a_pagar_eps + $this->valor_a_pagar_arl + $this->valor_a_pagar_afp;

				// Cuando todo lo paga la empresa ( 2 primeros días )
				if ( $this->valor_a_pagar_empresa > 0 && $valor_registro == 0)
				{
					$valor_registro = $this->valor_a_pagar_empresa;
				}

				$valor_novedad = $valor_registro;
				
				break;
			
			case 'permiso_remunerado':
				$valor_novedad = $salario_x_hora * $cantidad_horas_a_liquidar;
				break;
			
			case 'permiso_no_remunerado':
				$valor_novedad = 0.0001;
				break;
			
			case 'suspencion':
				$valor_novedad = 0.0001;
				break;
			
			default:
				# code...
				break;
		}

		return $valor_novedad;
	}

	public function crear_registro_concepto_pagado_por_la_empresa( $novedad, $documento_nomina, $empleado)
	{

		// Crear registro adicional en el documento (GASTO EMPRESA)
		if ( $this->valor_a_pagar_empresa > 0 )
		{
			$cantidad_horas = 0;

			$registro = NomDocRegistro::create(
                            [ 'nom_doc_encabezado_id' => $documento_nomina->id ] + 
                            [ 'fecha' => $documento_nomina->fecha] + 
                            [ 'core_empresa_id' => $documento_nomina->core_empresa_id] +  
                            [ 'nom_concepto_id' => (int)config('nomina.id_concepto_pagar_empresa_en_incapacidades') ] + 
                            [ 'core_tercero_id' => $empleado->core_tercero_id ] + 
                            [ 'nom_contrato_id' => $empleado->id ] +
                            [ 'estado' => 'Activo' ] + 
                            [ 'creado_por' => Auth::user()->email ] + 
                            [ 'modificado_por' => '' ] +
                            [ 'cantidad_horas' => $cantidad_horas ] +
							[ 'valor_devengo' => $this->valor_a_pagar_empresa ] +
							[ 'valor_deduccion' => 0 ] +
							[ 'novedad_tnl_id' => $novedad->id ]
                        );

		}
	}

	public function calcular_valores_liquidar_incapacidad( $novedad, $empleado, $cantidad_horas_a_liquidar )
	{
		$porcentaje_liquidacion_legal = 66.66;
		if ( $novedad->cantidad_dias_amortizados > 90 )
		{
			$porcentaje_liquidacion_legal = 50;
		}
		
		// Las incapacidades de origen "laboral" se pagan al 100%
		// Las incapacidades de origen "comun" se pagan al 66.66%
		// La empresa puede asumir o NO el pago del otro 33.33%
		$porcentaje_a_pagar = 100;
		if ( (int)config('nomina.pago_salario_completo_en_incapacidades') == 0 && $novedad->origen_incapacidad != 'laboral' )
		{
			$porcentaje_a_pagar = $porcentaje_liquidacion_legal;
		}

		$horas_laborales = (int)config('nomina.horas_laborales');
		if ( $empleado->horas_laborales != 0 )
		{
			$horas_laborales = $empleado->horas_laborales;
		}

		// El IBC almacenado es el del mes anterior; si no existe, se toma el sueldo
		$valor_hora = $empleado->valor_ibc() / $horas_laborales;
		// Se debe respetar al salario mínimo
		if( $valor_hora < (float)config('nomina.SMMLV') / (float)config('nomina.horas_laborales') )
		{
			$valor_hora = (float)config('nomina.SMMLV') / (float)config('nomina.horas_laborales');
		}

		$valor_total_liquidar = $valor_hora * $cantidad_horas_a_liquidar;
		
		$valor_a_pagar_eps = 0;
		$valor_a_pagar_arl = 0;
		$valor_a_pagar_afp = 0;
		$valor_a_pagar_empresa = 0;

		if ( $novedad->origen_incapacidad == 'laboral' )
		{
			$valor_a_pagar_arl = $valor_total_liquidar;
		}else{

			// Los dos primeros días a cargo de la empresa (Artículo 3.2.1.10 decreto 780)
			$valor_a_pagar_empresa = $valor_total_liquidar;

			$valor_porcentual = $valor_total_liquidar * ($porcentaje_a_pagar / 100);

			// Periodo de incapacidad de 3 a 180 día a cargo de la EPS (en el porcentaje legal) (Artículo 3.2.1.10 decreto 780)
			if ( $novedad->cantidad_dias_tnl > 2 )
			{
				$valor_a_pagar_eps = $valor_total_liquidar * ($porcentaje_liquidacion_legal / 100);
				$valor_a_pagar_empresa = $valor_porcentual - $valor_a_pagar_eps;
			}

			// Periodo de incapacidad mayor a 180 día a cargo del Fondo de pensiones (AFP) (en el porcentaje legal) (Artículo 41 ley 100 de 1993)
			if ( $novedad->cantidad_dias_amortizados > 180 )
			{
				$valor_a_pagar_afp = $valor_total_liquidar * ($porcentaje_liquidacion_legal / 100);
				$valor_a_pagar_empresa = $valor_porcentual - $valor_a_pagar_eps;
			}
		}

		$this->valor_a_pagar_eps = $valor_a_pagar_eps;
		$this->valor_a_pagar_arl = $valor_a_pagar_arl;
		$this->valor_a_pagar_afp = $valor_a_pagar_afp;
		$this->valor_a_pagar_empresa = $valor_a_pagar_empresa;
	}

	public function calcular_cantidad_horas_liquidar_novedad( $novedad, $lapso_documento )
	{

		$fecha_ini_novedad = strtotime( $novedad->fecha_inicial_tnl );
		$fecha_fin_novedad = strtotime( $novedad->fecha_final_tnl );

		$fecha_ini_documento = strtotime( $lapso_documento->fecha_inicial );
		$fecha_fin_documento = strtotime( $lapso_documento->fecha_final );


		// Caso 1: Liquidar todo el tiempo de la novedad
		if ( $fecha_ini_novedad >= $fecha_ini_documento && $fecha_ini_novedad <= $fecha_fin_documento && $fecha_fin_novedad <= $fecha_fin_documento )
		{
			return $novedad->cantidad_horas_tnl;
		}

		// Caso 2: Liquidar una parte del tiempo de la novedad, el tiempo restante queda para el siguiente documento
		if ( $fecha_ini_novedad >= $fecha_ini_documento && $fecha_ini_novedad < $fecha_fin_documento && $fecha_fin_novedad > $fecha_fin_documento )
		{
			$diferencia_en_dias = $this->diferencia_en_dias_entre_fechas( $novedad->fecha_inicial_tnl, $lapso_documento->fecha_final );

			return ( ( $diferencia_en_dias + 1 ) * self::CANTIDAD_HORAS_DIA_LABORAL ); // Se suma 1, pues se debe incluir el mismo día inicial.
		}

		// Caso 3: La novedad es vieja; ya tiene tiempos amortizados. Se continua amortizando desde la fecha inicial del lapso
		if ( $fecha_ini_novedad < $fecha_ini_documento )
		{
			if ( $fecha_fin_novedad > $fecha_fin_documento )
			{
				// Caso 3.1: liquidar todo el tiempo del lapso
				$diferencia_en_dias = $this->diferencia_en_dias_entre_fechas( $lapso_documento->fecha_inicial, $lapso_documento->fecha_final );
			}else{
				// Caso 3.2: liquidar hasta el tiempo final de la novedad
				$diferencia_en_dias = $this->diferencia_en_dias_entre_fechas( $lapso_documento->fecha_inicial, $novedad->fecha_final_tnl );
			}

			return ( ( $diferencia_en_dias + 1 ) * self::CANTIDAD_HORAS_DIA_LABORAL ); // Se suma 1, pues se debe incluir el mismo día inicial.
		}
	}

	public function diferencia_en_dias_entre_fechas( string $fecha_inicial, string $fecha_final )
	{
		$fecha_ini = Carbon::createFromFormat('Y-m-d', $fecha_inicial);
		$fecha_fin = Carbon::createFromFormat('Y-m-d', $fecha_final );

		return abs( $fecha_ini->diffInDays($fecha_fin) );
	}

	public function retirar(NomDocRegistro $registro)
	{
		$novedad = $registro->novedad_tnl;
		
		if( is_null( $novedad ) )
		{
			dd( [ 'TiempoNoLaborado Novedad NULL', $registro] );
		}

		if( is_null( $registro->contrato ) )
		{
			dd( [ 'TiempoNoLaborado Contrato NULL', $registro] );
		}

		$lapso_documento = $registro->encabezado_documento->lapso();
		$cantidad_horas_a_liquidar = abs( $this->calcular_cantidad_horas_liquidar_novedad( $novedad, $lapso_documento ) );

		if ( $registro->nom_concepto_id != (int)config('nomina.id_concepto_pagar_empresa_en_incapacidades') )
		{
			$novedad->cantidad_dias_amortizados -= $cantidad_horas_a_liquidar / self::CANTIDAD_HORAS_DIA_LABORAL;
			$novedad->cantidad_dias_pendientes_amortizar += $cantidad_horas_a_liquidar / self::CANTIDAD_HORAS_DIA_LABORAL;
		}

		if ( $novedad->tipo_novedad_tnl == 'incapacidad' )
		{
			$this->calcular_valores_liquidar_incapacidad( $novedad, $registro->contrato, $cantidad_horas_a_liquidar );

			$novedad->valor_a_pagar_eps -= $this->valor_a_pagar_eps;
			$novedad->valor_a_pagar_arl -= $this->valor_a_pagar_arl;
			$novedad->valor_a_pagar_afp -= $this->valor_a_pagar_afp;
			$novedad->valor_a_pagar_empresa -= $this->valor_a_pagar_empresa;

		}else{
			$novedad->valor_a_pagar_eps = 0;
			$novedad->valor_a_pagar_arl = 0;
			$novedad->valor_a_pagar_afp = 0;
			$novedad->valor_a_pagar_empresa = 0;
		}

		$novedad->save();

        $registro->delete();

        return 0;
	}
}