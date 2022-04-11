<template>
    <v-container class="blue-grey lighten-2 justify-center" fill-height fluid>

        <!-- content, if no slides displayed -->
        <div v-show="!showSlides" class="text-center">
            <p class="text-h4 text-md-h1">Projektbeschreibung</p>
            <v-btn @click.stop="showSlides = !showSlides" rounded small>Folien anzeigen</v-btn>
        </div>

        <!-- slides -->
        <v-carousel v-show="showSlides" height="100%">
            <!-- slides close button -->
            <v-btn @click.stop="showSlides = !showSlides" absolute class="z-index-1" color="black" icon right top>
                <v-icon>mdi-close</v-icon>
            </v-btn>

            <!-- slides structure -->
            <v-carousel-item v-for="(slide, index) in slides" :key="index">
                <v-sheet color="blue-grey lighten-2" height="100%" tile>
                    <v-row align="center" class="fill-height" justify="center">
                        <v-col v-if="slide.image" class="px-70">
                            <v-img class="mx-auto" max-width="1150" :src="slide.image"></v-img>
                        </v-col>
                        <div v-if="slide.header || slide.listItems" class="text-center">
                            <p v-if="slide.header" class="text-h4 text-md-h1 mb-10 px-70"> {{ slide.header }} </p>
                            <ul v-if="slide.listItems" class="text-md-h4 text-left mx-100">
                                <template v-for="item in slide.listItems">
                                    <li class="mt-5"> {{ item.text }} </li>
                                    <ul v-if="item.listItems2ndLevel" class="text-body-2 text-md-h5 text-left">
                                        <template v-for="subItem in item.listItems2ndLevel">
                                            <li> {{ subItem.text }} </li>
                                            <ul v-if="subItem.listItems3thLevel" class="text-caption text-md-h6 text-left">
                                                <li v-for="subItemSubText in subItem.listItems3thLevel">
                                                    {{ subItemSubText }}
                                                </li>
                                            </ul>
                                        </template>
                                    </ul>
                                </template>
                            </ul>
                        </div>
                    </v-row>
                </v-sheet>
            </v-carousel-item>
        </v-carousel>
    </v-container>
</template>

