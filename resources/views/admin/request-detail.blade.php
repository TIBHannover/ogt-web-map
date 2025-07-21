@extends('layouts.app')

@php
use App\Http\Controllers\WikidataPropertyController;
use function App\Helpers\formatWikidataProperty;
@endphp

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Anfrage-Details</h1>
        <a href="{{ route('admin.requests') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left me-1"></i> Zurück zur Übersicht
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    <!-- Status-Banner -->
    <div class="alert alert-{{
        $request->status == 'approved' ? 'success' :
        ($request->status == 'rejected' ? 'danger' :
        ($request->status == 'error' || $request->status == 'wikidata_failed' ? 'warning' :
        ($request->status == 'wikidata_published' ? 'info' : 'primary')))
    }} mb-4">
        <h5 class="alert-heading">Status:
            @if($request->status == 'pending')
                In Bearbeitung
            @elseif($request->status == 'approved')
                Genehmigt
            @elseif($request->status == 'rejected')
                Abgelehnt
            @elseif($request->status == 'error')
                Fehler
            @elseif($request->status == 'wikidata_failed')
                Fehler bei Wikidata-Erstellung
            @elseif($request->status == 'wikidata_submitted')
                An Wikidata u00fcbermittelt
            @elseif($request->status == 'wikidata_published')
                In Wikidata veröffentlicht
            @endif
        </h5>

        @if(($request->status == 'approved' || $request->status == 'wikidata_published') && $request->wikidata_id)
            <div class="mt-2 mb-3">
                <a href="https://www.wikidata.org/wiki/{{ $request->wikidata_id }}"
                   target="_blank" class="btn btn-sm btn-light mb-2">
                    Haupt-Wikidata-Eintrag öffnen <i class="bi bi-box-arrow-up-right ms-1"></i>
                </a>
            </div>
        @endif

        @if($request->status == 'rejected' && $request->rejection_reason)
            <div class="mt-2">
                <strong>Ablehnungsgrund:</strong> {{ $request->rejection_reason }}
            </div>
        @endif

        @if(($request->status == 'error' || $request->status == 'wikidata_failed') && $request->error_message)
            <div class="mt-2">
                <strong>Fehlermeldung:</strong> {{ $request->error_message }}
                <form action="/api/wikidata/requests/{{ $request->id }}/retry" method="POST" class="d-inline ml-2">
                    @csrf
                    <input type="hidden" name="redirect" value="{{ route('admin.requests.show', $request->id) }}">
                    <button type="submit" class="btn btn-sm btn-warning">
                        <i class="bi bi-arrow-repeat me-1"></i> Wiederholen
                    </button>
                </form>
            </div>
        @endif
    </div>

    <!-- Detaillierte Entity-Status-Anzeige -->
    @include('admin.partials.entity-status-detail')

    <div class="row">
        <!-- Hauptinformationen -->
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">{{ $request->title }}</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <!-- Typ des Eintrags -->
                        <span class="badge bg-primary me-2">
                            @if($request->type == 'Q5')
                                Person
                            @elseif($request->type == 'Q6983405')
                                Ereignis
                            @elseif($request->type == 'Q43229')
                                Organisation
                            @elseif($request->type == 'Q811979')
                                Architektonisches Struktur
                            @else
                                {{ $request->type }}
                            @endif
                        </span>

                        <!-- Erstellungsdatum -->
                        @if($request->created_at)
                            <span class="badge bg-secondary me-2">
                                Erstellt am: {{ $request->created_at->format('d.m.Y H:i') }}
                            </span>
                        @endif

                        <!-- Ersteller -->
                        <span class="badge bg-secondary">
                            Erstellt von: {{ $request->submitted_by ?? 'Unbekannt' }}
                        </span>
                    </div>

                    <!-- Beschreibung -->
                    <div class="mb-4">
                        <h6>Beschreibung:</h6>
                        <p>{{ $request->description }}</p>
                    </div>

                    <hr>

                    <!-- Koordinaten -->
                    @if($request->coordinates)
                        <div class="mb-4">
                            <h6>Koordinaten:</h6>
                            @php
                                $coords = $request->coordinates;
                                // Mögliche Formate: JSON-Objekt, Array oder Komma-getrennte Strings
                                if (is_string($coords)) {
                                    if (strpos($coords, '{') === 0) {
                                        // JSON-Objekt Format
                                        $coordinates = json_decode($coords);
                                        $lat = $coordinates->lat ?? null;
                                        $lng = $coordinates->lng ?? null;
                                    } elseif (strpos($coords, '[') === 0) {
                                        // JSON-Array Format
                                        $coordinates = json_decode($coords);
                                        $lat = $coordinates[0] ?? null;
                                        $lng = $coordinates[1] ?? null;
                                    } else {
                                        // Komma-getrennte Strings
                                        $parts = explode(',', $coords);
                                        if (count($parts) >= 2) {
                                            $lat = trim($parts[0]);
                                            $lng = trim($parts[1]);
                                        } else {
                                            $lat = null;
                                            $lng = null;
                                        }
                                    }
                                } else {
                                    $lat = null;
                                    $lng = null;
                                }
                            @endphp
                            @if($lat && $lng)
                                <div class="mb-2">
                                    {{ number_format((float)$lat, 6, '.', '') }}, {{ number_format((float)$lng, 6, '.', '') }}
                                    @if(isset($request->coordinates_qualifier) && !empty($request->coordinates_qualifier))
                                        @include('admin.partials.qualifier', ['qualifier' => $request->coordinates_qualifier])
                                    @endif
                                </div>
                                <div style="height: 300px; width: 100%" id="map-container">
                                    <!-- Die Karte wird per JavaScript gerendert -->
                                </div>
                            @else
                                <p>Ungültiges Koordinatenformat: {{ $request->coordinates }}</p>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            <!-- Typspezifische Informationen -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Spezifische Informationen</h5>
                </div>
                <div class="card-body">
                    <!-- Ereignis (Q6983405) -->
                    @if($request->type == 'Q6983405')
                        @if($request->time_point || $request->time_period_start)
                            <div class="mb-3">
                                <strong>
                                    @if($request->time_point) Zeitpunkt: @else Zeitraum: @endif
                                </strong>
                                @if($request->time_point)
                                    <p>
                                        {{ \Carbon\Carbon::parse($request->time_point)->format('d.m.Y') }}
                                        @if($request->time_point_qualifier)
                                            @include('admin.partials.qualifier', ['qualifier' => $request->time_point_qualifier])
                                        @endif
                                    </p>
                                @else
                                    <p>
                                        Von: {{ \Carbon\Carbon::parse($request->time_period_start)->format('d.m.Y') }}
                                        @if($request->time_period_start_qualifier)
                                            @include('admin.partials.qualifier', ['qualifier' => $request->time_period_start_qualifier])
                                        @endif
                                        @if($request->time_period_end)
                                            bis: {{ \Carbon\Carbon::parse($request->time_period_end)->format('d.m.Y') }}
                                            @if($request->time_period_end_qualifier)
                                                @include('admin.partials.qualifier', ['qualifier' => $request->time_period_end_qualifier])
                                            @endif
                                        @endif
                                    </p>
                                @endif
                            </div>
                        @endif

                        @if($request->victim_count || $request->survivor_count || $request->death_count || $request->arrest_count)
                            <div class="mb-3">
                                <strong>Geschädigtenzahlen:</strong>
                                <ul>
                                    @if($request->victim_count)
                                        <li>
                                            Opfer: {{ $request->victim_count }}
                                            @if($request->victim_count_qualifier)
                                                @include('admin.partials.qualifier', ['qualifier' => $request->victim_count_qualifier])
                                            @endif
                                        </li>
                                    @endif
                                    @if($request->survivor_count)
                                        <li>
                                            Überlebende: {{ $request->survivor_count }}
                                            @if($request->survivor_count_qualifier)
                                                @include('admin.partials.qualifier', ['qualifier' => $request->survivor_count_qualifier])
                                            @endif
                                        </li>
                                    @endif
                                    @if($request->death_count)
                                        <li>
                                            Todesfälle: {{ $request->death_count }}
                                            @if($request->death_count_qualifier)
                                                @include('admin.partials.qualifier', ['qualifier' => $request->death_count_qualifier])
                                            @endif
                                        </li>
                                    @endif
                                    @if($request->arrest_count)
                                        <li>
                                            Verhaftungen: {{ $request->arrest_count }}
                                            @if($request->arrest_count_qualifier)
                                                @include('admin.partials.qualifier', ['qualifier' => $request->arrest_count_qualifier])
                                            @endif
                                        </li>
                                    @endif
                                </ul>
                            </div>
                        @endif

                        <!-- Beteiligte Personen (Täter und Geschädigte) -->
                        <!-- --
                        <div class="mb-3">
                            <strong>Debug Information:</strong>
                            <pre class="mt-2 bg-light p-3" style="max-height: 200px; overflow: auto;">Perpetrators: {{ var_export($request->perpetrators, true) }}
