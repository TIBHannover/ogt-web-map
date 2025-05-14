<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Zeigt die Login-Seite an.
     *
     * @return \Illuminate\View\View
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Bearbeitet den Login-Versuch.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            
            // Wenn der Benutzer ein Admin ist, leite ihn zum Admin-Dashboard weiter
            if (Auth::user()->isAdmin()) {
                return redirect()->route('admin.dashboard');
            }
            
            // Sonst zur Hauptseite oder zum urspru00fcnglich angeforderten Pfad
            return redirect()->intended('/');
        }

        return back()->withErrors([
            'email' => 'Die angegebenen Anmeldeinformationen stimmen nicht mit unseren Datensu00e4tzen u00fcberein.',
        ])->withInput($request->only('email', 'remember'));
    }

    /**
     * Zeigt die Registrierungsseite an.
     *
     * @return \Illuminate\View\View
     */
    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    /**
     * Bearbeitet die Registrierungsanfrage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput();
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_admin' => false, // Standardmu00e4u00dfig kein Admin
        ]);

        Auth::login($user);

        return redirect('admin/dashboard');
    }

    /**
     * Meldet den Benutzer ab.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}