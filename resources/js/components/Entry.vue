<template>
    <v-container id="container" class="mb-110">
        <v-row
            :style="{
                'padding-left':
                    freeClientWidth < 75 && !isMenuDisplayed
                        ? 75 - freeClientWidth + 'px'
                        : 0,
            }"
        >
            <v-col>
                <h1
                    class="font-family-special-elite font-weight-bold hyphens-auto py-4 text-h4 text-md-h3"
                    lang="de"
                >
                    Eintrag anlegen
                </h1>
            </v-col>
        </v-row>

        <v-row v-if="currentStep === 1">
            <v-col>
                <div class="forms-container">
                    <h2 class="step-title">
                        Du möchtest uns helfen? So funktioniert's!
                    </h2>
                    <div class="info-section">
                        <p>
                            Bitte wähle den Eintragstyp aus, welchen du anlegen
                            möchtest. Die verschiedenen Typen werden auf der
                            Karte mit unterschiedlichen Markierungen angezeigt.
                        </p>
                        <p>
                            Jeder Eintragstyp ermöglicht es dir, spezifische
                            historische Informationen zu dokumentieren. Wähle
                            den Typ, der am besten zu deinem Eintrag passt.
                        </p>
                    </div>
                </div>
            </v-col>
        </v-row>

        <v-row>
            <v-col>
                <div class="forms-container">
                    <!-- Schritt 1: Eintragstyp wählen -->
                    <div v-if="currentStep === 1" class="entry-form-wrapper">
                        <div class="step-container">
                            <h2 class="step-title">
                                Schritt 1: Wähle den Eintragstyp
                            </h2>
                            <form class="forms">
                                <fieldset>
                                    <div class="type-selection-grid">
                                        <!-- Ereignis -->
                                        <div
                                            class="type-box"
                                            :class="{
                                                selected:
                                                    formsType === 'Q6983405',
                                            }"
                                            @click="
                                                formsType = 'Q6983405';
                                                updateTypeInfo();
                                            "
                                        >
                                            <div
                                                class="info-icon-container d-lg-none"
                                                @click.stop="
                                                    showTypeInfoPopup(
                                                        'Q6983405',
                                                    )
                                                "
                                                title="Mehr Informationen"
                                            >
                                                <v-icon
                                                    color="primary"
                                                    size="small"
                                                    >mdi-information-outline</v-icon
                                                >
                                            </div>
                                            <input
                                                v-model="formsType"
                                                name="type"
                                                type="radio"
                                                value="Q6983405"
                                                id="type-Q6983405"
                                                class="type-radio"
                                            />
                                            <div class="type-icon">
                                                <img
                                                    :src="`/images/leaflet/markerIcons/coloredFilledWhite/events.svg`"
                                                    alt="Ereignis Icon"
                                                />
                                            </div>
                                            <div class="type-data">
                                                <div class="type-label">
                                                    Ereignis
                                                </div>
                                                <div class="type-description">
                                                    Historische Geschehnisse im
                                                    Zeitraum 1933-1945
                                                </div>
                                                <div
                                                    class="type-action-container"
                                                >
                                                    <transition name="fade">
                                                        <button
                                                            v-if="
                                                                formsType ===
                                                                'Q6983405'
                                                            "
                                                            class="type-action-button"
                                                            type="button"
                                                            @click="
                                                                openTitleOverlay
                                                            "
                                                        >
                                                            Jetzt ein Ereignis
                                                            anlegen
                                                        </button>
                                                    </transition>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Person -->
                                        <div
                                            class="type-box"
                                            :class="{
                                                selected: formsType === 'Q5',
                                            }"
                                            @click="
                                                formsType = 'Q5';
                                                updateTypeInfo();
                                            "
                                        >
                                            <div
                                                class="info-icon-container d-lg-none"
                                                @click.stop="
                                                    showTypeInfoPopup('Q5')
                                                "
                                                title="Mehr Informationen"
                                            >
                                                <v-icon
                                                    color="primary"
                                                    size="small"
                                                    >mdi-information-outline</v-icon
                                                >
                                            </div>
                                            <input
                                                v-model="formsType"
                                                name="type"
                                                type="radio"
                                                value="Q5"
                                                id="type-Q5"
                                                class="type-radio"
                                            />
                                            <div class="type-icon">
                                                <img
                                                    :src="`/images/leaflet/markerIcons/coloredFilledWhite/persons.svg`"
                                                    alt="Person Icon"
                                                />
                                            </div>
                                            <div class="type-data">
                                                <div class="type-label">
                                                    Person
                                                </div>
                                                <div class="type-description">
                                                    Historische Personen und
                                                    Organisationen
                                                </div>
                                                <div
                                                    class="type-action-container"
                                                >
                                                    <transition name="fade">
                                                        <button
                                                            v-if="
                                                                formsType ===
                                                                'Q5'
                                                            "
                                                            class="type-action-button"
                                                            type="button"
                                                            @click="
                                                                openPersonSearchOverlay()
                                                            "
                                                        >
                                                            Jetzt eine Person
                                                            anlegen
                                                        </button>
                                                    </transition>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Erinnerungsort -->

                                        <div
                                            class="type-box"
                                            :class="{
                                                selected:
                                                    formsType === 'Q5003624',
                                            }"
                                            @click="
                                                formsType = 'Q5003624';
                                                updateTypeInfo();
                                            "
                                        >
                                            <div
                                                class="info-icon-container d-lg-none"
                                                @click.stop="
                                                    showTypeInfoPopup(
                                                        'Q5003624',
                                                    )
                                                "
                                                title="Mehr Informationen"
                                            >
                                                <v-icon
                                                    color="primary"
                                                    size="small"
                                                    >mdi-information-outline</v-icon
                                                >
                                            </div>
                                            <input
                                                v-model="formsType"
                                                name="type"
                                                type="radio"
                                                value="Q5003624"
                                                id="type-Q5003624"
                                                class="type-radio"
                                            />
                                            <div class="type-icon">
                                                <img
                                                    :src="`/images/leaflet/markerIcons/coloredFilledWhite/memorials.svg`"
                                                    alt="Erinnerungsort Icon"
                                                />
                                            </div>
                                            <div class="type-data">
                                                <div class="type-label">
                                                    Erinnerungsort
                                                </div>
                                                <div class="type-description">
                                                    Physische Orte, die an die
                                                    Opfer des
                                                    Nationalsozialismus erinnern
                                                </div>
                                                <div
                                                    class="type-action-container"
                                                >
                                                    <transition name="fade">
                                                        <button
                                                            v-if="
                                                                formsType ===
                                                                'Q5003624'
                                                            "
                                                            class="type-action-button"
                                                            type="button"
                                                            @click="
                                                                openTitleOverlay
                                                            "
                                                        >
                                                            Jetzt einen
                                                            Erinnerungsort
                                                            anlegen
                                                        </button>
                                                    </transition>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Staatspolizeistelle -->
                                        <!--
                                        <div
                                            class="type-box"
                                            :class="{
                                                selected:
                                                    formsType === 'Q108047567',
                                            }"
                                            @click="
                                                formsType = 'Q108047567';
                                                updateTypeInfo();
                                            "
                                        >
                                            <div
                                                class="info-icon-container d-lg-none"
                                                @click.stop="
                                                    showTypeInfoPopup(
                                                        'Q108047567',
                                                    )
                                                "
                                                title="Mehr Informationen"
                                            >
                                                <v-icon
                                                    color="primary"
                                                    size="small"
                                                    >mdi-information-outline</v-icon
                                                >
                                            </div>
                                            <input
                                                v-model="formsType"
                                                name="type"
                                                type="radio"
                                                value="Q108047567"
                                                id="type-Q108047567"
                                                class="type-radio"
                                            />
                                            <div class="type-icon">
                                                <img
                                                    :src="`/images/leaflet/markerIcons/coloredFilledWhite/statePoliceOffices.svg`"
                                                    alt="Staatspolizeistelle Icon"
                                                />
                                            </div>
                                            <div class="type-data">
                                                <div class="type-label">
                                                    Staatspolizeistelle
                                                </div>
                                                <div class="type-description">
                                                    Regionale Dienstellen der
                                                    Geheimen Staatspolizei
                                                </div>
                                                <div
                                                    class="type-action-container"
                                                >
                                                    <transition name="fade">
                                                        <button
                                                            v-if="
                                                                formsType ===
                                                                'Q108047567'
                                                            "
                                                            class="type-action-button"
                                                            type="button"
                                                            @click="
                                                                openTitleOverlay
                                                            "
                                                        >
                                                            Jetzt eine
                                                            Staatspolizeistelle
                                                            anlegen
                                                        </button>
                                                    </transition>
                                                </div>
                                            </div>
                                        </div>
-->
                                        <!-- Staatspolizeiaußenstelle -->
                                        <!--
                                        <div
                                            class="type-box"
                                            :class="{
                                                selected:
                                                    formsType === 'Q108047541',
                                            }"
                                            @click="
                                                formsType = 'Q108047541';
                                                updateTypeInfo();
                                            "
                                        >
                                            <div
                                                class="info-icon-container d-lg-none d-lg-none"
                                                @click.stop="
                                                    showTypeInfoPopup(
                                                        'Q108047541',
                                                    )
                                                "
                                                title="Mehr Informationen"
                                            >
                                                <v-icon
                                                    color="primary"
                                                    size="small"
                                                    >mdi-information-outline</v-icon
                                                >
                                            </div>
                                            <input
                                                v-model="formsType"
                                                name="type"
                                                type="radio"
                                                value="Q108047541"
                                                id="type-Q108047541"
                                                class="type-radio"
                                            />
                                            <div class="type-icon">
                                                <img
                                                    :src="`/images/leaflet/markerIcons/coloredFilledWhite/fieldOffices.svg`"
                                                    alt="Staatspolizeiaußenstelle Icon"
                                                />
                                            </div>
                                            <div class="type-data">
                                                <div class="type-label">
                                                    Staatspolizeiaußenstelle
                                                </div>
                                                <div class="type-description">
                                                    Lokale Zweigstellen einer
                                                    Staatspolizeistelle
                                                </div>
                                                <div
                                                    class="type-action-container"
                                                >
                                                    <transition name="fade">
                                                        <button
                                                            v-if="
                                                                formsType ===
                                                                'Q108047541'
                                                            "
                                                            class="type-action-button"
                                                            type="button"
                                                            @click="
                                                                openTitleOverlay
                                                            "
                                                        >
                                                            Jetzt eine
                                                            Staatspolizeiaußenstelle
                                                            anlegen
                                                        </button>
                                                    </transition>
                                                </div>
                                            </div>
                                        </div>
