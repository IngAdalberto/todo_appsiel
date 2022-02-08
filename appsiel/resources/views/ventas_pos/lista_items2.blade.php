<div class="row">
	<!-- <h4 style="text-align: center;">Selecciona la categoría luego indica el producto dando clic en COMPRAR</h4> -->
	<div class="col-md-12">
		<div class="accordion" id="accordionExample">
			<?php $i = 0; ?>
			@foreach( $productosTemp as $key=>$value)
			<div class="card">
				<div class="card-header" id="heading{{$i}}">
					<h2 class="mb-0">
						<button class="btn btn-primary btn-block text-left" type="button" data-toggle="collapse" data-target="#collapse{{$i}}" aria-expanded="true" aria-controls="collapse{{$i}}">
							{{$key}}
						</button>
					</h2>
				</div>
				<div id="collapse{{$i}}" class="collapse" aria-labelledby="heading{{$i}}" data-parent="#accordionExample">
					<div class="card-body">
						@if(count($value)>0)
						@foreach($value as $item)
							<div class="col-md-3" style="padding: 10px;" id="btn_{{ $item->id }}">
									<button onclick="mandar_codigo2({{ $item->id }})" class="btn btn-block btn-default btn-xs">
										<br>
										@if($item->imagen!='')
										 <img style="width: 100px; height: 100px; border-radius:4px;" src="{{url('')}}/appsiel/storage/app/inventarios/{{$item->imagen}}">
										@else
										<img style="width: 100px; height: 100px;" src="{{url('')}}/assets/img/box.png">
										@endif
										<p style="text-align: center;">{{ $item->descripcion }}</p>
									</button>
									<br>
							</div>
						@endforeach
						@else
						<h5>No hay productos en esta categoría</h5>
						@endif
					</div>
				</div>
			</div>
			<?php $i = $i + 1; ?>
			@endforeach
		</div>
	</div>
</div>