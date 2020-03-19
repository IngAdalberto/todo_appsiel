<?php


namespace App\Http\Controllers\web\services;

use App\Inventarios\InvGrupo;
use App\Inventarios\InvProducto;
use App\web\Pedidoweb;
use Form;
use Illuminate\Support\Facades\Input;

class ProductosComponent implements IDrawComponent
{
    public function __construct($widget)
    {
        $this->widget = $widget;
    }

    function DrawComponent()
    {
        $pedido = Pedidoweb::where('widget_id', $this->widget)->first();
        $items = null;
        if ($pedido != null) {
            $items = InvProducto::where([['mostrar_en_pagina_web', 1]])->orderBy('created_at', 'DESC')->get();
            if (count($items) > 0) {
                foreach ($items as $i) {
                    $i->grupo = "---";
                    $g = InvGrupo::find($i->inv_grupo_id);
                    if ($g != null) {
                        $i->grupo = $g->descripcion;
                    }
                }
            }
        }
        return Form::productos($items, $pedido);
    }

    function viewComponent()
    {
        $miga_pan = [
            [
                'url' => 'pagina_web' . '?id=' . Input::get('id'),
                'etiqueta' => 'Web'
            ],
            [
                'url' => 'paginas?id=' . Input::get('id'),
                'etiqueta' => 'Paginas y secciones'
            ],
            [
                'url' => 'NO',
                'etiqueta' => 'Productos'
            ]
        ];
        $widget = $this->widget;
        $variables_url = '?id=' . Input::get('id');
        $pedido = Pedidoweb::where('widget_id', $widget)->first();
        $items = null;
        if ($pedido != null) {
            $items = InvProducto::where([['mostrar_en_pagina_web', 1]])->orderBy('created_at', 'DESC')->get();
            if (count($items) > 0) {
                foreach ($items as $i) {
                    $i->grupo = "---";
                    $g = InvGrupo::find($i->inv_grupo_id);
                    if ($g != null) {
                        $i->grupo = $g->descripcion;
                    }
                }
            }
        }
        return view('web.components.productos', compact('miga_pan', 'variables_url', 'widget', 'pedido', 'items'));
    }
}