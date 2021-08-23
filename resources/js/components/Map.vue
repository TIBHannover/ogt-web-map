<template>
    <div>
        <map-options-sidebar></map-options-sidebar>

        <!-- leaflet map -->
        <div id="leafletMapId"></div>
    </div>
</template>

<script>
import Leaflet from '/js/leaflet.js';
import MapOptionsSidebar from './map/MapOptionsSidebar';

export default {
    name: 'Map',
    components: {Leaflet, MapOptionsSidebar},
    data() {
        return {
            layers: null,
            map: null,
            places: [],
            placesLayerGroupsConfig: {
                fieldOffices: {
                    layerName: 'Außendienststelle',
                    iconUrl: '/images/leaflet/marker-icon.png',
                },
                extPolicePrisonsAndLaborEducationCamps: {
                    layerName: 'Erweitertes Polizeigefängnis/AEL',
                    iconUrl: '/images/leaflet/marker-icon-purple.png',
                },
                prisons: {
                    layerName: 'Gefängnis',
                    iconUrl: '/images/leaflet/marker-icon-green.png',
                },
                statePoliceHeadquarters: {
                    layerName: 'Staatspolizeileitstelle',
                    iconUrl: '/images/leaflet/marker-icon-red.png',
                },
                statePoliceOffices: {
                    layerName: 'Staatspolizeistelle',
                    iconUrl: '/images/leaflet/marker-icon-yellow.png',
                },
            },
        };
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
                this.visualizePlaces(response.data);
            }).catch(error => {
                console.log(error);
            });
        },
        visualizePlaces: function (placeGroups) {
            for (const [placeGroupName, places] of Object.entries(placeGroups)) {
                let placeMarkers = this.createPlaceMarkers(placeGroupName, places);

                this.createPlacesLayerGroups(placeGroupName, placeMarkers);
            }
        },
        createPlaceMarkers: function (placeGroupName, places) {
            let placeMarkers = [];

            const defaultIcon = L.icon({
                iconUrl: this.placesLayerGroupsConfig[placeGroupName].iconUrl,
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
                        ${place.instanceLabels.value}
                    </div>
                    <br>
                    ${place.itemDescription ? place.itemDescription.value : ''}`;

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

                placeMarkers.push(marker);
            });

            return placeMarkers;
        },
        /**
         * Create a layer group for markers, activate layer group on map and
         * add layer group checkbox at Leaflet layer control.
         *
         * @param string placeGroupName Layer name.
         * @param array placeMarkers Leaflet markers.
         */
        createPlacesLayerGroups: function (placeGroupName, placeMarkers) {
            let layerGroup = L.layerGroup(placeMarkers);
            layerGroup.addTo(this.map);
            this.layers.addOverlay(layerGroup, this.placesLayerGroupsConfig[placeGroupName].layerName);
        },
    },
};
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

/* top-right Leaflet control */
.leaflet-top.leaflet-right {
    margin-top: 80px;
    margin-right: 10px;
}
</style>

<style scoped>
@import '/public/css/leaflet.css';

#leafletMapId {
    height: 100%;
    position: absolute;
    width: 100%;
    z-index: 0;
}
</style>
