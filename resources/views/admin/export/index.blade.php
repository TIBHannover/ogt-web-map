@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1>Daten exportieren</h1>
                <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Zurück zum Dashboard
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Wikidata Daten als CSV exportieren</h5>
                </div>
                <div class="card-body">
                    <p class="mb-4">
                        Exportieren Sie die von Wikidata abgerufenen Daten als CSV-Datei, die Sie in Excel öffnen
                        oder als PDF-Datei exportieren können. Bitte wählen Sie den Datentyp für den Export aus.
                    </p>

                    <form action="{{ route('admin.export.download') }}" method="POST">
                        @csrf
                        <div class="mb-4">
                            <label class="form-label">Datentyp wählen:</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="type" id="all" value="all" checked>
                                <label class="form-check-label" for="all">
                                    Alle Daten (Orte und Personen)
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="type" id="places" value="places">
                                <label class="form-check-label" for="places">
                                    Nur Orte (Mit Ereignissen)
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="type" id="places_no_events" value="places_no_events">
                                <label class="form-check-label" for="places_no_events">
                                    Nur Orte (ohne Ereignisse)
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="type" id="persons" value="persons">
                                <label class="form-check-label" for="persons">
                                    Nur Personen
                                </label>
                            </div>
                        </div>

                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            Der Export großer Datenmengen kann einige Momente dauern. Bitte warten Sie, bis der Download abgeschlossen ist.
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-download"></i> CSV-Datei herunterladen
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Hinweise</h5>
                </div>
                <div class="card-body">
                    <h6>Über die exportierten Daten:</h6>
                    <ul>
                        <li>Die Daten werden direkt von Wikidata abgerufen</li>
                        <li>CSV-Dateien können in Excel oder ähnlichen Tabellenkalkulationsprogrammen geöffnet werden</li>
                        <li>Die Datei enthält eine UTF-8 BOM-Markierung für korrekte Zeichendarstellung in Excel</li>
                    </ul>

                    <h6>Für den Orte-Export:</h6>
                    <p>Enthält ID, Label, Beschreibung, Typ, Koordinaten, Adresse, Gründungs- und Auflösungsdaten sowie Website-Links.</p>

                    <h6>Für den Orte-Export ohne Ereignisse:</h6>
                    <p>Enthält die gleichen Daten wie der Orte-Export, filtert aber Einträge vom Typ "NS-Verbrechen" (Wikidata Q6983405) aus.</p>

                    <h6>Für den Personen-Export:</h6>
                    <p>Enthält ID, Name, Beschreibung, Geschlecht, Geburts- und Sterbedaten, Geburts- und Sterbeorte, Staatsbürgerschaften und Personengruppe (Täter oder Opfer).</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
