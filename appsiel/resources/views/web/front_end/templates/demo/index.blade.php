<!DOCTYPE html>
<html lang="es">
  <head>

    <title>{{ $pagina->descripcion }}</title>
    
    <link rel="shortcut icon" href="{{ $pagina->get_url_favicon() }}" type="image/x-icon">

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Estilos Bootstrap -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.0/css/bootstrap.min.css">
    <!-- Add icon library -->
    <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">

    <!-- JavaScript Bootstrap And Jquery -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.0/js/bootstrap.min.js"></script>

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Merienda+One&display=swap" rel="stylesheet">

    <!-- ESTILOS DE MÓDULOS -->
    

    @if($pagina->codigo_google_analitics != '')
      <!-- Global site tag (gtag.js) - Google Analytics -->
      <script async src="https://www.googletagmanager.com/gtag/js?id={{ $pagina->codigo_google_analitics }}"></script>
      <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '{{ $pagina->codigo_google_analitics }}');
      </script>
    @endif


    <!-- MEJORA: permitir agregar scripts desde el CRUD del modelo Pagina -->
    <script src='https://www.google.com/recaptcha/api.js'></script>

    <style type="text/css">

      body{
        font-family: 'Merienda One', cursive;
      }

      .seccion_padre {
        /*border: dashed 1px red;
        width: 100%;
        padding: 10px;
        margin-top: 5px;
        margin-bottom: 5px;
        */
      }

      .seccion_hija {
        /*border: solid 1px black;
        width: 100%;
        display: inline-block;
        padding: 10px;
        */
      }

      .modulo {
        width: 100%;
        border: solid 2px green;
        display: inline-block;
        padding: 10px;
        /**/
      }

      span.titulo_seccion {
        font-size: 1.5em;
        display: block;
      }

      span.titulo_modulo {
        font-size: 1.2em;
        display: block;
      }

      .search {
        width: 100%;
        margin-top: 50px;
        box-sizing: border-box;
        border: 2px solid #ccc;
        border-radius: 4px;
        font-size: 16px;
        background-color: white;
        background-image: url( {{ asset( "assets/img/searching-2339723_1920.png" ) }} );
        background-size: 30px;
        background-position: 10px 6px; 
        background-repeat: no-repeat;
        padding: 12px 20px 12px 40px;
        -webkit-transition: width 0.4s ease-in-out;
        transition: width 0.4s ease-in-out;
      }



        .fa {
          /**/
          padding: 5px;
          width: 40px;
          text-align: center;
          text-decoration: none;
          border-radius: 10%;
          font-size: 24px;
        }

        .fa:hover {
            opacity: 0.7;
        }

        /*.fa-whatsapp {
          background: #3dbc28;
          color: white;
        }*/

        .fa-facebook {
          background: #3B5998;
          color: white;
        }

        .fa-youtube {
          background: #bb0000;
          color: white;
        }

        .fa-instagram {
          background: #9b39a6;
          color: white;
        }

        .img_categoria{
          display: table;
          margin-bottom: 10px;
        }


      .layer_overlay {
        position: absolute;
        top: 0;
        right: 0;
        bottom: 0;
        left: 0;
        background: rgba(0,0,0,.6);
        padding: 75px 0 0 0;
        margin: 0 15px 10px 15px;
        /*cursor: default;*/
        z-index: 110;
        color: white;
      }

      .layer_overlay:hover {
          background: rgba(0,0,0,.3);
      }
      
      .layer_overlay .td {
        /*padding: 30px 0 100px;
        vertical-align: middle;*/
        text-align: center;
    }

    .pie_pagina{
      font-family: "Lucida Sans Unicode", "Lucida Grande", sans-serif;
      font-size: 0.9em;
      background-color: #000;
      color: white;
    }

    .pie_pagina a{
      text-decoration: none;
      color: white;
    }

    .modal-footer{
      display: none;
    }
    </style>

  </head>
  <body id="myPage" data-spy="scroll" data-target=".navbar" data-offset="60">


    <div class="container-fluid">
      @include('layouts.demo_pagina_bloqueo_aplicaciones')
    </div>
  </body>
</html>