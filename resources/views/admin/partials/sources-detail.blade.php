<!-- Partial for displaying sources in the admin interface -->
<div class="mb-3">
    <h6>Quellenangaben:</h6>
    <div class="list-group mt-2">
        @foreach($request->sources as $source)
            <div class="list-group-item">
                @if(is_array($source))
                    <div class="d-flex align-items-center mb-1">
                        <!-- Icon based on source type -->
                        @if(isset($source['type']))
                            <div class="me-2">
                                @if($source['type'] == 'book')
                                    <i class="bi bi-book"></i>
                                @elseif($source['type'] == 'url')
                                    <i class="bi bi-link"></i>
                                @elseif($source['type'] == 'archive')
                                    <i class="bi bi-archive"></i>
                                @else
                                    <i class="bi bi-file-text"></i>
                                @endif
                            </div>
                        @endif

                        <div>
                            <!-- Title and Wikidata ID -->
                            @if(isset($source['title']))
                                <strong>{{ $source['title'] }}</strong>
                            @endif

                            @if(!empty($source['id']) && $source['id'] !== 'manual' && substr($source['id'], 0, 1) === 'Q')
                                <a href="https://www.wikidata.org/wiki/{{ $source['id'] }}" target="_blank" class="text-decoration-none">
                                    {{ $source['id'] }} <i class="bi bi-box-arrow-up-right"></i>
                                </a>
                            @endif
                        </div>
                    </div>

                    <!-- URL for web sources -->
                    @if(isset($source['type']) && $source['type'] == 'url' && isset($source['url']))
                        <div class="mb-1">
                            <a href="{{ $source['url'] }}" target="_blank" class="text-break">
                                {{ $source['url'] }} <i class="bi bi-box-arrow-up-right"></i>
                            </a>

                            @if(isset($source['accessDate']) && !empty($source['accessDate']))
                                <div><small class="text-muted">Abrufdatum: {{ $source['accessDate'] }}</small></div>
                            @endif
                        </div>
                    @endif

                    <!-- Archive details -->
                    @if(isset($source['type']) && $source['type'] == 'archive')
                        @if(isset($source['archive']) && !empty($source['archive']))
                            <div class="mb-1">
                                <strong>Archiv:</strong> {{ $source['archive'] }}
                            </div>
                        @endif
                        @if(isset($source['inventoryNumber']) && !empty($source['inventoryNumber']))
                            <div class="mb-1">
                                <strong>Inventarnummer:</strong> {{ $source['inventoryNumber'] }}
                            </div>
                        @endif
                    @endif

                    <!-- Book details -->
                    @if(isset($source['type']) && $source['type'] == 'book')
                        @if(isset($source['author']) && !empty($source['author']))
                            <div class="mb-1">
                                <strong>Autor:</strong> {{ $source['author'] }}
                            </div>
                        @endif
                        @if(isset($source['year']) && !empty($source['year']))
                            <div class="mb-1">
                                <strong>Jahr:</strong> {{ $source['year'] }}
                            </div>
                        @endif
                        @if(isset($source['pages']) && !empty($source['pages']))
                            <div class="mb-1">
                                <strong>Seiten:</strong> {{ $source['pages'] }}
                            </div>
                        @endif
                    @endif

                    <!-- Description for all source types -->
                    @if(isset($source['description']) && !empty($source['description']))
                        <div class="mb-1">
                            <strong>Beschreibung:</strong> {{ $source['description'] }}
                        </div>
                    @endif
                @else
                    {{ $source }}
                @endif
            </div>
        @endforeach
    </div>
</div>
