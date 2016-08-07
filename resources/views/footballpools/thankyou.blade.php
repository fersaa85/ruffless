@extends('template')
@section('section')
<a href="{{ URL::to('reto/share') }}" class="share" data-share="facebook" onclick="window.open('{{ $getFacebookShare }}','popupShare', 'toolbar=0, status=0, width=650, height=450');">Facebook</a>

<a href="{{ URL::to('reto/share') }}"  class="share" data-share="twitter" onclick="window.open('{{ $getTwitterShare  }}','popupShare', 'toolbar=0, status=0, width=450, height=250');">
    <img src="images/icon-share-twitter.png" height="18" />
</a>



<a href="{{ URL::to('reto/share') }}"  class="share" data-share="twitter" >Instangram</a>


@endsection