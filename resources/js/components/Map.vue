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
                administrativeTerritorialEntitys: [],
                description: '',
                dissolvedDates: [],
                employeesData: [],
                imageUrl: '',
                inceptionDates: [],
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
                parentOrganizations: [],
                replaces: [],
                replacedBy: [],
                significantEvents: [],
                sources: [{
                    dnbUrl: '',
                    label: '',
                    wikidataUrl: '',
                }],
                subsidiarys: [],
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

            await this.axios.get('/api/wikidata/placesextended').then(response => {
                //console.log(response.data);
                groupedPlaces = response.data;
            }).catch(error => {
                console.log(error);
            });

            this.visualizePlaces2(groupedPlaces);

            /*
            await this.axios.get('/api/wikidata/places').then(response => {
                groupedPlaces = response.data;
            }).catch(error => {
                console.log(error);
            });

            this.visualizePlaces(groupedPlaces);
            */
        },
        visualizePlaces: function (groupedPlaces) {
            for (const [group, places] of Object.entries(groupedPlaces)) {
                this.groupedPlaces[group]['places'] = places;

                let placeMarkers = this.createPlaceMarkers(group, places);

                this.createPlacesLayerGroups(group, placeMarkers);
            }
        },
        visualizePlaces2: function (groupedPlaces) {
            for (const [group, places] of Object.entries(groupedPlaces)) {
                this.groupedPlaces[group]['places'] = places;

                let placeMarkers = this.createPlaceMarkers2(group, places);

                this.createPlacesLayerGroups2(group, placeMarkers);
            }

            //console.log(this.groupedPlaces);
            //console.log(this.groupedPlaces.statePoliceOffices.places);
            //console.log(this.groupedPlaces.statePoliceOffices.places['Q64768399']);
        },
        createPlaceMarkers2: function (placeGroupName, places) {
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
                if (place.P625) {
                    let countedPlaceCoordinates = place.P625.propertyStatements.length;
                    //console.log("place");
                    //console.log(place.P625.propertyStatements);
                    //console.log("countedPlaceCoordinates = " + countedPlaceCoordinates);

                    let instanceLabels = [];

                    place.P31.propertyStatements.forEach((statement, statementIndex) => {
                        instanceLabels.push(statement.propertyValue);
                    });

                    place['instanceLabels'] = instanceLabels;

                    place['coordinates'] = [];

                    //console.log("instanceLabels = " + instanceLabels);

                    place.P625.propertyStatements.forEach((statement, statementIndex) => {
                        //console.log("propertyStatements");
                        //console.log(placeCoordinate);
                        //console.log(placeCoordinateIndex);

                        let placeCoordinate = statement.propertyValue;
                        place['coordinates'].push(placeCoordinate);
                        //console.log("placeCoordinate = ", placeCoordinate);
                        //console.log("place label = " + place.label);

                        let marker = L.marker(placeCoordinate, {
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
                            <div class="popUpTopicCategory">
                                ${instanceLabels.join(', ')}
                            </div>
                            <br>
                            ${place.description}`;

                        marker.bindPopup(markerPopUpHtmlTemplate, {
                            minWidth: 333,
                        });

                        marker.on('click', event => {
                            this.setSelectedPlaceInfo2(place, event.latlng, this.groupedPlaces[placeGroupName].layerName);
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
                            placeLabelWithIndex += ' (' + (statementIndex + 1) + ')';
                        }

                        this.groupedPlaces[placeGroupName]['placesByCoordinates'].push({
                            placeLabelWithIndex: placeLabelWithIndex,
                            latLng: placeCoordinate,
                            marker: marker,
                        });
                    });
                }
            }


            /*
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
            */
            return placeMarkers;
        },
        createPlacesLayerGroups2: function (placeGroupName, placeMarkers) {
            //console.log(placeGroupName);
            //console.log(placeMarkers);
            let layerGroup = L.layerGroup(placeMarkers);
            layerGroup.addTo(this.map);
            this.layers.addOverlay(layerGroup, this.groupedPlaces[placeGroupName].layerName);
            this.groupedPlaces[placeGroupName].layerGroup = layerGroup;
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
        setSelectedPlaceInfo2: function (place, latLng, layerName) {
            this.selectedPlaceInfo.description = place.description;

            // workaround to set main images
            if (latLng.lat == '52.3667941' && latLng.lng == '9.744844924') {
                this.selectedPlaceInfo.imageUrl = 'http://commons.wikimedia.org/wiki/Special:FilePath/Stadtbibliothek%20Hannover%20au%C3%9Fen.jpg';
            } else if (latLng.lat == '52.3907961' && latLng.lng == '9.7532908') {
                this.selectedPlaceInfo.imageUrl = 'http://commons.wikimedia.org/wiki/Special:FilePath/R%C3%BChmkorffstra%C3%9Fe%2C%20Hannover.jpg';
            } else if (latLng.lat == '52.3664978' && latLng.lng == '9.7321152') {
                this.selectedPlaceInfo.imageUrl = 'http://commons.wikimedia.org/wiki/Special:FilePath/Polizeipr%C3%A4sidium%20Hardenbergstra%C3%9Fe.jpg';
            } else if (latLng.lat == '52.3669889' && latLng.lng == '9.731877') {
                this.selectedPlaceInfo.imageUrl = 'http://commons.wikimedia.org/wiki/Special:FilePath/Polizeipr%C3%A4sidium%20Hardenbergstra%C3%9Fe.jpg';
            } else if ((latLng.lat == '52.364983111' && latLng.lng == '9.748284833') || (latLng.lat == '52.364983096' && latLng.lng == '9.748562603')) {
                // dont show a picture here
                this.selectedPlaceInfo.imageUrl = '';
            } else if (latLng.lat == '52.377719' && latLng.lng == '9.672342') {
                this.selectedPlaceInfo.imageUrl = 'http://commons.wikimedia.org/wiki/Special:FilePath/Gedenkst%C3%A4tte%20Ahlem%20Hinrichtungsst%C3%A4tte.jpg';
            } else {
                this.selectedPlaceInfo.imageUrl = place.P18 ? place.P18.propertyStatements[0].propertyValue : '';
            }

            this.selectedPlaceInfo.instanceLabels = place.instanceLabels.join(', ');
            this.selectedPlaceInfo.label = place.label;
            this.selectedPlaceInfo.layerName = layerName;
            this.selectedPlaceInfo.wikidataItem = "https://www.wikidata.org/wiki/" + place.id;

            let altCoordinates = [];

            if (place.coordinates.length > 1) {
                altCoordinates = place.coordinates.filter(function (coordinate) {

                    //console.log(coordinate[0] + " =? " + latLng.lat);
                    //console.log(coordinate[1] + " =? " + latLng.lng);

                    if (coordinate[0] == latLng.lat && coordinate[1] == latLng.lng) {
                        return false;
                    }

                    return true;
                });

                //console.log(altCoordinates);
            }
            this.selectedPlaceInfo.latLngAlt = altCoordinates;
            this.selectedPlaceInfo.latLng = latLng;

            this.selectedPlaceInfo.parentOrganizations = [];
            if (place.P749) {
                place.P749.propertyStatements.forEach((statement, statementIndex) => {
                    this.selectedPlaceInfo.parentOrganizations.push(statement.propertyValue);
                });
            }

            this.selectedPlaceInfo.subsidiarys = [];
            if (place.P355) {
                place.P355.propertyStatements.forEach((statement, statementIndex) => {
                    this.selectedPlaceInfo.subsidiarys.push(statement.propertyValue);
                });
            }

            this.selectedPlaceInfo.replaces = [];
            if (place.P1365) {
                place.P1365.propertyStatements.forEach((statement, statementIndex) => {
                    this.selectedPlaceInfo.replaces.push(statement.propertyValue);
                });
            }

            this.selectedPlaceInfo.replacedBy = [];
            if (place.P1366) {
                place.P1366.propertyStatements.forEach((statement, statementIndex) => {
                    this.selectedPlaceInfo.replacedBy.push(statement.propertyValue);
                });
            }

            this.selectedPlaceInfo.significantEvents = [];
            if (place.P793) {
                place.P793.propertyStatements.forEach((statement, statementIndex) => {
                    this.selectedPlaceInfo.significantEvents.push(statement.propertyValue);
                });
            }

            this.selectedPlaceInfo.administrativeTerritorialEntitys = [];
            if (place.P131) {
                place.P131.propertyStatements.forEach((statement, statementIndex) => {
                    this.selectedPlaceInfo.administrativeTerritorialEntitys.push(statement.propertyValue);
                });
            }

            this.selectedPlaceInfo.employeesData = [];
            if (place.P1128) {
                place.P1128.propertyStatements.forEach((statement, statementIndex) => {
                    this.selectedPlaceInfo.employeesData.push({
                        numberOfEmployees: statement.propertyValue,
                        pointInTime: statement.P585 ? this.formatDate(statement.P585.qualifierValue, statement.P585.qualifierValueDatePrecision) : '',
                        sourcingCircumstances: statement.P1480 ? statement.P1480.qualifierValue : '',
                    });
                });
            }

            this.selectedPlaceInfo.inceptionDates = [];
            if (place.P571) {
                place.P571.propertyStatements.forEach((statement, statementIndex) => {
                    const inceptionDate = this.formatDate(statement.propertyValue, statement.propertyValueDatePrecision);
                    this.selectedPlaceInfo.inceptionDates.push(inceptionDate);
                });
            }

            this.selectedPlaceInfo.dissolvedDates = [];
            if (place.P576) {
                place.P576.propertyStatements.forEach((statement, statementIndex) => {
                    const dissolvedDate = this.formatDate(statement.propertyValue, statement.propertyValueDatePrecision);
                    this.selectedPlaceInfo.dissolvedDates.push(dissolvedDate);
                });
            }


            this.selectedPlaceInfo.sources = [];

            if (place.P1343) {
                place.P1343.propertyStatements.forEach((statement, statementIndex) => {
                    let source = {
                        dnbUrl: '',
                        label: '',
                        wikidataUrl: statement.propertyValueId ? "https://www.wikidata.org/wiki/" + statement.propertyValueId : '',
                    };

                    let title = statement.propertyValue + ', ';
                    let pages = statement.P304 ? 'S. ' + statement.P304.qualifierValue : '';

                    source.label = title + pages;

                    this.selectedPlaceInfo.sources.push(source);
                });




                /*
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
                */


            }

        },
        formatDate: function (dateTimeString, precision) {
            let dateFormatOptions = { year:"numeric", month:"long", day:"numeric"};

            if (precision == 9) {
                dateFormatOptions = { year:"numeric"};
            } else if (precision == 10) {
                dateFormatOptions = { year:"numeric", month:"long"};
            }

            return new Date(dateTimeString).toLocaleDateString('de-de', dateFormatOptions);
        },
        /**
         * Set selected place info.
         *
         * @param object place Wikidata place data
         * @param object latLng Leaflet LatLng geographical point object
         * @param string layerName Name of layer group
         */
        XXXsetSelectedPlaceInfo: function (place, latLng, layerName) {
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
