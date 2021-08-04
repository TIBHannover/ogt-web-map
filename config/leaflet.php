<?php

return [
    // geographical center Lower Saxony
    'center' => [52.8398667, 9.0759744],
    'initialLayerName' => 'OSM default',
    // Leaflet-providers preview: http://leaflet-extras.github.io/leaflet-providers/preview/index.html
    'tileLayerProviders' => [
        'OSM default' => [
            'urlTemplate' => 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
            'options' => [
                'attribution' =>
                    '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            ],
        ],
        'CyclOSM' => [
            'urlTemplate' => 'https://{s}.tile-cyclosm.openstreetmap.fr/cyclosm/{z}/{x}/{y}.png',
            'options' => [
                'attribution' =>
                    '<a href="https://github.com/cyclosm/cyclosm-cartocss-style/releases" ' .
                    'title="CyclOSM - Open Bicycle render">CyclOSM</a> | Map data: &copy; ' .
                    '<a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            ],
        ],
        'Humanitarian' => [
            'urlTemplate' => 'https://{s}.tile.openstreetmap.fr/hot/{z}/{x}/{y}.png',
            'options' => [
                'attribution' =>
                    '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors, Tiles ' .
                    'style by <a href="https://www.hotosm.org/" target="_blank">Humanitarian OpenStreetMap Team</a> ' .
                    'hosted by <a href="https://openstreetmap.fr/" target="_blank">OpenStreetMap France</a>',
            ],
        ],
    ],
    'zoom' => 8,
];
