<?php 

namespace App\Inventarios\Services;

use App\Inventarios\InvMovimiento;

class FiltroMovimientos
{
	public $movimiento;

	public function __construct()
	{
		$this->movimiento = new InvMovimiento();
	}/**/

	public function filtro_entre_fechas( $fecha_ini, $fecha_fin )
	{
		if ( is_null($fecha_ini) )
		{
			$fecha_ini = InvMovimiento::first()->value('fecha');
		}
		
		$this->movimiento = $this->movimiento->whereBetween('fecha',[$fecha_ini,$fecha_fin]);
	}

	public function filtro_por_item_id( $item_id )
	{
		if( $item_id != 0 && $item_id != '' )
		{
			$this->movimiento = $this->movimiento->where('inv_producto_id', $item_id);
		}
	}

	public function filtro_por_bodega_id( $bodega_id )
	{
		if( $bodega_id != 0 && $bodega_id != '' )
		{
			$this->movimiento = $this->movimiento->where('inv_bodega_id', $bodega_id);
		}
	}

	public function filtro_por_inv_grupo_id( $inv_grupo_id )
	{
		if( $inv_grupo_id != 0 && $inv_grupo_id != '' )
		{
			$this->movimiento = $this->movimiento->leftJoin('inv_productos','inv_productos.id','=','inv_movimientos.inv_producto_id')->leftJoin('inv_grupos','inv_grupos.id','=','inv_productos.inv_grupo_id')->where('inv_grupo_id', $inv_grupo_id)->select('inv_movimientos.*');
		}
	}

	public function aplicar_filtros( $fecha_ini, $fecha_fin, $bodega_id, $inv_grupo_id, $item_id )
	{
		$this->filtro_entre_fechas( $fecha_ini, $fecha_fin );
        
        $this->filtro_por_bodega_id( $bodega_id );
        
        $this->filtro_por_inv_grupo_id( $inv_grupo_id );
        
        $this->filtro_por_item_id( $item_id );

		return $this->get_movimiento_filtrado()->get();
	}

	public function get_movimiento_filtrado()
	{
		return $this->movimiento;
	}
}