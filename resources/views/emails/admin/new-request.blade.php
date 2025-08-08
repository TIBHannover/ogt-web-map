@component('mail::message')
# Neue Wikidata-Eintragsanfrage

Es wurde eine neue Eintragsanfrage erstellt:

**Titel:** {{ $entryRequest->title }}
**Typ:** {{ $entryRequest->type }}
**Beschreibung:** {{ $entryRequest->description }}
**Erstellt am:** {{ $entryRequest->created_at->format('d.m.Y H:i') }}

@component('mail::button', ['url' => $adminUrl])
Anfrage im Admin-Bereich ansehen
@endcomponent

Bitte überprüfen Sie diese Anfrage und genehmigen oder lehnen Sie sie ab.

Mit freundlichen Grüßen,<br>
{{ config('app.name') }}
@endcomponent
