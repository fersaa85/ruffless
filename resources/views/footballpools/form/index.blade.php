
@if( !$getMathces->isEmpty() )
    @foreach($getMathces as $key => $value )
        {!! Form::radio("local[]", "$value->id-$value->local_id" ) !!} Local {{ $value->local_id  }}

        {!! Form::radio("tie[]", "$value->id-tie" ) !!} Empate

        {!! Form::radio("visit[]", "$value->id-$value->visit_id" ) !!} Visitante  {{ $value->visit_id  }}

        <br /><br />
    @endforeach
@endif



{!! Form::submit("Enviar") !!}