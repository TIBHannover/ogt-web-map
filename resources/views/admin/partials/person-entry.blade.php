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

// Wikidata-Felder, die als Q-Nummern dargestellt werden müssen
$wikidataFields = ['gender', 'place_of_birth', 'place_of_death', 'place_of_detention', 'nationality', 'employer'];
@endphp

<div class="list-group-item">
    @if(is_array($person))
        <div class="d-flex">
            <div class="flex-grow-1">
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

                {{-- Wikidata-Entitäten anzeigen --}}
                @foreach($wikidataFields as $field)
                    @if(isset($person[$field]))
                        <div class="small mt-1">
                            <strong>{{ WikidataPropertyController::translatePropertyLabel($field) }}:</strong>
                            @if(is_array($person[$field]) && isset($person[$field]['id']))
                                <a href="https://www.wikidata.org/wiki/{{ $person[$field]['id'] }}" target="_blank" class="text-decoration-none">
                                    {{ WikidataPropertyController::getQLabel($person[$field]['id']) }}
                                    <i class="bi bi-box-arrow-up-right ms-1 small"></i>
                                </a>
                            @elseif(is_string($person[$field]) && preg_match('/^Q\d+$/', $person[$field]))
                                <a href="https://www.wikidata.org/wiki/{{ $person[$field] }}" target="_blank" class="text-decoration-none">
                                    {{ WikidataPropertyController::getQLabel($person[$field]) }}
                                    <i class="bi bi-box-arrow-up-right ms-1 small"></i>
                                </a>
                            @else
                                @if(is_array($person[$field]) && !isset($person[$field]['id']))
                                    @if(count($person[$field]) > 0)
                                        <div class="d-inline-block">
                                            @foreach($person[$field] as $index => $item)
                                                @if(is_string($item) && preg_match('/^Q\d+$/', $item))
                                                    <a href="https://www.wikidata.org/wiki/{{ $item }}" target="_blank" class="text-decoration-none">
                                                        {{ WikidataPropertyController::getQLabel($item) }}
                                                        <i class="bi bi-box-arrow-up-right ms-1 small"></i>
                                                    </a>
                                                @elseif(is_array($item) && isset($item['id']) && preg_match('/^Q\d+$/', $item['id']))
                                                    <a href="https://www.wikidata.org/wiki/{{ $item['id'] }}" target="_blank" class="text-decoration-none">
                                                        {{ WikidataPropertyController::getQLabel($item['id']) }}
                                                        <i class="bi bi-box-arrow-up-right ms-1 small"></i>
                                                    </a>
                                                @else
                                                    {{ is_string($item) ? $item : json_encode($item) }}
                                                @endif
                                                @if($index < count($person[$field]) - 1), @endif
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                @else
                                    {{ is_string($person[$field]) ? $person[$field] : json_encode($person[$field]) }}
                                @endif
                            @endif
                        </div>
                    @endif
                @endforeach

                {{-- Allgemeine Eigenschaften anzeigen --}}
                @foreach($person as $key => $value)
                    @if(!in_array($key, array('description', 'id', 'tempId', 'birthDate', 'deathDate', 'death', 'birth', 'isOrganization', 'label', 'firstName', 'lastName', 'imageFilename', 'image_filename', 'image', 'imageUrl', 'image_url')) && $value)
                        <div class="small mt-1">
                            <strong>{{ WikidataPropertyController::translatePropertyLabel($key) }}:</strong>
                            @if(is_string($value) && preg_match('/^Q\d+$/', $value))
                                {{ WikidataPropertyController::getQLabel($value) }}
                            @elseif(is_array($value) && isset($value['id']) && preg_match('/^Q\d+$/', $value['id']))
                                {{ WikidataPropertyController::getQLabel($value['id']) }}
                            @else
                                @if(is_array($value) && !isset($value['id']))
                                    @if(count($value) > 0)
                                        <div class="d-inline-block">
                                            @foreach($value as $index => $item)
                                                @if(is_string($item) && preg_match('/^Q\d+$/', $item))
                                                    <a href="https://www.wikidata.org/wiki/{{ $item }}" target="_blank" class="text-decoration-none">
                                                        {{ WikidataPropertyController::getQLabel($item) }}
                                                        <i class="bi bi-box-arrow-up-right ms-1 small"></i>
                                                    </a>
                                                @elseif(is_array($item) && isset($item['id']) && preg_match('/^Q\d+$/', $item['id']))
                                                    <a href="https://www.wikidata.org/wiki/{{ $item['id'] }}" target="_blank" class="text-decoration-none">
                                                        {{ WikidataPropertyController::getQLabel($item['id']) }}
                                                        <i class="bi bi-box-arrow-up-right ms-1 small"></i>
                                                    </a>
                                                @else
                                                    {{ is_string($item) ? $item : json_encode($item) }}
                                                @endif
                                                @if($index < count($value) - 1), @endif
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                @else
                                    {{ is_string($value) ? $value : json_encode($value) }}
                                @endif
                            @endif
                        </div>
                    @endif
                @endforeach
            </div>

            {{-- Person Image Preview --}}
            @if(isset($person['imageFilename']) || isset($person['image_filename']) || isset($person['image']) || isset($person['imageUrl']) || isset($person['image_url']))
                <div class="person-image-preview ms-3">
                    @php
                        $imageFilename = $person['imageFilename'] ?? $person['image_filename'] ?? $person['image'] ?? null;
                        $imageUrl = $person['imageUrl'] ?? $person['image_url'] ?? null;

                        if ($imageFilename && !$imageUrl) {
                            $imageUrl = "https://commons.wikimedia.org/wiki/Special:FilePath/" . urlencode($imageFilename) . "?width=150";
                        }

                        // Create a larger version for the lightbox
                        $fullSizeImageUrl = $imageUrl ? str_replace('?width=150', '?width=800', $imageUrl) : null;
                    @endphp

                    @if($imageUrl)
                        <div class="image-container" style="position: relative;">
                            <a href="{{ $fullSizeImageUrl }}" target="_blank" title="Bild vergrößern" class="image-link">
                                <img src="{{ $imageUrl }}" alt="Bild von {{ $person['firstName'] ?? '' }} {{ $person['lastName'] ?? '' }}"
                                    class="person-thumbnail shadow-sm"
                                    style="width: 150px; height: 150px; object-fit: cover; border-radius: 8px; transition: transform 0.3s ease;">
                                <div class="image-overlay" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;
                                    background-color: rgba(0,0,0,0.3); opacity: 0; transition: opacity 0.3s ease;
                                    border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                    <i class="bi bi-zoom-in" style="color: white; font-size: 24px;"></i>
                                </div>
                            </a>
                            @if(isset($person['imageFilename']) || isset($person['image_filename']))
                                <div class="image-caption small text-muted text-center mt-1" style="max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $person['imageFilename'] ?? $person['image_filename'] ?? '' }}">
                                    {{ $person['imageFilename'] ?? $person['image_filename'] ?? '' }}
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            @endif
        </div>
    @elseif(is_string($person))
        {{ $person }}
    @endif
</div>
