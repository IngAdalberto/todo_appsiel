<section id="blog">
    <div class="container">
        @if($setup!=null)
        <div class="section-header">
            <h2 class="section-title text-center wow fadeInDown">{{$setup->titulo}}</h2>
            <p class="text-center wow fadeInDown">{{$setup->descripcion}}</p>
        </div>

        <div class="row col-md-12 wow fadeInDown">
            @if($setup->formato=='LISTA')
            @foreach($articles as $a)
            <div class="col-md-12 article-ls" style="line-height: 5px; margin-bottom: 20px;">
                <div class="media service-box" style="margin: 10px !important; font-size: 14px;">
                    <div class="pull-left">
                        <i style="cursor: pointer;" class="fa fa-edit"></i>
                    </div>
                    <div class="media-body">
                        <div class="row">
                            <div class="col-md-8">
                                <h6 style="font-size: 14px;" class="media-heading">{{$a->titulo}}</h6>
                                <p>{!! str_limit($a->contenido, $limit = 100, $end = '...') !!}</p>
                            </div>
                            <div class="col-md-4" style="padding: 5px; text-align: right;">
                                <p><span class="entry-author"><i class="fa fa-calendar"></i> <a href="#">{{$a->updated_at}}</a></span></p>
                                <p><span class="entry-category"><i class="fa fa-folder-o"></i> <a href="#">{{$setup->titulo}}</a></span></p>
                                <p><a href="#" class="btn btn-primary waves-effect btn-sm"><i class="fa fa-plus"></i> Leer más...</a></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
            @endif
            @if($setup->formato=='BLOG')
            @foreach($articles as $a)
            <div class="col-md-6">
                <div class="blog-post blog-media">
                    <article class="media clearfix">
                        <div class="entry-thumbnail pull-left">
                            <span class="post-format post-format-gallery"><i class="fa fa-bullhorn"></i></span>
                        </div>
                        <div class="media-body">
                            <header class="entry-header">
                                <div class="entry-date">{{$a->created_at}}</div>
                                <h2 class="entry-title"><a href="#">{{$a->titulo}}</a></h2>
                            </header>

                            <div class="entry-content">
                                <P>{!! str_limit($a->contenido, $limit = 100, $end = '...') !!}</P>
                                <a class="btn btn-primary" href="#">Leer más...</a>
                            </div>

                            <footer class="entry-meta">
                                <span class="entry-author"><i class="fa fa-calendar"></i> <a href="#">{{$a->updated_at}}</a></span>
                                <span class="entry-category"><i class="fa fa-folder-o"></i> <a href="#">{{$setup->titulo}}</a></span>
                            </footer>
                        </div>
                    </article>
                </div>
            </div>
            @endforeach
            @endif
            <div class="col-md-12">
                {{$articles->render()}}
            </div>
        </div>
        @else
        <div class="section-header">
            <h2 class="section-title text-center wow fadeInDown">Sección</h2>
            <p class="text-center wow fadeInDown">Sin configuración</p>
        </div>
        @endif
    </div>

</section>