{!! Form::open(['method' => 'post',
							'files' => true,
							'id' => 'form',
							'name' => 'frm']) !!}


@include('groups.form.index')


{!! Form::close() !!}