@include('admin.partials.wikidata-helpers')

<!-- Partial für Orte des Gestapoterrors in der Admin-Ansicht -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0">Details zum Ort des Gestapoterrors</h5>
    </div>
    <div class="card-body">
        <!-- Typ des Gestapoterror-Orts -->
        @if(isset($request->entity_subtype) && !empty($request->entity_subtype))
            <div class="mb-3">
                <h6>Typ des Gestapoterror-Orts:</h6>
                <div class="mb-2">
                    @php
                        $subtypeLabels = [
                            'Q277565' => 'Arbeitserziehungslager',
                            'Q40357' => 'Gefängnis',
                            'Q108047650' => 'Erweitertes Polizeigefängnis',
                            'Q108047541' => 'Außendienststelle',
                            'Q108047567' => 'Staatspolizeistelle',
                            'Q108047581' => 'Staatspolizeileitstelle'
                        ];
                        $subtypeLabel = $subtypeLabels[$request->entity_subtype] ?? $request->entity_subtype;
                    @endphp
                    {{ $subtypeLabel }} ({{ $request->entity_subtype }})
                </div>
            </div>
        @endif

        <!-- Datum der Gründung/Erbauung -->
        @if(isset($request->founding_date) && !empty($request->founding_date))
            <div class="mb-3">
                <h6>Datum der Gründung/Erbauung:</h6>
                <div class="mb-2">
                    {!! formatDateWithQualifier($request->founding_date, $request->founding_date_qualifier) !!}
                </div>
            </div>
        @endif

        <!-- Auflösungsdatum -->
        @if(isset($request->dissolution_date) && !empty($request->dissolution_date))
            <div class="mb-3">
                <h6>Auflösungsdatum:</h6>
                <div class="mb-2">
                    {!! formatDateWithQualifier($request->dissolution_date, $request->dissolution_date_qualifier) !!}
                </div>
            </div>
        @endif

        <!-- Adresse -->
        @if(isset($request->address) && !empty($request->address))
            <div class="mb-3">
                <h6>Adresse:</h6>
                <div class="mb-2">
                    {{ $request->address }}
                </div>
            </div>
        @endif

        <!-- Schlüsselereignis -->
        @if(isset($request->key_event) && !empty($request->key_event))
            <div class="mb-3">
                <h6>Schlüsselereignis:</h6>
                <div class="mb-2">
                    @if(is_array($request->key_event))
                        @foreach($request->key_event as $index => $event)
                            {!! safeFormatWikidata($event) !!}@if($index < count($request->key_event) - 1), @endif
                        @endforeach
                    @else
                        {!! safeFormatWikidata($request->key_event) !!}
                    @endif
                </div>
            </div>
        @endif

        <!-- Leiter -->
        @if(isset($request->director) && !empty($request->director))
            <div class="mb-3">
                <h6>Leiter:</h6>
                <div class="mb-2">
                    @if(is_array($request->director))
                        @foreach($request->director as $index => $director)
                            {!! safeFormatWikidata($director) !!}@if($index < count($request->director) - 1), @endif
                        @endforeach
                    @else
                        {!! safeFormatWikidata($request->director) !!}
                    @endif
                </div>
            </div>
        @endif

        <!-- Beschäftigte -->
        @if(isset($request->employees) && !empty($request->employees))
            <div class="mb-3">
                <h6>Beschäftigte:</h6>
                <div class="mb-2">
                    @if(is_array($request->employees))
                        @foreach($request->employees as $index => $employee)
                            {!! safeFormatWikidata($employee) !!}@if($index < count($request->employees) - 1), @endif
                        @endforeach
                    @else
                        {!! safeFormatWikidata($request->employees) !!}
                    @endif
                </div>
            </div>
        @endif

        <!-- Häftlingszahl -->
        @if(isset($request->prisoner_count) && !empty($request->prisoner_count))
            <div class="mb-3">
                <h6>Häftlingszahl:</h6>
                <div class="mb-2">
                    {!! formatNumberWithQualifier($request->prisoner_count, $request->prisoner_count_qualifier) !!}
                </div>
            </div>
        @endif

        <!-- Übergeordnete Organisation -->
        @if(isset($request->parent_organization) && !empty($request->parent_organization))
            <div class="mb-3">
                <h6>Übergeordnete Organisation:</h6>
                <div class="mb-2">
                    @if(is_array($request->parent_organization))
                        @foreach($request->parent_organization as $index => $org)
                            {!! safeFormatWikidata($org) !!}@if($index < count($request->parent_organization) - 1), @endif
                        @endforeach
                    @else
                        {!! safeFormatWikidata($request->parent_organization) !!}
                    @endif
                </div>
            </div>
        @endif

        <!-- Nachgeordnete Organisation -->
        @if(isset($request->child_organization) && !empty($request->child_organization))
            <div class="mb-3">
                <h6>Nachgeordnete Organisation:</h6>
                <div class="mb-2">
                    @if(is_array($request->child_organization))
                        @foreach($request->child_organization as $index => $org)
                            {!! safeFormatWikidata($org) !!}@if($index < count($request->child_organization) - 1), @endif
                        @endforeach
                    @else
                        {!! safeFormatWikidata($request->child_organization) !!}
                    @endif
                </div>
            </div>
        @endif

        <!-- Vorgängerorganisation -->
        @if(isset($request->predecessor_organization) && !empty($request->predecessor_organization))
            <div class="mb-3">
                <h6>Vorgängerorganisation:</h6>
                <div class="mb-2">
                    @if(is_array($request->predecessor_organization))
                        @foreach($request->predecessor_organization as $index => $org)
                            {!! safeFormatWikidata($org) !!}@if($index < count($request->predecessor_organization) - 1), @endif
                        @endforeach
                    @else
                        {!! safeFormatWikidata($request->predecessor_organization) !!}
                    @endif
                </div>
            </div>
        @endif

        <!-- Nachfolgeorganisation -->
        @if(isset($request->successor_organization) && !empty($request->successor_organization))
            <div class="mb-3">
                <h6>Nachfolgeorganisation:</h6>
                <div class="mb-2">
                    @if(is_array($request->successor_organization))
                        @foreach($request->successor_organization as $index => $org)
                            {!! safeFormatWikidata($org) !!}@if($index < count($request->successor_organization) - 1), @endif
                        @endforeach
                    @else
                        {!! safeFormatWikidata($request->successor_organization) !!}
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>
