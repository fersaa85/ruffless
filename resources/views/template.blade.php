<!DOCTYPE html>
<html lang="es">
<head>
    <title>Titulo de la web</title>

    <meta charset="utf-8" />

    <script> var site_domine = "{{ URL::to('/') }}"</script>

    {!! HTML::script('https://code.jquery.com/jquery-2.2.3.min.js') !!}
    {!! HTML::script('assets/js/default.js') !!}
</head>

<body>
@yield('section')
</body>
</html>