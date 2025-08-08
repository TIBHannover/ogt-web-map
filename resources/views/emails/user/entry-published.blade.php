@component('mail::message')
# Ihr Eintrag wurde veröffentlicht

Gute Nachrichten! Ihre Eintragsanfrage wurde genehmigt und in Wikidata veröffentlicht.

**Titel:** {{ $entryRequest->title }}
**Typ:** {{ $entryRequest->type }}
**Beschreibung:** {{ $entryRequest->description }}
**Veröffentlicht am:** {{ $entryRequest->status_updated_at->format('d.m.Y H:i') }}

@if($wikidataUrl)
@component('mail::button', ['url' => $wikidataUrl])
Eintrag in Wikidata ansehen
@endcomponent
@endif

@if($mapUrl)
@component('mail::button', ['url' => $mapUrl])
Eintrag auf der Karte ansehen
@endcomponent
@endif

Vielen Dank für Ihren Beitrag zur Dokumentation historischer Ereignisse. Ihre Mitwirkung ist wichtig für die Bewahrung des kollektiven Gedächtnisses.

Falls Sie Fragen haben oder weitere Informationen hinzufügen möchten, zögern Sie nicht, uns zu kontaktieren.

Mit freundlichen Grüßen,<br>
{{ config('app.name') }}
@endcomponent
