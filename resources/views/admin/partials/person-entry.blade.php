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
$typeLabel = $type === 'perpetrator' ? 'Täter*In' : 'Geschädigter';

// Wikidata-Felder, die als Q-Nummern dargestellt werden müssen
$wikidataFields = ['gender', 'place_of_birth', 'place_of_death', 'place_of_detention', 'place_of_residence', 'nationality', 'occupation', 'employer', 'birthName', 'key_event_person'];

// Debug-Hilfsfunktion
$debug = false; // Auf true setzen, um Debug-Informationen anzuzeigen

// Helper for formatting Wikidata fields
$formatWikidataValue = function($value) use ($debug) {
    // Handle null/empty values
    if (empty($value)) {
        if ($debug) echo "Empty value detected<br>";
        return [];
    }

    // If it's already an array, use it
    if (is_array($value)) {
        // Check if it's an array of Q-numbers
        if (count($value) > 0) {
            if ($debug) echo "Array value detected: " . json_encode($value) . "<br>";
            // If the first element is a string and looks like a Q-number, we assume it's an array of Q-numbers
            if (isset($value[0]) && is_string($value[0]) && preg_match('/^Q\d+$/', $value[0])) {
                if ($debug) echo "Array of Q-numbers detected<br>";
                return $value; // Return as-is
            }
        }
        return $value;
    }

    // If it's a JSON string, decode it
    if (is_string($value) && (strpos($value, '[') === 0 || strpos($value, '{') === 0)) {
        try {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                if ($debug) echo "Decoded JSON array: " . json_encode($decoded) . "<br>";
                return $decoded;
            }
        } catch (\Exception $e) {
            if ($debug) echo "JSON decode error: " . $e->getMessage() . "<br>";
        }
    }

    // For single values, wrap in array
    if ($debug) echo "Single value wrapped in array: " . $value . "<br>";
    return [$value];
};
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

                @if(isset($person['wikidata_id']))
                    <div>
                        <a href="https://www.wikidata.org/wiki/{{ $person['wikidata_id'] }}" target="_blank" class="text-decoration-none">
                            <span class="badge bg-secondary">{{ $person['wikidata_id'] }}</span>
                            <i class="bi bi-box-arrow-up-right ms-1 small"></i>
                        </a>
                    </div>
                @elseif(isset($person['id']))
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

                @if(isset($person['role']))
                    <div class="small mt-1">
                        <strong>Rolle:</strong>
                        {{$typeLabel}}
                    </div>
                @endif
                {{-- Formatierte Datumsanzeige --}}
                @if(isset($person['birthDate']) && $person['birthDate'])
                    <div class="small mt-1">
                        <strong>Geburtsdatum:</strong>
                        @php
                            try {
                                echo \Carbon\Carbon::parse($person['birthDate'])->format('d.m.Y');
                            } catch (\Exception $e) {
                                echo $person['birthDate'];
                            }
                        @endphp
                    </div>
                @elseif(isset($person['birth']) && $person['birth'])
                    <div class="small mt-1">
                        <strong>Geburtsdatum:</strong>
                        @php
                            try {
                                echo \Carbon\Carbon::parse($person['birth'])->format('d.m.Y');
                            } catch (\Exception $e) {
                                echo $person['birth'];
                            }
                        @endphp
                    </div>
                @endif

                @if(isset($person['deathDate']) && $person['deathDate'])
                    <div class="small mt-1">
                        <strong>Sterbedatum:</strong>
                        @php
                            try {
                                echo \Carbon\Carbon::parse($person['deathDate'])->format('d.m.Y');
                            } catch (\Exception $e) {
                                echo $person['deathDate'];
                            }
                        @endphp
                    </div>
                @elseif(isset($person['death']) && $person['death'])
                    <div class="small mt-1">
                        <strong>Sterbedatum:</strong>
                        @php
                            try {
                                echo \Carbon\Carbon::parse($person['death'])->format('d.m.Y');
                            } catch (\Exception $e) {
                                echo $person['death'];
                            }
                        @endphp
                    </div>
                @endif

                {{-- Wikidata-Entitäten anzeigen --}}
                @foreach($wikidataFields as $field)
                    @if(isset($person[$field]) && !empty($person[$field]))
                        <div class="small mt-1">
                            <strong>{{ WikidataPropertyController::translatePropertyLabel($field) }}:</strong>
                            @php
                                // Process field value for consistent display
                                $fieldValue = $formatWikidataValue($person[$field]);
                            @endphp

                            @if(count($fieldValue) > 0)
                                <div class="d-inline-block">
                                    @foreach($fieldValue as $index => $item)
                                        @php
                                            // Determine the Q-number based on item format
                                            $qNumber = null;
                                            if (is_string($item) && preg_match('/^Q\d+$/', $item)) {
                                                // Direct Q-number
                                                $qNumber = $item;
                                            } elseif (is_array($item) && isset($item['wikidata_id']) && preg_match('/^Q\d+$/', $item['wikidata_id'])) {
                                                // Object with wikidata_id property
                                                $qNumber = $item['wikidata_id'];
                                            } elseif (is_array($item) && isset($item['id']) && preg_match('/^Q\d+$/', $item['id'])) {
                                                // Object with ID property
                                                $qNumber = $item['id'];
                                            } elseif (is_array($item) && !empty($item) && isset($item[0]) && is_string($item[0]) && preg_match('/^Q\d+$/', $item[0])) {
                                                // Array containing a Q-number at index 0
                                                $qNumber = $item[0];
                                            }

                                            if ($debug) {
                                                echo "<!-- Item format: " . gettype($item) . " -->";
                                                if (is_array($item)) {
                                                    echo "<!-- Item content: " . json_encode($item) . " -->";
                                                } elseif (is_string($item)) {
                                                    echo "<!-- Item content: " . $item . " -->";
                                                }
                                                echo "<!-- Q-Number extracted: " . ($qNumber ?? 'null') . " -->";
                                            }
                                        @endphp

                                        @if($qNumber)
                                            <a href="https://www.wikidata.org/wiki/{{ $qNumber }}" target="_blank" class="text-decoration-none">
                                                {{ WikidataPropertyController::getQLabel($qNumber) }}
                                                <span class="badge bg-secondary text-white ms-1">{{ $qNumber }}</span>
                                                <i class="bi bi-box-arrow-up-right ms-1 small"></i>
                                            </a>
                                        @else
                                            {{ is_string($item) ? $item : json_encode($item) }}
                                        @endif
                                        @if($index < count($fieldValue) - 1), @endif
                                    @endforeach
                                </div>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </div>
                    @endif
                @endforeach

                {{-- Allgemeine Eigenschaften anzeigen --}}
                @foreach($person as $key => $value)
                    @if(!in_array($key, array('description', 'id', 'wikidata_id', 'tempId', 'birthDate', 'deathDate', 'death', 'birth', 'date_of_birth', 'date_of_death', 'isOrganization', 'label', 'title', 'type', 'role', 'firstName', 'lastName', 'birthName', 'imageFilename', 'image_filename', 'image', 'imageUrl', 'image_url', '_skipCreate', 'role_type')) && !in_array($key, $wikidataFields) && $value)
                        <div class="small mt-1">
                            <strong>{{ WikidataPropertyController::translatePropertyLabel($key) }}:</strong>
                            @php
                                // Process field value for consistent display
                                $valueArray = $formatWikidataValue($value);
                            @endphp

                            @if(count($valueArray) > 0)
                                <div class="d-inline-block">
                                    @foreach($valueArray as $index => $item)
                                        @php
                                            // Determine the Q-number based on item format
                                            $qNumber = null;
                                            if (is_string($item) && preg_match('/^Q\d+$/', $item)) {
                                                // Direct Q-number
                                                $qNumber = $item;
                                            } elseif (is_array($item) && isset($item['wikidata_id']) && preg_match('/^Q\d+$/', $item['wikidata_id'])) {
                                                // Object with wikidata_id property
                                                $qNumber = $item['wikidata_id'];
                                            } elseif (is_array($item) && isset($item['id']) && preg_match('/^Q\d+$/', $item['id'])) {
                                                // Object with ID property
                                                $qNumber = $item['id'];
                                            } elseif (is_array($item) && !empty($item) && isset($item[0]) && is_string($item[0]) && preg_match('/^Q\d+$/', $item[0])) {
                                                // Array containing a Q-number at index 0
                                                $qNumber = $item[0];
                                            }

                                            if ($debug) {
                                                echo "<!-- Other field: Item format: " . gettype($item) . " -->";
                                                if (is_array($item)) {
                                                    echo "<!-- Other field: Item content: " . json_encode($item) . " -->";
                                                } elseif (is_string($item)) {
                                                    echo "<!-- Other field: Item content: " . $item . " -->";
                                                }
                                                echo "<!-- Other field: Q-Number extracted: " . ($qNumber ?? 'null') . " -->";
                                            }
                                        @endphp

                                        @if($qNumber)
                                            <a href="https://www.wikidata.org/wiki/{{ $qNumber }}" target="_blank" class="text-decoration-none">
                                                {{ WikidataPropertyController::getQLabel($qNumber) }}
                                                <span class="badge bg-secondary text-white ms-1">{{ $qNumber }}</span>
                                                <i class="bi bi-box-arrow-up-right ms-1 small"></i>
                                            </a>
                                        @else
                                            {{ is_string($item) ? $item : json_encode($item) }}
                                        @endif
                                        @if($index < count($valueArray) - 1), @endif
                                    @endforeach
                                </div>
                            @else
                                {{ is_string($value) ? $value : json_encode($value) }}
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

<!-- Quellenangaben -->
@if(isset($request->sources) && is_array($request->sources) && count($request->sources) > 0)
    @include('admin.partials.sources-detail')
@endif
