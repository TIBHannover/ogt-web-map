<template>
    <div>
        <map-options-sidebar
            :groupedPlaces="groupedPlaces"
            :map="map"
            :mapMarkerIconsPath="mapMarkerIconsPath"
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
            derivedPlacesData: {
                // <locationId>: {
                //      commemoratedBy: [{
                //          id: '',
                //          name: '',
                //      }],
                //      employees: [{
                //          earliestDate: { locale: '', value: null, },
                //          earliestEndDate: { locale: '', value: null, },
                //          endTime: { locale: '', value: null, },
                //          id: '',
                //          latestDate: { locale: '', value: null, },
                //          latestStartDate: { locale: '', value: null, },
                //          startTime: { locale: '', value: null, },
                //          value: '',
                //      }],
                //      victims: [{
                //          hasPersonData: true,
                //          id: '',
                //          name: '',
                //      }],
                //      prisoners: [{
                //          id: '',
                //          name: '',
                //      }],
                // }
            },
            groupedPlaces: {
                events: {
                    color: '#8c1f21',
                    iconName: 'events.svg',
                    layerGroup: null,
                    layerName: 'Ereignisse',
                    places: [],
                    /* some places have multiple coordinates */
                    placesByCoordinates: [],
                },
                extPolicePrisons: {
                    color: '#3d3b3d',
                    iconName: 'extPolicePrisons.svg',
                    layerGroup: null,
                    layerName: 'Erweiterte Polizeigefängnisse',
                    places: [],
                    placesByCoordinates: [],
                },
                fieldOffices: {
                    color: '#456879',
                    iconName: 'fieldOffices.svg',
                    layerGroup: null,
                    layerName: 'Außendienststellen',
                    places: [],
                    placesByCoordinates: [],
                },
                laborEducationCamps: {
                    color: '#3d3b3d',
                    iconName: 'laborEducationCamps.svg',
                    layerGroup: null,
                    layerName: 'Arbeitserziehungslager',
                    places: [],
                    placesByCoordinates: [],
                },
                memorials: {
                    color: '#f3a923',
                    iconName: 'memorials.svg',
                    layerGroup: null,
                    layerName: 'Erinnerungsorte',
                    places: [],
                    placesByCoordinates: [],
                },
                prisons: {
                    color: '#3d3b3d',
                    iconName: 'prisons.svg',
                    layerGroup: null,
                    layerName: 'Gefängnisse',
                    places: [],
                    placesByCoordinates: [],
                },
                statePoliceHeadquarters: {
                    color: '#456879',
                    iconName: 'statePoliceHeadquarters.svg',
                    layerGroup: null,
                    layerName: 'Staatspolizeileitstellen',
                    places: [],
                    placesByCoordinates: [],
                },
                statePoliceOffices: {
                    color: '#456879',
                    iconName: 'statePoliceOffices.svg',
                    layerGroup: null,
                    layerName: 'Staatspolizeistellen',
                    places: [],
                    placesByCoordinates: [],
                },
            },
            layers: null,
            locationMarkers: [],
            map: null,
            mapMarkerIconsPath: '/images/leaflet/markerIcons/coloredFilledWhite/',
            persons: {
                perpetrators: {},
                victims: {},
            },
            selectedPlace: {
                addresses: {
                    additional: [{
                        endDate: {
                            locale: '',
                            value: null,
                        },
                        isUncertain: false,
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
                        isUncertain: false,
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
                    endDate: {
                        locale: '',
                        value: null,
                    },
                    hasLocationMarker: false,
                    id: '',
                    label: '',
                    startDate: {
                        locale: '',
                        value: null,
                    },
                }],
                citizenships: [],
                commemoratedBy: [{
                    hasLocationMarker: false,
                    id: '',
                    name: '',
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
                detentions: [{
                    endDate: {
                        locale: '',
                        value: null,
                    },
                    hasLocationMarker: false,
                    id: '',
                    name: '',
                    startDate: {
                        locale: '',
                        value: null,
                    },
                }],
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
                    endDate: {
                        locale: '',
                        value: null,
                    },
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
                employers: [{
                    endDate: {
                        locale: '',
                        value: null,
                    },
                    hasLocationMarker: false,
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
                endDate: {
                    locale: '',
                    value: null,
                },
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
                isDeportationEvent: false,
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
                    endDate: {
                        locale: '',
                        value: null,
                    },
                    hasLocationMarker: false,
                    id: '',
                    label: '',
                    startDate: {
                        locale: '',
                        value: null,
                    },
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
                    pointInTime: {
                        locale: '',
                        value: null,
                    },
                }],
                prisoners: [{
                    id: '',
                    name: '',
                }],
                prisonerCounts: [{
                    sourcingCircumstance: '',
                    value: 0,
                }],
                significantEvents: [{
                    hasLocationMarker: false,
                    id: '',
                    label: '',
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
                    pointInTime: {
                        locale: '',
                        value: null,
                    },
                }],
                targets: [],
                victims: [{
                    hasPersonData: false,
                    id: '',
                    name: '',
                }],
                website: '',
            },
            showPlaceInfoSidebar: false,
            sourceCircumstances: {
                Q5727902: 'ca.',
                Q47035128: '>',
                Q52834024: '<',
            },
        };
    },
    created() {
        this.WIKIDATA_ID_CLASSES = Object.freeze({
            deportation: [
                'Q379693',      // deportation - https://www.wikidata.org/wiki/Q379693
                'Q5883983',     // Holocaust train transports - https://www.wikidata.org/wiki/Q5883983
                                // - subclass of deportation
                'Q61927259',    // Holocaust train transport - https://www.wikidata.org/wiki/Q61927259
                                // - part of Holocaust train transports
            ],
        });

        this.getGroupedPlaces();
        this.getPersons();
    },
    mounted() {
        // Workaround so that the map is drawn with a 100% width instead of a gray area on the right side caused by
        // disabling the "app" property for the navbar on map view, so navbar behaves like a map overlay.
        let leafletMap = document.getElementById('leafletMapId');
        leafletMap.style.width = document.documentElement.clientWidth + 'px';

        this.setupLeafletMap();
        leafletMap.style.width = '100%';
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
            this.layers = L.control.layers(baseLayers);

            if (this.$ogtGlobals.isTestingEnv) {
                this.layers.addTo(this.map);
            }

            this.map.zoomControl.setPosition('bottomright');

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
            this.updateInfoSidebarData();
            this.checkUrlForLocation();
            
            const searchParams = new URLSearchParams(document.location.search);
            const searchID = searchParams.get("id");
            const searchGroup = searchParams.get("group");
            const searchLat = searchParams.get("lat");
            const searchLng = searchParams.get("lng");

            if(!searchID || !searchGroup || !searchLat || !searchLng) return;
            const place = ((this.groupedPlaces[searchGroup] || {}).places || {})[searchID];
            place && setTimeout(() => {
                this.setSelectedPlace(place, { lat: parseFloat(searchLat), lng: parseFloat(searchLng) }, searchGroup)
                this.toggleShowPlaceInfoSidebar(true);
            }, 500);
        },
        /**
         * Request persons data from cached file and Wikidata for perpetrators and victims.
         */
        getPersons() {
            this.axios.get('/api/wikidata/persons/cache').then(response => {
                this.persons = response.data;
                this.processPersonsData();
                this.checkUrlForPerson();
            }).catch(error => {
                console.log(error);
            });

            this.axios.get('/api/wikidata/persons').then(response => {
                this.persons = response.data;
                this.processPersonsData();
            }).catch(error => {
                console.log(error);
            });
        },
        /**
         * Process retrieved persons data to derive data for locations and to show/update person data in map info sidebar.
         */
        processPersonsData() {
            this.deriveLocationEmployees();
            this.deriveDataFromVictims();
            this.updateInfoSidebarData();
        },
        /**
         * Update locations/persons data displayed in opened info sidebar of the map.
         */
        updateInfoSidebarData() {
            if (! this.showPlaceInfoSidebar) {
                return;
            }

            const personGroups = Object.keys(this.persons);
            const locationGroups = Object.keys(this.groupedPlaces);

            if (personGroups.includes(this.selectedPlace.groupName)) {
                this.showPerson({
                    id: this.selectedPlace.id,
                    group: this.selectedPlace.groupName,
                });
            }
            else if (locationGroups.includes(this.selectedPlace.groupName)) {
                let location = this.groupedPlaces[this.selectedPlace.groupName]['places'][this.selectedPlace.id];
                this.setSelectedPlace(location, this.selectedPlace.latLng, this.selectedPlace.groupName);
            }
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
            let iconUrl = this.$ogtGlobals.proxyPath + this.mapMarkerIconsPath + this.groupedPlaces[placeGroupName].iconName;
            const defaultIcon = L.icon({
                className: '',
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
                        riseOnHover: true,
                        title: place.label,
                    });

                    marker.bindPopup(place.label, {
                        className: 'font-weight-bold',
                    });

                    marker.on('click', event => {
                        this.setSelectedPlace(place, event.latlng, placeGroupName);
                        this.toggleShowPlaceInfoSidebar(true);
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
                }
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

            window.history.replaceState(null, null, encodeURI(`${document.location.pathname}?id=${place.id}&group=${placeGroupName}&lat=${latLng.lat}&lng=${latLng.lng}`));

            this.selectedPlace.mainImageUrl = '';
            this.selectedPlace.mainImageLegend = '';

            const imageKeys = place.images ? Object.keys(place.images) : [];
            const imagesCounted = imageKeys.length;

            const coordinatesKeys = place.coordinates ? Object.keys(place.coordinates) : [];
            const coordinatesCounted = coordinatesKeys.length;

            if (imagesCounted == 1 && coordinatesCounted == 1) {
                // case: only one location image available and only one location coordinate, show this image
                this.selectedPlace.mainImageUrl = place.images[imageKeys[0]].value;
                this.selectedPlace.mainImageLegend =
                    place.images[imageKeys[0]].mediaLegend ? place.images[imageKeys[0]].mediaLegend.value : '';
            }
            else if (imagesCounted > 0) {
                // case: one or more images available for location, show image that belongs to coordinates, otherwise none
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

                // check if the coordinate is specified as uncertain (= inaccurate) in Wikidata
                // uncertainty - https://www.wikidata.org/wiki/Q13649246
                let isUncertain = false;
                if (coordinate.sourcingCircumstance && coordinate.sourcingCircumstance.id == 'Q13649246') {
                    isUncertain = true;
                }

                let address = {
                    endDate: endDate,
                    isUncertain: isUncertain,
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

            this.selectedPlace.directors = this.getPropertyData(place.directors, true, false, true);

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

            this.selectedPlace.isDeportationEvent = this.hasItemPropertyClass(place, 'instances', 'deportation');

            this.selectedPlace.significantEvents = [];

            if (place.significantEvents) {
                for (const [statementId, significantEvent] of Object.entries(place.significantEvents)) {
                    let hasLocationMarker = this.locationMarkers[significantEvent.id] ? true : false;

                    this.selectedPlace.significantEvents.push({
                        hasLocationMarker: hasLocationMarker,
                        id: significantEvent.id,
                        label: significantEvent.value,
                    });
                }
            }

            this.selectedPlace.parentOrganizations = [];

            if (place.parentOrganizations) {
                for (const [statementId, parentOrganization] of Object.entries(place.parentOrganizations)) {
                    let hasLocationMarker = this.locationMarkers[parentOrganization.id] ? true : false;

                    let startDate = parentOrganization.startTime ?
                        this.getDate(parentOrganization.startTime.value, parentOrganization.startTime.datePrecision) : null;

                    let endDate = parentOrganization.endTime ?
                        this.getDate(parentOrganization.endTime.value, parentOrganization.endTime.datePrecision) : null;

                    this.selectedPlace.parentOrganizations.push({
                        endDate: endDate,
                        hasLocationMarker: hasLocationMarker,
                        id: parentOrganization.id,
                        label: parentOrganization.value,
                        startDate: startDate,
                    });
                }

                this.selectedPlace.parentOrganizations.sort(this.sortByDate);
            }

            this.selectedPlace.childOrganizations = [];

            if (place.subsidiaries) {
                for (const [statementId, subsidiary] of Object.entries(place.subsidiaries)) {
                    let hasLocationMarker = this.locationMarkers[subsidiary.id] ? true : false;

                    let startDate = subsidiary.startTime ?
                        this.getDate(subsidiary.startTime.value, subsidiary.startTime.datePrecision) : null;

                    let endDate = subsidiary.endTime ?
                        this.getDate(subsidiary.endTime.value, subsidiary.endTime.datePrecision) : null;

                    this.selectedPlace.childOrganizations.push({
                        endDate: endDate,
                        hasLocationMarker: hasLocationMarker,
                        id: subsidiary.id,
                        label: subsidiary.value,
                        startDate: startDate,
                    });
                }

                this.selectedPlace.childOrganizations.sort(this.sortByDate);
            }

            this.selectedPlace.predecessors = [];

            if (place.replaces) {
                for (const [statementId, replace] of Object.entries(place.replaces)) {
                    let hasLocationMarker = this.locationMarkers[replace.id] ? true : false;

                    let pointInTime = replace.pointInTime ?
                        this.getDate(replace.pointInTime.value, replace.pointInTime.datePrecision) : null;

                    this.selectedPlace.predecessors.push({
                        hasLocationMarker: hasLocationMarker,
                        id: replace.id,
                        label: replace.value,
                        pointInTime: pointInTime,
                    });
                }

                this.selectedPlace.predecessors.sort(this.sortByPointInTime);
            }

            this.selectedPlace.successors = [];

            if (place.replacedBys) {
                for (const [statementId, replacedBy] of Object.entries(place.replacedBys)) {
                    let hasLocationMarker = this.locationMarkers[replacedBy.id] ? true : false;

                    let pointInTime = replacedBy.pointInTime ?
                        this.getDate(replacedBy.pointInTime.value, replacedBy.pointInTime.datePrecision) : null;

                    this.selectedPlace.successors.push({
                        hasLocationMarker: hasLocationMarker,
                        id: replacedBy.id,
                        label: replacedBy.value,
                        pointInTime: pointInTime,
                    });
                }

                this.selectedPlace.successors.sort(this.sortByPointInTime);
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
                    const value = (hasUse.value === "Ausbildung") ? "Bildungsarbeit" : hasUse.value;
                    this.selectedPlace.hasUses.push(value);
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

            this.selectedPlace.perpetrators = [];
            if (place.perpetrators) {
                for (const [statementId, perpetrator] of Object.entries(place.perpetrators)) {
                    let hasPersonData = this.persons.perpetrators[perpetrator.id] ? true : false;
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
            let gatheredVictimIds = [];
            if (place.victims) {
                for (const [statementId, victim] of Object.entries(place.victims)) {
                    gatheredVictimIds.push(victim.id);

                    let hasPersonData = this.persons.victims[victim.id] ? true : false;

                    this.selectedPlace.victims.push({
                        hasPersonData: hasPersonData,
                        id: victim.id,
                        name: victim.value,
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

            this.selectedPlace.commemoratedBy = [];
            this.selectedPlace.employees = [];
            this.selectedPlace.prisoners = [];

            let derivedPlacesData = this.derivedPlacesData[this.selectedPlace.id];

            if (! derivedPlacesData) {
                return;
            }

            if (derivedPlacesData.commemoratedBy) {
                this.selectedPlace.commemoratedBy = derivedPlacesData.commemoratedBy;

                for (const [statementId, commemoratedBy] of Object.entries(this.selectedPlace.commemoratedBy)) {
                    commemoratedBy.hasLocationMarker = this.locationMarkers[commemoratedBy.id] ? true : false;
                }
            }

            let directorIds = this.selectedPlace.directors.map(director => director.id);
            // only employees who are not directors of a location
            this.selectedPlace.employees = this.getPropertyData(derivedPlacesData.employees, true, false, false, directorIds);

            if (derivedPlacesData.prisoners) {
                // personal data is always available, there is no need to check if the person can be linked
                this.selectedPlace.prisoners = derivedPlacesData.prisoners;
            }

            if (derivedPlacesData.victims) {
                for (const [index, victim] of Object.entries(derivedPlacesData.victims)) {
                    if (gatheredVictimIds.includes(victim.id)) {
                        continue;
                    }

                    this.selectedPlace.victims.push(victim);
                }
            }
        },
        /**
         * Check, if one of the property values of an item matches a certain class of Wikidata ID's.
         *
         * @param item
         * @param propertyName
         * @param className
         * @returns {boolean}
         */
        hasItemPropertyClass: function (item, propertyName, className) {
            let hasItemPropertyClass = false;

            if (item[propertyName]) {
                const wikidataItemIds = this.WIKIDATA_ID_CLASSES[className];

                for (const [statementId, propertyValue] of Object.entries(item[propertyName])) {
                    if (wikidataItemIds.includes(propertyValue.id)) {
                        hasItemPropertyClass = true;
                        break;
                    }
                }
            }

            return hasItemPropertyClass;
        },
        /**
         * Get locale date string base on Wikidata time precision.
         *
         * @param {string} dateTimeString   Wikidata datetime
         * @param {int} datePrecision       9 => year precision, 10 => month precision, 11 => day precision
         * @param {string} locale           default 'de-de'
         *
         * @returns {{locale: string, value: Date}|{locale: string, value: null}}
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
                    return {
                        locale: '',
                        value: null,
                    };
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
                        name: memorial.label,
                    });
                }
            }
        },
        /**
         * Deriving location employees from perpetrator data to make them easily accessible for location data.
         */
        deriveLocationEmployees: function () {
            for (const [locationId, location] of Object.entries(this.derivedPlacesData)) {
                delete location.employees;
            }

            let perpetrators = this.persons.perpetrators;

            for (const [perpetratorId, perpetrator] of Object.entries(perpetrators)) {
                if (perpetrator.employers) {
                    for (const [statementId, employer] of Object.entries(perpetrator.employers)) {
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
                            earliestDate: employer.earliestDate,
                            earliestEndDate: employer.earliestEndDate,
                            endTime: employer.endTime,
                            id: perpetratorId,
                            latestDate: employer.latestDate,
                            latestStartDate: employer.latestStartDate,
                            startTime: employer.startTime,
                            value: perpetrator.label,
                        });
                    }
                }
            }
        },
        /**
         * Deriving location prisoners and event victims
         * from victim data to make them easily accessible for location/event data.
         */
        deriveDataFromVictims: function () {
            for (const [locationId, location] of Object.entries(this.derivedPlacesData)) {
                delete location.prisoners;
                delete location.victims;
            }

            let victims = this.persons.victims;

            for (const [victimId, victim] of Object.entries(victims)) {
                if (victim.detentionPlaces) {
                    for (const [statementId, detentionPlace] of Object.entries(victim.detentionPlaces)) {
                        if (! this.derivedPlacesData.hasOwnProperty(detentionPlace.id)) {
                            this.derivedPlacesData[detentionPlace.id] = {
                                prisoners: [],
                            };
                        }
                        else if (! this.derivedPlacesData[detentionPlace.id].hasOwnProperty('prisoners')) {
                            this.derivedPlacesData[detentionPlace.id]['prisoners'] = [];
                        }
                        else {
                            // done
                        }

                        this.derivedPlacesData[detentionPlace.id]['prisoners'].push({
                            id: victimId,
                            name: victim.label,
                        });
                    }
                }

                if (victim.significantEvents) {
                    for (const [statementId, significantEvent] of Object.entries(victim.significantEvents)) {
                        if (! this.derivedPlacesData.hasOwnProperty(significantEvent.id)) {
                            this.derivedPlacesData[significantEvent.id] = {
                                victims: [],
                            };
                        }
                        else if (! this.derivedPlacesData[significantEvent.id].hasOwnProperty('victims')) {
                            this.derivedPlacesData[significantEvent.id]['victims'] = [];
                        }
                        else {
                            // done
                        }

                        this.derivedPlacesData[significantEvent.id]['victims'].push({
                            hasPersonData: true,
                            id: victimId,
                            name: victim.label,
                        });
                    }
                }
            }
        },
        /**
         * Check for URL query parameters to display a specific location on map.
         */
        checkUrlForLocation: function () {
            if ('qId' in this.$route.query && this.$route.query.qId in this.locationMarkers) {
                this.switchLocation({locationId: this.$route.query.qId, zoom: 14});
                this.$router.push({path: '/map'});
            }
        },
        /**
         * Check for URL query parameters to display a specific person in map info sidebar.
         */
        checkUrlForPerson: function () {
            if ('qId' in this.$route.query) {
                let personGroup = '';

                if (this.$route.query.qId in this.persons.victims) {
                    personGroup = 'victims';
                }
                else if (this.$route.query.qId in this.persons.perpetrators) {
                    personGroup = 'perpetrators';
                }

                if (personGroup != '') {
                    this.showPerson({
                        id: this.$route.query.qId,
                        group: personGroup,
                    });

                    this.$router.push({path: '/map'});
                }
            }
        },
        /**
         * Switch location on map and location info.
         *
         * @param {string}              locationId
         * @param {object|null}         latLng      default null
         * @param {number|undefined}    zoom        zoom level, default undefined
         */
        switchLocation: function ({locationId, latLng = null, zoom = undefined}) {
            let locationMarkers = this.locationMarkers[locationId];

            if (locationMarkers == undefined) {
                // no markers for location found
                return;
            }

            let locationMarker = null;

            if (latLng) {
                locationMarker = locationMarkers[Object.values(latLng).join(',')];
            }
            else {
                let locationMarkerLatLng = Object.keys(locationMarkers)[0];
                locationMarker = this.locationMarkers[locationId][locationMarkerLatLng];
                latLng = locationMarker.getLatLng();
            }

            // workaround to bring a marker icon to foreground when multiple markers overlap on same coordinates
            locationMarker.remove();
            locationMarker.addTo(this.map);

            this.map.flyTo(latLng, zoom);

            locationMarker.fire('click', {
                latlng: latLng,
            });
        },
        /**
         * Show person data in map info sidebar.
         *
         * @param {string} id    Wikidata item id.
         * @param {string} group Group of person.
         */
        showPerson: function ({id, group = ''}) {
            this.toggleShowPlaceInfoSidebar(true);
            this.selectedPlace.groupName = group;

            if (! ['perpetrators', 'victims'].includes(this.selectedPlace.groupName)) {
                if (id in this.persons.victims) {
                    this.selectedPlace.groupName = 'victims';
                }
                else if (id in this.persons.perpetrators) {
                    this.selectedPlace.groupName = 'perpetrators';
                }
                else {
                    this.toggleShowPlaceInfoSidebar(false);
                    return;
                }
            }

            let person = this.persons[this.selectedPlace.groupName][id];

            if (this.selectedPlace.groupName == 'victims') {
                this.setSelectedVictimData(person);
            }
            else if (this.selectedPlace.groupName == 'perpetrators') {
                this.setSelectedPerpetratorData(person);
            }

            this.setSelectedPersonData(person);
        },
        /**
         * Set the perpetrator data of the selected person to be displayed in the map info sidebar.
         *
         * @param {object} person
         */
        setSelectedPerpetratorData: function (person) {
            this.selectedPlace.layerName = 'Täter*innen';

            this.selectedPlace.employers = this.getPropertyData(person.employers, true, true, false);
        },
        /**
         * Get item property data and optional include time periods, linked locations and linked persons.
         *
         * @param {object} itemProperty
         * @param {boolean} addTimePeriods
         * @param {boolean} addLinkedLocation
         * @param {boolean} addLinkedPerson
         * @param {array} excludedIds
         * @returns {*[]}
         */
        getPropertyData: function (itemProperty, addTimePeriods = false, addLinkedLocation = false, addLinkedPerson = false, excludedIds = []) {
            let propertyDataArray = [];

            if (! itemProperty) {
                return propertyDataArray;
            }

            for (const [statementId, propertyData] of Object.entries(itemProperty)) {
                if (excludedIds.includes(propertyData.id)) {
                    continue;
                }

                let data = {
                    id: propertyData.id,
                    name: propertyData.value,
                };

                if (addTimePeriods) {
                    data = {...data, ...this.getPropertyDataTimePeriods(propertyData)};
                }

                if (addLinkedLocation) {
                    data.hasLocationMarker = this.hasLocationMarker(propertyData.id);
                }

                if (addLinkedPerson) {
                    data.hasPersonData = this.hasPersonData(propertyData.id);
                }

                propertyDataArray.push(data);
            }

            propertyDataArray.sort(this.sortByDate);

            return propertyDataArray;
        },
        /**
         * Get time periods for item property data.
         *
         * @param {object} propertyData
         * @returns {{
         *              maxStartDate: (null|{locale: string, value: Date}|{locale: string, value: null}),
         *              endDate: (null|{locale: string, value: Date}|{locale: string, value: null}),
         *              minEndDate: (null|{locale: string, value: Date}|{locale: string, value: null}),
         *              startDate: (null|{locale: string, value: Date}|{locale: string, value: null}),
         *          }}
         */
        getPropertyDataTimePeriods: function (propertyData) {
            let startDate = null;

            if (propertyData.startTime) {
                startDate = this.getDate(propertyData.startTime.value, propertyData.startTime.datePrecision);
            }
            else if (propertyData.earliestDate) {
                startDate = this.getDate(propertyData.earliestDate.value, propertyData.earliestDate.datePrecision);
            }

            let maxStartDate = propertyData.latestStartDate ?
                this.getDate(propertyData.latestStartDate.value, propertyData.latestStartDate.datePrecision) : null;

            let endDate = null;

            if (propertyData.endTime) {
                endDate = this.getDate(propertyData.endTime.value, propertyData.endTime.datePrecision);
            }
            else if (propertyData.latestDate) {
                endDate = this.getDate(propertyData.latestDate.value, propertyData.latestDate.datePrecision);
            }

            let minEndDate = propertyData.earliestEndDate ?
                this.getDate(propertyData.earliestEndDate.value, propertyData.earliestEndDate.datePrecision) : null;

            return {
                endDate: endDate,
                maxStartDate: maxStartDate,
                minEndDate: minEndDate,
                startDate: startDate,
            };
        },
        /**
         * Check if the location has a map marker.
         *
         * @param {string} locationId A Wikidata item Q-Id.
         * @returns {boolean}
         */
        hasLocationMarker: function (locationId) {
            return this.locationMarkers[locationId] ? true : false;
        },
        /**
         * Check if person details are available.
         *
         * @param {string} personId A Wikidata item Q-Id.
         * @returns {boolean}
         */
        hasPersonData: function (personId) {
            return (this.persons.victims[personId] || this.persons.perpetrators[personId]) ? true : false;
        },
        /**
         * Set the victim data of the selected person to be displayed in the map info sidebar.
         *
         * @param {object} person
         */
        setSelectedVictimData: function (person) {
            this.selectedPlace.layerName = 'Geschädigte';

            this.selectedPlace.detentions = [];

            if (person.detentionPlaces) {
                for (const [statementId, detention] of Object.entries(person.detentionPlaces)) {
                    let hasLocationMarker = this.locationMarkers[detention.id] ? true : false;

                    let startDate = detention.startTime ?
                        this.getDate(detention.startTime.value, detention.startTime.datePrecision) : null;

                    let endDate = detention.endTime ?
                        this.getDate(detention.endTime.value, detention.endTime.datePrecision) : null;

                    this.selectedPlace.detentions.push({
                        endDate: endDate,
                        hasLocationMarker: hasLocationMarker,
                        id: detention.id,
                        name: detention.value,
                        startDate: startDate,
                    });
                }

                this.selectedPlace.detentions.sort(this.sortByDate);
            }
        },
        /**
         * Set the default data for the selected person to be displayed in the map info sidebar.
         *
         * @param {object} person
         */
        setSelectedPersonData: function (person) {
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

            this.selectedPlace.citizenships = [];
            if (person.citizenships) {
                for (const [statementId, citizenship] of Object.entries(person.citizenships)) {
                    this.selectedPlace.citizenships.push(citizenship.value);
                }
            }

            this.selectedPlace.sources = [];
            if (person.describedBySources) {
                for (const [statementId, describedBySource] of Object.entries(person.describedBySources)) {
                    this.selectedPlace.sources.push({
                        label: describedBySource.value,
                        pages: describedBySource.pages ? describedBySource.pages.value : '',
                    });
                }
            }

            this.selectedPlace.significantEvents = [];

            if (person.significantEvents) {
                for (const [statementId, significantEvent] of Object.entries(person.significantEvents)) {
                    let hasLocationMarker = this.locationMarkers[significantEvent.id] ? true : false;

                    this.selectedPlace.significantEvents.push({
                        hasLocationMarker: hasLocationMarker,
                        id: significantEvent.id,
                        label: significantEvent.value,
                    });
                }
            }
        },
        /**
         * Show/Hide place info sidebar and close opened navigation sidebar.
         *
         * @param bool status Show or hide place info sidebar.
         */
        toggleShowPlaceInfoSidebar: function (status) {
            this.showPlaceInfoSidebar = status;

            if (status) {
                document.getElementById('closeNavigationSidebar').click();
            }
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
/* top-right Leaflet control */
.leaflet-bottom.leaflet-right {
    display: flex;
    flex-direction: column;
    align-items: center;
    margin: auto;
    left: 0;
}
.leaflet-control {
    display: flex;
}
.leaflet-control a {
    border-radius: 2px !important;
    border-bottom: 1px solid #ccc !important;
}
.leaflet-control-attribution {
    display: block;
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