Victims: {{ var_export($request->victims, true) }}</pre>
                        </div>
                        -->

                        @php
                            // Parse JSON strings to arrays
                            $parsedPerpetrators = [];
                            $parsedVictims = [];

                            if ($request->perpetrators) {
                                if (is_string($request->perpetrators)) {
                                    try {
                                        $parsedPerpetrators = json_decode($request->perpetrators, true) ?: [];
                                    } catch (\Exception $e) {
                                        // Log error or handle it
                                    }
                                } elseif (is_array($request->perpetrators)) {
                                    $parsedPerpetrators = $request->perpetrators;
                                }
                            }

                            if ($request->victims) {
                                if (is_string($request->victims)) {
                                    try {
                                        $parsedVictims = json_decode($request->victims, true) ?: [];
                                    } catch (\Exception $e) {
                                        // Log error or handle it
                                    }
                                } elseif (is_array($request->victims)) {
                                    $parsedVictims = $request->victims;
                                }
                            }
                        @endphp

                        @if($parsedPerpetrators || $parsedVictims)
                            <div class="mb-5">
                                @if(!empty($parsedPerpetrators))
                                    <div class="mb-3">
                                        <strong>Täter*Innen:</strong>
                                        <div class="list-group mt-2">
                                            @foreach($parsedPerpetrators as $perpetrator)
                                                @include('admin.partials.person-entry', ['person' => $perpetrator, 'type' => 'perpetrator'])
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                @if(!empty($parsedVictims))
                                    <div>
                                        <strong>Geschädigte:</strong>
                                        <div class="list-group mt-2">
                                            @foreach($parsedVictims as $victim)
                                                @include('admin.partials.person-entry', ['person' => $victim, 'type' => 'victim'])
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endif


                        @if($request->selected_causes)
                            <div class="mb-3">
                                <strong>Ursachen der Verfolgung:</strong>
                                @php
                                    // Versuche JSON zu parsen
                                    $causes = json_decode($request->selected_causes, true);
                                    // Wenn es kein gu00fcltiges JSON ist, als Text behandeln
                                    $isJson = json_last_error() === JSON_ERROR_NONE && is_array($causes);
                                @endphp

                                @if($isJson && count($causes) > 0)
                                    <div class="list-group mt-2">
                                        @foreach($causes as $cause)
                                            <div class="list-group-item">
                                                @if(is_array($cause))
                                                    <div class="fw-bold">
                                                        <a href="https://www.wikidata.org/wiki/{{ $cause['id'] }}" target="_blank" class="text-decoration-none">
                                                            {{ WikidataPropertyController::getQLabel($cause['id']) }}
                                                            <i class="bi bi-box-arrow-up-right ms-1 small"></i>
                                                        </a>
                                                    </div>
                                                    @if(isset($cause['description']))
                                                        <div class="text-muted small mt-1">{{ $cause['description'] }}</div>
                                                    @endif
                                                @elseif(is_string($cause))
                                                    {{ $cause }}
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <p>{{ $request->selected_causes }}</p>
                                @endif
                            </div>
                        @endif


                        <!-- Quellenangaben -->
                        @if($request->sources && is_array($request->sources) && count($request->sources) > 0)
                            <div class="mb-3">
                                <h6>Quellenangaben:</h6>
                                <div class="list-group mt-2">
                                    @foreach($request->sources as $source)
                                        <div class="list-group-item">
                                            @if(is_array($source))
                                                <div class="d-flex align-items-center mb-1">
                                                    <!-- Icon je nach Quellentyp -->
                                                    @if(isset($source['type']))
                                                        <div class="me-2">
                                                            @if($source['type'] == 'book')
                                                                <i class="bi bi-book"></i>
                                                            @elseif($source['type'] == 'url')
                                                                <i class="bi bi-link"></i>
                                                            @elseif($source['type'] == 'archive')
                                                                <i class="bi bi-archive"></i>
                                                            @else
                                                                <i class="bi bi-file-text"></i>
                                                            @endif
                                                        </div>
                                                    @endif

                                                    <!-- Titel der Quelle -->
                                                    @if(isset($source['title']))
                                                        <div class="fw-bold">{{ $source['title'] }}</div>
                                                    @endif
                                                </div>

                                                <!-- Beschreibung -->
                                                @if(isset($source['description']))
                                                    <div class="text-muted small mb-1">{{ $source['description'] }}</div>
                                                @endif

                                                <!-- Typenspezifische Details -->
                                                <div class="small">
                                                    @if(isset($source['type']))
                                                        @if($source['type'] == 'book')
                                                            @if(isset($source['pages']))
                                                                <div><strong>Seiten:</strong> {{ $source['pages'] }}</div>
                                                            @endif
                                                            @if(isset($source['id']) && $source['id'] != 'manual')
                                                                <div>
                                                                    <a href="https://www.wikidata.org/wiki/{{ $source['id'] }}" target="_blank">
                                                                        <span class="badge bg-secondary">{{ $source['id'] }}</span>
                                                                        <i class="bi bi-box-arrow-up-right ms-1 small"></i>
                                                                    </a>
                                                                </div>
                                                            @endif
                                                        @elseif($source['type'] == 'url')
                                                            @if(isset($source['url']))
                                                                <div>
                                                                    <a href="{{ $source['url'] }}" target="_blank" class="text-break">
                                                                        {{ $source['url'] }}
                                                                        <i class="bi bi-box-arrow-up-right ms-1 small"></i>
                                                                    </a>
                                                                </div>
                                                            @endif
                                                            @if(isset($source['accessDate']))
                                                                <div><strong>Zugriff am:</strong> {{ \Carbon\Carbon::parse($source['accessDate'])->format('d.m.Y') }}</div>
                                                            @endif
                                                        @elseif($source['type'] == 'archive')
                                                            @if(isset($source['inventoryNumber']))
                                                                <div><strong>Inv. Nr.:</strong> {{ $source['inventoryNumber'] }}</div>
                                                            @endif
                                                            @if(isset($source['id']) && $source['id'] != 'manual')
                                                                <div>
                                                                    <a href="https://www.wikidata.org/wiki/{{ $source['id'] }}" target="_blank">
                                                                        <span class="badge bg-secondary">{{ $source['id'] }}</span>
                                                                        <i class="bi bi-box-arrow-up-right ms-1 small"></i>
                                                                    </a>
                                                                </div>
                                                            @endif
                                                        @endif
                                                    @endif
                                                </div>
                                            @elseif(is_string($source))
                                                {{ $source }}
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endif

                    <!-- Person (Q5) -->
                    @if($request->type == 'Q5')

                        @if($request->date_of_birth || $request->date_of_death)
                            <div class="mb-3">
                                <h6>Lebensdaten:</h6>
                                <p>
                                    @if($request->date_of_birth)
                                        Geboren: {{ \Carbon\Carbon::parse($request->date_of_birth)->format('d.m.Y') }}
                                        @if($request->date_of_birth_qualifier)
                                            @include('admin.partials.qualifier', ['qualifier' => $request->date_of_birth_qualifier])
                                        @endif
                                    @endif
                                    @if($request->date_of_death)
                                        {{ $request->date_of_birth ? ' | ' : '' }}Gestorben: {{ \Carbon\Carbon::parse($request->date_of_death)->format('d.m.Y') }}
                                        @if($request->date_of_death_qualifier)
                                            @include('admin.partials.qualifier', ['qualifier' => $request->date_of_death_qualifier])
                                        @endif
                                    @endif
                                </p>
                            </div>
                        @endif

                        @if($request->occupation)
                            <div class="mb-3">
                                <h6>Beruf:</h6>
                                <p>{{ $request->occupation }}</p>
                            </div>
                        @endif

                        @if($request->nationality)
                            <div class="mb-3">
                                <h6>Nationalität:</h6>
                                <p>{{ $request->nationality }}</p>
                            </div>
                        @endif
                    @endif

                    <!-- Organisation (Q43229) -->
                    @if($request->type == 'Q43229')
                        @if($request->inception || $request->dissolved_date)
                            <div class="mb-3">
                                <h6>Zeitraum:</h6>
                                <p>
                                    @if($request->inception)
                                        Gründung: {{ \Carbon\Carbon::parse($request->inception)->format('d.m.Y') }}
                                        @if($request->inception_qualifier)
                                            <span class="badge bg-info ms-2">
                                                @if($request->inception_qualifier == 'Q5727902')
                                                    Circa
                                                @elseif($request->inception_qualifier == 'Q60070514')
                                                    Ungefähr
                                                @endif
                                            </span>
                                        @endif
                                    @endif
                                    @if($request->dissolved_date)
                                        {{ $request->inception ? ' | ' : '' }}Auflösung: {{ \Carbon\Carbon::parse($request->dissolved_date)->format('d.m.Y') }}
                                        @if($request->dissolved_date_qualifier)
                                            <span class="badge bg-info ms-2">
                                                @if($request->dissolved_date_qualifier == 'Q5727902')
                                                    Circa
                                                @elseif($request->dissolved_date_qualifier == 'Q60070514')
                                                    Ungefähr
                                                @endif
                                            </span>
                                        @endif
                                    @endif
                                </p>
                            </div>
                        @endif

                        @if($request->headquarters_location)
                            <div class="mb-3">
                                <h6>Hauptsitz:</h6>
                                <p>{{ $request->headquarters_location }}</p>
                            </div>
                        @endif
                    @endif
                </div>
            </div>

            <!-- Debug: JSON-Darstellung des Requests -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Debug: JSON-Daten</h5>
                    <button class="btn btn-sm btn-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#collapseDebug" aria-expanded="false" aria-controls="collapseDebug">Anzeigen/Verbergen</button>
                </div>
                <div class="collapse" id="collapseDebug">
                    <div class="card-body">
                        <pre class="bg-light p-3" style="max-height: 400px; overflow-y: auto;">{{ json_encode($request, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                    </div>
                </div>
            </div>

            <!-- Debug: Aufbereitete Wikidata-Daten -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Debug: Aufbereitete Wikidata-Daten (vollständige API-Daten)</h5>
                    <button class="btn btn-sm btn-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#collapseWikidataDebug" aria-expanded="false" aria-controls="collapseWikidataDebug">Anzeigen/Verbergen</button>
                </div>
                <div class="collapse" id="collapseWikidataDebug">
                    <div class="card-body">
                        @php
                            // Wir benötigen den WikidataFormatterModels direkt
                            $formatter = app(\App\Services\Wikidata\WikidataFormatterModels::class);

                            // Deaktiviere Debug-Modus für Formatierung (um vollständige Daten zu erhalten)
                            $formatter->setDebugMode(false);

                            // Relevante Daten extrahieren
                            $requestData = [];
                            $requestArray = $request->toArray();
                            $excludeKeys = ['id', 'created_at', 'updated_at', 'status', 'submitted_by', 'approved_by', 'rejection_reason', 'error_message', 'wikidata_id', 'wikidata_url'];

                            foreach ($requestArray as $key => $value) {
                                if (!in_array($key, $excludeKeys) && !is_null($value)) {
                                    $requestData[$key] = $value;
                                }
                            }

                            // Formatierte Daten für Wikibase vorbereiten
                            $result = $formatter->formatEntityData($requestData, $request->type, false); // false = keine Entities erstellen

                            // Nach unseren Änderungen gibt formatEntityData ein Array mit zwei Schlüsseln zurück
                            if (is_array($result) && isset($result['formattedData'])) {
                                $formattedData = $result['formattedData'];
                                $updatedRequestData = $result['updatedData'];
                            } else {
                                $formattedData = $result; // Fallback für alte Struktur
                                $updatedRequestData = $requestData;
                            }
                        @endphp

                        <div class="mb-4">
                            <h6>Aufbereitete Daten für Wikidata API:</h6>
                            <pre class="bg-light p-3" style="max-height: 400px; overflow-y: auto;">{{ json_encode($formattedData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                        </div>

                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            Diese Daten werden vom System aufbereitet und an die Wikidata API gesendet, wenn die Anfrage genehmigt wird. Die Struktur entspricht den Anforderungen der Wikidata API für die Erstellung neuer Entitäten. In diesem Debug-Modus werden die vollständigen, formatieren Daten angezeigt, inklusive aller Properties (P-Werte) und Claims, die erstellt werden.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Seitenleiste -->
        <div class="col-md-4">
            <!-- Bild -->
            @if($request->image_url)
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Bild</h5>
                    </div>
                    <div class="card-body text-center">
                        <img src="{{ $request->image_url }}"
                             alt="{{ $request->title }}"
                             class="img-fluid mb-2"
                             style="max-height: 250px;">
                        @if($request->image_filename)
                            <div class="text-muted small">{{ $request->image_filename }}</div>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Aktionen -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Aktionen</h5>
                </div>
                <div class="card-body">
                    @if($request->status == 'pending')
                        <div class="d-grid gap-2">
                            <a href="{{ route('admin.requests.edit', $request->id) }}" class="btn btn-primary w-100 mb-2">
                                <i class="bi bi-pencil-square me-1"></i> Bearbeiten
                            </a>

                            <button type="button" class="btn btn-success w-100 mb-2" data-bs-toggle="modal" data-bs-target="#approveModal">
                                <i class="bi bi-check-circle me-1"></i> Genehmigen
                            </button>

                            <button type="button" class="btn btn-danger w-100 mb-2"
                                    data-bs-toggle="modal" data-bs-target="#rejectModal">
                                <i class="bi bi-x-circle me-1"></i> Ablehnen
                            </button>
                        </div>
                    @endif

                    @if($request->status == 'approved')
                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-danger w-100 mb-2"
                                    data-bs-toggle="modal" data-bs-target="#rejectApprovedModal">
                                <i class="bi bi-x-circle me-1"></i> Genehmigung zurückziehen
                            </button>

                            @if($request->wikidata_id)
                                <a href="https://www.wikidata.org/wiki/{{ $request->wikidata_id }}"
                                   target="_blank" class="btn btn-outline-primary w-100 mb-2">
                                    <i class="bi bi-box-arrow-up-right me-1"></i> Wikidata-Eintrag öffnen
                                </a>
                            @endif
                        </div>
                    @endif

                    @if($request->status == 'error')
                        <form action="/api/wikidata/requests/{{ $request->id }}/retry" method="POST">
                            @csrf
                            <input type="hidden" name="redirect" value="{{ route('admin.requests.show', $request->id) }}">
                            <button type="submit" class="btn btn-warning w-100 mb-2">
                                <i class="bi bi-arrow-repeat me-1"></i> Erneut versuchen
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            <!-- Metadaten -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Metadaten</h5>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-5">ID:</dt>
                        <dd class="col-sm-7">{{ $request->id }}</dd>

                        <dt class="col-sm-5">Erstellt am:</dt>
                        <dd class="col-sm-7">{{ $request->created_at->format('d.m.Y H:i') }}</dd>

                        @if($request->updated_at)
                            <dt class="col-sm-5">Aktualisiert:</dt>
                            <dd class="col-sm-7">{{ $request->updated_at->format('d.m.Y H:i') }}</dd>
                        @endif

                        @if($request->wikidata_id)
                            <dt class="col-sm-5">Wikidata ID:</dt>
                            <dd class="col-sm-7">
                                <a href="https://www.wikidata.org/wiki/{{ $request->wikidata_id }}" target="_blank">
                                    {{ $request->wikidata_id }}
                                </a>
                            </dd>
                        @endif

                        @if($request->wikidata_url)
                            <dt class="col-sm-5">URL:</dt>
                            <dd class="col-sm-7">
                                <a href="{{ $request->wikidata_url }}" target="_blank">
                                    Wikidata Link <i class="bi bi-box-arrow-up-right ms-1"></i>
                                </a>
                            </dd>
                        @endif
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Ablehnungs-Modal für Pending-Anfragen -->
@if($request->status == 'pending')
<div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="/api/wikidata/requests/{{ $request->id }}/reject" method="POST">
                @csrf
                <input type="hidden" name="redirect" value="{{ route('admin.requests.show', $request->id) }}">
                <div class="modal-header">
                    <h5 class="modal-title" id="rejectModalLabel">Anfrage ablehnen</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="reason" class="form-label">Ablehnungsgrund</label>
                        <textarea class="form-control" id="reason" name="reason"
                                  rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-x-circle me-1"></i> Anfrage ablehnen
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<!-- Ablehnungs-Modal für bereits genehmigte Anfragen -->
@if($request->status == 'approved')
<div class="modal fade" id="rejectApprovedModal" tabindex="-1" aria-labelledby="rejectApprovedModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="/api/wikidata/requests/{{ $request->id }}/retract" method="POST">
                @csrf
                <input type="hidden" name="redirect" value="{{ route('admin.requests.show', $request->id) }}">
                <div class="modal-header">
                    <h5 class="modal-title" id="rejectApprovedModalLabel">Genehmigung zurückziehen</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <strong>Achtung:</strong> Diese Aktion kennzeichnet den Eintrag als abgelehnt, entfernt jedoch nicht den bereits erstellten Wikidata-Eintrag. Falls der Eintrag in Wikidata gelöscht werden soll, müssen Sie dies manuell tun.
                    </div>

                    <div class="mb-3">
                        <label for="retract_reason" class="form-label">Grund für die Zurückziehung</label>
                        <textarea class="form-control" id="retract_reason" name="reason"
                                  rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-x-circle me-1"></i> Genehmigung zurückziehen
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<!-- Genehmigungs-Modal -->
@if($request->status == 'pending')
<div class="modal fade" id="approveModal" tabindex="-1" aria-labelledby="approveModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="/api/wikidata/requests/{{ $request->id }}/approve" method="POST">
                @csrf
                <input type="hidden" name="redirect" value="{{ route('admin.requests.show', $request->id) }}">
                <div class="modal-header">
                    <h5 class="modal-title" id="approveModalLabel">Anfrage genehmigen</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
                </div>
                <div class="modal-body">
                    <p>Möchten Sie die folgende Anfrage wirklich genehmigen?</p>
                    <div class="alert alert-info">
                        <strong>{{ $request->title }}</strong><br>
                        <small>Diese Aktion erstellt einen neuen Eintrag in Wikidata und kann nicht rückgängig gemacht werden.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle me-1"></i> Anfrage genehmigen
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection

@if($request->coordinates)
@push('scripts')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Koordinaten aus PHP-Variablen
        var lat = {{ $lat ?? 0 }};
        var lng = {{ $lng ?? 0 }};

        if (lat && lng) {
            // Container-Element
            var mapContainer = document.getElementById('map-container');

            // Karte initialisieren
            var map = L.map(mapContainer).setView([lat, lng], 15);

            // OpenStreetMap-Tile-Layer hinzufügen
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);

            // Marker hinzufügen
            L.marker([lat, lng]).addTo(map)
                .bindPopup("{{ addslashes($request->title) }}");
        }

        // Handle form submissions with redirects
        const forms = document.querySelectorAll('form[action^="/api/wikidata/requests/"]');
        forms.forEach(form => {
            form.addEventListener('submit', function(e) {
                if (form.querySelector('input[name="redirect"]')) {
                    e.preventDefault();
                    const formData = new FormData(form);

                    fetch(form.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.redirect) {
                            window.location.href = data.redirect;
                        } else {
                            // Fallback if no redirect provided
                            window.location.reload();
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.');
                    });
                }
            });
        });
    });
</script>
<style>
    #map-container {
        height: 300px;
        width: 100%;
        border-radius: 4px;
    }
</style>
@endpush
@endif