-->
                                        <!-- Arbeitserziehungslager -->
                                        <!--
                                        <div
                                            class="type-box"
                                            :class="{
                                                selected:
                                                    formsType === 'Q277565',
                                            }"
                                            @click="
                                                formsType = 'Q277565';
                                                updateTypeInfo();
                                            "
                                        >
                                            <div
                                                class="info-icon-container d-lg-none"
                                                @click.stop="
                                                    showTypeInfoPopup('Q277565')
                                                "
                                                title="Mehr Informationen"
                                            >
                                                <v-icon
                                                    color="primary"
                                                    size="small"
                                                    >mdi-information-outline</v-icon
                                                >
                                            </div>
                                            <input
                                                v-model="formsType"
                                                name="type"
                                                type="radio"
                                                value="Q277565"
                                                id="type-Q277565"
                                                class="type-radio"
                                            />
                                            <div class="type-icon">
                                                <img
                                                    :src="`/images/leaflet/markerIcons/coloredFilledWhite/laborEducationCamps.svg`"
                                                    alt="Arbeitserziehungslager Icon"
                                                />
                                            </div>
                                            <div class="type-data">
                                                <div class="type-label">
                                                    Arbeitserziehungslager
                                                </div>
                                                <div class="type-description">
                                                    Haftsystem der Gestapo
                                                    während des
                                                    Nationalsozialismus
                                                </div>
                                                <div
                                                    class="type-action-container"
                                                >
                                                    <transition name="fade">
                                                        <button
                                                            v-if="
                                                                formsType ===
                                                                'Q277565'
                                                            "
                                                            class="type-action-button"
                                                            type="button"
                                                            @click="
                                                                openTitleOverlay
                                                            "
                                                        >
                                                            Jetzt ein
                                                            Arbeitserziehungslager
                                                            anlegen
                                                        </button>
                                                    </transition>
                                                </div>
                                            </div>
                                        </div>
-->
                                        <!-- Gefängnis -->
                                        <!--
                                        <div
                                            class="type-box"
                                            :class="{
                                                selected:
                                                    formsType === 'Q40357',
                                            }"
                                            @click="
                                                formsType = 'Q40357';
                                                updateTypeInfo();
                                            "
                                        >
                                            <div
                                                class="info-icon-container d-lg-none"
                                                @click.stop="
                                                    showTypeInfoPopup('Q40357')
                                                "
                                                title="Mehr Informationen"
                                            >
                                                <v-icon
                                                    color="primary"
                                                    size="small"
                                                    >mdi-information-outline</v-icon
                                                >
                                            </div>
                                            <input
                                                v-model="formsType"
                                                name="type"
                                                type="radio"
                                                value="Q40357"
                                                id="type-Q40357"
                                                class="type-radio"
                                            />
                                            <div class="type-icon">
                                                <img
                                                    :src="`/images/leaflet/markerIcons/coloredFilledWhite/prisons.svg`"
                                                    alt="Gefängnis Icon"
                                                />
                                            </div>
                                            <div class="type-data">
                                                <div class="type-label">
                                                    Gefängnis
                                                </div>
                                                <div class="type-description">
                                                    Haftanstalten, die während
                                                    der NS-Zeit für politische
                                                    Gegner genutzt wurden
                                                </div>
                                                <div
                                                    class="type-action-container"
                                                >
                                                    <transition name="fade">
                                                        <button
                                                            v-if="
                                                                formsType ===
                                                                'Q40357'
                                                            "
                                                            class="type-action-button"
                                                            type="button"
                                                            @click="
                                                                openTitleOverlay
                                                            "
                                                        >
                                                            Jetzt ein Gefängnis
                                                            anlegen
                                                        </button>
                                                    </transition>
                                                </div>
                                            </div>
                                        </div>
                                        -->
                                    </div>
                                </fieldset>
                            </form>
                        </div>

                        <!-- Info-Box (Desktop) -->
                        <div class="info-box desktop-info" v-if="!isMobileView">
                            <div class="info-box-header">
                                <h3>
                                    {{
                                        typeInfoContent
                                            ? getTypeInfoTitle()
                                            : "Eintragstypen"
                                    }}
                                </h3>
                            </div>
                            <div class="info-box-content">
                                <div
                                    v-if="typeInfoContent"
                                    v-html="typeInfoContent"
                                ></div>
                                <div v-else>
                                    <p>
                                        Bitte wähle einen Eintragstyp aus, um
                                        Informationen darüber zu erhalten.
                                    </p>
                                    <p>
                                        Zu den verfügbaren Eintragstypen
                                        gehören:
                                    </p>
                                    <ul>
                                        <li>
                                            <strong>Ereignis</strong> -
                                            Historische Geschehnisse im Zeitraum
                                            1933-1945
                                        </li>
                                        <li>
                                            <strong>Erinnerungsort</strong> -
                                            Physische Orte, die an die Opfer des
                                            Nationalsozialismus erinnern
                                        </li>
                                        <li>
                                            <strong>Staatspolizeistelle</strong>
                                            - Regionale Dienstellen der Geheimen
                                            Staatspolizei
                                        </li>
                                        <li>
                                            <strong
                                                >Staatspolizeiaußenstelle</strong
                                            >
                                            - Lokale Zweigstellen einer
                                            Staatspolizeistelle
                                        </li>
                                        <li>
                                            <strong
                                                >Arbeitserziehungslager</strong
                                            >
                                            - Haftsystem der Gestapo während des
                                            Nationalsozialismus
                                        </li>
                                        <li>
                                            <strong>Gefängnis</strong> -
                                            Haftanstalten, die während der
                                            NS-Zeit für politische Gefangene
                                            genutzt wurden
                                        </li>
                                    </ul>
                                    <p>
                                        Wählen einen Typ, um detailliertere
                                        Informationen zu erhalten.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Schritt 2: Ereignis-Formular -->
                    <ErrorBoundary>
                        <EventsForm
                            ref="eventsFormRef"
                            v-if="currentStep === 2 && formsType === 'Q6983405'"
                            key="events-form"
                            v-model:formsTitle="formsTitle"
                            v-model:formsDescription="formsDescription"
                            v-model:wikidataResults="wikidataResults"
                            v-model:peopleSearchResults="peopleSearchResults"
                            v-model:selectedVictims="selectedVictims"
                            v-model:showNewVictimForm="showNewVictimForm"
                            v-model:newVictim="newVictim"
                            v-model:perpetratorsSearchResults="
                                perpetratorsSearchResults
                            "
                            v-model:selectedPerpetrators="selectedPerpetrators"
                            v-model:showNewPerpetratorForm="
                                showNewPerpetratorForm
                            "
                            v-model:newPerpetrator="newPerpetrator"
                            v-model:eventTimeType="eventTimeType"
                            v-model:eventDate="eventDate"
                            v-model:eventDateQualifier="eventDateQualifier"
                            v-model:eventStartDate="eventStartDate"
                            v-model:eventStartDateQualifier="
                                eventStartDateQualifier
                            "
                            v-model:eventEndDate="eventEndDate"
                            v-model:eventEndDateQualifier="
                                eventEndDateQualifier
                            "
                            v-model:victimCount="victimCount"
                            v-model:survivorCount="survivorCount"
                            v-model:deathCount="deathCount"
                            v-model:arrestCount="arrestCount"
                            v-model:victimCountQualifier="victimCountQualifier"
                            v-model:survivorCountQualifier="
                                survivorCountQualifier
                            "
                            v-model:deathCountQualifier="deathCountQualifier"
                            v-model:arrestCountQualifier="arrestCountQualifier"
                            v-model:causeSearchResults="causeSearchResults"
                            v-model:selectedCauses="selectedCauses"
                            v-model:mapZoom="mapZoom"
                            v-model:mapCenter="mapCenter"
                            v-model:selectedLocation="selectedLocation"
                            v-model:locationSearch="locationSearch"
                            v-model:locationQualifier="locationQualifier"
                            v-model:error="error"
                            v-model:imageUrl="imageUrl"
                            v-model:imageFilename="imageFilename"
                            v-model:selectedSources="selectedSources"
                            @searchWikidataEntities="searchWikidataEntities"
                            @selectExistingEntity="selectExistingEntity"
                            @update:eventTimeType="eventTimeType = $event"
                            @searchPeople="searchPeople"
                            @selectVictim="selectVictim"
                            @startNewVictimForm="startNewVictimForm"
                            @cancelNewVictimForm="cancelNewVictimForm"
                            @addNewVictim="addNewVictim"
                            @removeVictim="removeVictim"
                            @updateVictim="updateVictim"
                            @startAddingAnotherVictim="startAddingAnotherVictim"
                            @searchPerpetrators="searchPerpetrators"
                            @selectPerpetrator="selectPerpetrator"
                            @startNewPerpetratorForm="startNewPerpetratorForm"
                            @cancelNewPerpetratorForm="cancelNewPerpetratorForm"
                            @addNewPerpetrator="addNewPerpetrator"
                            @removePerpetrator="removePerpetrator"
                            @updatePerpetrator="updatePerpetrator"
                            @startAddingAnotherPerpetrator="
                                startAddingAnotherPerpetrator
                            "
                            @searchCause="searchCause"
                            @selectCause="selectCause"
                            @removeCause="removeCause"
                            @searchLocation="
                                (value) => {
                                    if (value) {
                                        if (
                                            typeof locationSearch ===
                                                'object' &&
                                            locationSearch !== null
                                        ) {
                                            locationSearch.value = value;
                                        } else {
                                            locationSearch = value;
                                        }
                                        searchLocation();
                                    }
                                }
                            "
                            @handleMapClick="handleMapClick"
                            @goToStep="goToStep"
                            @submit="resetForm"
                        />
                    </ErrorBoundary>

                    <!-- Schritt 2: Personen-Formular -->
                    <ErrorBoundary>
                        <SimplePersonForm
                            ref="personFormRef"
                            v-if="currentStep === 2 && formsType === 'Q5'"
                            :entry-type="formsType"
                            :title="formsTitle"
                            :description="formsDescription"
                            :temp-person-data="tempPersonData"
                            :person-role="props.role || 'standalone'"
                            v-model:selectedSources="selectedSources"
                            @update:title="
                                (val) => {
                                    if (val || formsTitle.value)
                                        formsTitle = val;
                                }
                            "
                            @update:description="formsDescription = $event"
                            @goToStep="goToStep"
                            @submit="submitPersonForm"
                            key="person-form"
                        />
                    </ErrorBoundary>

                    <!-- Schritt 2: Erinnerungsort-Formular -->
                    <ErrorBoundary>
                        <MemorialForm
                            ref="memorialFormRef"
                            v-if="currentStep === 2 && formsType === 'Q5003624'"
                            key="memorial-form"
                            v-model:formsTitle="formsTitle"
                            v-model:formsDescription="formsDescription"
                            v-model:wikidataResults="wikidataResults"
                            v-model:mapZoom="mapZoom"
                            v-model:mapCenter="mapCenter"
                            v-model:selectedLocation="selectedLocation"
                            v-model:locationSearch="locationSearch"
                            v-model:locationQualifier="locationQualifier"
                            v-model:error="error"
                            v-model:imageUrl="imageUrl"
                            v-model:imageFilename="imageFilename"
                            v-model:selectedSources="selectedSources"
                            @searchWikidataEntities="searchWikidataEntities"
                            @selectExistingEntity="selectExistingEntity"
                            @searchLocation="
                                (value) => {
                                    if (value) {
                                        if (
                                            typeof locationSearch ===
                                                'object' &&
                                            locationSearch !== null
                                        ) {
                                            locationSearch.value = value;
                                        } else {
                                            locationSearch = value;
                                        }
                                        searchLocation();
                                    }
                                }
                            "
                            @handleMapClick="handleMapClick"
                            @goToStep="goToStep"
                            @submit="resetForm"
                        />
                    </ErrorBoundary>

                    <!-- Für andere Typen können ähnliche Formulare mit spezifischen Anforderungen erstellt werden -->
                    <div
                        v-if="
                            currentStep === 2 &&
                            !['Q6983405', 'Q5', 'Q5003624'].includes(formsType)
                        "
                        class="step-container"
                    >
                        <h2 class="step-title">Schritt 2: Details eingeben</h2>
                        <p>
                            Formular für diesen Eintragstyp wird bald verfügbar
                            sein.
                        </p>
                        <div class="button-container">
                            <button
                                class="button bg-secondary"
                                type="button"
                                @click="goToStep(1)"
                            >
                                Zurück
                            </button>
                        </div>
                    </div>
                </div>
                <br /><br /><br /><br />
            </v-col>
        </v-row>
        <v-row>
            <v-col>
                <div class="forms-container">
                    <div class="info-section">
                        <h2 class="step-title">Du brauchst Hilfe?</h2>
                        <p>
                            Hier findest du alle Informationen zum Anlegen eines
                            neuen Eintrags
                        </p>

                        <div class="button-container">
                            <router-link
                                to="/eintrag-informationen"
                                class="entry-button"
                            >
                                <v-btn color="primary" size="large">
                                    <v-icon class="mr-2"
                                        >mdi-information</v-icon
                                    >
                                    Wie lege ich einen Eintrag an
                                </v-btn>
                            </router-link>
                        </div>
                    </div>
                </div>
            </v-col>
        </v-row>
    </v-container>

    <!-- Mobile Info-Overlay -->
    <div
        class="mobile-info-overlay"
        v-if="showMobileInfoOverlay"
        @click="closeMobileInfoOverlay"
    >
        <div class="overlay-content" @click.stop>
            <div class="overlay-header">
                <h3 class="text-black">{{ getTypeInfoTitle() }}</h3>
                <button class="close-overlay" @click="closeMobileInfoOverlay">
                    <v-icon>mdi-close</v-icon>
                </button>
            </div>
            <div class="overlay-body">
                <div v-html="typeInfoContent" class="info-content"></div>
            </div>
        </div>
    </div>

    <!-- Title Search Overlay -->
    <TitleSearchOverlay
        v-if="showTitleOverlay"
        :initial-title="formsTitle"
        :entry-type="formsType"
        :title="`Neuen ${getTypeInfoTitle()} anlegen`"
        :context="'create'"
        @update:title="updateTitle"
        @close="cancelTitleOverlay"
        @create="createWithTitle"
        key="title-search-overlay"
    />

    <!-- Person Search Overlay -->
    <PersonSearchOverlay
        v-if="showPersonSearchOverlay"
        :initial-name="formsTitle"
        :entry-type="formsType"
        :title="`Person suchen oder anlegen`"
        :context="'create'"
        @close="cancelPersonSearchOverlay"
        @select-person="selectExistingPerson"
        @create-new-person="createNewPerson"
        key="person-search-overlay"
    />
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed, watch, inject } from "vue";
import { LMap, LTileLayer, LMarker } from "@vue-leaflet/vue-leaflet";
import "leaflet/dist/leaflet.css";
import * as L from "leaflet";
import axios from "axios";
import { debounce } from "lodash";
import { useRouter, useRoute } from "vue-router";
import EventsForm from "./entry/EventsForm.vue";
import MemorialForm from "./entry/MemorialForm.vue";
import TitleSearchOverlay from "./entry/fields/TitleSearchOverlay.vue";
import PersonSearchOverlay from "./entry/fields/PersonSearchOverlay.vue";
import glossaryItems from "../data/glossaryData";
import { defineAsyncComponent, h } from "vue";
import SimplePersonForm from "./entry/SimplePersonForm.vue";

