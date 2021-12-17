<?php

namespace App\Core;

use Illuminate\Database\Eloquent\Model;

use App\Sistema\Modelo;

class ModeloEavValor extends Model
{
    protected $table = 'core_eav_valores';
    
	protected $fillable = ['modelo_padre_id', 'registro_modelo_padre_id', 'modelo_entidad_id', 'registro_modelo_entidad_id', 'core_campo_id', 'valor'];

	public $encabezado_tabla = ['Modelo Padre', 'Descripcion registro mod. padre', 'Entidad', 'Atributo', 'Valor', 'Acción'];

    public function campo()
    {
        return $this->belongsTo( 'App\Sistema\Campo', 'core_campo_id' );
    }

    public static function consultar_registros($nro_registros)
    {
        return ModeloEavValor::leftJoin('sys_modelos', 'sys_modelos.id', '=', 'core_eav_valores.modelo_entidad_id')
            ->leftJoin('sys_campos', 'sys_campos.id', '=', 'core_eav_valores.core_campo_id')
            ->select(
                'core_eav_valores.modelo_padre_id AS campo1',
                'core_eav_valores.registro_modelo_padre_id AS campo2',
                'sys_modelos.descripcion AS campo3',
                'sys_campos.descripcion AS campo4',
                'core_eav_valores.valor AS campo5',
                'core_eav_valores.id AS campo6'
            )
            ->orderBy('core_eav_valores.created_at', 'DESC')
            ->paginate($nro_registros);
    }

    public function get_campos_adicionales_create( $lista_campos )
    {
        $modelo_padre_id = Modelo::find( $this->crud_model_id )->id;

        // Personalizar campos
        $cantida_campos = count($lista_campos);
        for ($i=0; $i <  $cantida_campos; $i++)
        {
            if ( $lista_campos[$i]['name'] == 'modelo_padre_id' ) 
            {
                $lista_campos[$i]['value'] = $modelo_padre_id;
            }
        }

        return $lista_campos;
    }

    public function almacenar_registros_eav( $datos, $modelo_padre_id = null, $registro_modelo_padre_id = null )
    {
        // Se va a crear un registro por cada Atributo (campo) que tenga un Valor distinto a vacío 
        foreach ( $datos as $key => $value ) 
        {
            // Si el name del campo enviado tiene la palabra core_campo_id
            if ( strpos( $key, "core_campo_id") !== false ) 
            {
                $core_campo_id = explode( "-", $key )[1]; // Atributo ( ID del campo )
                $valor = $value; // Valor
                
                if ( is_array($value) ) // Para tipos CheckBox
                {
                    $valor = implode(",", $value);
                }


                if ( $modelo_padre_id == null )
                {
                    ModeloEavValor::create( [ 
                                                "modelo_padre_id" => $datos['modelo_padre_id'],
                                                "registro_modelo_padre_id" => $datos['registro_modelo_padre_id'],
                                                "modelo_entidad_id" => $datos['modelo_entidad_id'],
                                                "core_campo_id" => $core_campo_id,
                                                "valor" => $valor 
                                            ] );
                }else{
                    // Para campos normales asociados a un modelo directamente. Ejemplo: Paciente

                    if ( $valor != '' )
                    {
                        ModeloEavValor::create( [ 
                                                    "modelo_padre_id" => $modelo_padre_id,
                                                    "registro_modelo_padre_id" => $registro_modelo_padre_id,
                                                    "modelo_entidad_id" => 0,
                                                    "core_campo_id" => $core_campo_id,
                                                    "valor" => $valor 
                                                ] );
                    }
                }
            }
        }
    }

    public function get_campos_adicionales_edit($lista_campos, $registro)
    {
        $modelo_padre_id = $this->crud_model_id;

        // Personalizar campos
        $cantida_campos = count($lista_campos);
        for ($i=0; $i <  $cantida_campos; $i++)
        {
            // Si el name del campo enviado tiene la palabra core_campo_id
            if ( strpos( $lista_campos[$i]['name'], "core_campo_id") !== false ) 
            {
                $core_campo_id = $lista_campos[$i]['id']; // Atributo_ID

                $registro_eav = ModeloEavValor::where( [ 
                                                        "modelo_padre_id" => $modelo_padre_id,
                                                        "registro_modelo_padre_id" => $registro->registro_modelo_padre_id,
                                                        "modelo_entidad_id" => $registro->modelo_entidad_id,
                                                        "core_campo_id" => $core_campo_id
                                                    ] )
                                                ->get()
                                                ->first();

                if( !is_null( $registro_eav ) )
                {
                    $lista_campos[$i]['value'] = $registro_eav->valor;
                }
            }

        }

        return $lista_campos;
    }

