<div class="container-fluid article-font">
      <p style="padding: 30px; font-size: 18px; font-weight: bold;">  
            <a href="{{url('/')}}"> <i class="fa fa-home"></i> </a>
            /
            <a class="article-font" onclick="ver_contenedor_seccion_articulos()" style="text-decoration: none; color: #2a95be; cursor: pointer;"> Volver </a>   
      </p>
      <div class="col-md-12 light-txt">
      <div class="content-txt" style="padding: 20px; margin: 10px !important; font-size: 14px; border-radius: 20px !important; -webkit-box-shadow: 1px 1px 100px #cf9ec3; -moz-box-shadow: 1px 1px 100px #cf9ec3; box-shadow: 1px 1px 100px #cf9ec3;">
      <div class='blog-post blog-large wow fadeInLeft' data-wow-duration='300ms' data-wow-delay='0ms' style="border: none;">
                        <article class="media clearfix">
                              <div class="media-body">
                                    <header class="entry-header" style="background: transparent;">

                                              <?php
                                                  $url_imagen = 'assets/img/blog-default.jpg';
                                                  if( $articulo->imagen != '')
                                                  {
                                                      $url_imagen = $articulo->imagen;
                                                  }
                                              ?>
                                          @if($articulo->imagen != '')
                                          <p style="text-align: center;width: 100%;">
                                                <img src="{{ asset( $url_imagen )}}" style=" max-height: 350px;object-fit: cover;">
                                          </p>
                                          @endif
                                          <h2 class="entry-title article-font" style="width: 100%; text-align: center;"><a href="#">{{$articulo->titulo}}</a></h2>
                                    </header>

                                    <div class="entry-content">
                                          <P class="article-font">{!! $articulo->contenido !!}</P>
                                    </div>

                                    <footer class="entry-meta" style="text-align: right;">
                                          <span class="entry-author"><i class="fa fa-calendar"></i> <a style="font-weight: bold; font-size: 20px;" class="article-font" href="#">{{$articulo->updated_at}}</a></span>
                                          <span class="entry-category"><i class="fa fa-folder-o"></i> <a style="font-weight: bold; font-size: 20px;" class="article-font" href="#">@if($articulo->articlecategory!=null) {{$articulo->articlecategory->titulo}} @else Sin Categoría @endif</a></span>
                                    </footer>
                              </div>
                        </article>
                  </div>
            </div>
      </div>
</div>