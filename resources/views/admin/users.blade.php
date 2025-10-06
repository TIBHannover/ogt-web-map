@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <h1 class="mb-4">Benutzer-Verwaltung</h1>

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
            <h5 class="mb-0">Registrierte Benutzer</h5>
            <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
                <i class="bi bi-person-plus"></i> Neuen Benutzer erstellen
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>E-Mail</th>
                            <th>Admin</th>
                            <th>Wikidata Verbunden</th>
                            <th>Registriert am</th>
                            <th>Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $user)
                            <tr>
                                <td>{{ $user->id }}</td>
                                <td>{{ $user->name }}</td>
                                <td>{{ $user->email }}</td>
                                <td>
                                    @if($user->is_admin)
                                        <span class="badge bg-success">Ja</span>
                                    @else
                                        <span class="badge bg-secondary">Nein</span>
                                    @endif
                                </td>
                                <td>
                                    @if(isset($user->wikidata_username) && !empty($user->wikidata_username))
                                        <span class="badge bg-primary">{{ $user->wikidata_username }}</span>
                                    @else
                                        <span class="badge bg-secondary">Nicht verbunden</span>
                                    @endif
                                </td>
                                <td>{{ is_string($user->created_at) ? date('d.m.Y H:i', strtotime($user->created_at)) : $user->created_at->format('d.m.Y H:i') }}</td>
                                <td>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-primary dropdown-toggle" data-bs-toggle="dropdown">
                                            Aktionen
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li>
                                                <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#passwordResetModal{{ $user->id }}">
                                                    Passwort zurücksetzen
                                                </button>
                                            </li>
                                            @if(!$user->is_admin)
                                                <li>
                                                    <form action="{{ route('admin.user.promote', $user->id) }}" method="POST" class="promote-admin-form">
                                                        @csrf
                                                        <button type="submit" class="dropdown-item text-success">Zum Admin machen</button>
                                                    </form>
                                                </li>
                                            @else
                                                <li>
                                                    <form action="{{ route('admin.user.demote', $user->id) }}" method="POST" class="revoke-admin-form">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="dropdown-item text-danger">Admin-Rechte entziehen</button>
                                                    </form>
                                                </li>
                                            @endif
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @endforeach

                        @if($users->count() == 0)
                            <tr>
                                <td colspan="7" class="text-center">Keine Benutzer gefunden.</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-center mt-4">
                {{ $users->links() }}
            </div>
        </div>
    </div>

    <!-- Password Reset Modals -->
    @foreach($users as $user)
        <div class="modal fade" id="passwordResetModal{{ $user->id }}" tabindex="-1" aria-labelledby="passwordResetModalLabel{{ $user->id }}" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="passwordResetModalLabel{{ $user->id }}">Passwort zurücksetzen für {{ $user->name }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
                    </div>
                    <form action="{{ route('admin.users.reset-password', $user->id) }}" method="POST">
                        @csrf
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="password{{ $user->id }}" class="form-label">Neues Passwort</label>
                                <input type="password" class="form-control" id="password{{ $user->id }}" name="password" required minlength="8">
                                <div class="form-text">Das Passwort muss mindestens 8 Zeichen lang sein.</div>
                            </div>
                            <div class="mb-3">
                                <label for="password_confirmation{{ $user->id }}" class="form-label">Passwort bestätigen</label>
                                <input type="password" class="form-control" id="password_confirmation{{ $user->id }}" name="password_confirmation" required minlength="8">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                            <button type="submit" class="btn btn-primary">Passwort zurücksetzen</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
</div>
@endsection
