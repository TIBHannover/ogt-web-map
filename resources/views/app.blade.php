<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>OGT-app</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>

<body>
<div id="app"></div>

<script src="{{ asset('js/app.js') }}"></script>
<script>
    const configLeaflet = @json(config('leaflet'));
</script>
</body>

</html>