// Use SimplePersonForm directly instead of async loading
const resolvePersonForm = SimplePersonForm;

// Simple error boundary component
const ErrorBoundary = {
    data() {
        return {
            error: null,
        };
    },
    errorCaptured(err, vm, info) {
        console.error("Component error captured:", err, info);
        this.error = `${err}`;
        return false;
    },
    render() {
        if (this.error) {
            return h("div", { class: "error-boundary" }, [
                h("h3", { style: "color: red;" }, "Error loading component"),
                h("p", {}, this.error),
                h(
                    "button",
                    {
                        style: "padding: 8px 16px; margin-top: 10px;",
                        onClick: () => window.location.reload(),
                    },
                    "Reload page",
                ),
            ]);
        }
        return this.$slots.default ? this.$slots.default() : null;
    },
};

// Props
const props = defineProps({
    isMenuDisplayed: Boolean,
});

// Reactive references
const router = useRouter();
const ogtGlobals = inject("ogtGlobals");
const freeClientWidth = ref(document.documentElement.clientWidth);
const error = ref(null);
const formsType = ref("");
const formsTitle = ref(null);
const formsDescription = ref(null);
const isSubmitting = ref(false);
const wikidataResults = ref([]);
const peopleSearchResults = ref([]);
const personFormData = ref(null); // Store form data received from SimplePersonForm
const selectedVictims = ref([]);
const currentStep = ref(1);
const showNewVictimForm = ref(false);
const showPersonSearchOverlay = ref(false);
const tempPersonData = ref(null);
// Create Vue reference for the form's initial state - Person model
const newVictim = ref({
    firstName: "",
    lastName: "",
    birthDate: null,
    deathDate: null,
    description: "",
    isOrganization: false,
    gender: null,
    nationality: null, // Single string value like gender
    place_of_birth: null,
    place_of_death: null,
    place_of_detention: [],
    place_of_residence: null,
    occupation: null,
    employer: null,
    imageUrl: null,
    imageFilename: null,
});
const lastSearchQuery = ref("");

// Täter (Perpetrator) Zustandsvariablen
const perpetratorsSearchResults = ref([]);
const selectedPerpetrators = ref([]);
const showNewPerpetratorForm = ref(false);
const eventsFormRef = ref(null);
const memorialFormRef = ref(null);
const personFormRef = ref(null);
const newPerpetrator = ref({
    firstName: "",
    lastName: "",
    birthDate: null,
    deathDate: null,
    description: "",
    isOrganization: false,
    gender: null,
    nationality: null, // Single string value like gender
    place_of_birth: null,
    place_of_death: null,
    place_of_detention: [],
    place_of_residence: null,
    occupation: null,
    employer: null,
    imageUrl: null,
    imageFilename: null,
});
const lastPerpetratorSearchQuery = ref("");

const eventTimeType = ref("point"); // 'point' oder 'range'
const eventDate = ref(null);
const eventDateQualifier = ref("");
const eventStartDate = ref(null);
const eventStartDateQualifier = ref("");
const eventEndDate = ref(null);
const eventEndDateQualifier = ref("");

const victimCount = ref(null);
const survivorCount = ref(null);
const deathCount = ref(null);
const arrestCount = ref(null);
const victimCountQualifier = ref("");
const survivorCountQualifier = ref("");
const deathCountQualifier = ref("");
const arrestCountQualifier = ref("");
const cause = ref("");
const causeSearchResults = ref([]);
const selectedCauses = ref([]);
const lastCauseSearchQuery = ref("");

const mapRef = ref(null);
const mapZoom = ref(8);
const mapCenter = ref([52.8, 9.0]); // Zentrum von Niedersachsen
const selectedLocation = ref(null);
const locationSearch = ref("");
const locationQualifier = ref("");

const imageUrl = ref(null);
const imageFilename = ref(null);
const selectedSources = ref([]);

const createCustomIcon = (type) => {
    return L.icon({
        iconUrl: `/images/leaflet/markerIcons/coloredFilledWhite/${type}.svg`,
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowUrl: "/images/leaflet/marker-shadow.png",
        shadowSize: [41, 41],
    });
};

const markerIcon = computed(() => {
    if (formsType.value === "Q6983405") return createCustomIcon("events");
    if (formsType.value === "Q5") return createCustomIcon("persons");
    if (formsType.value === "Q5003624") return createCustomIcon("memorials");
    if (formsType.value === "Q108047567")
        return createCustomIcon("statePoliceOffices");
    if (formsType.value === "Q108047541")
        return createCustomIcon("fieldOffices");
    if (formsType.value === "Q277565")
        return createCustomIcon("laborEducationCamps");
    if (formsType.value === "Q40357") return createCustomIcon("prisons");
    return createCustomIcon("events"); // Standard-Icon
});

const setFreeClientWidth = () => {
    const container = document.getElementById("container");
    freeClientWidth.value =
        (document.documentElement.clientWidth - container.clientWidth) / 2;
};

const goToStep = (step) => {
    if (step === 2 && !formsType.value) {
        error.value = "Bitte wähle einen Eintragstyp aus";
        return;
    }

    error.value = null;
    currentStep.value = step;
};

const isMobileView = ref(false);
const checkScreenSize = () => {
    isMobileView.value = window.innerWidth < 1200;
};

const typeInfoContent = ref("");
const showMobileInfoOverlay = ref(false);

const showTypeInfoPopup = (typeValue) => {
    // Store the type we're showing info for
    displayedInfoType.value = typeValue;

    console.log("showTypeInfoPopup called for type:", typeValue);

    // Get content for the clicked type without changing selection
    const typeToGlossaryMapping = {
        Q5: "Person",
        Q6983405: "Ereignis",
        Q5003624: "Erinnerungsort",
        Q108047567: "Staatspolizeistelle",
        Q108047541: "Außendienststelle", // Im Glossar als "Außendienststelle"
        Q277565: "Arbeitserziehungslager",
        Q40357: "Gefängnis",
    };

    // Get the corresponding glossary item key for the clicked type
    const glossaryKey = typeToGlossaryMapping[typeValue];
    console.log("Glossary key for type:", typeValue, "is:", glossaryKey);

    // Get the glossary item if it exists
    const glossaryItem = glossaryKey ? glossaryItems[glossaryKey] : null;
    console.log("Glossary item found:", !!glossaryItem);

    if (glossaryItem) {
        let content = "";

        if (Array.isArray(glossaryItem.descriptions)) {
            glossaryItem.descriptions.forEach((desc) => {
                if (typeof desc === "string") {
                    content += `<p>${desc}</p>`;
                } else if (Array.isArray(desc)) {
                    content += "<ul class='info-list'>";
                    desc.forEach((item) => {
                        if (typeof item === "string") {
                            content += `<li>${item}</li>`;
                        }
                    });
                    content += "</ul>";
                }
            });
        }

        typeInfoContent.value = content;
        console.log("Generated typeInfoContent:", content);
    } else {
        typeInfoContent.value = "";
        console.log("No content generated, setting empty typeInfoContent");
    }

    // Show the popup
    showMobileInfoOverlay.value = true;
};

const updateTypeInfoContent = () => {
    // This function is now only used when updating the type selection
    // The showTypeInfoPopup function handles the popup content directly
    const typeToGlossaryMapping = {
        Q5: "Person",
        Q6983405: "Ereignis",
        Q5003624: "Erinnerungsort",
        Q108047567: "Staatspolizeistelle",
        Q108047541: "Außendienststelle", // Im Glossar als "Außendienststelle"
        Q277565: "Arbeitserziehungslager",
        Q40357: "Gefängnis",
    };

    // Get the corresponding glossary item key
    const glossaryKey = typeToGlossaryMapping[formsType.value];

    // Get the glossary item if it exists
    const glossaryItem = glossaryKey ? glossaryItems[glossaryKey] : null;

    if (glossaryItem) {
        let content = "";

        if (Array.isArray(glossaryItem.descriptions)) {
            glossaryItem.descriptions.forEach((desc) => {
                if (typeof desc === "string") {
                    content += `<p>${desc}</p>`;
                } else if (Array.isArray(desc)) {
                    content += "<ul class='info-list'>";
                    desc.forEach((item) => {
                        if (typeof item === "string") {
                            content += `<li>${item}</li>`;
                        }
                    });
                    content += "</ul>";
                }
            });
        }

        typeInfoContent.value = content;
    } else {
        typeInfoContent.value = "";
    }
};

