@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <h1 class="mb-4">Job-Verwaltung</h1>

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

    @if(session('info'))
        <div class="alert alert-info">
            {{ session('info') }}
        </div>
    @endif

    <!-- Pending Jobs Section -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Warteschlangen-Jobs</h5>
            <div>
                <form action="{{ route('admin.jobs.destroy-all') }}" method="POST" class="d-inline" onsubmit="return confirm('Sind Sie sicher, dass Sie alle Jobs löschen möchten?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash"></i> Alle Jobs löschen
                    </button>
                </form>
            </div>
        </div>
        <div class="card-body">
            @if(count($jobs) > 0)
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Job Name</th>
                                <th>Queue</th>
                                <th>Versuche</th>
                                <th>Erstellt am</th>
                                <th>Verfügbar ab</th>
                                <th>Reserviert</th>
                                <th>Aktionen</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($jobs as $job)
                                <tr>
                                    <td>{{ $job->id }}</td>
                                    <td>
                                        {{ $job->job_name }}
                                        @if($job->command_name)
                                            <span class="badge bg-secondary">{{ $job->command_name }}</span>
                                        @endif
                                    </td>
                                    <td><span class="badge bg-info">{{ $job->queue }}</span></td>
                                    <td>{{ $job->attempts }}</td>
                                    <td>{{ $job->created_at }}</td>
                                    <td>{{ $job->available_at }}</td>
                                    <td>{{ $job->reserved_at ?? 'Nicht reserviert' }}</td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#jobDetailsModal{{ $job->id }}">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <form action="{{ route('admin.jobs.destroy', $job->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Sind Sie sicher, dass Sie diesen Job löschen möchten?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>

                                <!-- Modal für Job-Details -->
                                <div class="modal fade" id="jobDetailsModal{{ $job->id }}" tabindex="-1" aria-labelledby="jobDetailsModalLabel{{ $job->id }}" aria-hidden="true">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="jobDetailsModalLabel{{ $job->id }}">Job-Details #{{ $job->id }}</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <h6>Payload:</h6>
                                                    <pre class="bg-light p-3">{{ $job->payload }}</pre>
                                                </div>
                                                @if($job->job_data)
                                                    <div class="mb-3">
                                                        <h6>Job-Daten:</h6>
                                                        <pre class="bg-light p-3">{{ $job->job_data }}</pre>
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Schließen</button>
                                                <form action="{{ route('admin.jobs.destroy', $job->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger">
                                                        <i class="bi bi-trash me-1"></i> Job löschen
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-center mt-4">
                    {{ $jobs->links() }}
                </div>
            @else
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i> Keine Jobs in der Warteschlange.
                </div>
            @endif
        </div>
    </div>

    <!-- Failed Jobs Section -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Fehlgeschlagene Jobs</h5>
            <div>
                <form action="{{ route('admin.jobs.retry-all-failed') }}" method="POST" class="d-inline" onsubmit="return confirm('Alle fehlgeschlagenen Jobs neu starten?');">
                    @csrf
                    <button type="submit" class="btn btn-success me-2">
                        <i class="bi bi-arrow-clockwise"></i> Alle neu starten
                    </button>
                </form>
                <form action="{{ route('admin.jobs.destroy-all-failed') }}" method="POST" class="d-inline" onsubmit="return confirm('Sind Sie sicher, dass Sie alle fehlgeschlagenen Jobs löschen möchten?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash"></i> Alle löschen
                    </button>
                </form>
            </div>
        </div>
        <div class="card-body">
            @if(count($failedJobs) > 0)
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Job Name</th>
                                <th>Queue</th>
                                <th>Fehlgeschlagen am</th>
                                <th>Fehler</th>
                                <th>Aktionen</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($failedJobs as $job)
                                <tr>
                                    <td>{{ $job->id }}</td>
                                    <td>
                                        {{ $job->job_name }}
                                        @if($job->command_name)
                                            <span class="badge bg-secondary">{{ $job->command_name }}</span>
                                        @endif
                                    </td>
                                    <td><span class="badge bg-info">{{ $job->queue }}</span></td>
                                    <td>{{ $job->failed_at }}</td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#exceptionModal{{ $job->id }}">
                                            {{ $job->exception_preview }}
                                        </button>
                                    </td>
                                    <td>
                                        <form action="{{ route('admin.jobs.retry-failed', $job->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success">
                                                <i class="bi bi-arrow-clockwise"></i> Neu starten
                                            </button>
                                        </form>
                                        <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#failedJobDetailsModal{{ $job->id }}">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <form action="{{ route('admin.jobs.destroy-failed', $job->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Sind Sie sicher, dass Sie diesen fehlgeschlagenen Job löschen möchten?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>

                                <!-- Modal für Fehlerdetails -->
                                <div class="modal fade" id="exceptionModal{{ $job->id }}" tabindex="-1" aria-labelledby="exceptionModalLabel{{ $job->id }}" aria-hidden="true">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header bg-danger text-white">
                                                <h5 class="modal-title" id="exceptionModalLabel{{ $job->id }}">Fehlerdetails #{{ $job->id }}</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
                                            </div>
                                            <div class="modal-body">
                                                <pre class="bg-light p-3 text-danger">{{ $job->exception }}</pre>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Schließen</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Modal für Job-Details -->
                                <div class="modal fade" id="failedJobDetailsModal{{ $job->id }}" tabindex="-1" aria-labelledby="failedJobDetailsModalLabel{{ $job->id }}" aria-hidden="true">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="failedJobDetailsModalLabel{{ $job->id }}">Failed Job-Details #{{ $job->id }}</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <h6>Payload:</h6>
                                                    <pre class="bg-light p-3">{{ $job->payload }}</pre>
                                                </div>
                                                @if($job->job_data)
                                                    <div class="mb-3">
                                                        <h6>Job-Daten:</h6>
                                                        <pre class="bg-light p-3">{{ $job->job_data }}</pre>
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Schließen</button>
                                                <form action="{{ route('admin.jobs.retry-failed', $job->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-success">
                                                        <i class="bi bi-arrow-clockwise me-1"></i> Job neu starten
                                                    </button>
                                                </form>
                                                <form action="{{ route('admin.jobs.destroy-failed', $job->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger">
                                                        <i class="bi bi-trash me-1"></i> Job löschen
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-center mt-4">
                    {{ $failedJobs->links() }}
                </div>
            @else
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i> Keine fehlgeschlagenen Jobs vorhanden.
                </div>
            @endif
        </div>
    </div>

    <!-- Filter Section -->
    <div class="row mb-4">
        <!-- Filter Regular Jobs -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Aktive Jobs nach Queue filtern</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.jobs.destroy-all') }}" method="POST" class="row g-3">
                        @csrf
                        @method('DELETE')
                        <div class="col-md-6">
                            <select name="queue" class="form-select">
                                <option value="">Alle Queues</option>
                                <option value="default">default</option>
                                <option value="wikidata">wikidata</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <button type="submit" class="btn btn-danger" onclick="return confirm('Sind Sie sicher, dass Sie alle Jobs in dieser Queue löschen möchten?');">
                                <i class="bi bi-trash me-1"></i> Gefilterte Jobs löschen
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Filter Failed Jobs -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Fehlgeschlagene Jobs nach Queue filtern</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <select id="failed-queue-filter" class="form-select">
                                <option value="">Alle Queues</option>
                                <option value="default">default</option>
                                <option value="wikidata">wikidata</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <form action="{{ route('admin.jobs.retry-all-failed') }}" method="POST" class="d-inline">
                                @csrf
                                <input type="hidden" id="retry-queue-input" name="retry_queue" value="">
                                <button type="submit" class="btn btn-success me-2" onclick="document.getElementById('retry-queue-input').value = document.getElementById('failed-queue-filter').value; return confirm('Alle gefilterten fehlgeschlagenen Jobs neu starten?');">
                                    <i class="bi bi-arrow-clockwise me-1"></i> Neu starten
                                </button>
                            </form>
                            <form action="{{ route('admin.jobs.destroy-all-failed') }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" id="failed-queue-input" name="failed_queue" value="">
                                <button type="submit" class="btn btn-danger" onclick="document.getElementById('failed-queue-input').value = document.getElementById('failed-queue-filter').value; return confirm('Sind Sie sicher, dass Sie alle gefilterten fehlgeschlagenen Jobs löschen möchten?');">
                                    <i class="bi bi-trash me-1"></i> Löschen
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
