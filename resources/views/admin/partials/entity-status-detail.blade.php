@php
    use App\Services\Wikidata\WikidataFormatterModels;

    // Define status badge colors and labels
    $statusInfo = [
        'pending' => ['color' => 'primary', 'label' => 'In Bearbeitung'],
        'approved' => ['color' => 'success', 'label' => 'Genehmigt'],
        'rejected' => ['color' => 'danger', 'label' => 'Abgelehnt'],
        'wikidata_submitted' => ['color' => 'info', 'label' => 'An Wikidata u00fcbermittelt'],
        'wikidata_published' => ['color' => 'success', 'label' => 'In Wikidata veröffentlicht'],
        'wikidata_failed' => ['color' => 'danger', 'label' => 'Fehler bei Wikidata-Erstellung'],
        'error' => ['color' => 'warning', 'label' => 'Fehler']
    ];

    // Helper function to get entity type label
    function getEntityTypeLabel($type) {
        $types = [
            \App\Services\Wikidata\WikidataFormatterModels::ENTITY_TYPE_BOOK => 'Literarisches Werk',
            \App\Services\Wikidata\WikidataFormatterModels::ENTITY_TYPE_PERSON => 'Person',
            \App\Services\Wikidata\WikidataFormatterModels::ENTITY_TYPE_EVENT => 'Ereignis',
            \App\Services\Wikidata\WikidataFormatterModels::ENTITY_TYPE_ORGANIZATION => 'Organisation',
            \App\Services\Wikidata\WikidataFormatterModels::ENTITY_TYPE_STRUCTURE => 'Architektonisches Struktur',
            'Q5003624' => 'Erinnerungsort',
            'Q108047567' => 'Staatspolizeistelle',
            'Q108047541' => 'Staatspolizeiaußenstelle',
            'Q277565' => 'Arbeitserziehungslager',
            'Q40357' => 'Gefängnis',
            'book' => 'Buch',
            \App\Services\Wikidata\WikidataFormatterModels::ROLE_PERPETRATOR => 'Täter*in',
            \App\Services\Wikidata\WikidataFormatterModels::ROLE_VICTIM => 'Opfer'
        ];

        return $types[$type] ?? $type;
    }
@endphp

