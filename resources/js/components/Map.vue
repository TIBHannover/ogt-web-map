<template>
    <div>
        <map-options-sidebar
            :groupedPlaces="groupedPlaces"
            :map="map"
        ></map-options-sidebar>

        <place-info-sidebar
            :selectedPlaceInfo="selectedPlaceInfo"
            :showPlaceInfoSidebar="showPlaceInfoSidebar"
            @hidePlaceInfoSidebar="toggleShowPlaceInfoSidebar(false)"
            @undoZoomIntoPlace="restoreCachedMapView()"
            @zoomIntoPlace="setMapView(selectedPlaceInfo.latLng, 18, true)"
        ></place-info-sidebar>

        <!-- leaflet map -->
        <div id="leafletMapId"></div>
    </div>
</template>

<script>
import Leaflet from 'leaflet/dist/leaflet';
import MapOptionsSidebar from './map/MapOptionsSidebar';
import PlaceInfoSidebar from './map/PlaceInfoSidebar';

export default {
    name: 'Map',
    components: {Leaflet, MapOptionsSidebar, PlaceInfoSidebar},
    data() {
        return {
            cachedMapView: {
                // Leaflet LatLng geographical point object
                latLng: {
                    lat: 0,
                    lng: 0,
                },
                zoomLevel: 0,
            },
            groupedPlaces: {
                events: {
                    color: '#D26211',
                    iconUrl: this.$ogtGlobals.proxyPath + '/images/leaflet/marker-icon-orange.png',
                    layerGroup: null,
                    layerName: 'Ereignisse',
                    places: [],
                    /* some places have multiple coordinates */
                    placesByCoordinates: [],
                },
                extPolicePrisonsAndLaborEducationCamps: {
                    color: '#743aaf',
                    iconUrl: this.$ogtGlobals.proxyPath + '/images/leaflet/marker-icon-purple.png',
                    layerGroup: null,
                    layerName: 'Erweiterte Polizeigefängnisse/AELs',
                    places: [],
                    placesByCoordinates: [],
                },
                fieldOffices: {
                    color: '#2b83cb',
                    iconUrl: this.$ogtGlobals.proxyPath + '/images/leaflet/marker-icon.png',
                    layerGroup: null,
                    layerName: 'Außendienststellen',
                    places: [],
                    placesByCoordinates: [],
                },
                prisons: {
                    color: '#38ab3e',
                    iconUrl: this.$ogtGlobals.proxyPath + '/images/leaflet/marker-icon-green.png',
                    layerGroup: null,
                    layerName: 'Gefängnisse',
                    places: [],
                    placesByCoordinates: [],
                },
                statePoliceHeadquarters: {
                    color: '#af3a3a',
                    iconUrl: this.$ogtGlobals.proxyPath + '/images/leaflet/marker-icon-red.png',
                    layerGroup: null,
                    layerName: 'Staatspolizeileitstellen',
                    places: [],
                    placesByCoordinates: [],
                },
                statePoliceOffices: {
                    color: '#bcbb29',
                    iconUrl: this.$ogtGlobals.proxyPath + '/images/leaflet/marker-icon-yellow.png',
                    layerGroup: null,
                    layerName: 'Staatspolizeistellen',
                    places: [],
                    placesByCoordinates: [],
                },
            },
            layers: null,
            map: null,
            selectedPlaceInfo: {
                description: '',
                imageUrl: '',
                instanceLabels: '',
                label: '',
                // Leaflet LatLng geographical point object
                latLng: {
                    lat: 0,
                    lng: 0,
                },
                // additional Leaflet LatLng geographical point objects of the place
                latLngAlt: [],
                layerName: '',
                sources: [{
                    dnbUrl: '',
                    label: '',
                    wikidataUrl: '',
                }],
                wikidataItem: '',
            },
            showPlaceInfoSidebar: false,
        };
    },
    created() {
        this.getGroupedPlaces();
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

            this.setCachedMapView();

            // add layers control to switch between different base layers and switch overlays on/off
            this.layers = L.control.layers(baseLayers).addTo(this.map);

            this.map.zoomControl.setPosition('topright');

            // add scale control using metric system
            L.control.scale({
                imperial: false,
            }).addTo(this.map);
        },
        /**
         * Request grouped places of Gestapo terror.
         */
        async getGroupedPlaces() {
            let groupedPlaces = {};

            await this.axios.get('/api/wikidata/places').then(response => {
                groupedPlaces = response.data;
            }).catch(error => {
                console.log(error);
            });

            this.visualizePlaces(groupedPlaces);
        },
        visualizePlaces: function (groupedPlaces) {
            for (const [group, places] of Object.entries(groupedPlaces)) {
                this.groupedPlaces[group]['places'] = places;

                let placeMarkers = this.createPlaceMarkers(group, places);

                this.createPlacesLayerGroups(group, placeMarkers);
            }
        },
        createPlaceMarkers: function (placeGroupName, places) {
            let placeMarkers = [];

            const defaultIcon = L.icon({
                iconUrl: this.groupedPlaces[placeGroupName].iconUrl,
                // added workaround to use default marker icons for retina displays
                iconRetinaUrl: this.groupedPlaces[placeGroupName].iconUrl,
                shadowUrl: this.$ogtGlobals.proxyPath + '/images/leaflet/marker-shadow.png',
                iconSize: [25, 41],
                iconAnchor: [12, 41],
                popupAnchor: [1, -34],
                tooltipAnchor: [16, -28],
                shadowSize: [41, 41],
            });

            places.forEach(place => {
                let countedPlaceCoordinates = place.coordinates.length;

                place.coordinates.forEach((placeCoordinate, placeCoordinateIndex) => {
                    let marker = L.marker(placeCoordinate, {
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
                        this.setSelectedPlaceInfo(place, event.latlng, this.groupedPlaces[placeGroupName].layerName);
                        this.toggleShowPlaceInfoSidebar(true);

                        const zoomInButton = marker.getPopup().getElement().getElementsByClassName('zoomInButton')[0];

                        let vm = this;

                        zoomInButton.onclick = function () {
                            vm.map.flyTo(event.latlng, 18);
                        };
                    });

                    placeMarkers.push(marker);

                    let placeLabelWithIndex = place.itemLabel.value;

                    if (countedPlaceCoordinates > 1) {
                        placeLabelWithIndex += ' (' + (placeCoordinateIndex + 1) + ')';
                    }

                    this.groupedPlaces[placeGroupName]['placesByCoordinates'].push({
                        placeLabelWithIndex: placeLabelWithIndex,
                        latLng: placeCoordinate,
                        marker: marker,
                    });
                });
            });

            return placeMarkers;
        },
        /**
         * Set selected place info.
         *
         * @param object place Wikidata place data
         * @param object latLng Leaflet LatLng geographical point object
         * @param string layerName Name of layer group
         */
        setSelectedPlaceInfo: function (place, latLng, layerName) {
            this.selectedPlaceInfo.description = place.itemDescription ? place.itemDescription.value : '';
            this.selectedPlaceInfo.imageUrl = place.imageUrl ? place.imageUrl.value : '';
            this.selectedPlaceInfo.instanceLabels = place.instanceLabels.value;
            this.selectedPlaceInfo.label = place.itemLabel.value;
            this.selectedPlaceInfo.layerName = layerName;
            this.selectedPlaceInfo.wikidataItem = place.item.value;

            let altCoordinates = [];

            if (place.coordinates.length > 1) {
                altCoordinates = place.coordinates.filter(function (coordinate) {
                    if (coordinate.lat == latLng.lat && coordinate.lng == latLng.lng) {
                        return false;
                    }

                    return true;
                });
            }
            this.selectedPlaceInfo.latLngAlt = altCoordinates;
            this.selectedPlaceInfo.latLng = latLng;

            this.selectedPlaceInfo.sources = [];
            if (place.source) {
                this.selectedPlaceInfo.sources = [{
                    dnbUrl: '',
                    label: '',
                    wikidataUrl: '',
                }];

                let sourceAuthorLabels = place.sourceAuthorLabels.value ? place.sourceAuthorLabels.value + ', ' : '';
                let sourceLabel = place.sourceLabel ? place.sourceLabel.value + '. ' : '';
                let sourcePublisherCityLabel = place.sourcePublisherCityLabel ? place.sourcePublisherCityLabel.value + ': ' : '';
                let sourcePublisherLabel = place.sourcePublisherLabel ? place.sourcePublisherLabel.value + ' ' : '';
                let sourcePublicationYear = place.sourcePublicationYear ? place.sourcePublicationYear.value + ', ' : '';
                let sourcePages = place.sourcePages ? 'S. ' + place.sourcePages.value : '';
                this.selectedPlaceInfo.sources[0].label =
                    sourceAuthorLabels +
                    sourceLabel +
                    sourcePublisherCityLabel +
                    sourcePublisherLabel +
                    sourcePublicationYear +
                    sourcePages;
                this.selectedPlaceInfo.sources[0].dnbUrl = place.sourceDnbLink ? 'https://d-nb.info/' + place.sourceDnbLink.value : '';
                this.selectedPlaceInfo.sources[0].wikidataUrl = place.source ? place.source.value : '';
            }
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
            this.layers.addOverlay(layerGroup, this.groupedPlaces[placeGroupName].layerName);
            this.groupedPlaces[placeGroupName].layerGroup = layerGroup;
        },
        /**
         * Show/Hide place info sidebar.
         *
         * @param bool status Show or hide place info sidebar.
         */
        toggleShowPlaceInfoSidebar: function (status) {
            this.showPlaceInfoSidebar = status;
        },
        /**
         * Switch to cached map view.
         */
        restoreCachedMapView: function () {
            this.setMapView(this.cachedMapView.latLng, this.cachedMapView.zoomLevel);
        },
        /**
         * Fly to new map view and optionally cache the current map view.
         *
         * @param object latLng
         * @param int zoomLevel
         * @param bool cacheCurrentMapView
         */
        setMapView: function (latLng, zoomLevel, cacheCurrentMapView = false) {
            if (cacheCurrentMapView) {
                this.setCachedMapView();
            }

            this.map.flyTo(latLng, zoomLevel);
        },
        /**
         * Cache current map view (latitude, longitude, zoom-level).
         */
        setCachedMapView: function () {
            this.cachedMapView.latLng = this.map.getCenter();
            this.cachedMapView.zoomLevel = this.map.getZoom();
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

.leaflet-tile-pane {
    -webkit-filter: grayscale(100%);
    filter: grayscale(100%);
}
</style>

<style scoped>
@import 'leaflet/dist/leaflet.css';

#leafletMapId {
    height: 100%;
    position: absolute;
    width: 100%;
    z-index: 0;
}
</style>
