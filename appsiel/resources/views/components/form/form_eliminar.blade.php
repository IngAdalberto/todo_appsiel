{{ Form::open( [ 'url' => $url.'?id='.Input::get('id').'&id_modelo='.Input::get('id_modelo'), 'style' => 'display:inline;' ] ) }}
	{{ Form::hidden( 'recurso_a_eliminar_id', $recurso_id ) }}
	<button class="btn-gmail" title="Eliminar"> <i class="fa fa-trash"></i> </button>
{{ Form::close() }}