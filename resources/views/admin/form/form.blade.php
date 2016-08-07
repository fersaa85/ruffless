@extends('template')
@section('section')
@if( !$getMathces->isEmpty() )
    @foreach($getMathces as $key => $value )
        {!! Form::radio("local-$key", "$value->id-$value->local_id" ) !!} Local {{ $value->local_id  }}

        {!! Form::radio("tie-$key", "$value->id-tie" ) !!} Empate

        {!! Form::radio("visit-$key", "$value->id-$value->visit_id" ) !!} Visitante  {{ $value->visit_id  }}

        <br /><br />
    @endforeach
@endif



{!! Form::submit("Enviar") !!}
@endsection