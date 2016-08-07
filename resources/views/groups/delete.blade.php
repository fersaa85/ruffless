
realmente desea borralr este grupo

{!! Form::open(['method' => 'delete',
							'files' => true,
							'id' => 'form',
							'name' => 'frm']) !!}

    {!! Form::hidden("id", $id) !!}
    {!! Form::submit("Enviar") !!}


{!! Form::close() !!}