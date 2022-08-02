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
            @switchAddress="switchAddress"
            @switchMarker="switchMarker($event)"
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
            markersByItemIdAndLatLng: [],
            prisonGroups: ['extPolicePrisons', 'laborEducationCamps', 'prisons'],
            selectedPlaceInfo: {
                additionalAddresses: [],
                administrativeUnits: [],
                description: '',
                directors: [],
                dissolvedDate: null,
                dissolvedLocaleDate: '',
                employeeCounts: [],
                inceptionDate: null,
                inceptionLocaleDate: '',
                label: '',
                // Leaflet LatLng geographical point object
                latLng: {
                    lat: 0,
                    lng: 0,
                },
                layerName: '',
                mainImageUrl: '',
                mainImageLegend: '',
                marker: null,
                parentOrganizations: [],
                prisonerCounts: [],
                replacedBys: [],
                replaces: [],
                selectedAddress: {
                    address: '',
                    coordinate: [],
                    isSelected: false,
                    startDate: null,
                    startDateLocale: '',
                    endDate: null,
                    endDateLocale: '',
                },
                significantEvents: [],
                sources: [{
                    label: '',
                    pages: '',
                    wikidataId: '',
                }],
                subsidiaries: [],
                //streetAddresses: [],
                wikidataItemUrl: '',
            },
            showPlaceInfoSidebar: false,
        };
    },
    created() {
        this.getGroupedPlaces2();
    },
    mounted() {
        this.setupLeafletMap();
    },
    methods: {
        switchMarker: function (itemId) {
            console.log("itemId", itemId);
            console.log("this.markersByItemIdAndLatLng[itemId]", this.markersByItemIdAndLatLng[itemId]);

            let coordinate = Object.keys(this.markersByItemIdAndLatLng[itemId])[0];
            console.log(coordinate);

            let marker = this.markersByItemIdAndLatLng[itemId][coordinate];
            console.log(marker);

            let latLngSplitted = coordinate.split(',');

            // workaround to bring a marker symbol to the foreground
            // when multiple markers overlap on the same coordinates.
            marker.remove();
            marker.addTo(this.map);

            this.map.flyTo(latLngSplitted);

            marker.fire('click', {
                latlng: {
                    lat: latLngSplitted[0],
                    lng: latLngSplitted[1],
                },
            });



            /*
            const iterator1 = this.markersByItemIdAndLatLng[itemId].entries();

            console.log(iterator1.next());
            console.log(iterator1.value);
            console.log(iterator1.key);

             */

            /*
            for (var latLng in this.markersByItemIdAndLatLng[itemId]) {
                // object[prop]

                console.log()

                break;
            }

            */

            //let latLng = Object.keys()[0];

            //console.log(this.markersByItemIdAndLatLng[itemId]);
            //console.log(this.markersByItemIdAndLatLng[itemId][0]);


            /*
            this.map.flyTo(coordinate);

            xxx.fire('click', {
                latlng: {
                    lat: coordinate[0],
                    lng: coordinate[1],
                },
            });
            */
        },
        switchAddress: function ({ coordinate, xxx }) {
            this.map.flyTo(coordinate);

            xxx.fire('click', {
                latlng: {
                    lat: coordinate[0],
                    lng: coordinate[1],
                },
            });

        },
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
        /**
         * Request grouped places of Gestapo terror.
         */
        async getGroupedPlaces2() {
            let groupedPlaces = {};

            await this.axios.get('/api/wikidata/locations').then(response => {
                groupedPlaces = response.data;
            }).catch(error => {
                console.log(error);
            });

            this.visualizePlaces2(groupedPlaces);
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
                let countedPlaceCoordinates = place.coordinates.length;


                //place['coordinates'] = [];

                place.markers = {};

                place.coordinates.forEach((coordinate, coordinateIndex) => {

                    this.markersByItemIdAndLatLng[placeId] = [];

                    let placeCoordinate = coordinate.value;
                    //place['coordinates'].push(placeCoordinate);

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
                        <br>
                        ${place.description}`;

                    marker.bindPopup(markerPopUpHtmlTemplate, {
                        minWidth: 333,
                    });


                    marker.on('click', event => {
                        //this.setSelectedPlaceInfo2(placeId, place, event.latlng, this.groupedPlaces[placeGroupName].layerName);
                        this.setSelectedPlaceInfo2(placeId, place, event.latlng, placeGroupName, marker);
                        this.toggleShowPlaceInfoSidebar(true);

                        const zoomInButton = marker.getPopup().getElement().getElementsByClassName('zoomInButton')[0];

                        let vm = this;

                        zoomInButton.onclick = function () {
                            vm.map.flyTo(event.latlng, 18);
                        };
                    });

                    let placeCoordinateString = placeCoordinate.toString();
                    place.markers[placeCoordinateString] = marker;
                    this.markersByItemIdAndLatLng[placeId][placeCoordinateString] = marker;

                    placeMarkers.push(marker);

                    let placeLabelWithIndex = place.label;

                    if (countedPlaceCoordinates > 1) {
                        placeLabelWithIndex += ' (' + (coordinateIndex + 1) + ')';
                    }

                    this.groupedPlaces[placeGroupName]['placesByCoordinates'].push({
                        placeLabelWithIndex: placeLabelWithIndex,
                        latLng: placeCoordinate,
                        marker: marker,
                    });
                });
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
            this.selectedPlaceInfo.description = place.itemDescription ? place.itemDescription.value : '';
            this.selectedPlaceInfo.imageUrl = place.imageUrl ? place.imageUrl.value : '';
            this.selectedPlaceInfo.label = place.itemLabel.value;
            this.selectedPlaceInfo.layerName = layerName;
            this.selectedPlaceInfo.wikidataItem = place.item.value;
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
        setSelectedPlaceInfo2: function (placeId, place, latLng, placeGroupName, marker) {
            this.selectedPlaceInfo.description = place.description;
            this.selectedPlaceInfo.label = place.label;
            this.selectedPlaceInfo.latLng = latLng;
            this.selectedPlaceInfo.layerName = this.groupedPlaces[placeGroupName].layerName;
            this.selectedPlaceInfo.wikidataItemUrl = 'https://www.wikidata.org/wiki/' + placeId;
            this.selectedPlaceInfo.mainImageUrl = '';
            this.selectedPlaceInfo.marker = marker;

            this.selectedPlaceInfo.markers = place.markers;

            const placeImagesCounted = place.images ? place.images.length : 0;
            this.selectedPlaceInfo.mainImageUrl = '';
            this.selectedPlaceInfo.mainImageLegend = '';

            if (placeImagesCounted == 1) {
                this.selectedPlaceInfo.mainImageUrl = place.images[0].value;
                this.selectedPlaceInfo.mainImageLegend = place.images[0].mediaLegend ? place.images[0].mediaLegend.value : '';
            }
            else if (placeImagesCounted > 1) {
                place.images.some((image, imageIndex) => {
                    if (image.coordinates && image.coordinates.value[0] == latLng.lat && image.coordinates.value[1] == latLng.lng) {
                        this.selectedPlaceInfo.mainImageUrl =  image.value;
                        this.selectedPlaceInfo.mainImageLegend = image.mediaLegend ? image.mediaLegend.value : '';
                        return true;
                    }
                });
            }

            this.selectedPlaceInfo.inceptionDate = null;
            this.selectedPlaceInfo.inceptionLocaleDate = '';
            if (place.inceptionDates) {
                this.selectedPlaceInfo.inceptionDate = new Date(place.inceptionDates[0].value);
                this.selectedPlaceInfo.inceptionLocaleDate = this.getLocaleDate2(
                    this.selectedPlaceInfo.inceptionDate,
                    place.inceptionDates[0].datePrecision
                );
            }

            this.selectedPlaceInfo.dissolvedDate = null;
            this.selectedPlaceInfo.dissolvedLocaleDate = '';
            if (place.dissolvedDates) {
                this.selectedPlaceInfo.dissolvedDate = new Date(place.dissolvedDates[0].value);
                this.selectedPlaceInfo.dissolvedLocaleDate = this.getLocaleDate2(
                    this.selectedPlaceInfo.dissolvedDate,
                    place.dissolvedDates[0].datePrecision
                );
            }

            this.selectedPlaceInfo.administrativeUnits = [];
            if (place.administrativeUnits && ! this.prisonGroups.includes(placeGroupName)) {
                place.administrativeUnits.forEach((administrativeUnit, administrativeUnitIndex) => {
                    this.selectedPlaceInfo.administrativeUnits.push(administrativeUnit.value);
                });
            }

            this.selectedPlaceInfo.selectedAddress = null;
            this.selectedPlaceInfo.additionalAddresses = [];
            if (place.streetAddresses) {
                place.streetAddresses.forEach((streetAddress) => {

                    let streetAddressCoordinate = [];

                    place.coordinates.some((coordinate, imageIndex) => {
                        if (coordinate.streetAddress && coordinate.streetAddress.value == streetAddress.value) {
                            streetAddressCoordinate =  coordinate.value;
                            return true;
                        } else if (place.coordinates.length == 1 && place.streetAddresses.length == 1) {
                            streetAddressCoordinate =  coordinate.value;
                            return true;
                        }
                    });

                    let addressIsSelected = false;
                    if (streetAddressCoordinate[0] == latLng.lat && streetAddressCoordinate[1] == latLng.lng) {
                        addressIsSelected = true;
                    }

                    let [startDate, startDateLocale] = streetAddress.startTime ?
                            this.getLocaleDate(streetAddress.startTime.value, streetAddress.startTime.datePrecision) : ['', ''];

                    let [endDate, endDateLocale] = streetAddress.endTime ?
                        this.getLocaleDate(streetAddress.endTime.value, streetAddress.endTime.datePrecision) : ['', ''];

                    let address = {
                        address: streetAddress.value,
                        coordinate: streetAddressCoordinate,
                        startDate: startDate,
                        startDateLocale: startDateLocale,
                        endDate: endDate,
                        endDateLocale: endDateLocale,
                    };

                    if (addressIsSelected) {
                        this.selectedPlaceInfo.selectedAddress = address;
                    }
                    else {
                        this.selectedPlaceInfo.additionalAddresses.push(address);
                    }
                });

                this.selectedPlaceInfo.additionalAddresses.sort((a, b) => {
                    if (a.startDate && b.startDate) {
                        return a.startDate.getTime() - b.startDate.getTime();
                    }
                    else if (a.endDate && b.endDate) {
                        return a.endDate.getTime() - b.endDate.getTime();
                    }
                    else {
                        return -1;
                    }
                });
            }


            this.selectedPlaceInfo.employeeCounts = [];
            if (place.employeeCounts) {
                place.employeeCounts.forEach((employeeCount) => {

                    let pointInTime = '';
                    let pointInLocaleTime = '';

                    if (employeeCount.pointInTime) {
                        pointInTime = new Date(employeeCount.pointInTime.value);
                        pointInLocaleTime = this.getLocaleDate2(pointInTime, employeeCount.pointInTime.datePrecision);
                    }

                    let sourcingCircumstance = employeeCount.sourcingCircumstances ? employeeCount.sourcingCircumstances.value : '';

                    let employeeCountData = {
                        counted: employeeCount.value,
                        pointInTime: pointInTime,
                        pointInLocaleTime: pointInLocaleTime,
                        sourcingCircumstance: sourcingCircumstance,
                    };

                    this.selectedPlaceInfo.employeeCounts.push(employeeCountData);
                });

                this.selectedPlaceInfo.employeeCounts.sort((a, b) => {
                    if (a.pointInTime && b.pointInTime) {
                        return a.pointInTime.getTime() - b.pointInTime.getTime();
                    }
                    else {
                        return -1;
                    }
                });
            }

            this.selectedPlaceInfo.directors = [];
            if (place.directors) {
                place.directors.forEach((director) => {

                    let earliestDate = '';
                    let earliestLocaleDate = '';

                    if (director.startTime) {
                        earliestDate = new Date(director.startTime.value);
                        earliestLocaleDate = this.getLocaleDate2(earliestDate, director.startTime.datePrecision);
                    } else if (director.earliestDate) {
                        earliestDate = new Date(director.earliestDate.value);
                        earliestLocaleDate = this.getLocaleDate2(earliestDate, director.earliestDate.datePrecision);
                    }

                    let latestStartDate = '';
                    let latestLocaleStartDate = '';

                    if (director.latestStartDate) {
                        latestStartDate = new Date(director.latestStartDate.value);
                        latestLocaleStartDate = this.getLocaleDate2(latestStartDate, director.latestStartDate.datePrecision);
                    }

                    let latestDate = '';
                    let latestLocaleDate = '';

                    if (director.endTime) {
                        latestDate = new Date(director.endTime.value);
                        latestLocaleDate = this.getLocaleDate2(latestDate, director.endTime.datePrecision);
                    } else if (director.latestDate) {
                        latestDate = new Date(director.latestDate.value);
                        latestLocaleDate = this.getLocaleDate2(latestDate, director.latestDate.datePrecision);
                    }

                    let earliestEndDate = '';
                    let earliestLocaleEndDate = '';

                    if (director.earliestEndDate) {
                        earliestEndDate = new Date(director.earliestEndDate.value);
                        earliestLocaleEndDate = this.getLocaleDate2(earliestEndDate, director.earliestEndDate.datePrecision);
                    }

                    let directorData = {
                        name: director.value,
                        earliestDate: [earliestDate, earliestLocaleDate],
                        latestStartDate: [latestStartDate, latestLocaleStartDate],
                        earliestEndDate: [earliestEndDate, earliestLocaleEndDate],
                        latestDate: [latestDate, latestLocaleDate],
                    };

                    this.selectedPlaceInfo.directors.push(directorData);
                });

                this.selectedPlaceInfo.directors.sort((a, b) => {
                    if (a.earliestDate[0] && b.earliestDate[0]) {
                        return a.earliestDate[0].getTime() - b.earliestDate[0].getTime();
                    }
                    else if (a.latestDate[0] && b.latestDate[0]) {
                        return a.latestDate[0].getTime() - b.latestDate[0].getTime();
                    }
                    else {
                        return -1;
                    }
                });
            }

            this.selectedPlaceInfo.prisonerCounts = [];
            if (place.prisonerCounts) {
                place.prisonerCounts.forEach((prisonerCount) => {
                    let sourcingCircumstance = '';

                    if (prisonerCount.sourcingCircumstances) {
                        console.log("prisonerCount.sourcingCircumstances.value", prisonerCount.sourcingCircumstances.value);
                        console.log("prisonerCount.sourcingCircumstances.id", prisonerCount.sourcingCircumstances.id);

                        if (prisonerCount.sourcingCircumstances.id == 'Q47035128') {
                            sourcingCircumstance = '>';
                        }
                    }

                    let prisonerCountData = {
                        counted: prisonerCount.value,
                        sourcingCircumstance: sourcingCircumstance,
                    };

                    this.selectedPlaceInfo.prisonerCounts.push(prisonerCountData);
                });
            }

            this.selectedPlaceInfo.significantEvents = [];
            if (place.significantEvents) {
                place.significantEvents.forEach((significantEvent) => {
                    this.selectedPlaceInfo.significantEvents.push({
                        id: significantEvent.id,
                        label: significantEvent.value,
                    });
                });
            }

            this.selectedPlaceInfo.parentOrganizations = [];
            if (place.parentOrganizations) {
                place.parentOrganizations.forEach((parentOrganization) => {
                    let hasMarker = false;

                    if (this.markersByItemIdAndLatLng[parentOrganization.id]){
                        hasMarker = true;
                    }

                    this.selectedPlaceInfo.parentOrganizations.push({
                        id: parentOrganization.id,
                        label: parentOrganization.value,
                        hasMarker: hasMarker,
                    });
                });
            }

            this.selectedPlaceInfo.subsidiaries = [];
            if (place.subsidiaries) {
                place.subsidiaries.forEach((subsidiary) => {
                    let hasMarker = false;

                    if (this.markersByItemIdAndLatLng[subsidiary.id]){
                        hasMarker = true;
                    }

                    this.selectedPlaceInfo.subsidiaries.push({
                        id: subsidiary.id,
                        label: subsidiary.value,
                        hasMarker: hasMarker,
                    });
                });
            }

            this.selectedPlaceInfo.replaces = [];
            if (place.replaces) {
                place.replaces.forEach((replace) => {
                    let hasMarker = false;

                    if (this.markersByItemIdAndLatLng[replace.id]){
                        console.log("FOUND marker for ", replace.id);
                        hasMarker = true;
                    } else {
                        console.log("N/A marker for ", replace.id);
                    }

                    this.selectedPlaceInfo.replaces.push({
                        id: replace.id,
                        label: replace.value,
                        hasMarker: hasMarker,
                    });
                });
            }

            this.selectedPlaceInfo.replacedBys = [];
            if (place.replacedBys) {
                place.replacedBys.forEach((replacedBy) => {
                    let hasMarker = false;

                    if (this.markersByItemIdAndLatLng[replacedBy.id]){
                        console.log("FOUND marker for ", replacedBy.id);
                        hasMarker = true;
                    } else {
                        console.log("N/A marker for ", replacedBy.id);
                    }

                    this.selectedPlaceInfo.replacedBys.push({
                        id: replacedBy.id,
                        label: replacedBy.value,
                        hasMarker: hasMarker,
                    });
                });
            }

            this.selectedPlaceInfo.sources = [];
            if (place.describedBySources) {
                place.describedBySources.forEach((describedBySource) => {
                    this.selectedPlaceInfo.sources.push({
                        label: describedBySource.value,
                        pages: describedBySource.pages ? describedBySource.pages.value : '',
                    });
                });
            }

            /*
            this.selectedPlaceInfo.officialWebsite = place.P856 ? place.P856.propertyStatements[0].propertyValue : '';
            this.selectedPlaceInfo.dateOfOfficialOpening = place.P1619 ? this.formatDate(place.P1619.propertyStatements[0].propertyValue) : '';
            this.selectedPlaceInfo.startDate = place.P580 ? this.formatDate(place.P580.propertyStatements[0].propertyValue) : '';

            this.selectedPlaceInfo.operators = [];
            if (place.P137) {
                place.P137.propertyStatements.forEach((statement, statementIndex) => {
                    this.selectedPlaceInfo.operators.push(statement.propertyValue);
                });
            }







            this.selectedPlaceInfo.perpetrators = [];
            if (place.P8031) {
                place.P8031.propertyStatements.forEach((statement, statementIndex) => {
                    this.selectedPlaceInfo.perpetrators.push(statement.propertyValue);
                });
            }

            this.selectedPlaceInfo.victims = [];
            if (place.P8032) {
                place.P8032.propertyStatements.forEach((statement, statementIndex) => {
                    this.selectedPlaceInfo.victims.push({
                        name: statement.propertyValue,
                        id: this.humans[statement.propertyValueId] ? statement.propertyValueId : null,
                    });
                });


                //place.P8032.propertyStatements.forEach((statement, statementIndex) => {
                //    this.selectedPlaceInfo.victims.push(statement.propertyValue);
                //});

            }

            this.selectedPlaceInfo.targets = [];
            if (place.P533) {
                place.P533.propertyStatements.forEach((statement, statementIndex) => {
                    this.selectedPlaceInfo.targets.push(statement.propertyValue);
                });
            }

            this.selectedPlaceInfo.hasUseData = [];
            if (place.P366) {
                place.P366.propertyStatements.forEach((statement, statementIndex) => {
                    this.selectedPlaceInfo.hasUseData.push(statement.propertyValue);
                });
            }







            this.selectedPlaceInfo.commemoratesData = [];
            if (place.P547) {
                place.P547.propertyStatements.forEach((statement, statementIndex) => {
                    this.selectedPlaceInfo.commemoratesData.push(statement.propertyValue);
                });
            }


            */
        },
        /**
         * Convert Wikidata datetime to date base on Wikidata time precision and transform date to locale .
         *
         * @param dateTimeString    e.g. 1942-11-22T00:00:00Z
         * @param datePrecision     9 => year precision, 10 => month precision, 11 => day precision
         * @param locale            default 'de-de'
         * @returns {string}
         */
        getLocaleDate: function (dateTimeString, datePrecision, locale = 'de-de') {

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
                    return '';
            }

            let date = new Date(dateTimeString);
            return [date, date.toLocaleDateString(locale, dateFormatOptions)];
        },
        /**
         * Convert Wikidata datetime to date base on Wikidata time precision and transform date to locale .
         *
         * @param dateTimeString    e.g. 1942-11-22T00:00:00Z
         * @param datePrecision     9 => year precision, 10 => month precision, 11 => day precision
         * @param locale            default 'de-de'
         * @returns {string}
         */
        getLocaleDate2: function (date, datePrecision, locale = 'de-de') {

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
                    return '';
            }

            return date.toLocaleDateString(locale, dateFormatOptions);
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
