@extends('layouts.login')

@section('content')
<div class="login-form">

  <div class="form-login">
    <div class="col-md-12" style="text-align: center;">
      <img style="width: 100%;" src="{{asset('assets/img/appsiel-logo.png')}}" alt="Logo">
      <h2 class="text-center" style="font-size: 30px; font-family: 'Gotham-Narrow-Medium';">BIENVENIDOS</h2>
    </div>
    
    {{ Form::open( [ 'url' => url('/login') ] ) }}
    <?php
    if (app()->environment() != 'demo') {
      $mensaje = '';
      $mensaje2 = '';
      $email = old('email');
      $contrasenia = '';
    } else {
      $mensaje = '<div class="alert alert-warning">
                            <strong>¡Advertencia!</strong> Los datos de la plataforma demo serán borrados periodicamente.
                          </div>';
      $mensaje2 = '<div style="color: red; width: 100%; text-align: center;">Presione el botón para ingresar. <i class="fa fa-arrow-up"></i><div>';
      $email = 'demo@appsiel.com.co';
      $contrasenia = 'demo123*';
    }
    ?>

    {!! $mensaje !!}

    <div class="form-group{{ $errors->has('email') ? ' has-error' : '' }}">
      <div class="input-group">
        <span class="input-group-addon"><i class="glyphicon glyphicon-user"></i></span>
        <input id="email" type="text" class="form-control2" name="email" placeholder="Usuario" value="{{ $email }}" required="required">
      </div>
      @if ($errors->has('email'))
      <span class="help-block">
        <strong>{{ $errors->first('email') }}</strong>
      </span>
      @endif
    </div>

    <div class="form-group{{ $errors->has('password') ? ' has-error' : '' }}">
      <div class="input-group">
        <span class="input-group-addon"><i class="glyphicon glyphicon-lock"></i></span>
        <input id="password" type="password" class="form-control2" name="password" placeholder="Contraseña" required="required" value="{{ $contrasenia }}">
      </div>
      @if ($errors->has('password'))
      <span class="help-block">
        <strong>{{ $errors->first('password') }}</strong>
      </span>
      @endif
    </div>

    <div class="form-group">
      <button type="submit" class="boton">Ingresar</button>
      {!! $mensaje2 !!}
    </div>
    {{ Form::close() }}
  </div>
</div>

<div class="footer">
  <p>Desarrollado por: <a href="http://admin.appsiel.com.co/" target="_blank" style="color: #000;">APPSIEL S.A.S.</a></p>
</div>
@endsection