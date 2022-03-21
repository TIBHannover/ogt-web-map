<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>OGT-app</title>
    <link type="text/css" rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>

<body>
<p>just a test on app-blade.php</p>
<div id="app"></div>

<script type="text/javascript" src="{{ asset('js/app.js') }}"></script>
<script>
    const configLeaflet = @json(config('leaflet'));
</script>
</body>

</html>