const updateTypeInfo = () => {
    // Update the info content for the currently selected type
    updateTypeInfoContent();

    // No longer automatically showing overlay on type selection
    // Info icon will be used instead
};

// Close mobile info overlay
const closeMobileInfoOverlay = () => {
    showMobileInfoOverlay.value = false;
    displayedInfoType.value = ""; // Reset the displayed info type
};

// Reference to store which type's info is currently being displayed
const displayedInfoType = ref("");

const getTypeInfoTitle = () => {
    const typeTitles = {
        Q5: "Person",
        Q6983405: "Ereignis",
        Q5003624: "Erinnerungsort",
        Q108047567: "Staatspolizeistelle",
        Q108047541: "Staatspolizeiaußenstelle",
        Q277565: "Arbeitserziehungslager",
        Q40357: "Gefängnis",
    };

    // Use displayedInfoType for popups, otherwise use the selected formsType
    const typeToUse = showMobileInfoOverlay.value
        ? displayedInfoType.value
        : formsType.value;

    // Handle null/undefined cases
    if (!typeToUse) {
        console.log("Warning: typeToUse is undefined or null");
        return "Eintragstyp";
    }

    const title = typeTitles[typeToUse] || "Eintragstyp";
    return title;
};

const showTitleOverlay = ref(false);

// Person search overlay functions
const openPersonSearchOverlay = () => {
    // Clear any previous title to start fresh
    formsTitle.value = "";
    showPersonSearchOverlay.value = true;
};

const openTitleOverlay = () => {
    showTitleOverlay.value = true;
};

const cancelTitleOverlay = () => {
    showTitleOverlay.value = false;
};

const cancelPersonSearchOverlay = () => {
    showPersonSearchOverlay.value = false;
};

const updateTitle = (newTitle) => {
    formsTitle.value = newTitle;
};

// Handle selecting an existing person from the search overlay
const selectExistingPerson = (person) => {
    // Update title with the selected person's name
    formsTitle.value = person.label || "";

    // Extract name parts from label to use in the form
    if (person.firstName) {
        // If API already provides firstName, use that
        formsDescription.value = person.description || "";
    } else if (person.label) {
        // If no firstName but we have a label, try to extract firstName/lastName
        const nameParts = person.label.split(" ");
        if (nameParts.length > 1) {
            // Assume last word is lastName, rest is firstName
            formsDescription.value = person.description || "";
        }
    }

    // Don't proceed to step 2 - this function now just shows the Wikidata entry details
    // User must click "create new person" button explicitly to proceed to step 2
};

// Handle creating a new person when none is found
const createNewPerson = (name, personData = null) => {
    // Guard against empty name to prevent recursion
    if (name && name.trim()) {
        // Set the form title to the provided name
        formsTitle.value = name;
    } else {
        console.log("Skipping update with empty name");
        // Use a non-empty default to prevent recursion
        if (!formsTitle.value) {
            formsTitle.value = "Neue Person";
        }
    }

    // Set default description if needed
    if (!formsDescription.value) {
        formsDescription.value = "";
    }

    // Add role if available from props to personData
    if (personData && props.role) {
        personData.role = props.role;
        console.log(`Setting person role from props: ${props.role}`);
    }

    // Store person data in a ref that can be accessed by the SimplePersonForm
    if (personData) {
        tempPersonData.value = JSON.stringify(personData);
    }

    // Move to step 2 to complete the person details
    currentStep.value = 2;

    // Reset tempPersonData to avoid reinitializing again if the component rerenders
    setTimeout(() => {
        if (tempPersonData.value) {
            tempPersonData.value = null;
        }
    }, 500);
};

const createWithTitle = (title) => {
    try {
        // First set the title
        formsTitle.value = title;

        // Then close both overlays
        showTitleOverlay.value = false;
        showPersonSearchOverlay.value = false;

        // Use setTimeout to ensure the overlay is fully closed before changing state
        // This prevents any potential race conditions or state conflicts
        setTimeout(() => {
            try {
                // Validate that we have the required data for step 2
                if (!formsType.value) {
                    console.error("Form type is missing before step change");
                    error.value = "Bitte wähle einen Eintragstyp aus";
                    return;
                }

                // Clear any previous error state
                error.value = null;

                // Move to step 2
                currentStep.value = 2;

                // Check which form component should be rendered
            } catch (innerError) {
                console.error("Error during step change:", innerError);
                error.value =
                    "Fehler beim Laden des Formulars. Bitte versuchen Sie es erneut.";
            }
        }, 10);
    } catch (error) {
        console.error("Error in createWithTitle:", error);
        error.value =
            "Fehler beim Erstellen des Eintrags. Bitte versuchen Sie es erneut.";
    }
};

const resetMapView = () => {
    if (mapRef.value && mapRef.value.leafletObject) {
        mapRef.value.leafletObject.setView(mapCenter.value, mapZoom.value, {
            reset: true,
            animate: false,
        });
    }
    selectedLocation.value = null;
};

const reinitializeMap = () => {
    applyLeafletCSS();

    if (mapRef.value && mapRef.value.leafletObject) {
        setTimeout(() => {
            mapRef.value.leafletObject.invalidateSize(true);
            resetMapView();

            // Erzwinge ein Update der Tile-Layer
            mapRef.value.leafletObject.eachLayer(function (layer) {
                if (layer instanceof L.TileLayer) {
                    layer.redraw();
                }
            });
        }, 100);
    }
};

const handleMapClick = (event) => {
    selectedLocation.value = event.latlng;

    if (mapRef.value && mapRef.value.leafletObject) {
        setTimeout(() => {
            mapRef.value.leafletObject.invalidateSize(true);
        }, 50);
    }
};

const searchLocation = async () => {
    // Handle both ref and string cases
    const searchValue =
        typeof locationSearch === "object" && locationSearch !== null
            ? locationSearch.value
            : locationSearch;

    if (!searchValue) {
        return;
    }

    // Ensure searchValue is a string before proceeding
    const searchValueString = String(searchValue);

    if (searchValueString.trim() === "") {
        return;
    }

    try {
        const response = await axios.get(
            `https://nominatim.openstreetmap.org/search`,
            {
                params: {
                    q: searchValueString,
                    format: "json",
                    limit: 1,
                },
                headers: {
                    "Accept-Language": "de",
                },
            },
        );

        if (response.data && response.data.length > 0) {
            const result = response.data[0];
            const lat = parseFloat(result.lat);
            const lng = parseFloat(result.lon);

            mapCenter.value = [lat, lng];
            mapZoom.value = 15; // Näher heranzoomen

            selectedLocation.value = { lat, lng };
        } else {
            alert("Keine Ergebnisse gefunden");
        }
    } catch (err) {
        console.error("Fehler bei der Standortsuche:", err);
        alert("Fehler bei der Suche");
    }
};

const searchWikidataEntities = debounce(async () => {
    if (!formsTitle.value || formsTitle.value.length < 3) {
        wikidataResults.value = [];
        return;
    }

    try {
        const response = await axios.get("/api/wikidata/entities", {
            params: {
                title: formsTitle.value,
                limit: 5,
            },
        });

        if (response.data.success && response.data.results) {
            wikidataResults.value = response.data.results;
        } else {
            wikidataResults.value = [];
        }
    } catch (err) {
        console.error("Fehler bei der Wikidata-Suche:", err);
        wikidataResults.value = [];
    }
}, 300);

const applyLeafletCSS = () => {
    const styleId = "leaflet-tile-fix";

    const existingStyle = document.getElementById(styleId);
    if (existingStyle) {
        existingStyle.remove();
    }

    const style = document.createElement("style");
    style.id = styleId;
    style.textContent = `
        /* Grundlegende Map-Fixes */
        .leaflet-container {
            height: 400px !important;
            width: 600px !important;
            z-index: 0 !important;
        }

        /* Container-Fixes */
        .leaflet-tile-container {
            position: absolute !important;
            left: 0 !important;
            top: 0 !important;
            width: 100% !important;
            height: 100% !important;
            transform-origin: 0 0 !important;
        }

        /* Tile-Fixes */
        .leaflet-tile {
            position: absolute !important;
            box-shadow: none !important;
            border: none !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        /* Korrektur für Tile-Bilder */
        .leaflet-tile-container img {
            display: block !important;
            width: 256px !important;
            height: 256px !important;
            margin: 0 !important;
            padding: 0 !important;
            border: none !important;
            box-shadow: none !important;
        }

        /* Marker-Fixes */
        .leaflet-marker-pane {
            position: absolute;
            z-index: 100 !important;
        }

        .leaflet-marker-icon {
            position: absolute;
            z-index: 1000 !important;
        }
    `;
    document.head.appendChild(style);
};

const selectExistingEntity = (entity) => {
    const wikidataUrl = `https://www.wikidata.org/wiki/${entity.id}`;
    window.open(wikidataUrl, "_blank");

    wikidataResults.value = [];
};

const searchPeople = debounce(async (query) => {
    if (!query || query.length < 2) {
        peopleSearchResults.value = [];
        return;
    }

    lastSearchQuery.value = query;

    try {
        const response = await axios.get("/api/wikidata/search/people", {
            params: { query },
        });
        peopleSearchResults.value = response.data;
    } catch (error) {
        console.error("Fehler bei der Personensuche:", error);
        peopleSearchResults.value = [];
    }
}, 300);

const startNewVictimForm = () => {
    showNewVictimForm.value = true;
    if (lastSearchQuery.value) {
        const parts = lastSearchQuery.value.trim().split(" ");
        if (parts.length > 1) {
            newVictim.value.firstName = parts[0];
            newVictim.value.lastName = parts.slice(1).join(" ");
        } else {
            newVictim.value.lastName = parts[0];
        }
    }
};

const cancelNewVictimForm = () => {
    showNewVictimForm.value = false;
    resetNewVictimForm();
};

const resetNewVictimForm = () => {
    newVictim.value = {
        firstName: "",
        lastName: "",
        birthDate: null,
        deathDate: null,
        description: "",
        birthName: "",
        isOrganization: false,
        gender: null,
        nationality: null,
        place_of_birth: null,
        place_of_death: null,
        place_of_detention: [],
        place_of_residence: null,
        occupation: null,
        imageUrl: null,
        imageFilename: null,
    };
    lastSearchQuery.value = "";
};

const addNewVictim = () => {
    const tempId = "new_" + Date.now();
    const firstName = newVictim.value.firstName || "";
    const lastName = newVictim.value.lastName || "";

    // Create formatted name for title
    const formattedTitle = `${firstName} ${lastName}`.trim();

    // Use nationality as a direct string value (like gender)
    console.log("Adding victim with nationality:", newVictim.value.nationality);

    selectedVictims.value.push({
        tempId: tempId,
        firstName: firstName,
        lastName: lastName,
        title: formattedTitle, // Add title field with formatted name
        birthDate: newVictim.value.birthDate,
        deathDate: newVictim.value.deathDate,
        description: newVictim.value.description,
        birthName: newVictim.value.birthName || "",
        imageUrl: newVictim.value.imageUrl || null,
        imageFilename: newVictim.value.imageFilename || null,
        isOrganization: newVictim.value.isOrganization || false,
        // Wikidata fields
        gender: newVictim.value.gender || null,
        nationality: newVictim.value.nationality || null,
        place_of_birth: newVictim.value.place_of_birth || null,
        place_of_death: newVictim.value.place_of_death || null,
        place_of_detention: newVictim.value.place_of_detention || [],
        place_of_residence: newVictim.value.place_of_residence || null,
        occupation: newVictim.value.occupation || null,
        type: "Q5", // Add type field to identify this as a person
        role: "victim", // Explicitly set the role
    });

    showNewVictimForm.value = false;
    resetNewVictimForm();
};

