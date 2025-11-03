@include('admin.partials.wikidata-helpers')

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
                    {!! safeFormatWikidata($request->commemorates) !!}
                </div>
            </div>
        @endif

        <!-- Betreiber -->
        @if(isset($request->operator) && !empty($request->operator))
            <div class="mb-3">
                <h6>Betreiber:</h6>
                <div class="mb-2">
                    {!! safeFormatWikidata($request->operator) !!}
                </div>
            </div>
        @endif

        <!-- Eröffnungsdatum -->
        @if(isset($request->opening_date) && !empty($request->opening_date))
            <div class="mb-3">
                <h6>Eröffnungsdatum:</h6>
                <div class="mb-2">
                    {!! formatDateWithQualifier($request->opening_date, $request->opening_date_qualifier) !!}
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
