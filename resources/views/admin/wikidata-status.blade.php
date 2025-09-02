@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Wikidata Status</div>

                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success" role="alert">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger" role="alert">
                            {{ session('error') }}
                        </div>
                    @endif

                    <h3>OAuth-Status</h3>
                    @if ($hasCredentials)
                        <div class="alert alert-success">
                            Wikidata OAuth Verbindung ist aktiv und gültig.
                        </div>
                    @else
                        <div class="alert alert-warning">
                            Keine gültige Wikidata OAuth Verbindung vorhanden.
                        </div>
                        <a href="{{ url('/api/wikidata/oauth') }}" class="btn btn-primary">
                            Mit Wikidata verbinden
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