const startAddingAnotherVictim = () => {
    showNewVictimForm.value = false;
    resetNewVictimForm();
    peopleSearchResults.value = [];
};

const canAddNewVictim = computed(() => {
    return newVictim.value.firstName.trim() && newVictim.value.lastName.trim();
});

const selectVictim = (person) => {
    if (!selectedVictims.value.some((v) => v.id === person.id)) {
        // Log the person object we're adding
        console.log("Adding victim with full data:", person);

        // Create a complete victim object that preserves the Wikidata Q number
        const victimObj = {
            id: person.id, // CRITICAL - Preserve Wikidata Q number
            label: person.label,
            title: person.label, // Ensure title is set
            description: person.description,
            birth: person.birth,
            death: person.death,
            nationality: person.nationality || null,
            type: "Q5", // Ensure type is set for existing entities
            role: "victim", // Ensure role is set for existing entities
            // Include additional fields that might be needed
            firstName: person.firstName || "",
            lastName: person.lastName || "",
            birthDate: person.birthDate || null,
            deathDate: person.deathDate || null,
            isOrganization: person.isOrganization || false,
        };

        // Log the object to be added to the array
        console.log("Victim object to be added:", victimObj);

        selectedVictims.value.push(victimObj);
        console.log("Added victim with ID:", person.id);

        // Verify the object was properly added with id
        console.log("Updated victims array:", selectedVictims.value);
    }
    peopleSearchResults.value = []; // Clear search results
};

const removeVictim = (victimId) => {
    selectedVictims.value = selectedVictims.value.filter(
        (v) => (v.id || v.tempId) !== victimId,
    );
};

const searchPerpetrators = debounce(async (query) => {
    if (!query || query.length < 2) {
        perpetratorsSearchResults.value = [];
        return;
    }

    lastPerpetratorSearchQuery.value = query;

    try {
        const response = await axios.get("/api/wikidata/search/people", {
            params: { query, type: "perpetrators" },
        });
        perpetratorsSearchResults.value = response.data;
    } catch (error) {
        console.error("Fehler bei der Tätersuche:", error);
        perpetratorsSearchResults.value = [];
    }
}, 300);

const startNewPerpetratorForm = () => {
    showNewPerpetratorForm.value = true;
    if (lastPerpetratorSearchQuery.value) {
        const parts = lastPerpetratorSearchQuery.value.trim().split(" ");
        if (parts.length > 1) {
            newPerpetrator.value.firstName = parts[0];
            newPerpetrator.value.lastName = parts.slice(1).join(" ");
        } else {
            newPerpetrator.value.lastName = parts[0];
        }
    }
};

const cancelNewPerpetratorForm = () => {
    showNewPerpetratorForm.value = false;
    resetNewPerpetratorForm();
};

const resetNewPerpetratorForm = () => {
    newPerpetrator.value = {
        firstName: "",
        lastName: "",
        birthDate: null,
        deathDate: null,
        description: "",
        birthName: "",
        isOrganization: false,
        gender: null,
        nationality: null,
        place_of_birth: null,
        place_of_death: null,
        place_of_detention: [],
        place_of_residence: null,
        occupation: null,
        imageUrl: null,
        imageFilename: null,
    };
    lastPerpetratorSearchQuery.value = "";
};

const addNewPerpetrator = () => {
    const tempId = "new_" + Date.now();
    const firstName = newPerpetrator.value.firstName || "";
    const lastName = newPerpetrator.value.lastName || "";

    // Create formatted name for title
    const formattedTitle = `${firstName} ${lastName}`.trim();

    selectedPerpetrators.value.push({
        tempId: tempId,
        firstName: firstName,
        lastName: lastName,
        title: formattedTitle, // Add title field with formatted name
        birthDate: newPerpetrator.value.birthDate,
        deathDate: newPerpetrator.value.deathDate,
        description: newPerpetrator.value.description,
        birthName: newPerpetrator.value.birthName || "",
        imageUrl: newPerpetrator.value.imageUrl || null,
        imageFilename: newPerpetrator.value.imageFilename || null,
        isOrganization: newPerpetrator.value.isOrganization || false,
        // Wikidata fields
        gender: newPerpetrator.value.gender || null,
        nationality: newPerpetrator.value.nationality || null,
        place_of_birth: newPerpetrator.value.place_of_birth || null,
        place_of_death: newPerpetrator.value.place_of_death || null,
        place_of_detention: newPerpetrator.value.place_of_detention || [],
        place_of_residence: newPerpetrator.value.place_of_residence || null,
        occupation: newPerpetrator.value.occupation || null,
        type: "Q5", // Add type field to identify this as a person
        role: "perpetrator", // Explicitly set the role
    });

    showNewPerpetratorForm.value = false;
    resetNewPerpetratorForm();
};

const startAddingAnotherPerpetrator = () => {
    showNewPerpetratorForm.value = false;
    resetNewPerpetratorForm();
    perpetratorsSearchResults.value = [];
};

const canAddNewPerpetrator = computed(() => {
    return (
        newPerpetrator.value.firstName.trim() &&
        newPerpetrator.value.lastName.trim()
    );
});

const selectPerpetrator = (person) => {
    if (!selectedPerpetrators.value.some((p) => p.id === person.id)) {
        console.log("Adding perpetrator with full data:", person);

        // Create a complete perpetrator object that preserves the Wikidata Q number
        const perpetratorObj = {
            id: person.id, // CRITICAL - Preserve Wikidata Q number
            label: person.label,
            title: person.label, // Ensure title is set
            description: person.description,
            birth: person.birth,
            death: person.death,
            nationality: person.nationality || null,
            type: "Q5", // Ensure type is set for existing entities
            role: "perpetrator", // Ensure role is set for existing entities
            // Include additional fields that might be needed
            firstName: person.firstName || "",
            lastName: person.lastName || "",
            birthDate: person.birthDate || null,
            deathDate: person.deathDate || null,
            isOrganization: person.isOrganization || false,
        };

        // Log the object to be added to the array
        console.log("Perpetrator object to be added:", perpetratorObj);

        selectedPerpetrators.value.push(perpetratorObj);
        console.log("Added perpetrator with ID:", person.id);

        // Verify the object was properly added with id
        console.log("Updated perpetrators array:", selectedPerpetrators.value);
    }
    perpetratorsSearchResults.value = []; // Clear search results
};

const removePerpetrator = (perpetratorId) => {
    selectedPerpetrators.value = selectedPerpetrators.value.filter(
        (v) => (v.id || v.tempId) !== perpetratorId,
    );
};

const updateVictim = (person) => {
    const index = selectedVictims.value.findIndex(
        (v) => (v.id || v.tempId) === (person.id || person.tempId),
    );

    if (index !== -1) {
        // Create updated person object with all fields
        const updatedPerson = {
            ...person,
            tempId: person.tempId || selectedVictims.value[index].tempId,
            id: person.id || selectedVictims.value[index].id,
        };

        // Replace the person at the found index
        selectedVictims.value.splice(index, 1, updatedPerson);

        console.log(
            "Updated victim with nationality:",
            updatedPerson.nationality,
        );
    }
};

const updatePerpetrator = (person) => {
    const index = selectedPerpetrators.value.findIndex(
        (p) => (p.id || p.tempId) === (person.id || person.tempId),
    );

    if (index !== -1) {
        // Create updated person object with all fields
        const updatedPerson = {
            ...person,
            tempId: person.tempId || selectedPerpetrators.value[index].tempId,
            id: person.id || selectedPerpetrators.value[index].id,
        };

        // Replace the person at the found index
        selectedPerpetrators.value.splice(index, 1, updatedPerson);

        console.log(
            "Updated perpetrator with nationality:",
            updatedPerson.nationality,
        );
    }
};

const searchCause = debounce(async (query) => {
    if (!query || query.length < 2) {
        causeSearchResults.value = [];
        return;
    }

    lastCauseSearchQuery.value = query;

    try {
        const response = await axios.get("/api/wikidata/search/cause", {
            params: {
                query,
            },
        });
        causeSearchResults.value = response.data;
    } catch (error) {
        console.error("Fehler bei der Ursachensuche:", error);
        causeSearchResults.value = [];
    }
}, 300);

const selectCause = (cause) => {
    if (!selectedCauses.value.some((item) => item.id === cause.id)) {
        selectedCauses.value.push({
            id: cause.id,
            label: cause.label,
            description: cause.description,
        });
    }
    causeSearchResults.value = [];
    lastCauseSearchQuery.value = "";
};

const removeCause = (causeId) => {
    selectedCauses.value = selectedCauses.value.filter(
        (cause) => cause.id !== causeId,
    );
};

// Clear submission state when router changes
const resetSubmissionState = () => {
    isSubmitting.value = false;
    error.value = null;
    console.log("Reset submission state due to router navigation");
};

