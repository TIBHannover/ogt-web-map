@php
    // This partial displays a qualifier badge with the appropriate label
    // The qualifier ID is passed as a parameter
    // Example usage: @include('admin.partials.qualifier', ['qualifier' => $request->time_point_qualifier])

    if (empty($qualifier)) {
        return;
    }
@endphp

<span class="badge bg-info ms-2">
    @switch($qualifier)
        @case('Q5727902')
            Circa
            @break
        @case('Q60070514')
            Ungefähr
            @break
        @case('Q52834024')
            Weniger als
            @break
        @case('Q47035128')
            Mehr als
            @break
        @case('Q40719727')
            Anfang
            @break
        @case('Q79030196')
            Früher als (Vor)
            @break
        @case('Q12913816')
            Später als (Nach)
            @break
        @case('Q40719766')
            Ende
            @break
        @case('Q5727902')
            Circa
            @break
        @case('Q28863779')
            Koordinaten approximiert
            @break
        @case('Q47116015')
            In der Nähe von
            @break
        @case('Q47116028')
            Innerhalb von
            @break
        @case('Q60070514')
            Ungefähr
            @break
        @default
            {{ $qualifier }}
    @endswitch
</span>
