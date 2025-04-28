<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gestapo. Terror. Orte</title>
    <meta name="description" content="Die interaktive Karte zeigt Orte des Gestapo-Terrors in Niedersachsen zur NS-Zeit und spätere Erinnerungsorte. Recherchiere Infos oder trage neue Daten ein! ">
    <meta name="keywords" content="Gestapo, Terror, Orte, Geschichtliches Erbe, Open Access, Erinnerungskultur">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>

<body>
<div id="app"></div>

<script src="{{ asset('js/app.js') }}"></script>
</body>

</html>
