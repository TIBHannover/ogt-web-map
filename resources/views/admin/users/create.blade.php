@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <h1 class="mb-4">Neuen Benutzer erstellen</h1>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Benutzerdaten</h5>
            <a href="{{ route('admin.users') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Zurück zur Benutzerliste
            </a>
        </div>
        <div class="card-body">
            @if(session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
            @endif

            <form action="{{ route('admin.users.store') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label for="name" class="form-label">Name</label>
                    <input
                        type="text"
                        class="form-control @error('name') is-invalid @enderror"
                        id="name"
                        name="name"
                        value="{{ old('name') }}"
                        required
                    >
                    @error('name')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">E-Mail Adresse</label>
                    <input
                        type="email"
                        class="form-control @error('email') is-invalid @enderror"
                        id="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                    >
                    @error('email')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Passwort</label>
                    <input
                        type="password"
                        class="form-control @error('password') is-invalid @enderror"
                        id="password"
                        name="password"
                        required
                        minlength="8"
                    >
                    <div class="form-text">Das Passwort muss mindestens 8 Zeichen lang sein.</div>
                    @error('password')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="password_confirmation" class="form-label">Passwort bestätigen</label>
                    <input
                        type="password"
                        class="form-control"
                        id="password_confirmation"
                        name="password_confirmation"
                        required
                        minlength="8"
                    >
                </div>

                <div class="mb-3 form-check">
                    <input
                        type="checkbox"
                        class="form-check-input"
                        id="is_admin"
                        name="is_admin"
                        value="1"
                        checked
                    >
                    <label class="form-check-label" for="is_admin">
                        Als Administrator erstellen
                    </label>
                    <div class="form-text">
                        Administratoren haben vollen Zugriff auf alle Bereiche der Anwendung.
                    </div>
                </div>

                <div class="d-flex justify-content-between">
                    <a href="{{ route('admin.users') }}" class="btn btn-secondary">Abbrechen</a>
                    <button type="submit" class="btn btn-primary">Benutzer erstellen</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
