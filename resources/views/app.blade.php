<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <!-- <base href="https://www.example.com/"> -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>OGT-app</title>
    <link type="text/css" rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>

<body>

<div id="app"></div>

<script type="text/javascript" src="{{ asset('js/app.js') }}"></script>
<script>
    const configLeaflet = @json(config('leaflet'));
</script>
</body>

</html>