<script>
export default {
    name: 'Project',
    data() {
        return {
            showSlides: false,
            slides: [
                /* supported slides content format
                {
                    header: 'text',
                    image: 'image path',
                    listItems: [
                        {
                            text: 'on top level',
                            listItems2ndLevel: [
                                {
                                    text: 'on 2nd level',
                                    listItems3thLevel: [
                                        'text on 3th level',
                                    ],
                                },
                            ],
                        },
                    ],
                },
                */
                {
                    image: this.$ogtGlobals.proxyPath + '/images/en/banner.jpg',
                },
                {
                    header: 'OGT project',
                    listItems: [
                        {
                            text: 'Places of Gestapo Terror in Lower Saxony 1933–1945',
                        },
                        {
                            text: 'Project Nov 2020–Oct 2022',
                        },
                        {
                            text: 'Supervisors: Lambert & Ina',
                        },
                        {
                            text: 'Contributors: Janine, Lucia, Nadine, Tobias, Nils, ...',
                        },
                        {
                            text: 'Cooperation with the Lower Saxony Memorials Foundation and with memorials',
                        },
                        {
                            text: 'Funding: Lower Saxony Ministry for Science and Culture',
                        },
                    ],
                },
                {
                    header: 'Objectives and motivation',
                    listItems: [
                        {
                            text: 'Research activity of Gestapo with regard to its locations and infrastructure',
                        },
                        {
                            text: 'Make existing knowledge about the crimes of the Gestapo and the later memory of them digitally accessible',
                            listItems2ndLevel: [
                                {
                                    text: 'Store & evaluate archive-, literature- and other source data using Wikidata & Wikibase instance',
                                },
                                {
                                    text: 'Visualize data on an interactive web-map and charts',
                                },
                                {
                                    text: 'Public resource, which can be used and extended',
                                },
                            ],
                        },
                    ],
                },
                {
                    header: 'Some first steps ...',
                    listItems: [
                        {
                            text: 'Literature research of Gestapo data & data modeling',
                            listItems2ndLevel: [
                                {
                                    text: 'Excel -> OpenRefine -> Wikidata import',
                                },
                            ],
                        },
                        {
                            text: 'Requirements analysis (4-step MoSCoW prioritization)',
                        },
                        {
                            text: 'Setup development process & workflow',
                            listItems2ndLevel: [
                                {
                                    text: 'Jira as task and project tracking software',
                                },
                                {
                                    text: 'Few parts of Scrum as process model for agile software development',
                                },
                                {
                                    text: 'Base on Vincent Driessen\'s Git branching model (git & git-flow)',
                                },
                                {
                                    text: 'Semantic versioning for releases',
                                },
                            ],
                        },
                        {
                            text: 'Simple JS/HTML prototype',
                            listItems2ndLevel: [
                                {
                                    text: 'Code-sandbox to test ...',
                                    listItems3thLevel: [
                                        'Wikidata-API / SPARQL',
                                        'Web-map-APIs & features & plugins',
                                    ],
                                },
                            ],
                        },
                    ],
                },
                {
                    header: 'Now ...',
                    listItems: [
                        {
                            text: 'Minimum Viable Product',
                            listItems2ndLevel: [
                                {
                                    text: 'Quick user feedback',
                                },
                                {
                                    text: 'Prevent undesirable developments of the requirements',
                                },
                            ],
                        },
                        {
                            text: 'Software stack',
                            listItems2ndLevel: [
                                {
                                    text: 'Linux',
                                },
                                {
                                    text: 'Apache',
                                },
                                {
                                    text: 'Wikibase',
                                },
                                {
                                    text: 'Laravel',
                                },
                                {
                                    text: 'Vue.js + Vue Router + Vuetify',
                                },
                                {
                                    text: 'Leaflet',
                                },
                            ],
                        },
                    ],
                },
                {
                    header: 'a first Website preview',
                },
                {
                    header: 'Website features',
                    listItems: [
                        {
                            text: 'Default content/features',
                            listItems2ndLevel: [
                                {
                                    text: 'Description of the project and the thematic background',
                                },
                                {
                                    text: 'Charts data visualization',
                                },
                                {
                                    text: 'Summary of all info of the same dataset stored in Wikidata and Wikibase',
                                },
                                {
                                    text: 'English/German language support',
                                },
                            ],
                        },
                        {
                            text: 'Map content',
                            listItems2ndLevel: [
                                {
                                    text: 'Historical map layer',
                                },
                                {
                                    text: 'Timeline and filters',
                                },
                                {
                                    text: 'Place selection',
                                },
                                {
                                    text: 'Sidebar Scrollytelling',
                                },
                            ],
                        },
                        {
                            text: 'Style in progress',
                            listItems2ndLevel: [
                                {
                                    text: 'colors, fonts (size), map icons, naming of elements, ...',
                                },
                            ],
                        },
                        {
                            text: 'Started with ...',
                            listItems2ndLevel: [
                                {
                                    text: 'Map as the core element and related features',
                                },
                                {
                                    text: 'Navigation drawer',
                                },
                            ],
                        },
                    ],
                },
                {
                    header: 'Thank you',
                    listItems: [
                        {
                            text: 'Any questions',
                        },
                        {
                            text: 'Any feature ideas',
                        },
                    ],
                },
            ],
        };
    },
};
</script>

<style scoped>
.z-index-1 {
    z-index: 1;
}

.mx-100 {
    margin-left: 100px;
    margin-right: 100px;
}

.px-70 {
    padding-left: 70px;
    padding-right: 70px;
}
</style>
