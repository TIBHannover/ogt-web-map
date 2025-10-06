@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Mein Profil</div>

                <div class="card-body">
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

                    <div class="alert alert-info">
                        <strong>Hinweis:</strong> Sie können jederzeit zur <a href="{{ url('/') }}">Hauptanwendung</a> zurückkehren.
                    </div>

                    <h4>Benutzerinformationen</h4>
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" class="form-control" value="{{ Auth::user()->name }}" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">E-Mail</label>
                        <input type="email" class="form-control" value="{{ Auth::user()->email }}" readonly>
                    </div>

                    <div class="mb-3">
                        <a href="{{ route('password.form') }}" class="btn btn-primary">Passwort ändern</a>
                    </div>

                    <hr>

                    <h4>Wikidata-Verbindung</h4>
                    @if(Auth::user()->hasWikidataAccount())
                        <div class="alert alert-success">
                            Ihr Konto ist mit Wikidata verbunden. Benutzername: {{ Auth::user()->wikidata_username }}
                        </div>

                        <form action="{{ route('disconnect.wikidata') }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-danger">Wikidata-Verbindung trennen</button>
                        </form>
                    @else
                        <div class="alert alert-warning">
                            Ihr Konto ist nicht mit Wikidata verbunden.
                        </div>

                        <a href="{{ route('connect.wikidata') }}" class="btn btn-primary">Mit Wikidata verbinden</a>
                    @endif

                    @if(Auth::user()->isAdmin())
                        <hr>
                        <h4>Administrator-Status</h4>
                        <div class="alert alert-info">
                            Sie haben Administratorrechte.
                        </div>
                        <a href="{{ route('admin.dashboard') }}" class="btn btn-primary">Zum Admin-Bereich</a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
