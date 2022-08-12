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
            @switchLocation="switchLocation"
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
            locationMarkers: [],
            map: null,
            selectedPlaceInfo: {
                addresses: {
                    additional: [{
                        endDate: {
                            locale: '',
                            value: null,
                        },
                        label: '',
                        latLng: {
                            lat: 0,
                            lng: 0,
                        },
                        startDate: {
                            locale: '',
                            value: null,
                        },
                    }],
                    selected: {
                        endDate: {
                            locale: '',
                            value: null,
                        },
                        label: '',
                        latLng: {
                            lat: 0,
                            lng: 0,
                        },
                        startDate: {
                            locale: '',
                            value: null,
                        },
                    },
                },
                description: '',
                directors: [{
                    endDate: {
                        locale: '',
                        value: null,
                    },
                    maxStartDate: {
                        locale: '',
                        value: null,
                    },
                    minEndDate: {
                        locale: '',
                        value: null,
                    },
                    name: '',
                    startDate: {
                        locale: '',
                        value: null,
                    },
                }],
                dissolvedDate: {
                    locale: '',
                    value: null,
                },
                employeeCounts: [{
                    pointInTime: {
                        locale: '',
                        value: null,
                    },
                    sourcingCircumstance: '',
                    value: 0,
                }],
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
                prisonerCounts: [{
                    sourcingCircumstance: '',
                    value: 0,
                }],
                sources: [{
                    label: '',
                    pages: '',
                }],
            },
            showPlaceInfoSidebar: false,
            sourceCircumstances: {
                Q5727902: 'ca.',
                Q47035128: '>',
            },
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
                this.locationMarkers[placeId] = [];

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

                    this.locationMarkers[placeId][Object.values(placeCoordinate.value).join(',')] = marker;

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

            this.selectedPlaceInfo.addresses = {
                additional: [],
                selected: null,
            };

            // add location coordinates and related additional information to location address data
            for (const [statementId, coordinate] of Object.entries(place.coordinates)) {
                let label = coordinate.streetAddress ? coordinate.streetAddress.value : '';

                let startDate = coordinate.startTime ?
                    this.getDate(coordinate.startTime.value, coordinate.startTime.datePrecision) : null;

                let endDate = coordinate.endTime ?
                    this.getDate(coordinate.endTime.value, coordinate.endTime.datePrecision) : null;

                let address = {
                    endDate: endDate,
                    label: label,
                    latLng: coordinate.value,
                    startDate: startDate,
                };

                if (coordinate.value.lat == latLng.lat || coordinate.value.lng == latLng.lng) {
                    this.selectedPlaceInfo.addresses.selected = address;
                }
                else {
                    this.selectedPlaceInfo.addresses.additional.push(address);
                }
            }

            // merge street addresses and related additional information with location address data
            if (place.streetAddresses) {
                let selectedAddress = this.selectedPlaceInfo.addresses.selected;
                let additionalAddresses = this.selectedPlaceInfo.addresses.additional;

                let streetAddressKeys = Object.keys(place.streetAddresses);

                if (streetAddressKeys.length == 1 && additionalAddresses.length == 0) {
                    // case: automatically merge address data, if location has only one coordinate and one street address
                    let address = place.streetAddresses[streetAddressKeys[0]];

                    if (selectedAddress.label == '') {
                        selectedAddress.label = address.value;
                    }

                    if (address.startTime) {
                        selectedAddress.startDate = this.getDate(address.startTime.value, address.startTime.datePrecision);
                    }

                    if (address.endTime) {
                        selectedAddress.endDate = this.getDate(address.endTime.value, address.endTime.datePrecision);
                    }
                }
                else {
                    streetAddressKeys.forEach((addressKey) => {
                        let streetAddress = place.streetAddresses[addressKey];

                        let startDate = streetAddress.startTime ?
                            this.getDate(streetAddress.startTime.value, streetAddress.startTime.datePrecision) : null;

                        let endDate = streetAddress.endTime ?
                            this.getDate(streetAddress.endTime.value, streetAddress.endTime.datePrecision) : null;

                        if (streetAddress.value == selectedAddress.label) {
                            // case: street address is selected address
                            if (startDate) {
                                selectedAddress.startDate = startDate;
                            }

                            if (endDate) {
                                selectedAddress.endDate = endDate;
                            }
                        }
                        else {
                            let foundAdditionalAddress = false;

                            // find street address within additional addresses and update dates
                            additionalAddresses.some((additionalAddress) => {
                                if (streetAddress.value == additionalAddress.label) {
                                    foundAdditionalAddress = true;

                                    if (startDate) {
                                        additionalAddress.startDate = startDate;
                                    }

                                    if (endDate) {
                                        additionalAddress.endDate = endDate;
                                    }

                                    return true;
                                }
                            });

                            if (! foundAdditionalAddress) {
                                // case: street address not found in additional addresses, so add it
                                additionalAddresses.push({
                                    endDate: endDate,
                                    label: streetAddress.value,
                                    latLng: null,
                                    startDate: startDate,
                                });
                            }
                        }
                    });
                }
            }

            this.selectedPlaceInfo.addresses.additional.sort(this.sortByDate);

            this.selectedPlaceInfo.employeeCounts = [];

            if (place.employeeCounts) {
                for (const [statementId, employeeCount] of Object.entries(place.employeeCounts)) {
                    let pointInTime = employeeCount.pointInTime ?
                        this.getDate(employeeCount.pointInTime.value, employeeCount.pointInTime.datePrecision) : null;

                    let sourcingCircumstance = '';
                    if (employeeCount.sourcingCircumstance && employeeCount.sourcingCircumstance.id in this.sourceCircumstances) {
                        sourcingCircumstance = this.sourceCircumstances[employeeCount.sourcingCircumstance.id];
                    }

                    this.selectedPlaceInfo.employeeCounts.push({
                        pointInTime: pointInTime,
                        sourcingCircumstance: sourcingCircumstance,
                        value: employeeCount.value,
                    });
                }

                this.selectedPlaceInfo.employeeCounts.sort(this.sortByPointInTime);
            }

            this.selectedPlaceInfo.directors = [];

            if (place.directors) {
                for (const [statementId, director] of Object.entries(place.directors)) {
                    let startDate = null;

                    if (director.startTime) {
                        startDate = this.getDate(director.startTime.value, director.startTime.datePrecision);
                    }
                    else if (director.earliestDate) {
                        startDate = this.getDate(director.earliestDate.value, director.earliestDate.datePrecision);
                    }

                    let maxStartDate = director.latestStartDate ?
                        this.getDate(director.latestStartDate.value, director.latestStartDate.datePrecision) : null;

                    let endDate = null;

                    if (director.endTime) {
                        endDate = this.getDate(director.endTime.value, director.endTime.datePrecision);
                    }
                    else if (director.latestDate) {
                        endDate = this.getDate(director.latestDate.value, director.latestDate.datePrecision);
                    }

                    let minEndDate = director.earliestEndDate ?
                        this.getDate(director.earliestEndDate.value, director.earliestEndDate.datePrecision) : null;

                    this.selectedPlaceInfo.directors.push({
                        endDate: endDate,
                        maxStartDate: maxStartDate,
                        minEndDate: minEndDate,
                        name: director.value,
                        startDate: startDate,
                    });
                }

                this.selectedPlaceInfo.directors.sort(this.sortByDate);
            }

            this.selectedPlaceInfo.prisonerCounts = [];

            if (place.prisonerCounts) {
                for (const [statementId, prisonerCount] of Object.entries(place.prisonerCounts)) {
                    let sourcingCircumstance = '';
                    if (prisonerCount.sourcingCircumstance && prisonerCount.sourcingCircumstance.id in this.sourceCircumstances) {
                        sourcingCircumstance = this.sourceCircumstances[prisonerCount.sourcingCircumstance.id];
                    }

                    this.selectedPlaceInfo.prisonerCounts.push({
                        sourcingCircumstance: sourcingCircumstance,
                        value: prisonerCount.value,
                    });
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
         * Compare items by date, used by sort array items, for e.g. addresses.
         *
         * @param {object} itemA The first element for comparison.
         * @param {object} itemB The second element for comparison.
         *
         * @returns {number}     > 0 sort A after B
         *                       < 0  sort A before B
         *                       === 0 keep original order of A and B
         */
        sortByDate: function (itemA, itemB) {
            if (itemA.startDate && itemB.startDate) {
                return itemA.startDate.value.getTime() - itemB.startDate.value.getTime();
            }
            else if (itemA.endDate && itemB.endDate) {
                return itemA.endDate.value.getTime() - itemB.endDate.value.getTime();
            }
            else if (itemA.startDate && itemB.endDate) {
                return itemA.startDate.value.getTime() - itemB.endDate.value.getTime();
            }
            else if (itemA.endDate && itemB.startDate) {
                return itemA.endDate.value.getTime() - itemB.startDate.value.getTime();
            }
            else if (itemA.startDate || itemA.endDate) {
                // case: only item A has a date
                return -1;
            }
            else if (itemB.startDate || itemB.endDate) {
                // case: only item B has a date
                return 1;
            }
            else {
                return 0;
            }
        },
        /**
         * Compare items by a point in time, used by sort array items, for e.g. number of employees.
         *
         * @param {object} itemA The first element for comparison.
         * @param {object} itemB The second element for comparison.
         *
         * @returns {number}     > 0 sort A after B
         *                       < 0  sort A before B
         *                       === 0 keep original order of A and B
         */
        sortByPointInTime: function (itemA, itemB) {
            if (itemA.pointInTime && itemB.pointInTime) {
                return itemA.pointInTime.value.getTime() - itemB.pointInTime.value.getTime();
            }
            else if (itemA.pointInTime) {
                // case: only item A has a point in time
                return -1;
            }
            else if (itemB.pointInTime) {
                // case: only item B has a point in time
                return 1;
            }
            else {
                return 0;
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
         * Switch location on map.
         *
         * @param {string} locationId
         * @param {object} latLng
         */
        switchLocation: function ({locationId, latLng}) {
            this.map.flyTo(latLng);

            let locationMarker = this.locationMarkers[locationId][Object.values(latLng).join(',')];

            // workaround to bring a marker icon to foreground when multiple markers overlap on same coordinates
            locationMarker.remove();
            locationMarker.addTo(this.map);

            locationMarker.fire('click', {
                latlng: latLng,
            });
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
