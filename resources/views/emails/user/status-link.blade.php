@component('mail::message')
# Ihr Eintragsanfragestatus

Vielen Dank für Ihre Eintragsanfrage: **{{ $entryRequest->title }}**

Mit dem unten stehenden Link können Sie jederzeit den aktuellen Status Ihrer Anfrage überprüfen.
Bitte bewahren Sie diese E-Mail auf, um den Status Ihrer Anfrage verfolgen zu können.

@component('mail::button', ['url' => $statusUrl])
Status meiner Anfrage prüfen
@endcomponent

**Details zu Ihrer Anfrage:**

**Titel:** {{ $entryRequest->title }}
**Typ:** {{ $entryRequest->type }}
**Beschreibung:** {{ $entryRequest->description }}
**Erstellt am:** {{ $entryRequest->created_at->format('d.m.Y H:i') }}
**Aktueller Status:** {{ $entryRequest->status }}

Wir werden Ihre Anfrage so schnell wie möglich bearbeiten. Bei weiteren Fragen können Sie uns gerne kontaktieren.

Mit freundlichen Grüßen,<br>
{{ config('app.name') }}
@endcomponent