<!-- Detaillierte Entity-Status-Anzeige -->
<div class="card mb-4 entity-status-card">
    <div class="card-header bg-light">
        <h5 class="card-title mb-0">Detaillierter Status der Wikidata-Einträge</h5>
    </div>
    <div class="card-body">
        <!-- Hauptentität Status -->
        <div class="mb-4">
            <h6 class="border-bottom pb-2">Haupteintrag</h6>
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <span class="fw-bold">{{ $request->title }}</span>
                    <span class="badge bg-secondary ms-2">{{ getEntityTypeLabel($request->type) }}</span>
                </div>
                <span class="badge bg-{{ $statusInfo[$request->status]['color'] }}">{{ $statusInfo[$request->status]['label'] }}</span>
            </div>

            @if($request->wikidata_id)
                <div class="mb-2">
                    <strong>Wikidata-ID:</strong>
                    <a href="https://www.wikidata.org/wiki/{{ $request->wikidata_id }}" target="_blank" class="text-decoration-none">
                        {{ $request->wikidata_id }} <i class="bi bi-box-arrow-up-right"></i>
                    </a>
                </div>
            @endif

            @if($request->status_updated_at)
                <div class="mb-2">
                    <strong>Letzte Aktualisierung:</strong> {{ \Carbon\Carbon::parse($request->status_updated_at)->format('d.m.Y H:i') }}
                </div>
            @endif

            @if($request->comment)
                <div class="mb-2">
                    <strong>Kommentar:</strong> {{ $request->comment }}
                </div>
            @endif

            @if($request->status == 'wikidata_failed' || $request->status == 'error')
                <div class="alert alert-danger mb-0">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <strong>Fehler:</strong> {{ $request->error_message ?? 'Ein Fehler ist bei der Verarbeitung aufgetreten.' }}

                    <div class="mt-2">
                        <form action="/api/wikidata/requests/{{ $request->id }}/retry" method="POST" class="d-inline">
                            @csrf
                            <input type="hidden" name="redirect" value="{{ route('admin.requests.show', $request->id) }}">
                            <button type="submit" class="btn btn-sm btn-warning">
                                <i class="bi bi-arrow-repeat me-1"></i> Erneut versuchen
                            </button>
                        </form>
                    </div>
                </div>
            @endif
        </div>

        <!-- Status-Historie -->
        @if(!empty($request->status_history) && count($request->status_history) > 0)
            <div class="mb-4">
                <h6 class="border-bottom pb-2">Status-Verlauf</h6>
                <div class="status-timeline">
                    @foreach($request->status_history as $history)
                        <div class="status-item">
                            <div class="status-badge bg-{{ $statusInfo[$history['status']]['color'] ?? 'secondary' }}"></div>
                            <div class="status-content">
                                <div class="d-flex justify-content-between">
                                    <strong>{{ $statusInfo[$history['status']]['label'] ?? $history['status'] }}</strong>
                                    <small class="text-muted">{{ \Carbon\Carbon::parse($history['date'])->format('d.m.Y H:i') }}</small>
                                </div>
                                @if(!empty($history['comment']))
                                    <p class="mb-0 small">{{ $history['comment'] }}</p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Verknüpfte Entitäten -->
        @if(!empty($request->created_entities) && count($request->created_entities) > 0)
            <div class="mb-3">
                <h6 class="border-bottom pb-2">Verknüpfte Entitäten</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>Typ</th>
                                <th>Name</th>
                                <th>Wikidata-ID</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($request->created_entities as $entity)
                                <tr>
                                    <td>
                                        <span class="badge bg-{{
                                            $entity['type'] == 'perpetrator' ? 'danger' :
                                            ($entity['type'] == 'victim' ? 'info' :
                                            ($entity['type'] == 'book' ? 'primary' : 'secondary'))
                                        }}">
                                            {{ getEntityTypeLabel($entity['type']) }}
                                        </span>
                                    </td>
                                    <td>{{ $entity['name'] ?? $entity['label'] ?? 'Unbenannt' }}</td>
                                    <td>
                                        <a href="https://www.wikidata.org/wiki/{{ $entity['id'] }}" target="_blank" class="text-decoration-none">
                                            {{ $entity['id'] }} <i class="bi bi-box-arrow-up-right"></i>
                                        </a>
                                    </td>
                                    <td>
                                        <span class="badge bg-success">Erstellt</span>
                                    </td>
                                </tr>
                            @endforeach

                            <!-- Verknüpfte existierende Entitäten -->
                            @if(isset($request->perpetrators) && is_array($request->perpetrators))
                                @foreach($request->perpetrators as $perpetrator)
                                    @if(!empty($perpetrator['id']) && substr($perpetrator['id'], 0, 1) === 'Q' && empty($perpetrator['_wasCreated']))
                                        <tr>
                                            <td><span class="badge bg-danger">{{ getEntityTypeLabel('perpetrator') }}</span></td>
                                            <td>{{ $perpetrator['firstName'] ?? '' }} {{ $perpetrator['lastName'] ?? '' }}</td>
                                            <td>
                                                <a href="https://www.wikidata.org/wiki/{{ $perpetrator['id'] }}" target="_blank" class="text-decoration-none">
                                                    {{ $perpetrator['id'] }} <i class="bi bi-box-arrow-up-right"></i>
                                                </a>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary">Existierend</span>
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                            @endif

                            @if(isset($request->victims) && is_array($request->victims))
                                @foreach($request->victims as $victim)
                                    @if(!empty($victim['id']) && substr($victim['id'], 0, 1) === 'Q' && empty($victim['_wasCreated']))
                                        <tr>
                                            <td><span class="badge bg-info">{{ getEntityTypeLabel('victim') }}</span></td>
                                            <td>{{ $victim['firstName'] ?? '' }} {{ $victim['lastName'] ?? '' }}</td>
                                            <td>
                                                <a href="https://www.wikidata.org/wiki/{{ $victim['id'] }}" target="_blank" class="text-decoration-none">
                                                    {{ $victim['id'] }} <i class="bi bi-box-arrow-up-right"></i>
                                                </a>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary">Existierend</span>
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                            @endif

                            @if(isset($request->sources) && is_array($request->sources))
                                @foreach($request->sources as $source)
                                    @if(is_array($source) && !empty($source['id']) && $source['id'] !== 'manual' && substr($source['id'], 0, 1) === 'Q')
                                        <tr>
                                            <td><span class="badge bg-primary">{{ getEntityTypeLabel('book') }}</span></td>
                                            <td>{{ $source['title'] ?? 'Quelle' }}</td>
                                            <td>
                                                <a href="https://www.wikidata.org/wiki/{{ $source['id'] }}" target="_blank" class="text-decoration-none">
                                                    {{ $source['id'] }} <i class="bi bi-box-arrow-up-right"></i>
                                                </a>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary">Existierend</span>
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</div>

<!-- CSS-Styling für Status-Timeline -->
<style>
.entity-status-card .status-timeline {
    position: relative;
    padding-left: 30px;
    margin-bottom: 20px;
}

.entity-status-card .status-timeline::before {
    content: '';
    position: absolute;
    top: 0;
    bottom: 0;
    left: 10px;
    width: 2px;
    background-color: #e9ecef;
}

.entity-status-card .status-item {
    position: relative;
    margin-bottom: 15px;
}

.entity-status-card .status-badge {
    position: absolute;
    left: -30px;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    z-index: 1;
}

.entity-status-card .status-content {
    padding: 10px 15px;
    background-color: #f8f9fa;
    border-radius: 4px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}
</style>
