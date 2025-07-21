@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <div class="jumbotron text-center bg-light p-5 rounded">
        <h1 class="display-4">Willkommen bei Gestapo Terror Orte</h1>
        <p class="lead">Administrationsbereich für die Verwaltung und Genehmigung von Wikidata-Eintru00e4gen.</p>
        <hr class="my-4">

        @guest
            <p>Bitte melden Sie sich an, um Zugriff auf den Admin-Bereich zu erhalten.</p>
            <div class="d-flex justify-content-center gap-2">
                <a class="btn btn-primary btn-lg" href="{{ route('login') }}" role="button">Anmelden</a>
                <a class="btn btn-outline-secondary btn-lg" href="{{ route('register') }}" role="button">Registrieren</a>
            </div>
        @else
            <p>Sie sind angemeldet als <strong>{{ Auth::user()->name }}</strong>.</p>

            <div class="d-flex justify-content-center gap-2">
                @if(Auth::user()->isAdmin())
                    <a class="btn btn-primary btn-lg" href="{{ route('admin.dashboard') }}" role="button">Zum Admin-Bereich</a>
                @else
                    <p class="text-muted">Sie haben keine Administrator-Rechte.</p>
                @endif

                <a class="btn btn-outline-secondary btn-lg" href="{{ route('profile') }}" role="button">Mein Profil</a>
            </div>
        @endguest
    </div>

    @guest
    <div class="row mt-5">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Mit Wikidata verbinden</h5>
                </div>
                <div class="card-body">
                    <p>Sie können sich direkt mit Ihrem Wikidata-Konto anmelden, um die Genehmigung von Eintru00e4gen zu erleichtern.</p>
                    <a href="{{ route('auth.wikidata') }}" class="btn btn-success">Mit Wikidata anmelden</a>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Verwaltungsfunktionen</h5>
                </div>
                <div class="card-body">
                    <ul>
                        <li>Genehmigung von Wikidata-Eintru00e4gen</li>
                        <li>OAuth-Integration mit Wikidata</li>
                        <li>u00dcberwachung der API-Anfragen</li>
                        <li>Benutzerverwaltung</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    @endguest
</div>
@endsection
