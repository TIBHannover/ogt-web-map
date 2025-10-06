@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <h1 class="mb-4">Dashboard</h1>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <div class="alert alert-info">
        <strong>Hinweis:</strong> Sie können jederzeit zur <a href="{{ url('/') }}">Hauptanwendung</a> zurückkehren.
    </div>

    <div class="row">
        <div class="col-md-3 mb-4">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Anfragen in Bearbeitung</h5>
                    <h2 class="card-text">{{ $stats['pending_requests'] }}</h2>
                    <a href="{{ route('admin.requests', ['status' => 'pending']) }}" class="text-white">Details anzeigen</a>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Genehmigte Anfragen</h5>
                    <h2 class="card-text">{{ $stats['approved_requests'] }}</h2>
                    <a href="{{ route('admin.requests', ['status' => 'approved']) }}" class="text-white">Details anzeigen</a>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h5 class="card-title">Abgelehnte Anfragen</h5>
                    <h2 class="card-text">{{ $stats['rejected_requests'] }}</h2>
                    <a href="{{ route('admin.requests', ['status' => 'rejected']) }}" class="text-white">Details anzeigen</a>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-4">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <h5 class="card-title">Anfragen mit Fehlern</h5>
                    <h2 class="card-text">{{ $stats['error_requests'] }}</h2>
                    <a href="{{ route('admin.requests', ['status' => 'error']) }}" class="text-dark">Details anzeigen</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Neueste Anfragen</span>
                    <a href="{{ route('admin.export') }}" class="btn btn-success">
                        <i class="bi bi-file-earmark-excel"></i> Daten exportieren
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Titel</th>
                                    <th>Status</th>
                                    <th>Erstellt am</th>
                                    <th>Wikidata ID</th>
                                    <th>Aktionen</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($latestRequests as $request)
                                    <tr>
                                        <td>{{ $request->id }}</td>
                                        <td>
                                            <a href="{{ route('admin.requests.show', $request->id) }}" class="text-decoration-none text-primary">
                                                {{ $request->title }}
                                            </a>
                                        </td>
                                        <td>
                                            @if($request->status == 'pending')
                                                <span class="badge bg-primary">In Bearbeitung</span>
                                            @elseif($request->status == 'approved')
                                                <span class="badge bg-success">Genehmigt</span>
                                            @elseif($request->status == 'rejected')
                                                <span class="badge bg-danger">Abgelehnt</span>
                                            @elseif($request->status == 'error')
                                                <span class="badge bg-warning">Fehler</span>
                                            @elseif($request->status == 'wikidata_published')
                                                <span class="badge bg-info">In Wikidata veröffentlicht</span>
                                            @endif
                                        </td>
                                        <td>{{ is_string($request->created_at) ? date('d.m.Y H:i', strtotime($request->created_at)) : $request->created_at->format('d.m.Y H:i') }}</td>
                                        <td>{{ $request->wikidata_id ?? '-' }}</td>
                                        <td>
                                            <a href="{{ route('admin.requests.show', $request->id) }}" class="btn btn-sm btn-primary">
                                                <i class="bi bi-eye"></i> Details
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <a href="{{ route('admin.requests') }}" class="btn btn-primary">Alle Anfragen anzeigen</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