    public function update_adicional( $datos, $id )
    {
        // Se va a actualizar/crear un registro por cada Atributo (campo)
        foreach ( $datos as $key => $value) 
        {
            // Si el name del campo enviado tiene la palabra core_campo_id
            if ( strpos( $key, "core_campo_id") !== false ) 
            {
                $core_campo_id = explode("-", $key)[1]; // Atributo
                $valor = $value; // Valor

                if ( is_array($value) )
                {
                    $valor = implode(",", $value);
                }

                $registro_eav = ModeloEavValor::where( [ 
                                                            "modelo_padre_id" => $datos['modelo_padre_id'],
                                                            "registro_modelo_padre_id" => $datos['registro_modelo_padre_id'],
                                                            "modelo_entidad_id" => $datos['modelo_entidad_id'],
                                                            "core_campo_id" => $core_campo_id
                                                        ] )
                                                ->get()
                                                ->first();

                if ( is_null( $registro_eav ) )
                {
                    if ( $valor != '' )
                    {
                        ModeloEavValor::create( [ 
                                                "modelo_padre_id" => $datos['modelo_padre_id'],
                                                "registro_modelo_padre_id" => $datos['registro_modelo_padre_id'],
                                                "modelo_entidad_id" => $datos['modelo_entidad_id'],
                                                "core_campo_id" => $core_campo_id,
                                                "valor" => $valor 
                                            ] );
                    }
                        
                }else{
                    $registro_eav->valor = $valor;
                    $registro_eav->save();
                }
            }
        }
    }

    public static function get_valor_campo( $string_ids_campos )
    {
        $vec_ids_campos = explode('-', $string_ids_campos);

        if ( !isset( $vec_ids_campos[1] ) )
        {
            return '';
        }

        return ModeloEavValor::where( [ 
                                        "modelo_padre_id" => $vec_ids_campos[0],
                                        "registro_modelo_padre_id" => $vec_ids_campos[1],
                                        "core_campo_id" => $vec_ids_campos[3]
                                    ] )
                            ->value('valor');
    }

    public function get_pares_campos_valores( $modelo_sys, $modelo_padre_id, $registro_modelo_padre_id )
    {
        $campos = $modelo_sys->campos()->where('name','core_campo_id-ID')->orderBy('id')->get();
        
        $valores_entidades = app($modelo_sys->name_space)->where(
                                                            [   'modelo_padre_id' => $modelo_padre_id,
                                                                'registro_modelo_padre_id' => $registro_modelo_padre_id,
                                                                'modelo_entidad_id' => $modelo_sys->id
                                                            ]
                                                        )
                                                    ->get();


        $datos = [];
        if ( empty( $valores_entidades->toArray() )  )
        {
            $datos[] = (object)[ 'descripcion' => '--', 'valor' => '--' ];
        }

        foreach ( $campos as $linea ) 
        {
            $el_valor = $this->get_valor_desde_valores_entidades( $valores_entidades, $linea->id );
            
            if ($linea->id == 1410) {
                $linea->value = $this->get_valor_desde_valores_entidades( $valores_entidades, $linea->id );
                $el_valor = \App\Salud\RecomendacionLaboral::get_valor_to_show( $linea->toArray() );
            }

            $datos[] = (object)[ 'descripcion' => $linea->descripcion, 'valor' => $el_valor ];
        }

        return $datos;
    }

    public function get_valor_desde_valores_entidades( $valores_entidades, $core_campo_id )
    {
        $valor = '--';

        foreach ($valores_entidades as $linea )
        {
            if( $linea->core_campo_id == $core_campo_id )
            {
                $valor = $linea->valor;
            }
        }

        return $valor;
    }
}
