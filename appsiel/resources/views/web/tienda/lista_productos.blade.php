@if( empty( $items->toArray()['data'] ) )
    <div class="alert alert-warning">
        <strong>Lo sentimos, no se encontraron resultados para "{{$texto}}"</strong> 
        <br>
        Intente utilizar otras palabras para la busqueda.
    </div>
@else
    <ul class="products-grid row first odd">
        @foreach($items as $item)
            <li class="item first" style="list-style: none;padding: 0 0 0 0">
                <div class="item-inner">
                    <div class="ma-box-content" data-id="{{$item->id}}">
                        <input id="tasa_impuesto" type="hidden" value="{{$item->tasa_impuesto}}">
                        <input id="precio_venta" type="hidden" value="{{$item->precio_venta}}">
                        <div class="products clearfix">
                            <a href="#"
                               title="{{$item->descripcion}}" class="product-image">
                                <div class="product-image">
                                    <?php
                                    $url_imagen_producto = '#';
                                    if ( $item->imagen != '' )
                                    {
                                        $url_imagen_producto = asset( config('configuracion.url_instancia_cliente') . 'storage/app/inventarios/' . $item->imagen );
                                    }
                                    ?>
                                    <img src="{{ $url_imagen_producto }}" loading="lazy"
                                    width="200" height="200" alt="{{$item->descripcion}}" onerror="imgError(this)" style="object-fit: contain">
                                </div>
                            </a>
                        </div>
                        <h2 class="product-name text-center" onclick="window.location.href='{{route('tienda.detalleproducto',$item->id)}}'" style="height: 45px">
                            <a href="{{route('tienda.detalleproducto',$item->id)}}" title="{{$item->descripcion}}">{{$item->descripcion}}</a>
                        </h2>
                        <div class="price-box text-center mx-2">
                            <span class="regular-price" id="product-price-1">
                            <!--<span class="price">${{ number_format( $item->precio_venta,0,',','.' ) }} x {{ $item->unidad_medida1 }}</span></span>-->
                            
                            @if( $item->descuento == 0)
                                <span class="regular-price" id="product-price-1">
                                    <span class="price">${{ number_format( $item->precio_venta,0,',','.' ) }} x {{ $item->unidad_medida1 }}</span>
                                </span>
                            @else
                                <span class="regular-price" id="product-price-1">
                                    <span class="price"> <del>${{ number_format( $item->precio_venta,0,',','.' ) }} x {{ $item->unidad_medida1 }}</del></span>
                                    -{{ $item->descuento }}%
                                    <br>
                                    <span class="price">${{ number_format( $item->precio_venta - $item->valor_descuento,0,',','.' ) }} x {{ $item->unidad_medida1 }}</span>
                                </span>
                            @endif
                        </div>
                        <div class="actions agregar-carrito">
                            <button type="button" class="btn-cart btn-primary form-control" style="background-color: var(--color-primario); border: none ;font-size: 16px" >
                                <i class="fa fa-cart-plus"></i>  
                                Comprar
                            </button>
                        </div>
                    </div>
                </div>
            </li>
        @endforeach
    </ul>
@endif
<!-- 
<div class="col-md-12">
    { { $items->render() }}
</div>
-->