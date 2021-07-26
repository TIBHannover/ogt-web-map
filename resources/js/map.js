let baseLayers = {};

for (const [providerName, providerData] of Object.entries(configLeaflet.tileLayerProviders)) {
    baseLayers[providerName] = L.tileLayer(providerData.urlTemplate, providerData.options);
}

// initialize the map on the "leafletMapId" div
let map = L.map('leafletMapId', {
    center: configLeaflet.center,
    zoom: configLeaflet.zoom,
    layers: [baseLayers[configLeaflet.initialLayerName]],
});

// add layers control to switch between different base layers and switch overlays on/off
L.control.layers(baseLayers).addTo(map);

map.zoomControl.setPosition('topright');

// add scale control using metric system
L.control.scale({
    imperial: false,
}).addTo(map);
