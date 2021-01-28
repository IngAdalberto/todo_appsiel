<?php

$fondos = json_decode($nav->background, true);

if (is_null($fondos)) {
    $fondos['background_0'] = $nav->background;
    $fondos['background_1'] = $nav->background;
}


$logo = json_decode($nav->logo, true);

if (is_null($logo)) {
    $logo['imagen_logo'] = $nav->logo;
    $logo['altura_logo'] = 100;
    $logo['anchura_logo'] = 100;
}

if ($nav->fixed) {
    $clase_header = 'fixed-top';
    //$estilo = 'clear: both;';
} else {
    $clase_header = 'no-fixed';
}

?>

<style>
    #myHeader {
        /*position: fixed;
        z-index: 99999;
        top: 50px;*/
        width: 100%;
        height: 70px;
    }

    .navegacion-font{
        @if( !is_null($nav) )
            @if( !is_null($nav->configuracionfuente ) )
                font-family: <?php echo $nav->configuracionfuente->fuente->font; ?> !important;
            @endif
        @endif
    }

    header {
        color: <?php echo $nav->color; ?>;
        background: <?php echo $fondos['background_0']; ?>;
    }

    #navegacion>header.sticky {
        position: fixed;
        z-index: 1000;
        top: 0;
        width: 100%;
        background: <?php echo $fondos['background_1']; ?>;
        -webkit-box-shadow: 0px 5px 5px 0px rgba(0, 0, 0, 0.75);
        -moz-box-shadow: 0px 5px 5px 0px rgba(0, 0, 0, 0.75);
        box-shadow: 0px 5px 5px 0px rgba(0, 0, 0, 0.75);
    }


    a.icono img {
        height: <?php echo $logo['altura_logo']; ?>px;
        width: <?php echo $logo['anchura_logo']; ?>px;
    }


    #navegacion>header.sticky .icono img {
        height: 80px;
        max-height: 80px;
        width: auto;
    }


    li.active a {
        color: #FFF !important;
        background-color: <?php echo $fondos['background_1']; ?> !important;
    }

    #myHeader .navbar-nav>li>a:hover {
        color: #FFF !important;
        background-color: <?php echo $fondos['background_1']; ?> !important;
        transition: all 0.5s;
    }

    .owl-prev:hover,
    .owl-next:hover {
        background-color: <?php echo $fondos['background_0']; ?> !important;
    }

    .btn.btn-primary {
        background-color: <?php echo $fondos['background_0']; ?> !important;
        border-color: <?php echo $fondos['background_1']; ?> !important;
    }

    .dropdown-menu {
        background-color: <?php echo $fondos['background_1']; ?> !important;
        border-radius: 0 !important;
        border: 1px solid !important;
        border-color: <?php echo $fondos['background_1']; ?> !important;
    }

    .dropdown-item:hover {
        background-color: <?php echo $fondos['background_0']; ?> !important;
    }
</style>

<?php
    $style = '';
    if( $clase_header == 'fixed-top')
    {
        $style = "background: " . $fondos['background_0'] . " !important; opacity: " . ($nav->alpha/10) . ";";
    } 
?>

<header class="no" id="myHeader">
    <div class="container-fluid">
        <nav class="navbar navbar-expand-lg navbar-light {{ $clase_header }}" style="{{ $style }}" >
            <!-- mu-navbar  d-flex -->

            <!-- Text based logo -->
            @if( $nav->logo != '' )
            <a style="width: 30%;" class="navbar-brand p-0 icono" href="{{url('/')}}">
                <img src="{{asset( $logo['imagen_logo'] )}}">
            </a>
            @endif

            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="fa fa-bars" style="color: {{$nav->color}}"></span>
            </button>
            <div class="collapse navbar-collapse navegacion-font" id="navbarSupportedContent">
                <ul class="navbar-nav mr-auto mu-navbar-nav">
                    @foreach($nav->menus as $item)
                    @if($item->parent_id == 0)
                    @if($item->subMenus()->count()>0)
                    <li class="nav-item dropdown {{request()->url() == $item->enlace ? 'active':''}}">
                        <a class="dropdown-toggle" style="color: {{$nav->color}}; text-transform: none !important; font-weight: 100;" href="{{$item->enlace}}" role="button" id="navbarDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="fa fa-{{$item->icono}}" style="font-size: 20px;"></i>{{' '.$item->titulo}}</a>
                        <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                            @foreach($item->subMenus() as $subItems)
                            <a style="color: {{$nav->color}}; text-transform: none !important; font-weight: 100;" class="dropdown-item" href="{{$subItems->enlace}}"><i class="fa fa-{{$subItems->icono}}" style="font-size: 20px;"></i>{{' '.$subItems->titulo}}</a>
                            @endforeach
                        </div>
                    </li>
                    @else
                    <li class="nav-item {{request()->url() == $item->enlace ? 'active':''}}"><a href="{{$item->enlace}}" style="color: {{$nav->color}}; text-transform: none !important; font-weight: 100;"><i class="fa fa-{{$item->icono}}" style="font-size: 20px;"></i>{{' '.$item->titulo}}</a></li>
                    @endif
                    @endif
                    @endforeach
                </ul>
            </div>
        </nav>
    </div>
</header>