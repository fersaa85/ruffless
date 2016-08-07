{!! Form::open(['method' => 'post',
							'files' => true,
							'id' => 'form',
							'name' => 'frm']) !!}


@include('groups.form.friends')


{!! Form::close() !!}