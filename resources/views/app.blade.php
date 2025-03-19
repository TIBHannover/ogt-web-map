<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gestapo. Terror. Orte</title>
    <meta name="description" content="Die interaktive Karte zeigt Orte des Gestapo-Terrors in Niedersachsen zur NS-Zeit und spätere Erinnerungsorte. Recherchiere Infos oder trage neue Daten ein! ">
    <meta name="keywords" content="Gestapo, Terror, Orte, Geschichtliches Erbe, Open Access, Erinnerungskultur">
    <link rel="stylesheet" href="{{ env('MIX_ASSET_URL') }}/css/app.css">
</head>

<body>
<div id="app"></div>

<script src="{{ env('MIX_ASSET_URL') }}/js/app.js"></script>
</body>

</html>
