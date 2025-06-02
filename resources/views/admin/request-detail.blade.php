@extends('layouts.app')

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
        ($request->status == 'error' ? 'warning' : 'primary'))
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
            @endif
        </h5>

        @if($request->status == 'approved' && $request->wikidata_id)
            <div class="mt-2">
                <a href="https://www.wikidata.org/wiki/{{ $request->wikidata_id }}"
                   target="_blank" class="btn btn-sm btn-light">
                    Wikidata-Eintrag öffnen <i class="bi bi-box-arrow-up-right ms-1"></i>
                </a>
            </div>
        @endif

        @if($request->status == 'rejected' && $request->rejection_reason)
            <div class="mt-2">
                <strong>Ablehnungsgrund:</strong> {{ $request->rejection_reason }}
            </div>
        @endif

        @if($request->status == 'error' && $request->error_message)
            <div class="mt-2">
                <strong>Fehlermeldung:</strong> {{ $request->error_message }}
                <form action="/api/wikidata/requests/{{ $request->id }}/retry" method="POST" class="d-inline ml-2">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-warning">
                        <i class="bi bi-arrow-repeat me-1"></i> Wiederholen
                    </button>
                </form>
            </div>
        @endif
    </div>

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
                                <div class="mb-2">{{ number_format((float)$lat, 6, '.', '') }}, {{ number_format((float)$lng, 6, '.', '') }}</div>
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
                                <h6>
                                    @if($request->time_point) Zeitpunkt: @else Zeitraum: @endif
                                </h6>
                                @if($request->time_point)
                                    <p>{{ \Carbon\Carbon::parse($request->time_point)->format('d.m.Y') }}</p>
                                @else
                                    <p>
                                        Von: {{ \Carbon\Carbon::parse($request->time_period_start)->format('d.m.Y') }}
                                        @if($request->time_period_end)
                                            bis: {{ \Carbon\Carbon::parse($request->time_period_end)->format('d.m.Y') }}
                                        @endif
                                    </p>
                                @endif
                            </div>
                        @endif

                        @if($request->cause)
                            <div class="mb-3">
                                <h6>Ursache:</h6>
                                <p>{{ $request->cause }}</p>
                            </div>
                        @endif

                        @if($request->selected_causes)
                            <div class="mb-3">
                                <h6>Ursachen der Verfolgung:</h6>
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
                                                            {{ $cause['label'] }}
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

                        @if($request->victim_count || $request->survivor_count || $request->death_count || $request->arrest_count)
                            <div class="mb-3">
                                <h6>Statistiken:</h6>
                                <ul>
                                    @if($request->victim_count)
                                        <li>Opfer: {{ $request->victim_count }}</li>
                                    @endif
                                    @if($request->survivor_count)
                                        <li>Überlebende: {{ $request->survivor_count }}</li>
                                    @endif
                                    @if($request->death_count)
                                        <li>Todesfälle: {{ $request->death_count }}</li>
                                    @endif
                                    @if($request->arrest_count)
                                        <li>Verhaftungen: {{ $request->arrest_count }}</li>
                                    @endif
                                </ul>
                            </div>
                        @endif

                        <!-- Beteiligte Personen (Täter und Geschädigte) -->
                        @if($request->perpetrators || $request->victims)
                            <div class="mb-3">
                                <h6>Beteiligte Personen:</h6>

                                @if($request->perpetrators && count($request->perpetrators) > 0)
                                    <div class="mb-3">
                                        <strong>Täter*Innen:</strong>
                                        <div class="list-group mt-2">
                                            @foreach($request->perpetrators as $perpetrator)
                                                <div class="list-group-item">
                                                    @if(is_array($perpetrator))
                                                        @if(isset($perpetrator['label']))
                                                            <div class="fw-bold">{{ $perpetrator['label'] }}</div>
                                                        @else
                                                            <div class="fw-bold">{{ $perpetrator['firstName'] }} {{ $perpetrator['lastName'] }}</div>
                                                        @endif

                                                        @if(isset($perpetrator['id']))
                                                            <div>
                                                                <a href="https://www.wikidata.org/wiki/{{ $perpetrator['id'] }}" target="_blank" class="text-decoration-none">
                                                                    <span class="badge bg-secondary">{{ $perpetrator['id'] }}</span>
                                                                    <i class="bi bi-box-arrow-up-right ms-1 small"></i>
                                                                </a>
                                                            </div>
                                                        @endif

                                                        @if(isset($perpetrator['description']))
                                                            <div class="text-muted small mt-1">{{ $perpetrator['description'] }}</div>
                                                        @endif

                                                        @foreach($perpetrator as $key => $value)
                                                            @if(!in_array($key, ['id', 'label', 'description', 'tempId']) && !is_array($value))
                                                                <div class="small mt-1">
                                                                    <strong>{{ ucfirst($key) }}:</strong> {{ $value }}
                                                                </div>
                                                            @endif
                                                        @endforeach
                                                    @elseif(is_string($perpetrator))
                                                        {{ $perpetrator }}
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                @if($request->victims && count($request->victims) > 0)
                                    <div>
                                        <strong>Geschädigte:</strong>
                                        <div class="list-group mt-2">
                                            @foreach($request->victims as $victim)
                                                <div class="list-group-item">
                                                    @if(is_array($victim))
                                                        @if(isset($victim['label']))
                                                            <div class="fw-bold">{{ $victim['label'] }}</div>
                                                        @else
                                                            <div class="fw-bold">{{ $victim['firstName'] }} {{ $victim['lastName'] }}</div>
                                                        @endif

                                                        @if(isset($victim['id']))
                                                            <div>
                                                                <a href="https://www.wikidata.org/wiki/{{ $victim['id'] }}" target="_blank" class="text-decoration-none">
                                                                    <span class="badge bg-secondary">{{ $victim['id'] }}</span>
                                                                    <i class="bi bi-box-arrow-up-right ms-1 small"></i>
                                                                </a>
                                                            </div>
                                                        @endif

                                                        @if(isset($victim['description']))
                                                            <div class="text-muted small mt-1">{{ $victim['description'] }}</div>
                                                        @endif

                                                        @foreach($victim as $key => $value)
                                                            @if(!in_array($key, ['id', 'label', 'description', 'tempId']) && !is_array($value))
                                                                <div class="small mt-1">
                                                                    <strong>{{ ucfirst($key) }}:</strong> {{ $value }}
                                                                </div>
                                                            @endif
                                                        @endforeach
                                                    @elseif(is_string($victim))
                                                        {{ $victim }}
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endif

                        <!-- Quellenangaben -->
                        @if($request->sources && count($request->sources) > 0)
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
                                    @endif
                                    @if($request->date_of_death)
                                        {{ $request->date_of_birth ? ' | ' : '' }}Gestorben: {{ \Carbon\Carbon::parse($request->date_of_death)->format('d.m.Y') }}
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
                                    @endif
                                    @if($request->dissolved_date)
                                        {{ $request->inception ? ' | ' : '' }}Auflösung: {{ \Carbon\Carbon::parse($request->dissolved_date)->format('d.m.Y') }}
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
                    <h5 class="card-title mb-0">Debug: Aufbereitete Wikidata-Daten</h5>
                    <button class="btn btn-sm btn-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#collapseWikidataDebug" aria-expanded="false" aria-controls="collapseWikidataDebug">Anzeigen/Verbergen</button>
                </div>
                <div class="collapse" id="collapseWikidataDebug">
                    <div class="card-body">
                        @php
                            // Wir benu00f6tigen den WikidataFormatter direkt
                            $formatter = app(\App\Services\Wikidata\WikidataFormatter::class);

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

                        <div class="mb-4">
                            <h6>Aktualisierte Eingabedaten (mit neuen IDs):</h6>
                            <pre class="bg-light p-3" style="max-height: 400px; overflow-y: auto;">{{ json_encode($updatedRequestData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                        </div>

                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            Diese Daten werden vom System aufbereitet und an die Wikidata API gesendet, wenn die Anfrage genehmigt wird. Die Struktur entspricht den Anforderungen der Wikidata API für die Erstellung neuer Entitäten.
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
                            <button type="button" class="btn btn-success w-100 mb-2" data-bs-toggle="modal" data-bs-target="#approveModal">
                                <i class="bi bi-check-circle me-1"></i> Genehmigen
                            </button>

                            <button type="button" class="btn btn-danger w-100 mb-2"
                                    data-bs-toggle="modal" data-bs-target="#rejectModal">
                                <i class="bi bi-x-circle me-1"></i> Ablehnen
                            </button>
                        </div>
                    @endif

                    @if($request->status == 'error')
                        <form action="/api/wikidata/requests/{{ $request->id }}/retry" method="POST">
                            @csrf
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

<!-- Ablehnungs-Modal -->
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

<!-- Genehmigungs-Modal -->
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
