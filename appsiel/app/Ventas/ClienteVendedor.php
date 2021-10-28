<?php

namespace App\Ventas;

use Illuminate\Database\Eloquent\Model;

use Auth;

use App\Core\Tercero;

use App\Ventas\Cliente;
use App\Ventas\ClaseCliente;
use App\Ventas\Vendedor;

// Solo para usuarios con el role Vendedor
class ClienteVendedor extends Cliente
{
    protected $table = 'vtas_clientes';
	
	protected $fillable = [ 'core_tercero_id', 'encabezado_dcto_pp_id', 'clase_cliente_id', 'lista_precios_id', 'lista_descuentos_id', 'vendedor_id','inv_bodega_id', 'zona_id', 'liquida_impuestos', 'condicion_pago_id', 'cupo_credito', 'bloquea_por_cupo', 'bloquea_por_mora', 'estado' ];

	public $encabezado_tabla = ['<i style="font-size: 20px;" class="fa fa-check-square-o"></i>', 'Identificación', 'Tercero', 'Dirección', 'Teléfono', 'Lista de precios', 'Lista de descuentos', 'Zona'];

    // Las acciones tienen valores predeterminados, si el modelo no va a tener una acción, se debe asignar la palabra "no" a la acción.
    public $urls_acciones = '{"index":"web","create":"web/create","edit":"web/id_fila/edit","store":"web","update":"vtas_clientes/id_fila","imprimir":"no","show":"vtas_clientes/id_fila","eliminar":"no","cambiar_estado":"no","otros_enlaces":"no"}'; // El valor de otros_enlaces dede ser en formato JSON
// 
    //public $vistas = '{"create":"ventas.clientes.create2"}';

	public static function consultar_registros($nro_registros, $search)
    {

        $array_wheres = [['vtas_clientes.id', '>', 0]];

        $vendedor = Vendedor::where('user_id', Auth::user()->id)->get()->first();

        if (!is_null($vendedor))
        {
            $array_wheres = array_merge($array_wheres, ['vtas_clientes.vendedor_id' => $vendedor->id]);
        }

        return Cliente::leftJoin('core_terceros', 'core_terceros.id', '=', 'vtas_clientes.core_tercero_id')
            ->leftJoin('vtas_clases_clientes', 'vtas_clases_clientes.id', '=', 'vtas_clientes.clase_cliente_id')
            ->leftJoin('vtas_listas_precios_encabezados', 'vtas_listas_precios_encabezados.id', '=', 'vtas_clientes.lista_precios_id')
            ->leftJoin('vtas_listas_dctos_encabezados', 'vtas_listas_dctos_encabezados.id', '=', 'vtas_clientes.lista_descuentos_id')
            ->leftJoin('vtas_zonas', 'vtas_zonas.id', '=', 'vtas_clientes.zona_id')
            ->where($array_wheres)
            ->select('core_terceros.numero_identificacion AS campo1', 'core_terceros.descripcion AS campo2', 'core_terceros.direccion1 AS campo3', 'core_terceros.telefono1 AS campo4', 'vtas_listas_precios_encabezados.descripcion AS campo5', 'vtas_listas_dctos_encabezados.descripcion AS campo6', 'vtas_zonas.descripcion AS campo7', 'vtas_clientes.id AS campo8')
            ->orderBy('vtas_clientes.created_at', 'DESC')
            ->paginate($nro_registros);
    }

    public static function opciones_campo_select()
    {
        $opciones = Cliente::leftJoin('core_terceros','core_terceros.id','=','vtas_clientes.core_tercero_id')->where('vtas_clientes.estado','Activo')
                    ->select('vtas_clientes.id','core_terceros.descripcion')
                    ->orderby('core_terceros.descripcion')
                    ->get();

        $vec['']='';
        foreach ($opciones as $opcion)
        {
            $vec[$opcion->id] = $opcion->descripcion;
        }

        return $vec;
    }
    

    public static function get_cuenta_cartera( $cliente_id )
    {
        $clase_cliente_id = Cliente::where( 'id', $cliente_id )->value( 'clase_cliente_id' );
        return ClaseCliente::where( 'id', $clase_cliente_id )->value( 'cta_x_cobrar_id' );
    }

    public function store_adicional( $datos, $registro )
    {
        // Se copia los datos asociados al Usuario/Vendedor que esta creando al cliente
        $user_id = Auth::user()->id;
        $vendedor = Vendedor::where('user_id',$user_id)->get()->first();
        $datos_tercero = $vendedor->tercero;
        $datos_cliente = $vendedor->cliente;

        $datos['codigo_ciudad'] = $datos_tercero->codigo_ciudad;
        $datos['core_empresa_id'] = $datos_tercero->core_empresa_id;
        $datos['tipo'] = $datos_tercero->tipo;
        $datos['id_tipo_documento_id'] = $datos_tercero->id_tipo_documento_id;
        $datos['digito_verificacion'] = $datos_tercero->digito_verificacion;

        $datos['encabezado_dcto_pp_id'] = $datos_cliente->encabezado_dcto_pp_id;
        $datos['clase_cliente_id'] = $datos_cliente->clase_cliente_id;
        $datos['lista_precios_id'] = $datos_cliente->lista_precios_id;
        $datos['lista_descuentos_id'] = $datos_cliente->lista_descuentos_id;
        $datos['vendedor_id'] = $vendedor->id;
        $datos['inv_bodega_id'] = $datos_cliente->inv_bodega_id;
        $datos['zona_id'] = $datos_cliente->zona_id;
        $datos['liquida_impuestos'] = $datos_cliente->liquida_impuestos;
        $datos['condicion_pago_id'] = $datos_cliente->condicion_pago_id;
        $datos['cupo_credito'] = $datos_cliente->cupo_credito;
        $datos['bloquea_por_cupo'] = $datos_cliente->bloquea_por_cupo;
        $datos['bloquea_por_mora'] = $datos_cliente->bloquea_por_mora;
        
        $datos['estado'] = "Activo";
        $datos['creado_por'] = Auth::user()->email;

        $tercero = new Tercero;
        $tercero->fill( $datos );
        $tercero->save();

        $datos['core_tercero_id'] = $tercero->id;
        
        // Datos del Cliente
        $registro->fill( $datos );
        $registro->save();
    }
}
