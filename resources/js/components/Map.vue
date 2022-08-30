<template>
    <div>
        <map-options-sidebar
            :groupedPlaces="groupedPlaces"
            :map="map"
        ></map-options-sidebar>

        <place-info-sidebar
            :selectedPlace="selectedPlace"
            :showPlaceInfoSidebar="showPlaceInfoSidebar"
            @hidePlaceInfoSidebar="toggleShowPlaceInfoSidebar(false)"
            @showPerson="showPerson($event)"
            @switchLocation="switchLocation"
            @undoZoomIntoPlace="restoreCachedMapView()"
            @zoomIntoPlace="setMapView(selectedPlace.latLng, 18, true)"
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
            derivedPlacesData: {},
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
            persons: [],
            selectedPlace: {
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
                childOrganizations: [{
                    hasLocationMarker: false,
                    id: '',
                    label: '',
                }],
                commemoratedBy: [{
                    hasLocationMarker: false,
                    id: '',
                    label: '',
                }],
                commemorates: [{
                    hasLocationMarker: false,
                    id: '',
                    label: '',
                }],
                dateOfBirth: {
                    locale: '',
                    value: null,
                },
                dateOfDeath: {
                    locale: '',
                    value: null,
                },
                description: '',
                destinationPoints: [],
                directors: [{
                    endDate: {
                        locale: '',
                        value: null,
                    },
                    hasPersonData: false,
                    id: '',
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
                employees: [{
                    id: '',
                    label: '',
                }],
                endDate: {
                    locale: '',
                    value: null,
                },
                events: [{
                    label: '',
                }],
                familyName: '',
                gender: '',
                givenName: '',
                groupName: '',
                hasUses: [],
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
                locations: [],
                mainImageUrl: '',
                mainImageLegend: '',
                numberOfArrests: [],
                numberOfCasualties: [],
                numberOfDeaths: [],
                numberOfSurvivors: [],
                openingDate: {
                    locale: '',
                    value: null,
                },
                operators: [],
                parentOrganizations: [{
                    hasLocationMarker: false,
                    id: '',
                    label: '',
                }],
                perpetrators: [{
                    hasLocationMarker: false,
                    hasPersonData: false,
                    id: '',
                    label: '',
                }],
                placeOfBirth: '',
                placeOfDeath: '',
                pointInTime: {
                    locale: '',
                    value: null,
                },
                predecessors: [{
                    hasLocationMarker: false,
                    id: '',
                    label: '',
                }],
                prisonerCounts: [{
                    sourcingCircumstance: '',
                    value: 0,
                }],
                significantPlaces: [],
                sources: [{
                    label: '',
                    pages: '',
                }],
                startDate: {
                    locale: '',
                    value: null,
                },
                startPoints: [],
                successors: [{
                    hasLocationMarker: false,
                    id: '',
                    label: '',
                }],
                targets: [],
                victims: [{
                    hasPersonData: false,
                    id: '',
                    label: '',
                }],
                website: '',
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
        this.getPersons();
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
            this.derivePlaceData();
        },
        /**
         * Request persons data, for e.g. perpetrators.
         */
        async getPersons() {
            await this.axios.get('/api/wikidata/persons').then(response => {
                this.persons = response.data;
            }).catch(error => {
                console.log(error);
            });

            this.deriveLocationEmployees();
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
                        this.setSelectedPlace(place, event.latlng, placeGroupName);
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
                        latLng: placeCoordinate.value,
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
         * @param string placeGroupName Name of place group
         */
        setSelectedPlace: function (place, latLng, placeGroupName) {
            this.selectedPlace.description = place.description;
            this.selectedPlace.label = place.label;
            this.selectedPlace.groupName = placeGroupName;
            this.selectedPlace.layerName = this.groupedPlaces[placeGroupName].layerName;
            this.selectedPlace.id = place.id;
            this.selectedPlace.latLng = latLng;

            this.selectedPlace.mainImageUrl = '';
            this.selectedPlace.mainImageLegend = '';

            const imageKeys = place.images ? Object.keys(place.images) : [];
            const imagesCounted = imageKeys.length;

            if (imagesCounted == 1) {
                // case: only one location image available, show this
                this.selectedPlace.mainImageUrl = place.images[imageKeys[0]].value;
                this.selectedPlace.mainImageLegend =
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
                        this.selectedPlace.mainImageUrl = image.value;
                        this.selectedPlace.mainImageLegend = image.mediaLegend ? image.mediaLegend.value : '';
                        return true;
                    }
                });
            }

            this.selectedPlace.inceptionDate = {
                locale: '',
                value: null,
            };

            if (place.inceptionDates) {
                for (const [statementId, inceptionDate] of Object.entries(place.inceptionDates)) {
                    this.selectedPlace.inceptionDate = this.getDate(inceptionDate.value, inceptionDate.datePrecision);
                    break;
                }
            }

            this.selectedPlace.dissolvedDate = {
                locale: '',
                value: null,
            };

            if (place.dissolvedDates) {
                for (const [statementId, dissolvedDate] of Object.entries(place.dissolvedDates)) {
                    this.selectedPlace.dissolvedDate = this.getDate(dissolvedDate.value, dissolvedDate.datePrecision);
                    break;
                }
            }

            this.selectedPlace.addresses = {
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
                    this.selectedPlace.addresses.selected = address;
                }
                else {
                    this.selectedPlace.addresses.additional.push(address);
                }
            }

            // merge street addresses and related additional information with location address data
            if (place.streetAddresses) {
                let selectedAddress = this.selectedPlace.addresses.selected;
                let additionalAddresses = this.selectedPlace.addresses.additional;

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

            this.selectedPlace.addresses.additional.sort(this.sortByDate);

            this.selectedPlace.employeeCounts = [];

            if (place.employeeCounts) {
                for (const [statementId, employeeCount] of Object.entries(place.employeeCounts)) {
                    let pointInTime = employeeCount.pointInTime ?
                        this.getDate(employeeCount.pointInTime.value, employeeCount.pointInTime.datePrecision) : null;

                    let sourcingCircumstance = '';
                    if (employeeCount.sourcingCircumstance && employeeCount.sourcingCircumstance.id in this.sourceCircumstances) {
                        sourcingCircumstance = this.sourceCircumstances[employeeCount.sourcingCircumstance.id];
                    }

                    this.selectedPlace.employeeCounts.push({
                        pointInTime: pointInTime,
                        sourcingCircumstance: sourcingCircumstance,
                        value: employeeCount.value,
                    });
                }

                this.selectedPlace.employeeCounts.sort(this.sortByPointInTime);
            }

            this.selectedPlace.directors = [];

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

                    let hasPersonData = this.persons[director.id] ? true : false;

                    this.selectedPlace.directors.push({
                        endDate: endDate,
                        hasPersonData: hasPersonData,
                        id: director.id,
                        maxStartDate: maxStartDate,
                        minEndDate: minEndDate,
                        name: director.value,
                        startDate: startDate,
                    });
                }

                this.selectedPlace.directors.sort(this.sortByDate);
            }

            this.selectedPlace.prisonerCounts = [];

            if (place.prisonerCounts) {
                for (const [statementId, prisonerCount] of Object.entries(place.prisonerCounts)) {
                    let sourcingCircumstance = '';
                    if (prisonerCount.sourcingCircumstance && prisonerCount.sourcingCircumstance.id in this.sourceCircumstances) {
                        sourcingCircumstance = this.sourceCircumstances[prisonerCount.sourcingCircumstance.id];
                    }

                    this.selectedPlace.prisonerCounts.push({
                        sourcingCircumstance: sourcingCircumstance,
                        value: prisonerCount.value,
                    });
                }
            }

            this.selectedPlace.events = [];

            if (place.significantEvents) {
                for (const [statementId, significantEvent] of Object.entries(place.significantEvents)) {
                    this.selectedPlace.events.push({
                        label: significantEvent.value,
                    });
                }
            }

            this.selectedPlace.parentOrganizations = [];

            if (place.parentOrganizations) {
                for (const [statementId, parentOrganization] of Object.entries(place.parentOrganizations)) {
                    let hasLocationMarker = this.locationMarkers[parentOrganization.id] ? true : false;

                    this.selectedPlace.parentOrganizations.push({
                        hasLocationMarker: hasLocationMarker,
                        id: parentOrganization.id,
                        label: parentOrganization.value,
                    });
                }
            }

            this.selectedPlace.childOrganizations = [];

            if (place.subsidiaries) {
                for (const [statementId, subsidiary] of Object.entries(place.subsidiaries)) {
                    let hasLocationMarker = this.locationMarkers[subsidiary.id] ? true : false;

                    this.selectedPlace.childOrganizations.push({
                        hasLocationMarker: hasLocationMarker,
                        id: subsidiary.id,
                        label: subsidiary.value,
                    });
                }
            }

            this.selectedPlace.predecessors = [];

            if (place.replaces) {
                for (const [statementId, replace] of Object.entries(place.replaces)) {
                    let hasLocationMarker = this.locationMarkers[replace.id] ? true : false;

                    this.selectedPlace.predecessors.push({
                        hasLocationMarker: hasLocationMarker,
                        id: replace.id,
                        label: replace.value,
                    });
                }
            }

            this.selectedPlace.successors = [];

            if (place.replacedBys) {
                for (const [statementId, replacedBy] of Object.entries(place.replacedBys)) {
                    let hasLocationMarker = this.locationMarkers[replacedBy.id] ? true : false;

                    this.selectedPlace.successors.push({
                        hasLocationMarker: hasLocationMarker,
                        id: replacedBy.id,
                        label: replacedBy.value,
                    });
                }
            }

            this.selectedPlace.sources = [];
            if (place.describedBySources) {
                for (const [statementId, describedBySource] of Object.entries(place.describedBySources)) {
                    this.selectedPlace.sources.push({
                        label: describedBySource.value,
                        pages: describedBySource.pages ? describedBySource.pages.value : '',
                    });
                }
            }

            this.selectedPlace.website = '';
            if (place.officialWebsite) {
                for (const [statementId, website] of Object.entries(place.officialWebsite)) {
                    this.selectedPlace.website = website.value;
                    break;
                }
            }

            this.selectedPlace.operators = [];
            if (place.operators) {
                for (const [statementId, operator] of Object.entries(place.operators)) {
                    this.selectedPlace.operators.push(operator.value);
                }
            }

            this.selectedPlace.hasUses = [];
            if (place.hasUses) {
                for (const [statementId, hasUse] of Object.entries(place.hasUses)) {
                    this.selectedPlace.hasUses.push(hasUse.value);
                }
            }

            this.selectedPlace.startDate = {
                locale: '',
                value: null,
            };

            if (place.startTime) {
                for (const [statementId, startDate] of Object.entries(place.startTime)) {
                    this.selectedPlace.startDate = this.getDate(startDate.value, startDate.datePrecision);
                    break;
                }
            }

            this.selectedPlace.openingDate = {
                locale: '',
                value: null,
            };

            if (place.openingDate) {
                for (const [statementId, openingDate] of Object.entries(place.openingDate)) {
                    this.selectedPlace.openingDate = this.getDate(openingDate.value, openingDate.datePrecision);
                    break;
                }
            }

            this.selectedPlace.endDate = {
                locale: '',
                value: null,
            };

            if (place.endTime) {
                for (const [statementId, endTime] of Object.entries(place.endTime)) {
                    this.selectedPlace.endDate = this.getDate(endTime.value, endTime.datePrecision);
                    break;
                }
            }

            this.selectedPlace.pointInTime = {
                locale: '',
                value: null,
            };

            if (place.pointInTime) {
                for (const [statementId, pointInTime] of Object.entries(place.pointInTime)) {
                    this.selectedPlace.pointInTime = this.getDate(pointInTime.value, pointInTime.datePrecision);
                    break;
                }
            }

            this.selectedPlace.commemorates = [];
            if (place.commemorates) {
                for (const [statementId, commemorate] of Object.entries(place.commemorates)) {
                    let hasLocationMarker = this.locationMarkers[commemorate.id] ? true : false;

                    this.selectedPlace.commemorates.push({
                        hasLocationMarker: hasLocationMarker,
                        id: commemorate.id,
                        label: commemorate.value,
                    });
                }
            }

            this.selectedPlace.commemoratedBy = [];
            if (this.derivedPlacesData[this.selectedPlace.id] && this.derivedPlacesData[this.selectedPlace.id]['commemoratedBy']) {
                this.selectedPlace.commemoratedBy = this.derivedPlacesData[this.selectedPlace.id]['commemoratedBy'];

                for (const [statementId, commemoratedBy] of Object.entries(this.selectedPlace.commemoratedBy)) {
                    commemoratedBy.hasLocationMarker = this.locationMarkers[commemoratedBy.id] ? true : false;
                }
            }

            this.selectedPlace.perpetrators = [];
            if (place.perpetrators) {
                for (const [statementId, perpetrator] of Object.entries(place.perpetrators)) {
                    let hasPersonData = this.persons[perpetrator.id] ? true : false;
                    let hasLocationMarker = this.locationMarkers[perpetrator.id] ? true : false;

                    this.selectedPlace.perpetrators.push({
                        hasLocationMarker: hasLocationMarker,
                        hasPersonData: hasPersonData,
                        id: perpetrator.id,
                        label: perpetrator.value,
                    });
                }
            }

            this.selectedPlace.victims = [];
            if (place.victims) {
                for (const [statementId, victim] of Object.entries(place.victims)) {
                    let hasPersonData = this.persons[victim.id] ? true : false;

                    this.selectedPlace.victims.push({
                        hasPersonData: hasPersonData,
                        id: victim.id,
                        label: victim.value,
                    });
                }
            }

            this.selectedPlace.targets = [];
            if (place.targets) {
                for (const [statementId, target] of Object.entries(place.targets)) {
                    this.selectedPlace.targets.push(target.value);
                }
            }

            this.selectedPlace.numberOfCasualties = [];
            if (place.numberOfCasualties) {
                for (const [statementId, numberOfCasualties] of Object.entries(place.numberOfCasualties)) {
                    this.selectedPlace.numberOfCasualties.push(numberOfCasualties.value);
                }
            }

            this.selectedPlace.numberOfDeaths = [];
            if (place.numberOfDeaths) {
                for (const [statementId, numberOfDeaths] of Object.entries(place.numberOfDeaths)) {
                    this.selectedPlace.numberOfDeaths.push(numberOfDeaths.value);
                }
            }

            this.selectedPlace.numberOfSurvivors = [];
            if (place.numberOfSurvivors) {
                for (const [statementId, numberOfSurvivors] of Object.entries(place.numberOfSurvivors)) {
                    this.selectedPlace.numberOfSurvivors.push(numberOfSurvivors.value);
                }
            }

            this.selectedPlace.numberOfArrests = [];
            if (place.numberOfArrests) {
                for (const [statementId, numberOfArrests] of Object.entries(place.numberOfArrests)) {
                    this.selectedPlace.numberOfArrests.push(numberOfArrests.value);
                }
            }

            this.selectedPlace.locations = [];
            if (place.locations) {
                for (const [statementId, location] of Object.entries(place.locations)) {
                    this.selectedPlace.locations.push(location.value);
                }
            }

            this.selectedPlace.significantPlaces = [];
            if (place.significantPlaces) {
                for (const [statementId, significantPlace] of Object.entries(place.significantPlaces)) {
                    this.selectedPlace.significantPlaces.push(significantPlace.value);
                }
            }

            this.selectedPlace.startPoints = [];
            if (place.startPoints) {
                for (const [statementId, startPoint] of Object.entries(place.startPoints)) {
                    this.selectedPlace.startPoints.push(startPoint.value);
                }
            }

            this.selectedPlace.destinationPoints = [];
            if (place.destinationPoints) {
                for (const [statementId, destinationPoint] of Object.entries(place.destinationPoints)) {
                    this.selectedPlace.destinationPoints.push(destinationPoint.value);
                }
            }

            this.selectedPlace.employees = [];
            if (this.derivedPlacesData[this.selectedPlace.id] && this.derivedPlacesData[this.selectedPlace.id]['employees']) {
                this.selectedPlace.employees = this.derivedPlacesData[this.selectedPlace.id]['employees'];
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
         * Derive location data to make it easily accessible to other locations,
         * e.g. to know if a location is commemorated by a memorial.
         */
        derivePlaceData: function () {
            let memorials = this.groupedPlaces['memorials'].places;

            for (const [memorialId, memorial] of Object.entries(memorials)) {
                for (const [statementId, commemorate] of Object.entries(memorial.commemorates)) {

                    if (! this.derivedPlacesData.hasOwnProperty(commemorate.id)) {
                        this.derivedPlacesData[commemorate.id] = {
                            commemoratedBy: [],
                        };
                    }
                    else if (! this.derivedPlacesData[commemorate.id].hasOwnProperty('commemoratedBy')) {
                        this.derivedPlacesData[commemorate.id]['commemoratedBy'] = [];
                    }
                    else {
                        // done
                    }

                    this.derivedPlacesData[commemorate.id]['commemoratedBy'].push({
                        id: memorialId,
                        label: memorial.label,
                    });
                }
            }
        },
        /**
         * Deriving location employees from personal data to make them easily accessible for location data.
         */
        deriveLocationEmployees: function () {
            let persons = this.persons;

            for (const [personId, person] of Object.entries(persons)) {
                if (person.employers) {
                    for (const [statementId, employer] of Object.entries(person.employers)) {
                        if (! this.derivedPlacesData.hasOwnProperty(employer.id)) {
                            this.derivedPlacesData[employer.id] = {
                                employees: [],
                            };
                        }
                        else if (! this.derivedPlacesData[employer.id].hasOwnProperty('employees')) {
                            this.derivedPlacesData[employer.id]['employees'] = [];
                        }
                        else {
                            // done
                        }

                        this.derivedPlacesData[employer.id]['employees'].push({
                            id: personId,
                            label: person.label,
                        });
                    }
                }
            }
        },
        /**
         * Switch location on map and location info.
         *
         * @param {string}      locationId
         * @param {object|null} latLng      default null
         */
        switchLocation: function ({locationId, latLng = null}) {
            let locationMarker = null;

            if (latLng) {
                locationMarker = this.locationMarkers[locationId][Object.values(latLng).join(',')];
            }
            else {
                let locationMarkerLatLng = Object.keys(this.locationMarkers[locationId])[0];
                locationMarker = this.locationMarkers[locationId][locationMarkerLatLng];
                latLng = locationMarker.getLatLng();
            }

            // workaround to bring a marker icon to foreground when multiple markers overlap on same coordinates
            locationMarker.remove();
            locationMarker.addTo(this.map);

            this.map.flyTo(latLng);

            locationMarker.fire('click', {
                latlng: latLng,
            });
        },
        /**
         * Show person data in info sidebar.
         *
         * @param personId
         */
        showPerson: function (personId) {
            let person = this.persons[personId];

            this.selectedPlace.groupName = 'perpetrators';
            this.selectedPlace.layerName = 'Täter*innen';
            this.selectedPlace.id = person.id;
            this.selectedPlace.label = person.label;
            this.selectedPlace.description = person.description;

            this.selectedPlace.mainImageUrl = '';
            this.selectedPlace.mainImageLegend = '';

            if (person.images) {
                for (const [statementId, image] of Object.entries(person.images)) {
                    this.selectedPlace.mainImageUrl = image.value;
                    this.selectedPlace.mainImageLegend = image.mediaLegend ? image.mediaLegend.value : '';
                    break;
                }
            }

            this.selectedPlace.givenName = '';
            if (person.givenName) {
                for (const [statementId, givenName] of Object.entries(person.givenName)) {
                    this.selectedPlace.givenName = givenName.value;
                    break;
                }
            }

            this.selectedPlace.familyName = '';
            if (person.familyName) {
                for (const [statementId, familyName] of Object.entries(person.familyName)) {
                    this.selectedPlace.familyName = familyName.value;
                    break;
                }
            }

            this.selectedPlace.gender = '';
            if (person.gender) {
                for (const [statementId, gender] of Object.entries(person.gender)) {
                    this.selectedPlace.gender = gender.value;
                    break;
                }
            }

            this.selectedPlace.placeOfBirth = '';
            if (person.placeOfBirth) {
                for (const [statementId, placeOfBirth] of Object.entries(person.placeOfBirth)) {
                    this.selectedPlace.placeOfBirth = placeOfBirth.value;
                    break;
                }
            }

            this.selectedPlace.placeOfDeath = '';
            if (person.placeOfDeath) {
                for (const [statementId, placeOfDeath] of Object.entries(person.placeOfDeath)) {
                    this.selectedPlace.placeOfDeath = placeOfDeath.value;
                    break;
                }
            }

            this.selectedPlace.dateOfBirth = {
                locale: '',
                value: null,
            };

            if (person.dateOfBirth) {
                for (const [statementId, dateOfBirth] of Object.entries(person.dateOfBirth)) {
                    this.selectedPlace.dateOfBirth = this.getDate(dateOfBirth.value, dateOfBirth.datePrecision);
                    break;
                }
            }

            this.selectedPlace.dateOfDeath = {
                locale: '',
                value: null,
            };

            if (person.dateOfDeath) {
                for (const [statementId, dateOfDeath] of Object.entries(person.dateOfDeath)) {
                    this.selectedPlace.dateOfDeath = this.getDate(dateOfDeath.value, dateOfDeath.datePrecision);
                    break;
                }
            }
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
