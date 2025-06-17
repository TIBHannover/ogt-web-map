@php
use App\Http\Controllers\WikidataPropertyController;

/**
 * Partial zur Anzeige einer Person (Tu00e4ter oder Opfer)
 *
 * @param array $person - Die Personen-Daten
 * @param string $type - Der Typ der Person ("perpetrator" oder "victim")
 */

// Typspezifische Klasse fu00fcr die Anzeige
$badgeClass = $type === 'perpetrator' ? 'danger' : 'info';
$typeLabel = $type === 'perpetrator' ? 'Tu00e4ter/in' : 'Opfer';

// Wikidata-Felder, die als Q-Nummern dargestellt werden mu00fcssen
$wikidataFields = ['gender', 'place_of_birth', 'place_of_death', 'place_of_detention', 'nationality'];
@endphp

<div class="list-group-item">
    @if(is_array($person))
        @if(isset($person['label']))
            <div class="fw-bold">{{ $person['label'] }}</div>
        @else
            <div class="fw-bold">{{ $person['firstName'] ?? '' }} {{ $person['lastName'] ?? '' }}</div>
        @endif

        @if(isset($person['id']))
            <div>
                <a href="https://www.wikidata.org/wiki/{{ $person['id'] }}" target="_blank" class="text-decoration-none">
                    <span class="badge bg-secondary">{{ $person['id'] }}</span>
                    <i class="bi bi-box-arrow-up-right ms-1 small"></i>
                </a>
            </div>
        @endif

        @if(isset($person['description']))
            <div class="text-muted small mt-1">{{ $person['description'] }}</div>
        @endif

        {{-- Formatierte Datumsanzeige --}}
        @if(isset($person['birthDate']) && $person['birthDate'])
            <div class="small mt-1">
                <strong>Geburtsdatum:</strong>
                {{ \Carbon\Carbon::parse($person['birthDate'])->format('d.m.Y') ?? $person['birthDate'] }}
            </div>
        @elseif(isset($person['birth']) && $person['birth'])
            <div class="small mt-1">
                <strong>Geburtsdatum:</strong>
                {{ \Carbon\Carbon::parse($person['birth'])->format('d.m.Y') ?? $person['date_of_birth'] }}
            </div>
        @endif

        @if(isset($person['deathDate']) && $person['deathDate'])
            <div class="small mt-1">
                <strong>Sterbedatum:</strong>
                {{ \Carbon\Carbon::parse($person['deathDate'])->format('d.m.Y') ?? $person['deathDate'] }}
            </div>
        @elseif(isset($person['death']) && $person['death'])
            <div class="small mt-1">
                <strong>Sterbedatum:</strong>
                {{ \Carbon\Carbon::parse($person['death'])->format('d.m.Y') ?? $person['date_of_death'] }}
            </div>
        @endif

        {{-- Wikidata-Entitu00e4ten anzeigen --}}
        @foreach($wikidataFields as $field)
            @if(isset($person[$field]) && is_array($person[$field]) && isset($person[$field]['id']))
                <div class="small mt-1">
                    <strong>{{ WikidataPropertyController::translatePropertyLabel($field) }}:</strong>
                    <a href="https://www.wikidata.org/wiki/{{ $person[$field]['id'] }}" target="_blank" class="text-decoration-none">
                        {{ WikidataPropertyController::getQLabel($person[$field]['id']) }}
                        <i class="bi bi-box-arrow-up-right ms-1 small"></i>
                    </a>
                </div>
            @endif
        @endforeach

        {{-- Allgemeine Eigenschaften anzeigen --}}
        @foreach($person as $key => $value)
            @if(!in_array($key, array('description', 'id', 'tempId', 'birthDate', 'deathDate', 'death', 'birth', 'isOrganization', 'label', 'firstName', 'lastName')) && $value)
                <div class="small mt-1">
                    <strong>{{ WikidataPropertyController::translatePropertyLabel($key) }}:</strong>
                    @if(is_string($value) && preg_match('/^Q\d+$/', $value))
                        {{ WikidataPropertyController::getQLabel($value) }}
                    @elseif(is_array($value) && isset($value['id']) && preg_match('/^Q\d+$/', $value['id']))
                        {{ WikidataPropertyController::getQLabel($value['id']) }}
                    @else
                        {{ $value }}
                    @endif
                </div>
            @endif
        @endforeach

    @elseif(is_string($person))
        {{ $person }}
    @endif
</div>
