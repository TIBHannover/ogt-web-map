@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <h1 class="mb-4">Wikidata-Einstellungen</h1>

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

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    OAuth-Status
                </div>
                <div class="card-body">
                    @if($hasCredentials)
                        <div class="alert alert-success">
                            <strong>OAuth-Verbindung aktiv:</strong> Die Verbindung zu Wikidata wurde erfolgreich hergestellt.
                        </div>
                        <p>Sie ko00fcnnen nun Wikidata-Antru00e4ge automatisch erstellen lassen.</p>
                    @else
                        <div class="alert alert-warning">
                            <strong>Keine OAuth-Verbindung:</strong> Es wurde noch keine Verbindung zu Wikidata hergestellt.
                        </div>
                        <p>Um die Wikidata-Integration zu nutzen, müssen Sie sich mit Ihrem Wikidata-Konto authentifizieren.</p>
                        <a href="{{ route('auth.wikidata') }}" class="btn btn-primary">Mit Wikidata verbinden</a>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    Verarbeitungsstatistiken
                </div>
                <div class="card-body">
                    <p>Diese Statistiken zeigen Ihnen, wie erfolgreich die Wikidata-Integration arbeitet.</p>

                    <div class="mb-3">
                        <strong>Erfolgreich erstellt:</strong>
                        {{ App\Models\WikibaseEntryRequest::where('status', 'approved')->whereNotNull('wikidata_id')->count() }}
                    </div>

                    <div class="mb-3">
                        <strong>Fehler aufgetreten:</strong>
                        {{ App\Models\WikibaseEntryRequest::where('status', 'error')->count() }}
                    </div>

                    <div class="mb-3">
                        <strong>Gesamt genehmigt:</strong>
                        {{ App\Models\WikibaseEntryRequest::where('status', 'approved')->count() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    Konfiguration
                </div>
                <div class="card-body">
                    <p>Hier können Sie die Wikidata-Einstellungen konfigurieren.</p>

                    <div class="mb-3">
                        <label class="form-label"><strong>OAuth-Callback-URL:</strong></label>
                        <input type="text" class="form-control" value="{{ config('wikidata.oauth_callback_url') }}" readonly>
                        <small class="text-muted">Diese URL muss in der Wikidata-OAuth-Registrierung angegeben werden.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><strong>API-URL:</strong></label>
                        <input type="text" class="form-control" value="{{ config('wikidata.api_url') }}" readonly>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
