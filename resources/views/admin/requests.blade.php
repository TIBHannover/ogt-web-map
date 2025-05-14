@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <h1 class="mb-4">Anfragen-Verwaltung</h1>

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
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0">Anfragen</h5>
            </div>
            <div>
                <form action="{{ route('admin.requests') }}" method="get" class="d-flex">
                    <select name="status" class="form-select me-2">
                        <option value="all" {{ $currentStatus == 'all' ? 'selected' : '' }}>Alle Stati</option>
                        <option value="pending" {{ $currentStatus == 'pending' ? 'selected' : '' }}>In Bearbeitung</option>
                        <option value="approved" {{ $currentStatus == 'approved' ? 'selected' : '' }}>Genehmigt</option>
                        <option value="rejected" {{ $currentStatus == 'rejected' ? 'selected' : '' }}>Abgelehnt</option>
                        <option value="error" {{ $currentStatus == 'error' ? 'selected' : '' }}>Fehler</option>
                    </select>
                    <button type="submit" class="btn btn-primary">Filtern</button>
                </form>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Titel</th>
                            <th>Beschreibung</th>
                            <th>Status</th>
                            <th>Erstellt am</th>
                            <th>Wikidata ID</th>
                            <th>Aktion</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($requests as $request)
                            <tr>
                                <td>{{ $request->id }}</td>
                                <td>
                                    <a href="{{ route('admin.requests.show', $request->id) }}" class="text-decoration-none text-primary">
                                        {{ $request->title }}
                                    </a>
                                </td>
                                <td>{{ Str::limit($request->description, 50) }}</td>
                                <td>
                                    @if($request->status == 'pending')
                                        <span class="badge bg-primary">In Bearbeitung</span>
                                    @elseif($request->status == 'approved')
                                        <span class="badge bg-success">Genehmigt</span>
                                    @elseif($request->status == 'rejected')
                                        <span class="badge bg-danger">Abgelehnt</span>
                                    @elseif($request->status == 'error')
                                        <span class="badge bg-warning">Fehler</span>
                                    @endif
                                </td>
                                <td>{{ $request->created_at->format('d.m.Y H:i') }}</td>
                                <td>
                                    @if($request->wikidata_id)
                                        <a href="https://www.wikidata.org/wiki/{{ $request->wikidata_id }}" target="_blank">
                                            {{ $request->wikidata_id }}
                                        </a>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('admin.requests.show', $request->id) }}" class="btn btn-sm btn-primary">
                                        <i class="bi bi-eye"></i> Details
                                    </a>
                                </td>
                            </tr>
                        @endforeach

                        @if($requests->count() == 0)
                            <tr>
                                <td colspan="7" class="text-center">Keine Anfragen gefunden.</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-center mt-4">
                {{ $requests->links() }}
            </div>
        </div>
    </div>
</div>
@endsection