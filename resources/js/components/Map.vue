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
                    iconUrl: '/images/leaflet/markerIcons/greyFilled/events.svg',
                    layerGroup: null,
                    layerName: 'Ereignisse',
                    places: [],
                    /* some places have multiple coordinates */
                    placesByCoordinates: [],
                },
                extPolicePrisons: {
                    color: '#3AAFAF',
                    iconUrl: '/images/leaflet/markerIcons/greyFilled/extPolicePrisons.svg',
                    layerGroup: null,
                    layerName: 'Erweiterte Polizeigefängnisse',
                    places: [],
                    placesByCoordinates: [],
                },
                fieldOffices: {
                    color: '#2b83cb',
                    iconUrl: '/images/leaflet/markerIcons/greyFilled/fieldOffices.svg',
                    layerGroup: null,
                    layerName: 'Außendienststellen',
                    places: [],
                    placesByCoordinates: [],
                },
                laborEducationCamps: {
                    color: '#743aaf',
                    iconUrl: '/images/leaflet/markerIcons/greyFilled/laborEducationCamps.svg',
                    layerGroup: null,
                    layerName: 'Arbeitserziehungslager',
                    places: [],
                    placesByCoordinates: [],
                },
                memorials: {
                    color: '#D255BE',
                    iconUrl: '/images/leaflet/markerIcons/greyFilled/memorials.svg',
                    layerGroup: null,
                    layerName: 'Erinnerungsorte',
                    places: [],
                    placesByCoordinates: [],
                },
                prisons: {
                    color: '#38ab3e',
                    iconUrl: '/images/leaflet/markerIcons/greyFilled/prisons.svg',
                    layerGroup: null,
                    layerName: 'Gefängnisse',
                    places: [],
                    placesByCoordinates: [],
                },
                statePoliceHeadquarters: {
                    color: '#af3a3a',
                    iconUrl: '/images/leaflet/markerIcons/greyFilled/statePoliceHeadquarters.svg',
                    layerGroup: null,
                    layerName: 'Staatspolizeileitstellen',
                    places: [],
                    placesByCoordinates: [],
                },
                statePoliceOffices: {
                    color: '#bcbb29',
                    iconUrl: '/images/leaflet/markerIcons/greyFilled/statePoliceOffices.svg',
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
                dissolvedDate: {
                    locale: '',
                    value: null,
                },
                id: '',
                inceptionDate: {
                    locale: '',
                    value: null,
                },
                label: '',
                // Leaflet LatLng geographical point object
                latLng: {
                    lat: 0,
                    lng: 0,
                },
                layerName: '',
                mainImageUrl: '',
                mainImageLegend: '',
                sources: [{
                    label: '',
                    pages: '',
                }],
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
            let iconUrl = this.$ogtGlobals.proxyPath + this.groupedPlaces[placeGroupName].iconUrl;
            const defaultIcon = L.icon({
                iconUrl: iconUrl,
                // Workaround to use same marker icons for Retina and non-Retina displays.
                // - default file '/images/leaflet/marker-icon-2x.png'
                iconRetinaUrl: iconUrl,
                shadowUrl: this.$ogtGlobals.proxyPath + '/images/leaflet/marker-shadow.png',
                iconSize: [48, 53], // default [25, 41]
                iconAnchor: [24, 52], // default [12, 41]
                popupAnchor: [1, -34],
                tooltipAnchor: [16, -28],
                shadowSize: [76, 52], // default [41, 41]
            });

            for (const [placeId, place] of Object.entries(places)) {
                let countedPlaceCoordinates = Object.keys(place.coordinates).length;
                let coordinatesIndex = 0;

                for (const [statementId, placeCoordinate] of Object.entries(place.coordinates)) {
                    let marker = L.marker(placeCoordinate.value, {
                        icon: defaultIcon,
                        title: place.label,
                    });

                    let markerPopUpHtmlTemplate = `
                        <div class="popUpTopic">
                            <a href="https://www.wikidata.org/wiki/${placeId}" target="_blank">
                                ${place.label}
                            </a>
                            <button class="zoomInButton">
                                &#x1f50d;
                            </button>
                        </div>
                        <br>
                        ${place.description}`;

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

                    let placeLabelWithIndex = place.label;

                    if (countedPlaceCoordinates > 1) {
                        placeLabelWithIndex += ' (' + (++coordinatesIndex) + ')';
                    }

                    this.groupedPlaces[placeGroupName]['placesByCoordinates'].push({
                        placeLabelWithIndex: placeLabelWithIndex,
                        latLng: placeCoordinate,
                        marker: marker,
                    });
                };
            }

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
            this.selectedPlaceInfo.description = place.description;
            this.selectedPlaceInfo.label = place.label;
            this.selectedPlaceInfo.layerName = layerName;
            this.selectedPlaceInfo.id = place.id;
            this.selectedPlaceInfo.latLng = latLng;

            this.selectedPlaceInfo.mainImageUrl = '';
            this.selectedPlaceInfo.mainImageLegend = '';

            const imageKeys = place.images ? Object.keys(place.images) : [];
            const imagesCounted = imageKeys.length;

            if (imagesCounted == 1) {
                // case: only one location image available, show this
                this.selectedPlaceInfo.mainImageUrl = place.images[imageKeys[0]].value;
                this.selectedPlaceInfo.mainImageLegend =
                    place.images[imageKeys[0]].mediaLegend ? place.images[imageKeys[0]].mediaLegend.value : '';
            }
            else if (imagesCounted > 1) {
                // case: multiple images available for location, show image that belongs to coordinates, otherwise none
                imageKeys.some((imageKey) => {
                    let image = place.images[imageKey];

                    if (image.coordinate &&
                        image.coordinate.value.lat == latLng.lat &&
                        image.coordinate.value.lng == latLng.lng
                    ) {
                        this.selectedPlaceInfo.mainImageUrl = image.value;
                        this.selectedPlaceInfo.mainImageLegend = image.mediaLegend ? image.mediaLegend.value : '';
                        return true;
                    }
                });
            }

            this.selectedPlaceInfo.inceptionDate = {
                locale: '',
                value: null,
            };

            if (place.inceptionDates) {
                for (const [statementId, inceptionDate] of Object.entries(place.inceptionDates)) {
                    this.selectedPlaceInfo.inceptionDate = this.getDate(inceptionDate.value, inceptionDate.datePrecision);
                    break;
                }
            }

            this.selectedPlaceInfo.dissolvedDate = {
                locale: '',
                value: null,
            };

            if (place.dissolvedDates) {
                for (const [statementId, dissolvedDate] of Object.entries(place.dissolvedDates)) {
                    this.selectedPlaceInfo.dissolvedDate = this.getDate(dissolvedDate.value, dissolvedDate.datePrecision);
                    break;
                }
            }

            this.selectedPlaceInfo.sources = [];
            if (place.describedBySources) {
                for (const [statementId, describedBySource] of Object.entries(place.describedBySources)) {
                    this.selectedPlaceInfo.sources.push({
                        label: describedBySource.value,
                        pages: describedBySource.pages ? describedBySource.pages.value : '',
                    });
                }
            }
        },
        /**
         * Get locale date string base on Wikidata time precision.
         *
         * @param {string} dateTimeString   Wikidata datetime
         * @param {int} datePrecision       9 => year precision, 10 => month precision, 11 => day precision
         * @param {string} locale           default 'de-de'
         *
         * @returns {{locale: string, value: Date}|null}
         */
        getDate: function (dateTimeString, datePrecision, locale = 'de-de') {
            let dateFormatOptions = {};

            switch (datePrecision) {
                case '9':
                    dateFormatOptions = {
                        year: 'numeric',
                    };
                    break;

                case '10':
                    dateFormatOptions = {
                        year: 'numeric',
                        month: 'short',
                    };
                    break;

                case '11':
                    dateFormatOptions = {
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric',
                    };
                    break;

                default:
                    return null;
            }

            let date = new Date(dateTimeString);

            return {
                locale: date.toLocaleDateString(locale, dateFormatOptions),
                value: date,
            };
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

/* greyscale map tiles */
.leaflet-tile-pane {
    filter: grayscale(1);
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
