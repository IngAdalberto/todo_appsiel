
<button onclick="mandar_codigo2({{ $item->id }})" class="btn btn-block btn-default btn-xs" title="{{ $item->descripcion }}">
    <br>
    @if($item->imagen!='')
        <img style="width: 100px; height: 100px; border-radius:4px;" src="{{url('')}}/appsiel/storage/app/inventarios/{{$item->imagen}}">
    @else
        <img style="width: 100px; height: 100px;" src="{{url('')}}/assets/img/box.png">
    @endif
    <p style="text-align: center; white-space: nowrap; overflow: hidden; white-space: initial;">{{ $item->descripcion }}</p>
</button>