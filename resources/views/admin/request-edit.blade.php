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
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font@latest/css/materialdesignicons.min.css">
<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Material+Icons">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/vuetify@3.0.0/dist/vuetify.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
    /* Custom button styles for EventsForm */
    .info-icon {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        background-color: #1976d2;
        color: white;
        border: none;
        font-size: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }
    
    .info-icon:hover {
        background-color: #1565c0;
    }
    
    /* Additional styles for other components */
    .button-container {
        display: flex;
        justify-content: space-between;
        width: 100%;
        margin-top: 2.5rem;
    }
    
    .button {
        padding: 12px 24px;
        background-color: #1976d2;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-weight: bold;
        transition: background-color 0.2s;
    }
    
    .button:hover {
        background-color: #1565c0;
    }
    
    .button:disabled {
        background-color: #bbdefb;
        cursor: not-allowed;
    }
    
    .button.bg-secondary {
        background-color: #757575;
    }
    
    .button.bg-secondary:hover {
        background-color: #616161;
    }
    
    /* Icon styling for source and other components */
    .source-type-icon {
        margin-right: 1rem;
        color: #555;
    }
    
    /* Search result styling */
    .search-loading {
        display: flex;
        align-items: center;
        margin-top: 0.5rem;
        color: #666;
    }
    
    /* Form field styling */
    .form-group {
        margin-bottom: 1rem;
        width: 100%;
    }
    
    /* Person item styling */
    .selected-victim, .selected-perpetrator {
        display: flex;
        align-items: center;
        padding: 0.5rem;
        margin-bottom: 0.5rem;
        background-color: white;
        border-radius: 4px;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
    }
    
    /* Action buttons styling */
    .remove-victim, .remove-perpetrator, .remove-cause, .remove-source, .remove-image {
        background: none;
        border: none;
        color: #d32f2f;
        cursor: pointer;
        font-size: 1rem;
        font-weight: bold;
        padding: 0.25rem 0.5rem;
    }
    
    /* Leaflet map specific styles */
    .leaflet-pane {
        z-index: 1;
    }
    
    .leaflet-tile-pane {
        z-index: 0;
    }
    
    .leaflet-tile {
        margin: 0 !important;
        padding: 0 !important;
    }
    
    .leaflet-tile-container img {
        width: 256px !important;
        height: 256px !important;
        margin: 0 !important;
        padding: 0 !important;
        left: 0 !important;
        top: 0 !important;
        border: none !important;
    }
    
    .leaflet-control {
        z-index: 10;
    }
    
    .leaflet-top,
    .leaflet-bottom {
        z-index: 10;
    }
    
    .map-container {
        width: 800px;
        margin-bottom: 1rem;
        border: 1px solid #ccc;
        border-radius: 4px;
        overflow: visible;
        position: relative;
        max-width: 100%;
    }
    
    .map-container .leaflet-container {
        width: 100% !important;
        height: 400px !important;
        background-color: #f8f8f8;
        overflow: hidden;
    }
    
    /* Info box styles */
    .info-box {
        width: 30%;
        padding: 1.5rem;
        background-color: #f8f9fa;
        border-radius: 8px;
        position: sticky;
        top: 2rem;
        align-self: flex-start;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        margin-top: 2.7rem;
        max-height: calc(100vh - 4rem);
        overflow-y: auto;
    }
    
    .info-box-header {
        border-bottom: 1px solid #e9ecef;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
    }
    
    .info-box-header h3 {
        font-size: 1.2rem;
        color: #1976d2;
        margin: 0;
    }
    
    .info-box-content {
        font-size: 0.95rem;
        line-height: 1.5;
        color: #495057;
    }
    
    /* Mobile Info Overlay */
    .mobile-info-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 1000;
        display: flex;
        justify-content: center;
        align-items: center;
    }
    
    .overlay-content {
        width: 90%;
        max-width: 500px;
        max-height: 80vh;
        background-color: white;
        border-radius: 8px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }
    
    .overlay-header {
        padding: 1rem;
        background-color: #1976d2;
        color: white;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .close-overlay {
        background: none;
        border: none;
        color: white;
        font-size: 1.5rem;
        cursor: pointer;
        padding: 0;
        margin: 0;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        transition: background-color 0.3s ease;
    }
    
    /* EventsForm layout styles */
    .event-form-wrapper {
        display: flex;
        flex-direction: row;
        gap: 2rem;
        width: 100%;
    }
    
    .step-container {
        background-color: rgba(255, 255, 255, 0.9);
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
        width: 65%;
    }
    
    .step-title {
        margin-bottom: 20px;
        font-size: 1.5rem;
        color: #333;
    }
    
    .forms > *:not(:last-child) {
        margin-bottom: 1rem;
    }
    
    .forms input,
    .forms textarea {
        padding: 0.75rem 1rem;
        width: 100%;
        background-color: white;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 1rem;
        box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1);
        transition: border-color 0.2s, box-shadow 0.2s;
    }
    
    .forms input:focus,
    .forms textarea:focus {
        outline: none;
        border-color: #1976d2;
        box-shadow: 0 0 0 3px rgba(25, 118, 210, 0.25);
    }
    
    .forms textarea {
        min-height: 8.5rem;
        resize: none;
    }
    
    .container-section {
        width: 100%;
        margin-bottom: 1.5rem;
        background-color: #f5f5f5;
        padding: 1rem;
        border-radius: 4px;
    }
    
    /* Make sure icons are properly displayed */
    .bi {
        display: inline-block;
        vertical-align: -0.125em;
    }
    
    /* Material Design Icons */
    .mdi {
        display: inline-block;
        font: normal normal normal 24px/1 "Material Design Icons";
        font-size: inherit;
        text-rendering: auto;
        line-height: inherit;
        -webkit-font-smoothing: antialiased;
    }
    
    /* Material Icons */
    .material-icons {
        font-family: 'Material Icons';
        font-weight: normal;
        font-style: normal;
        font-size: 24px;
        line-height: 1;
        letter-spacing: normal;
        text-transform: none;
        display: inline-block;
        white-space: nowrap;
        word-wrap: normal;
        direction: ltr;
        -webkit-font-smoothing: antialiased;
    }
    
    /* Responsive styles */
    @media (max-width: 1200px) {
        .event-form-wrapper {
            flex-direction: column;
        }
        
        .step-container {
            width: 100%;
        }
        
        .info-box {
            display: none; /* Desktop-Infobox auf mobilen Geräten ausblenden */
        }
    }
</style>
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