// Handle submission from SimplePersonForm
const submitPersonForm = async (formData) => {
    console.log("Received form data from SimplePersonForm:", formData);

    if (isSubmitting.value) {
        console.log(
            "Form submission already in progress, ignoring duplicate call",
        );
        return;
    }

    // Make sure any previous error is cleared
    error.value = null;
    isSubmitting.value = true;

    // Set a timeout to reset isSubmitting in case of hanging request
    const submissionTimeout = setTimeout(() => {
        if (isSubmitting.value) {
            console.log(
                "Safety timeout: resetting submission state after 30 seconds",
            );
            isSubmitting.value = false;
            error.value =
                "Die Anfrage hat zu lange gedauert. Bitte versuchen Sie es erneut.";
            console.warn("Submission timed out after 30 seconds");
        }
    }, 30000); // 30 second timeout

    try {
        // Validate that the data structure is correct
        // Make sure person data is only in victims or perpetrators arrays
        if (formData.type === "Q5") {
            console.log(
                "FORM DATA BEFORE CLEANING:",
                JSON.stringify(formData, null, 2),
            );

            // Process arrays first
            // Helper function to normalize field to array format
            const normalizeField = (field) => {
                if (!field) return null;
                if (typeof field === "string") return [{ id: field }];
                if (typeof field === "object" && !Array.isArray(field))
                    return [field];
                return field;
            };

            // Process both victims and perpetrators arrays to ensure fields are in correct format
            if (formData.victims && Array.isArray(formData.victims)) {
                formData.victims.forEach((person) => {
                    if (person.gender)
                        person.gender = normalizeField(person.gender);
                    // Don't normalize nationality - keep as string
                    if (person.place_of_birth)
                        person.place_of_birth = normalizeField(
                            person.place_of_birth,
                        );
                    if (person.place_of_death)
                        person.place_of_death = normalizeField(
                            person.place_of_death,
                        );
                    if (person.place_of_detention)
                        person.place_of_detention = normalizeField(
                            person.place_of_detention,
                        );
                    if (person.place_of_residence)
                        person.place_of_residence = normalizeField(
                            person.place_of_residence,
                        );
                    if (person.occupation)
                        person.occupation = normalizeField(person.occupation);
                    if (person.employer)
                        person.employer = normalizeField(person.employer);
                });
            }

            if (formData.perpetrators && Array.isArray(formData.perpetrators)) {
                formData.perpetrators.forEach((person) => {
                    if (person.gender)
                        person.gender = normalizeField(person.gender);
                    // Don't normalize nationality - keep as string
                    if (person.place_of_birth)
                        person.place_of_birth = normalizeField(
                            person.place_of_birth,
                        );
                    if (person.place_of_death)
                        person.place_of_death = normalizeField(
                            person.place_of_death,
                        );
                    if (person.place_of_detention)
                        person.place_of_detention = normalizeField(
                            person.place_of_detention,
                        );
                    if (person.place_of_residence)
                        person.place_of_residence = normalizeField(
                            person.place_of_residence,
                        );
                    if (person.occupation)
                        person.occupation = normalizeField(person.occupation);
                    if (person.employer)
                        person.employer = normalizeField(person.employer);
                });
            }

            // Now clear the root level fields
            formData.gender = null;
            formData.nationality = null;
            formData.place_of_birth = null;
            formData.place_of_death = null;
            formData.place_of_detention = null;
            formData.place_of_residence = null;
            formData.occupation = null;
            formData.employer = null;
            formData.image = null;
            formData.imageUrl = null;
            formData.imageFilename = null;

            console.log(
                "Ensured person data is only in arrays, not at root level",
            );
            console.log("Victims:", JSON.stringify(formData.victims, null, 2));
            console.log(
                "Perpetrators:",
                JSON.stringify(formData.perpetrators, null, 2),
            );

            // Make sure victims and perpetrators arrays are properly initialized
            // Ensure arrays are properly initialized
            if (!Array.isArray(formData.victims)) {
                formData.victims = formData.victims ? [formData.victims] : [];
                console.log(
                    "Fixed victims array:",
                    JSON.stringify(formData.victims, null, 2),
                );
            }

            if (!Array.isArray(formData.perpetrators)) {
                formData.perpetrators = formData.perpetrators
                    ? [formData.perpetrators]
                    : [];
                console.log(
                    "Fixed perpetrators array:",
                    JSON.stringify(formData.perpetrators, null, 2),
                );
            }

            // Double check that all person objects have properly formatted fields
            formData.victims.forEach((person) => {
                console.log("Checking victim fields:", person);
                ["place_of_detention", "employer"].forEach((field) => {
                    // Ensure multi-value fields are always arrays
                    if (!person[field]) {
                        // Initialize as empty array if null/undefined
                        person[field] = [];
                    } else if (!Array.isArray(person[field])) {
                        // Convert string to array with one item
                        console.log(`Converting ${field} to array format`);
                        person[field] = [person[field]];
                    }
                });

                // Keep nationality as a string (like gender)
                console.log("Nationality:", person.nationality);

                // Handle single-value fields (ensure they're strings, not arrays)
                [
                    "place_of_birth",
                    "place_of_death",
                    "place_of_residence",
                    "occupation",
                ].forEach((field) => {
                    if (Array.isArray(person[field])) {
                        // Extract the first value if it's an array
                        console.log(`Converting ${field} array to string`);
                        if (person[field].length > 0) {
                            if (typeof person[field][0] === "string") {
                                person[field] = person[field][0];
                            } else if (
                                person[field][0] &&
                                person[field][0].id
                            ) {
                                person[field] = person[field][0].id;
                            } else {
                                person[field] = null;
                            }
                        } else {
                            person[field] = null;
                        }
                    }
                });
                // Ensure gender is always a string
                if (person.gender && Array.isArray(person.gender)) {
                    console.log("Converting gender array to string");
                    if (person.gender.length > 0) {
                        if (typeof person.gender[0] === "string") {
                            person.gender = person.gender[0];
                        } else if (person.gender[0] && person.gender[0].id) {
                            person.gender = person.gender[0].id;
                        }
                    } else {
                        person.gender = null;
                    }
                }
            });

            formData.perpetrators.forEach((person) => {
                console.log("Checking perpetrator fields:", person);
                ["place_of_detention", "employer"].forEach((field) => {
                    // Ensure multi-value fields are always arrays
                    if (!person[field]) {
                        // Initialize as empty array if null/undefined
                        person[field] = [];
                    } else if (!Array.isArray(person[field])) {
                        // Convert string to array with one item
                        console.log(`Converting ${field} to array format`);
                        person[field] = [person[field]];
                    }
                });

                // Keep nationality as a string (like gender)
                console.log("Nationality:", person.nationality);

                // Handle single-value fields (ensure they're strings, not arrays)
                [
                    "place_of_birth",
                    "place_of_death",
                    "place_of_residence",
                    "occupation",
                ].forEach((field) => {
                    if (Array.isArray(person[field])) {
                        // Extract the first value if it's an array
                        console.log(`Converting ${field} array to string`);
                        if (person[field].length > 0) {
                            if (typeof person[field][0] === "string") {
                                person[field] = person[field][0];
                            } else if (
                                person[field][0] &&
                                person[field][0].id
                            ) {
                                person[field] = person[field][0].id;
                            } else {
                                person[field] = null;
                            }
                        } else {
                            person[field] = null;
                        }
                    }
                });
                // Ensure gender is always a string
                if (person.gender && Array.isArray(person.gender)) {
                    console.log("Converting gender array to string");
                    if (person.gender.length > 0) {
                        if (typeof person.gender[0] === "string") {
                            person.gender = person.gender[0];
                        } else if (person.gender[0] && person.gender[0].id) {
                            person.gender = person.gender[0].id;
                        }
                    } else {
                        person.gender = null;
                    }
                }
            });

            console.log(
                "FORM DATA AFTER CLEANING:",
                JSON.stringify(formData, null, 2),
            );
        }

        // Ensure we have a CSRF token
        let csrfToken = document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute("content");

        if (!csrfToken) {
            console.warn("CSRF token not found, using fallback method");
            // Try to get from cookies as fallback
            const match = document.cookie.match(
                new RegExp("(^|;\\s*)XSRF-TOKEN=([^;]*)"),
            );
            csrfToken = match ? decodeURIComponent(match[2]) : "";
        }

        // Perform final validation before sending
        console.log("Final form data check:");
        if (formData.victims && formData.victims.length > 0) {
            formData.victims.forEach((victim, index) => {
                console.log(`Victim ${index} gender:`, victim.gender);
                // Ensure gender is a string
                if (victim.gender && Array.isArray(victim.gender)) {
                    victim.gender =
                        typeof victim.gender[0] === "string"
                            ? victim.gender[0]
                            : victim.gender[0]?.id || null;
                }
            });
        }

        if (formData.perpetrators && formData.perpetrators.length > 0) {
            formData.perpetrators.forEach((perp, index) => {
                console.log(`Perpetrator ${index} gender:`, perp.gender);
                // Ensure gender is a string
                if (perp.gender && Array.isArray(perp.gender)) {
                    perp.gender =
                        typeof perp.gender[0] === "string"
                            ? perp.gender[0]
                            : perp.gender[0]?.id || null;
                }
            });
        }

        console.log("Submitting form data:", JSON.stringify(formData, null, 2));
        console.log("CSRF Token:", csrfToken ? "Found" : "Missing");

        try {
            const response = await axios.post(
                "/api/wikidata/entities",
                formData,
                {
                    headers: {
                        "X-CSRF-TOKEN": csrfToken,
                        "Content-Type": "application/json",
                        Accept: "application/json",
                    },
                },
            );

            if (response.data.success) {
                const requestId =
                    formData.statusToken || response.data.requestId;
                localStorage.setItem("entryStatusToken", requestId);

                console.log("Submission successful with ID:", requestId);
                console.log("Response data:", response.data);

                // Redirect to thank-you page
                router.push({
                    name: "thank-you",
                    params: { requestId },
                    query: { token: requestId },
                });
            } else {
                if (ogtGlobals?.showToast) {
                    ogtGlobals.showToast(
                        response.data.message ||
                            "Fehler bei der Erstellung des Eintrags",
                        "error",
                    );
                } else {
                    console.error(
                        response.data.message ||
                            "Fehler bei der Erstellung des Eintrags",
                    );
                }
            }
        } catch (axiosError) {
            throw axiosError; // Re-throw to be caught by outer catch block
        }
    } catch (error) {
        console.error("Outer try/catch error in resetForm:", error);
        console.error("Full error details:", {
            message: error.message,
            response: error.response?.data,
            status: error.response?.status,
            statusText: error.response?.statusText,
            stack: error.stack,
        });

        // Try to log the response data in a more detailed way
        if (error.response?.data) {
            try {
                console.error(
                    "Response data details:",
                    JSON.stringify(error.response.data, null, 2),
                );
            } catch (e) {
                console.error("Could not stringify response data:", e);
            }
        }
        let errorMessage = "Ein unbekannter Fehler ist aufgetreten";

        // Try to get a more specific error message
        if (error.response?.data?.message) {
            errorMessage = error.response.data.message;
        } else if (error.response?.data?.error) {
            errorMessage = error.response.data.error;
        } else if (error.message) {
            errorMessage = error.message;
        }

        // Check for common server errors
        if (error.response?.status === 500) {
            errorMessage =
                "Server-Fehler: Bitte kontaktieren Sie den Administrator (500)";
            console.error("Server 500 error details:", error.response?.data);

            // Add a more specific error message if possible
            if (
                typeof error.response?.data === "string" &&
                error.response.data.includes("duplicate")
            ) {
                errorMessage =
                    "Server-Fehler: Möglicherweise gibt es doppelte Feldwerte in der Anfrage";
            }

            // Additional check for data format errors
            if (
                error.response?.data?.message &&
                (error.response.data.message.includes("array") ||
                    error.response.data.message.includes("gender"))
            ) {
                errorMessage = `Datenformat-Fehler: ${error.response.data.message}`;
                console.error(
                    "Data format error details:",
                    error.response.data,
                );
            }

            // Try to get more detailed error from Laravel debug info
            if (error.response?.data?.message) {
                console.error(
                    "Laravel error message:",
                    error.response.data.message,
                );
            }
            if (error.response?.data?.exception) {
                console.error("Exception:", error.response.data.exception);
            }
            if (error.response?.data?.file) {
                console.error(
                    "File:",
                    error.response.data.file,
                    "Line:",
                    error.response.data.line,
                );
            }
            if (error.response?.data?.trace) {
                console.error(
                    "First trace item:",
                    error.response.data.trace[0],
                );
            }
        } else if (error.response?.status === 422) {
            errorMessage =
                "Validierungsfehler: Bitte überprüfen Sie Ihre Eingaben (422)";

            // Try to extract validation errors
            if (error.response?.data?.errors) {
                const errors = error.response.data.errors;
                const errorFields = Object.keys(errors);
                if (errorFields.length > 0) {
                    errorMessage += ": " + errorFields.join(", ");
                }
            }
        }

        if (ogtGlobals?.showToast) {
            ogtGlobals.showToast(`Fehler: ${errorMessage}`, "error");
        } else {
            console.error(`Fehler: ${errorMessage}`);
        }

        // If there's a specific issue with person form, try to reset it
        if (
            personFormRef.value &&
            typeof personFormRef.value.resetSubmitting === "function"
        ) {
            personFormRef.value.resetSubmitting();
        }
    } finally {
        isSubmitting.value = false;
        if (submissionTimeout) {
            clearTimeout(submissionTimeout);
        }

        // Also reset the child component's loading state if it exists
        if (
            personFormRef.value &&
            typeof personFormRef.value.resetSubmitting === "function"
        ) {
            try {
                personFormRef.value.resetSubmitting();
                console.log(
                    "Successfully reset child component's isSubmitting state",
                );
            } catch (e) {
                console.error(
                    "Could not reset child component's isSubmitting state",
                    e,
                );
            }
        }

        // If there was an error, make sure the user can still interact with the form
        error.value = error.value || null;
    }
};

