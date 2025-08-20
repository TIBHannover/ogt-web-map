@component('mail::message')
# Ihr Eintrag wurde veröffentlicht

Gute Nachrichten! Ihre Eintragsanfrage wurde genehmigt und in Wikidata veröffentlicht.

**Titel:** {{ $entryRequest->title }} <br>
**Typ:** {{ $entryRequest->type }} <br>
**Beschreibung:** {{ $entryRequest->description }} <br>
**Veröffentlicht am:** {{ is_object($entryRequest->status_updated_at) && method_exists($entryRequest->status_updated_at, 'format') ? $entryRequest->status_updated_at->format('d.m.Y H:i') : date('d.m.Y H:i', strtotime($entryRequest->status_updated_at ?? now())) }}

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

Vielen Dank für deinen Beitrag zur Dokumentation historischer Ereignisse. Ihre Mitwirkung ist wichtig für die Bewahrung des kollektiven Gedächtnisses.

Falls du Fragen haben oder weitere Informationen hinzufügen möchtest, zögern nicht, uns zu kontaktieren.

Mit freundlichen Grüßen,<br>
Dein Gestapo.Terror.Orte. Team
@endcomponent
