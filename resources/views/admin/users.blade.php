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
        <div class="card-header">
            <h5 class="mb-0">Registrierte Benutzer</h5>
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
                                    @if($user->isAdmin())
                                        <span class="badge bg-success">Ja</span>
                                    @else
                                        <span class="badge bg-secondary">Nein</span>
                                    @endif
                                </td>
                                <td>
                                    @if($user->hasWikidataAccount())
                                        <span class="badge bg-primary">{{ $user->wikidata_username }}</span>
                                    @else
                                        <span class="badge bg-secondary">Nicht verbunden</span>
                                    @endif
                                </td>
                                <td>{{ $user->created_at->format('d.m.Y H:i') }}</td>
                                <td>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-primary dropdown-toggle" data-bs-toggle="dropdown">
                                            Aktionen
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li>
                                                <a href="#" class="dropdown-item">Details</a>
                                            </li>
                                            @if(!$user->isAdmin())
                                                <li>
                                                    <form action="#" method="POST" class="promote-admin-form">
                                                        @csrf
                                                        <button type="submit" class="dropdown-item text-success">Zum Admin machen</button>
                                                    </form>
                                                </li>
                                            @else
                                                <li>
                                                    <form action="#" method="POST" class="revoke-admin-form">
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
</div>
@endsection