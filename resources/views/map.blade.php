@extends('layouts.app')
@section('head-content')
    <link rel="stylesheet" href="{{ mix('css/leaflet.css') }}">
    <link rel="stylesheet" href="{{ mix('css/map.css') }}">
    <script src="{{ mix('js/leaflet.js') }}"></script>
    <script defer src="{{ mix('js/map.js') }}"></script>
@section('body-content')
    <div id="leafletMapId" class="center-block"></div>
    <script>
        const configLeaflet = @json(config('leaflet'));
    </script>
@endsection
