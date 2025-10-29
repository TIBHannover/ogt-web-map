<!-- Partial für Erinnerungsorte in der Admin-Ansicht -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0">Erinnerungsort-Details</h5>
    </div>
    <div class="card-body">
        <!-- Erinnert an -->
        @if(isset($request->commemorates) && !empty($request->commemorates))
            <div class="mb-3">
                <h6>Erinnert an:</h6>
                <div class="mb-2">
                    @php
                        $commemorates = $request->commemorates;
                        $commemoratesDisplay = '';
                        if (is_string($commemorates) && substr($commemorates, 0, 1) === 'Q') {
                            $commemoratesData = \App\Http\Controllers\WikidataPropertyController::getEntityData($commemorates);
                            $commemoratesDisplay = formatWikidataProperty($commemorates, $commemoratesData['label'] ?? $commemorates);
                        } else {
                            $commemoratesDisplay = $commemorates;
                        }
                        echo $commemoratesDisplay;
                    @endphp
                </div>
            </div>
        @endif

        <!-- Betreiber -->
        @if(isset($request->operator) && !empty($request->operator))
            <div class="mb-3">
                <h6>Betreiber:</h6>
                <div class="mb-2">
                    @php
                        $operator = $request->operator;
                        $operatorDisplay = '';
                        if (is_string($operator) && substr($operator, 0, 1) === 'Q') {
                            $operatorData = \App\Http\Controllers\WikidataPropertyController::getEntityData($operator);
                            $operatorDisplay = formatWikidataProperty($operator, $operatorData['label'] ?? $operator);
                        } else {
                            $operatorDisplay = $operator;
                        }
                        echo $operatorDisplay;
                    @endphp
                </div>
            </div>
        @endif

        <!-- Eröffnungsdatum -->
        @if(isset($request->opening_date) && !empty($request->opening_date))
            <div class="mb-3">
                <h6>Eröffnungsdatum:</h6>
                <div class="mb-2">
                    {{ formatDateBasedOnPrecision($request->opening_date) }}
                    @if(isset($request->opening_date_qualifier) && !empty($request->opening_date_qualifier))
                        @include('admin.partials.qualifier', ['qualifier' => $request->opening_date_qualifier])
                    @endif
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

        <!-- Offizielle Website -->
        @if(isset($request->website) && !empty($request->website))
            <div class="mb-3">
                <h6>Offizielle Website:</h6>
                <div class="mb-2">
                    <a href="{{ $request->website }}" target="_blank">{{ $request->website }} <i class="bi bi-box-arrow-up-right"></i></a>
                </div>
            </div>
        @endif
    </div>
</div>
