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
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#passwordChangeModal">
                            Passwort ändern
                        </button>
                    </div>

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

<!-- Password Change Modal -->
<div class="modal fade" id="passwordChangeModal" tabindex="-1" aria-labelledby="passwordChangeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="passwordChangeModalLabel">Passwort ändern</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
            </div>
            <form method="POST" action="{{ route('password.change') }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Aktuelles Passwort</label>
                        <input id="current_password" type="password" class="form-control @error('current_password') is-invalid @enderror" name="current_password" required>
                        @error('current_password')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Neues Passwort</label>
                        <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required minlength="8">
                        @error('password')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                        <div class="form-text">
                            Das Passwort muss mindestens 8 Zeichen lang sein.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label">Passwort bestätigen</label>
                        <input id="password_confirmation" type="password" class="form-control" name="password_confirmation" required minlength="8">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="submit" class="btn btn-primary">Passwort ändern</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Get form inside modal
    const passwordChangeForm = document.querySelector('#passwordChangeModal form');

    // Add submit event listener
    passwordChangeForm.addEventListener('submit', function(e) {
        e.preventDefault();

        // Clear previous error messages
        document.querySelectorAll('.is-invalid').forEach(field => {
            field.classList.remove('is-invalid');
        });
        document.querySelectorAll('.invalid-feedback').forEach(feedback => {
            feedback.remove();
        });

        // Submit form via AJAX
        const formData = new FormData(this);

        fetch(this.getAttribute('action'), {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Success
                const modal = bootstrap.Modal.getInstance(document.getElementById('passwordChangeModal'));
                modal.hide();

                // Show success message
                const successAlert = document.createElement('div');
                successAlert.className = 'alert alert-success mt-3';
                successAlert.textContent = data.message;
                document.querySelector('.card-body').prepend(successAlert);

                // Reset form
                passwordChangeForm.reset();
            } else {
                // Handle validation errors
                const errors = data.errors;

                for (const field in errors) {
                    const inputField = document.querySelector(`[name="${field}"]`);
                    if (inputField) {
                        inputField.classList.add('is-invalid');

                        const feedback = document.createElement('div');
                        feedback.className = 'invalid-feedback';
                        feedback.innerHTML = `<strong>${errors[field][0]}</strong>`;
                        inputField.parentNode.appendChild(feedback);
                    }
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    });
});
</script>
@endsection
