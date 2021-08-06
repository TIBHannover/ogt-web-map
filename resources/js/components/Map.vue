<template>
    <div id="leafletMapId"></div>
</template>

<script>
import '/js/leaflet.js';

export default {
    name: "Map",
    data() {
        return {
            places: [],
            layers: null,
            map: null,
        }
    },
    created() {
        this.getPlaces();
    },
    mounted() {
        this.setupLeafletMap();
    },
    methods: {
        setupLeafletMap: function () {
            let baseLayers = {};

            for (const [providerName, providerData] of Object.entries(configLeaflet.tileLayerProviders)) {
                baseLayers[providerName] = L.tileLayer(providerData.urlTemplate, providerData.options);
            }

            // initialize the map on the "leafletMapId" div
            this.map = L.map('leafletMapId', {
                center: configLeaflet.center,
                zoom: configLeaflet.zoom,
                layers: [baseLayers[configLeaflet.initialLayerName]],
            });

            // add layers control to switch between different base layers and switch overlays on/off
            this.layers = L.control.layers(baseLayers).addTo(this.map);

            this.map.zoomControl.setPosition('topright');

            // add scale control using metric system
            L.control.scale({
                imperial: false,
            }).addTo(this.map);
        },
        async getPlaces() {
            await this.axios.get('/wikidata/places').then(response => {
                this.displayGestapoMarkers(response.data.results.bindings);
            }).catch(error => {
                console.log(error)
            });
        },
        displayGestapoMarkers: function (places) {
            let gestapoPlacesLayerGroup = L.layerGroup();

            let defaultIcon = L.icon({
                iconUrl: '/images/vendor/leaflet/dist/marker-icon.png',
                iconRetinaUrl: '/images/leaflet/marker-icon-2x.png',
                shadowUrl: '/images/leaflet/marker-shadow.png',
                iconSize: [25, 41],
                iconAnchor: [12, 41],
                popupAnchor: [1, -34],
                tooltipAnchor: [16, -28],
                shadowSize: [41, 41],
            });

            places.forEach(place => {
                let marker = L.marker([place.lat.value, place.lng.value], {
                    icon: defaultIcon,
                    title: place.itemLabel.value,
                });

                let markerPopUpHtmlTemplate = `
                    <div class="popUpTopic">
                        <a href="${place.item.value}" target="_blank">
                            ${place.itemLabel.value}
                        </a>
                        <button class="zoomInButton">
                            &#x1f50d;
                        </button>
                    </div>
                    <div class="popUpTopicCategory">
                        ${place.itemInstanceLabelConcat.value}
                    </div>
                    <br>
                    ${place.itemDescription.value}`;

                marker.bindPopup(markerPopUpHtmlTemplate, {
                    minWidth: 333,
                });

                marker.on('click', event => {
                    const zoomInButton = marker.getPopup().getElement().getElementsByClassName('zoomInButton')[0];

                    let vm = this;

                    zoomInButton.onclick = function () {
                        vm.map.flyTo(event.latlng, 18);
                    };
                });

                gestapoPlacesLayerGroup.addLayer(marker);
            });

            this.layers.addOverlay(gestapoPlacesLayerGroup, 'OGT-places');
            gestapoPlacesLayerGroup.addTo(this.map);
        }
    },
}
</script>

<style>
.popUpTopic {
    font-weight: bold;
}

.popUpTopicCategory {
    font-style: italic;
}

.zoomInButton {
    background-color: inherit;
    border: none;
    cursor: pointer;
    font-size: 20px;
}
</style>

<style scoped>
@import '/public/css/leaflet.css';

#leafletMapId {
    height: 100%;
    position: absolute;
    width: 100%;
    z-index: 3;
}
</style>
