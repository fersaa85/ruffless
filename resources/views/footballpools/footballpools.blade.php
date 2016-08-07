{!! Form::open(['method' => 'post',
							'files' => true,
							'id' => 'form',
							'name' => 'frm']) !!}


@include('footballpools.form.index')


{!! Form::close() !!}