


{!! Form::text('name', Request::input('name'), ['class'=>'form-control','placeholder'=>'NOMBRE']) !!}
<br /><br />

{!! Form::text('last_name', Request::input('name'), ['class'=>'form-control', 'placeholder'=>'Apellido']) !!}
<br /><br />

{!! Form::text('email', Request::input('email'), ['class'=>'form-control', 'placeholder'=>'email']) !!}
<br /><br />


{!! Form::text('phone', Request::input('phone'), ['class'=>'form-control', 'placeholder'=>'Teléfono']) !!}
<br /><br />

{!! Form::text('phone2', Request::input('phone2'), ['class'=>'form-control', 'placeholder'=>'Teléfono Alterno']) !!}
<br /><br />


{!! Form::text('email2', Request::input('email2'), ['class'=>'form-control', 'placeholder'=>'Correo Alterno']) !!}
<br /><br />

{!! Form::select('team_id', $teams, $selected["team_id"], ['class'=>'form-control', 'placeholder'=>'Equipo favorito']) !!}
<br /><br />

{!! Form::text('points_twitter', Request::input('points_twitter'), ['class'=>'form-control', 'placeholder'=>'Vincular Twitter']) !!}
<br /><br />

{!! Form::text('points_facebook', Request::input('points_twitter'), ['class'=>'form-control', 'placeholder'=>'Vincular Facebook']) !!}
<br /><br />
{!! Form::submit('Enviar', ['class'=>'form-control']) !!}

<br />
<a href="{{ URL::to('perfil/join-facebook') }}" >Unirce a  facebook</a>

<br />
<a href="{{ URL::to('perfil/join-twitter') }}" >Unirce a  twitter</a>