// Reset form and submit to API
const resetForm = async () => {
    console.log("resetForm called with formType:", formsType.value);
    error.value = null;
    isSubmitting.value = true;

    // Trigger validation based on form type
    if (formsType.value === "Q6983405") {
        // Validate Events form
        if (
            eventsFormRef.value &&
            typeof eventsFormRef.value.validateForm === "function"
        ) {
            eventsFormRef.value.validateForm();
        }
    } else if (formsType.value === "Q5") {
        // Validate Person form
        if (
            personFormRef.value &&
            typeof personFormRef.value.validateForm === "function"
        ) {
            personFormRef.value.validateForm();
        }
    }

    try {
        console.log("Validating form with type:", formsType.value);
        console.log("Current title:", formsTitle.value);
        console.log("Current description:", formsDescription.value);

        // Debug logging for perpetrators and victims
        if (
            selectedPerpetrators.value &&
            selectedPerpetrators.value.length > 0
        ) {
            console.log("Selected perpetrators:", selectedPerpetrators.value);
            // Check for id field in each perpetrator
            selectedPerpetrators.value.forEach((perp, i) => {
                console.log(`Perpetrator ${i} ID:`, perp.id);
            });
        }

        if (selectedVictims.value && selectedVictims.value.length > 0) {
            console.log("Selected victims:", selectedVictims.value);
            // Check for id field in each victim
            selectedVictims.value.forEach((victim, i) => {
                console.log(`Victim ${i} ID:`, victim.id);
            });
        }

        const missingFields = [];

        if (!(formsTitle.value || "").trim()) {
            missingFields.push("Titel");
        }

        if (!(formsDescription.value || "").trim()) {
            missingFields.push("Kurzbeschreibung");
        }

        // Common validations for all form types ended here

        // Form-specific validations below

        if (formsType.value === "Q6983405") {
            // Event-specific validations
            if (!selectedLocation.value) {
                missingFields.push("Standort");
            }

            // Pru00fcfe, ob Zeitpunkt/Zeitraum angegeben wurde
            if (
                (eventTimeType.value === "point" && !eventDate.value) ||
                (eventTimeType.value === "range" &&
                    (!eventStartDate.value || !eventEndDate.value))
            ) {
                missingFields.push("Zeitpunkt / Zeitraum");
            }

            // Prüfe, ob mindestens ein Täter vorhanden ist
            if (
                !selectedPerpetrators.value ||
                selectedPerpetrators.value.length === 0
            ) {
                missingFields.push("Täter*In");
            }
        } else if (formsType.value === "Q5") {
            // Person-specific validations
            // These are mostly handled in the PersonForm component
            // We'll rely on the validation there
        }

        // Prüfe, ob mindestens eine Quelle vorhanden ist
        if (
            (formsType.value === "Q6983405" ||
                formsType.value === "Q5" ||
                formsType.value === "Q5003624") &&
            (!selectedSources.value || selectedSources.value.length === 0)
        ) {
            missingFields.push("Quellen");
        }

        if (missingFields.length) {
            const errorMsg = `Fehlende Pflichtfelder: ${missingFields.join(", ")}`;
            error.value = errorMsg;
            if (ogtGlobals?.showToast) {
                ogtGlobals.showToast(errorMsg, "error");
            } else {
                console.error(errorMsg);
            }
            isSubmitting.value = false;

            // Also reset the child component's loading state
            if (
                personFormRef.value &&
                typeof personFormRef.value.resetSubmitting === "function"
            ) {
                try {
                    personFormRef.value.resetSubmitting();
                } catch (e) {
                    console.error(
                        "Could not reset child component's isSubmitting state",
                        e,
                    );
                }
            }
            return;
        }

        let coordinates = null;
        if (selectedLocation.value) {
            coordinates = `${selectedLocation.value.lat}, ${selectedLocation.value.lng}`;
        }

        const csrfToken = document
            .querySelector('meta[name="csrf-token"]')
            .getAttribute("content");

        // Generate a secure random token for the entry (will be used for the status page URL)
        const generateSecureToken = () => {
            const array = new Uint8Array(24); // 24 bytes will generate a reasonably long string
            window.crypto.getRandomValues(array);
            return Array.from(array, (byte) =>
                byte.toString(16).padStart(2, "0"),
            ).join("");
        };

        // Force preserve IDs in all perpetrators and victims
        if (
            selectedPerpetrators.value &&
            selectedPerpetrators.value.length > 0
        ) {
            selectedPerpetrators.value.forEach((perp) => {
                // If an ID is present, ensure it's properly set
                if (perp.id && perp.id.startsWith("Q")) {
                    console.log("Preserving perpetrator Wikidata ID:", perp.id);
                    // Make sure the ID field stays intact through JSON serialization
                    perp.id = perp.id;
                }
            });
        }

        // Do the same for victims
        if (selectedVictims.value && selectedVictims.value.length > 0) {
            selectedVictims.value.forEach((victim) => {
                if (victim.id && victim.id.startsWith("Q")) {
                    console.log("Preserving victim Wikidata ID:", victim.id);
                    victim.id = victim.id;
                }
            });
        }

        const secureToken = generateSecureToken();

        // Function to prepare form data for submission - helps avoid circular references
        const prepareFormData = () => {
            console.log("Preparing form data for type:", formsType.value);

            // Base form data for all entry types
            const baseData = {
                title: formsTitle.value?.trim() || "",
                description: formsDescription.value?.trim() || "",
                type: formsType.value,
                sources: selectedSources.value || [],
                statusToken: secureToken,
            };

            if (formsType.value === "Q5") {
                // Determine role from props
                const role = props.role || "standalone";
                console.log(`Person submission - role: ${role}`);

                // Get person data from form if available
                let personDetails = {};
                if (
                    personFormRef.value &&
                    personFormRef.value.personData &&
                    personFormRef.value.personData.value
                ) {
                    personDetails = { ...personFormRef.value.personData.value };
                }

                // Base person object with properly formatted name
                const personObject = {
                    firstName: personDetails.firstName || "",
                    lastName: personDetails.lastName || "",
                    title: baseData.title,
                    description: baseData.description,
                    type: "Q5",
                    role: role,
                    gender: personDetails.gender || null,
                    nationality: personDetails.nationality || null,
                    birthDate: personDetails.birthDate || null,
                    deathDate: personDetails.deathDate || null,
                    birthName: personDetails.birthName || "",
                    place_of_birth: personDetails.place_of_birth || null,
                    place_of_death: personDetails.place_of_death || null,
                    place_of_detention: personDetails.place_of_detention || [],
                    place_of_residence:
                        personDetails.place_of_residence || null,
                    occupation: personDetails.occupation || null,
                };

                // For a standalone person entry
                const personData = {
                    ...baseData,
                    is_standalone_person: role === "standalone",
                    // Add fields from person details
                    gender: personObject.gender,
                    nationality: personObject.nationality,
                    place_of_birth: personObject.place_of_birth,
                    place_of_death: personObject.place_of_death,
                    place_of_detention: personObject.place_of_detention,
                    occupation: personObject.occupation,
                    // Store the full person object for reference
                    person: personObject,
                };

                // Add to the appropriate array based on role
                if (role === "victim") {
                    // Ensure ID is preserved
                    if (personObject.id && personObject.id.startsWith("Q")) {
                        console.log(
                            "Preserving victim ID in form submission:",
                            personObject.id,
                        );
                        // Ensure the ID is set directly on the object, not as a nested property
                        personObject = {
                            ...personObject,
                            id: personObject.id,
                            wikidata_id: personObject.id, // Also set the wikidata_id field
                        };
                    }
                    personData.victims = [personObject];
                } else if (role === "perpetrator") {
                    // Ensure ID is preserved
                    if (personObject.id && personObject.id.startsWith("Q")) {
                        console.log(
                            "Preserving perpetrator ID in form submission:",
                            personObject.id,
                        );
                        // Ensure the ID is set directly on the object, not as a nested property
                        personObject = {
                            ...personObject,
                            id: personObject.id,
                            wikidata_id: personObject.id, // Also set the wikidata_id field
                        };
                    }
                    personData.perpetrators = [personObject];
                }

                console.log(`Person form data being submitted:`, personData);
                return personData;
            } else {
                // For events, memorials, and other entity types
                let formData = {
                    ...baseData,
                    coordinates: coordinates,
                    coordinates_qualifier: locationQualifier.value,
                    victims: selectedVictims.value,
                    perpetrators: selectedPerpetrators.value,
                    eventTimeType: eventTimeType.value,
                    eventDate: eventDate.value,
                    eventDateQualifier: eventDateQualifier.value,
                    eventStartDate: eventStartDate.value,
                    eventStartDateQualifier: eventStartDateQualifier.value,
                    eventEndDate: eventEndDate.value,
                    eventEndDateQualifier: eventEndDateQualifier.value,
                    victimCount: victimCount.value,
                    survivorCount: survivorCount.value,
                    deathCount: deathCount.value,
                    arrestCount: arrestCount.value,
                    victimCountQualifier: victimCountQualifier.value,
                    survivorCountQualifier: survivorCountQualifier.value,
                    deathCountQualifier: deathCountQualifier.value,
                    arrestCountQualifier: arrestCountQualifier.value,
                    causes: selectedCauses.value,
                    imageUrl: imageUrl.value,
                    imageFilename: imageFilename.value,
                    isStandalonePerson: false,
                };

                // Double-check perpetrators have their ids preserved
                if (formData.perpetrators && formData.perpetrators.length > 0) {
                    formData.perpetrators.forEach((perp, i) => {
                        if (perp.id && perp.id.startsWith("Q")) {
                            console.log(
                                `Event form - Perpetrator ${i} has ID: ${perp.id}`,
                            );
                            // Ensure wikidata_id is set from id if not already present
                            if (!perp.wikidata_id) {
                                perp.wikidata_id = perp.id;
                                console.log(
                                    `Preserving perpetrator Wikidata ID: ${perp.id}`,
                                );
                            }
                        }
                    });
                }

                // Also check victims to ensure wikidata_id is set
                if (formData.victims && formData.victims.length > 0) {
                    formData.victims.forEach((victim, i) => {
                        if (victim.id && victim.id.startsWith("Q")) {
                            console.log(
                                `Event form - Victim ${i} has ID: ${victim.id}`,
                            );
                            // Ensure wikidata_id is set from id if not already present
                            if (!victim.wikidata_id) {
                                victim.wikidata_id = victim.id;
                                console.log(
                                    `Preserving victim Wikidata ID: ${victim.id}`,
                                );
                            }
                        }
                    });
                }

                // Add memorial-specific fields if it's a memorial place
                if (formsType.value === "Q5003624" && memorialFormRef.value) {
                    console.log("Adding memorial-specific fields to form data");

                    try {
                        // Check if getData method exists
                        if (
                            typeof memorialFormRef.value.getData === "function"
                        ) {
                            const memorialData =
                                memorialFormRef.value.getData();

                            // Add the memorial-specific fields to the form data
                            formData = {
                                ...formData,
                                commemorates: memorialData.commemorates,
                                operator: memorialData.operator,
                                opening_date: memorialData.opening_date,
                                opening_date_qualifier:
                                    memorialData.opening_date_qualifier,
                                address: memorialData.address,
                                website: memorialData.website,
                            };

                            console.log("Memorial form data:", memorialData);
                        } else {
                            // Get data directly from refs if getData is not available
                            console.log(
                                "getData method not found, accessing field values directly",
                            );

                            // Access the ref values directly
                            if (memorialFormRef.value.commemoratesValue) {
                                formData.commemorates =
                                    memorialFormRef.value.commemoratesValue;
                            }
                            if (memorialFormRef.value.operatorValue) {
                                formData.operator =
                                    memorialFormRef.value.operatorValue;
                            }
                            if (memorialFormRef.value.openingDateValue) {
                                formData.opening_date =
                                    memorialFormRef.value.openingDateValue;
                            }
                            if (
                                memorialFormRef.value.openingDateQualifierValue
                            ) {
                                formData.opening_date_qualifier =
                                    memorialFormRef.value.openingDateQualifierValue;
                            }
                            if (memorialFormRef.value.addressValue) {
                                formData.address =
                                    memorialFormRef.value.addressValue;
                            }
                            if (memorialFormRef.value.websiteValue) {
                                formData.website =
                                    memorialFormRef.value.websiteValue;
                            }
                        }
                    } catch (error) {
                        console.error("Error getting memorial data:", error);
                        // Continue with basic form data
                    }
                }

                return formData;
            }
        };

        // Debug log for submission data
        console.log("Form type:", formsType.value);

        // Only log location qualifier for event forms
        if (formsType.value === "Q6983405") {
            console.log("Submitting location qualifier:", {
                locationQualifierValue: locationQualifier.value,
                rawValue:
                    typeof locationQualifier.value === "object"
                        ? JSON.stringify(locationQualifier.value)
                        : locationQualifier.value,
            });
        }

        // Get prepared form data
        const formData = prepareFormData();
        console.log("Final formData to be submitted:", formData);

        // Make sure each perpetrator has the id field intact
        if (formData.perpetrators && formData.perpetrators.length > 0) {
            formData.perpetrators.forEach((perp, i) => {
                if (perp.id && perp.id.startsWith("Q")) {
                    console.log(
                        `Final check - Perpetrator ${i} has ID: ${perp.id}`,
                    );
                }
            });
        }

        // Also check victims in final verification
        if (formData.victims && formData.victims.length > 0) {
            formData.victims.forEach((victim, i) => {
                if (victim.id && victim.id.startsWith("Q")) {
                    console.log(
                        `Final check - Victim ${i} has ID: ${victim.id}`,
                    );
                }
            });
        }

        // Complete log of submission data
        console.log("Full submission data:", formData);
        console.log("Form type:", formsType.value);
        console.log("Is person form:", formsType.value === "Q5");

        try {
            // Log the full request payload to verify perpetrator data
            console.log(
                "Full request payload:",
                JSON.stringify(formData, null, 2),
            );

            const response = await axios.post(
                "/api/wikidata/entities",
                formData,
                {
                    headers: {
                        "X-CSRF-TOKEN": csrfToken,
                    },
                },
            );

            if (response.data.success) {
                // Debug logging
                console.log(
                    "Submission successful! Response data:",
                    response.data,
                );
                console.log("Form type that was submitted:", formsType.value);

                if (formsType.value === "Q6983405") {
                    console.log("Submitted qualifier values for event:", {
                        victimCountQualifier: victimCountQualifier.value,
                        survivorCountQualifier: survivorCountQualifier.value,
                        deathCountQualifier: deathCountQualifier.value,
                        arrestCountQualifier: arrestCountQualifier.value,
                    });
                }

                // Use the token as the requestId for the thank-you page
                const requestId = secureToken || response.data.requestId;
                console.log(
                    "Sending to thank-you page with requestId:",
                    requestId,
                );

                // Store token in localStorage for better persistence
                if (requestId) {
                    localStorage.setItem("entryStatusToken", requestId);
                }

                // Redirect to thank-you page with request ID
                router.push({
                    name: "thank-you",
                    params: { requestId: requestId },
                    query: { token: requestId }, // Add as query param for better compatibility
                });
            } else {
                error.value =
                    response.data.message ||
                    "Fehler bei der Erstellung des Eintrags";
            }
        } catch (err) {
            console.error("Fehler bei der API-Anfrage:", err);
            error.value = `Fehler: ${err.response?.data?.message || err.message}`;
        } finally {
            isSubmitting.value = false;
            console.log(
                "Form submission completed with type:",
                formsType.value,
            );
        }
    } catch (outerError) {
        console.error("Outer try/catch error in resetForm:", outerError);
        error.value = `Kritischer Fehler: ${outerError.message}`;
        isSubmitting.value = false;
    }
};

