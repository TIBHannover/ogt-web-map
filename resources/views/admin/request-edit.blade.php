@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Anfrage bearbeiten</h1>
        <a href="{{ route('admin.requests.show', $request->id) }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left me-1"></i> Zurück zur Übersicht
        </a>
    </div>

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

    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">{{ $request->title }}</h5>
        </div>
        <div class="card-body">
            <!-- Hier kommt das Vue-Formular für die Bearbeitung -->
            <div id="edit-request-form" data-request-id="{{ $request->id }}" data-request-type="{{ $request->type }}" data-request-data='{{ json_encode($request->toArray()) }}'></div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Die Anfragedaten werden als data-Attribute an das Root-Element übergeben
        const requestData = JSON.parse(document.getElementById('edit-request-form').getAttribute('data-request-data'));
        const requestType = document.getElementById('edit-request-form').getAttribute('data-request-type');
        const requestId = document.getElementById('edit-request-form').getAttribute('data-request-id');
        
        // Initialisiere Vue-Anwendung mit Anfragedaten
        window.initRequestEditForm(requestData, requestType, requestId);
    });
</script>
@endpush