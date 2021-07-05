<style>
    #Price {
        position: relative;
        z-index: 80 !important;
        padding: 100px 0 75px;

        <?php
        if ($Price != null) {
            if ($Price->tipo_fondo == 'COLOR') {
                echo "background-color: " . $Price->fondo . ";";
            } else {
        ?>background: url('{{$Price->fondo}}') {{$Price->repetir}} center {{$Price->direccion}};
        <?php
            }
        }
        ?>
    }

    .price-font {
        @if( !is_null($Price) )
            @if( !is_null($Price->configuracionfuente ) )
                font-family: <?php echo $Price->configuracionfuente->fuente->font; ?> !important;
            @endif
        @endif
    }


    @media (max-width: 468px) {
        .container h2 {
            font-size: 28px !important;
        }

        .container p {
            font-size: 16px !important;
        }

    }
</style>
@if($Price!=null)
<section id="Price" class="price-font">
    <div id="visor_contenido_servicios">

    </div>
    <div class="container" id="contenedor_seccion_servicios">
        @if($Price!=null)
        <div class="section-header">
            <h2 class="section-title text-center wow fadeInDown animated price-font" style="visibility: visible; animation-name: fadeInDown; color: {{$Price->title_color}} !important;">{{$Price->title}}</h2>
            <p class="text-center wow fadeInDown animated price-font" style="visibility: visible; animation-name: fadeInDown; color: {{$Price->description_color}} !important;">{{$Price->description}}</p>
        </div>
        <div class="row">
            @if(count($Price->priceitems) > 0)
            @foreach($Price->priceitems as $item)
            <!-- Price member -->
            <div class="col-xs-12 col-sm-6 col-md-4 wow fadeInUp animated service-info" data-wow-duration="300ms" data-wow-delay="0ms" style="visibility: visible; animation-duration: 300ms; animation-delay: 0ms; animation-name: fadeInUp; margin-bottom: 20px;">
                <div style="border-radius: 20px !important; -webkit-box-shadow: 1px 1px 100px var(--color-terciario); -moz-box-shadow: 1px 1px 100px var(--color-terciario); box-shadow: 1px 1px 100px var(--color-terciario);">
                    <div style="background-color: {{$item->background_color}}; border-top-right-radius: 20px !important; border-top-left-radius: 20px !important;"><img style="width: 100%;" src="{{asset($item->imagen_cabecera)}}"></div>
                    <div style="background-color: {{$item->background_color}}; padding: 20px; border-bottom-right-radius: 20px !important; border-bottom-left-radius: 20px !important;">
                        <h4 class="media-heading price-font" style="margin-top: 0px; color: {{$item->text_color}} !important;">{{$item->precio}}</h4>
                        <?php
                            if ($item->lista_items != 'null') {
                                $lista = json_decode($item->lista_items);
                                $i=0;
                                foreach($lista as $l){
                                    $i=$i+1;
                                    if($i<4){
                                        echo "<p class='price-font' style='color: ".$item->text_color." !important;'><i style='color: ".$item->button_color." !important;' class='fa fa-".$l->icono."'></i> ".$l->item."</p>";
                                    }
                                }
                            }else{
                                echo "<p class='price-font' style='color: ".$item->text_color." !important;'>No hay información en este plan</p>";
                            }
                        ?>
                        @if(count($lista) > 2)
                        <div class="collapse" id="collapse_{{$item->id}}">
                            <div class="well">
                                <?php
                                if ($item->lista_items != 'null') {
                                    $lista = json_decode($item->lista_items);
                                    for ($i=3; $i < count($lista); $i++) { 
                                        echo "<p class='price-font' style='color: ".$item->text_color." !important;'><i style='color: ".$item->button_color." !important;' class='fa fa-".$lista[$i]->icono."'></i> ".$lista[$i]->item."</p>";
                                    }
                                }else{
                                    echo "<p class='price-font' style='color: ".$item->text_color." !important;'>No hay información en este plan</p>";
                                }
                                ?>
                            </div>
                        </div>
                        @endif
                        <a class="btn btn-default btn-block" style="margin-bottom: 30px; color: {{$item->text_color}} !important; border: 2px solid; border-color: {{$item->text_color}} !important;" role="button" data-toggle="collapse" href="#collapse_{{$item->id}}" aria-expanded="false" aria-controls="collapseExample">
                        Ver todas las características <i class="fa fa-plus"></i></a>
                        
                        <a style="background-color: {{$item->button_color}} !important; border-color: {{$item->button2_color}} !important;" class="btn btn-primary animate btn-block price-font" href="{{$item->url}}">DESCUBRE EL PLAN...</a>
                    </div>
                </div>
            </div>
            <!-- ./Price member -->
            @endforeach
            @endif
        </div>
        <!--/.row-->
        @else
        <div class="section-header">
            <h2 class="section-title text-center wow fadeInDown">Sección</h2>
            <p class="text-center wow fadeInDown">Sin configuración</p>
        </div>
        @endif
    </div>
    <!--/.container-->

</section>
@endif
<script type="text/javascript">
    document.addEventListener('DOMContentLoaded',function(){
        $('#pirce .collapse').on('hidden.bs.collapse', function (event) {
            event.target.parentElement.querySelector('a.btn').innerHTML = 'Ver todas las características <i class="fa fa-plus"></i>' ;
        })
        $('#price .collapse').on('shown.bs.collapse', function (event) {
            event.target.parentElement.querySelector('a.btn').innerHTML = 'Ver menos características <i class="fa fa-minus"></i>' ;
        })
    })
</script>