const clearForm = () => {
    formsTitle.value = null;
    formsDescription.value = null;
    selectedLocation.value = null;
    selectedVictims.value = [];
    newVictim.value = {
        firstName: "",
        lastName: "",
        birthDate: null,
        deathDate: null,
        description: "",
        birthName: "",
        isOrganization: false,
        gender: null,
        nationality: null,
        place_of_birth: null,
        place_of_death: null,

        place_of_residence: null,
        occupation: null,
        imageUrl: null,
        imageFilename: null,
        place_of_detention: [],
    };
    showNewVictimForm.value = false;

    selectedPerpetrators.value = [];
    newPerpetrator.value = {
        firstName: "",
        lastName: "",
        birthDate: null,
        deathDate: null,
        description: "",
        birthName: "",
        isOrganization: false,
        gender: null,
        nationality: null,
        place_of_birth: null,
        place_of_death: null,
        place_of_detention: [],
        place_of_residence: null,
        occupation: null,
        imageUrl: null,
        imageFilename: null,
    };
    showNewPerpetratorForm.value = false;

    eventTimeType.value = "point";
    eventDate.value = null;
    eventStartDate.value = null;
    eventEndDate.value = null;

    victimCount.value = null;
    survivorCount.value = null;
    deathCount.value = null;
    arrestCount.value = null;
    victimCountQualifier.value = "";
    survivorCountQualifier.value = "";
    deathCountQualifier.value = "";
    arrestCountQualifier.value = "";
    causeSearchResults.value = [];
    selectedCauses.value = [];
    lastCauseSearchQuery.value = "";
    locationQualifier.value = "";

    mapZoom.value = 8;
    mapCenter.value = [52.8, 9.0];
    locationSearch.value = "";

    imageUrl.value = null;
    imageFilename.value = null;
    selectedSources.value = [];
};

watch(currentStep, (newStep) => {
    if (newStep === 2) {
        // If switching to step 2 (person form) ensure we have a title to prevent infinite loops
        if (
            formsType.value === "Q5" &&
            (!formsTitle.value || !formsTitle.value.trim())
        ) {
            console.log("Setting default title for person form");
            formsTitle.value = "Neue Person";
        }

        setTimeout(() => {
            reinitializeMap();
        }, 300);
    }
});

// Lifecycle hooks
onMounted(() => {
    window.addEventListener("resize", setFreeClientWidth);

    checkScreenSize();
    window.addEventListener("resize", checkScreenSize);

    // Set up a watcher to reset state when route changes
    const route = useRoute();
    watch(
        () => route.path,
        (newPath, oldPath) => {
            if (oldPath === "/eintrag-erstellen" && newPath !== "/thank-you") {
                resetSubmissionState();
            }
        },
    );

    applyLeafletCSS();

    // Fix für Leaflet-Icons in Vue
    // Dies ist notwendig, da Leaflet Standard-Icon-Pfade erwartet
    // Siehe https://github.com/vue-leaflet/vue-leaflet/issues/158
    delete L.Icon.Default.prototype._getIconUrl;
    L.Icon.Default.mergeOptions({
        iconRetinaUrl:
            "https://unpkg.com/leaflet@1.7.1/dist/images/marker-icon-2x.png",
        iconUrl: "https://unpkg.com/leaflet@1.7.1/dist/images/marker-icon.png",
        shadowUrl:
            "https://unpkg.com/leaflet@1.7.1/dist/images/marker-shadow.png",
    });

    setTimeout(() => {
        reinitializeMap();
    }, 300);
});

onUnmounted(() => {
    window.removeEventListener("resize", setFreeClientWidth);
    window.removeEventListener("resize", checkScreenSize);

    // Reset submission state when component is unmounted
    resetSubmissionState();
});
</script>

<style>
@import "./entry/styles/form-styles.css";

/* Type Selection Styling */
.info-icon-container {
    position: absolute;
    top: 8px;
    right: 8px;
    z-index: 5;
    cursor: pointer;
    background-color: rgba(255, 255, 255, 0.9);
    border-radius: 50%;
    padding: 4px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
}

.info-icon-container d-lg-none:hover {
    background-color: rgba(33, 150, 243, 0.1);
    transform: scale(1.1);
}

.info-content p {
    margin-bottom: 12px;
    line-height: 1.6;
}

.info-content .info-list {
    margin-bottom: 15px;
    padding-left: 25px;
}

.info-content .info-list li {
    margin-bottom: 8px;
    line-height: 1.5;
}

.type-action-container {
    margin-top: 10px;
    width: 100%;
    min-height: 40px; /* Reserve space for the button */
    position: relative;
}

.type-action-button {
    background-color: #2196f3;
    color: white;
    border: none;
    padding: 8px 14px;
    border-radius: 4px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    width: 100%;
}

.type-action-button:hover {
    background-color: #1976d2;
    box-shadow: 0 3px 6px rgba(0, 0, 0, 0.25);
}

/* Fade transition for button appearance */
.fade-enter-active,
.fade-leave-active {
    transition:
        opacity 0.3s ease,
        transform 0.3s ease;
    position: absolute;
    width: 100%; /* Full width of the container */
    left: 0;
    right: 0;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
    transform: translateY(0);
}

/* Type Selection Styling */
.info-section {
    margin-bottom: 20px;
    padding: 15px;
    background-color: #f9f9f9;
    border-left: 3px solid #2196f3;
    border-radius: 4px;
}

.type-selection-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 15px;
    margin-top: 15px;
}

.type-box {
    display: flex;
    flex-direction: column;
    align-items: center;
    width: 100%;
    padding: 15px;
    gap: 1rem;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease-in-out;
    position: relative;
    overflow: visible; /* Ensure the info icon is visible */
}

.type-box:hover {
    border-color: #2196f3;
    box-shadow: 0 2px 8px rgba(33, 150, 243, 0.2);
}

.type-box.selected {
    border-color: #2196f3;
    background-color: rgba(33, 150, 243, 0.1);
    box-shadow: 0 3px 10px rgba(33, 150, 243, 0.3);
}

.type-radio {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
}

.type-icon {
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    height: 60px;
}

.type-icon img {
    height: 100%;
    width: auto;
}

.forms fieldset div.type-label {
    font-weight: 500;
    text-align: center;
    margin-top: 0;
}

.forms fieldset div.type-data {
    align-items: unset;
    flex-direction: column;
    margin-top: 0;
}

.forms fieldset div.type-description {
    margin-top: 0.5rem;
}

@media (max-width: 768px) {
    .type-selection-grid {
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    }
}

.v-select .v-field .v-field__input > input {
    border: 0 !important;
    box-shadow: none;
}

.info-box {
    margin-top: 0;
}

@media (max-width: 768px) {
    .step-container,
    .info-box {
        width: 100%;
    }
}
</style>

<style scoped></style>
