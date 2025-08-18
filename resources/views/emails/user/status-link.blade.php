@component('mail::message')
# Ihr Eintragsanfragestatus

Vielen Dank für deine Eintragsanfrage: **{{ $entryRequest->title }}**

Mit dem unten stehenden Link kannst du jederzeit den aktuellen Status deiner Anfrage überprüfen.
Bitte bewahre diese E-Mail auf, um den Status deiner Anfrage verfolgen zu können.

@component('mail::button', ['url' => $statusUrl])
Status meiner Anfrage prüfen
@endcomponent

**Details zu deiner Anfrage:**

**Titel:** {{ $entryRequest->title }} <br>
**Typ:** {{ $entryRequest->type }} <br>
**Beschreibung:** {{ $entryRequest->description }} <br>
**Erstellt am:** {{ $entryRequest->created_at->format('d.m.Y H:i') }} <br>
**Aktueller Status:** {{ $entryRequest->status }}

Wir werden deine Anfrage so schnell wie möglich bearbeiten. Bei weiteren Fragen kannst du uns gerne kontaktieren.

Mit freundlichen Grüßen,<br>
Dein Gestapo.Terror.Orte. Team
@endcomponent
