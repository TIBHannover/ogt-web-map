<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class UserController extends Controller
{
    /**
     * Display a listing of all users
     *
     * @return \Illuminate\View\View
     */
    public function index(): View
    {
        // Check admin permissions
        if (!Auth::user() || !Auth::user()->is_admin) {
            abort(403, "Unauthorized access");
        }

        // Use Eloquent model to ensure proper date casting
        $users = User::orderBy("created_at", "desc")->paginate(10);

        return view("admin.users", ["users" => $users]);
    }

    /**
     * Promote a user to admin role
     *
     * @param int $id User ID to promote
     * @return \Illuminate\Http\RedirectResponse
     */
    public function promote($id): RedirectResponse
    {
        // Verify current user is admin
        if (!Auth::user() || !Auth::user()->is_admin) {
            abort(403, "Unauthorized access");
        }

        try {
            $user = User::findOrFail($id);

            // Don't promote if already admin
            if ($user->is_admin == true) {
                return back()->with(
                    "info",
                    "{$user->name} ist bereits Administrator.",
                );
            }

            $user->is_admin = true;
            $user->save();

            return back()->with(
                "success",
                "{$user->name} wurde zum Administrator befördert.",
            );
        } catch (\Exception $e) {
            Log::error("Error promoting user: " . $e->getMessage());

            return back()->with(
                "error",
                "Fehler beim Befördern des Benutzers: {$e->getMessage()}",
            );
        }
    }

    /**
     * Demote a user from admin role
     *
     * @param int $id User ID to demote
     * @return \Illuminate\Http\RedirectResponse
     */
    public function demote($id): RedirectResponse
    {
        // Verify current user is admin
        if (!Auth::user() || !Auth::user()->is_admin) {
            abort(403, "Unauthorized access");
        }

        // Prevent users from demoting themselves
        if (Auth::id() == $id) {
            return back()->with(
                "error",
                "Sie können sich nicht selbst die Administratorrechte entziehen.",
            );
        }

        try {
            $user = User::findOrFail($id);

            // Don't demote if not admin
            if (!$user->is_admin) {
                return back()->with(
                    "info",
                    "{$user->name} ist kein Administrator.",
                );
            }

            $user->is_admin = false;
            $user->save();

            return back()->with(
                "success",
                "{$user->name} ist nicht mehr Administrator.",
            );
        } catch (\Exception $e) {
            Log::error("Error demoting user: " . $e->getMessage());

            return back()->with(
                "error",
                "Fehler beim Entfernen der Administratorrechte: {$e->getMessage()}",
            );
        }
    }

    /**
     * Show form for creating a new admin user
     *
     * @return \Illuminate\View\View
     */
    public function create(): View
    {
        // Verify current user is admin
        if (!Auth::user() || !Auth::user()->is_admin) {
            abort(403, "Unauthorized access");
        }

        return view("admin.users.create");
    }

    /**
     * Store a newly created admin user
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request): RedirectResponse
    {
        // Verify current user is admin
        if (!Auth::user() || !Auth::user()->is_admin) {
            abort(403, "Unauthorized access");
        }

        // Validate request data
        $request->validate([
            "name" => "required|string|max:255",
            "email" => "required|string|email|max:255|unique:users",
            "password" => "required|string|min:8|confirmed",
        ]);

        try {
            // Create the new user with admin status based on checkbox
            $user = User::create([
                "name" => $request->name,
                "email" => $request->email,
                "password" => Hash::make($request->password),
                "is_admin" => $request->has("is_admin"),
            ]);

            return redirect()
                ->route("admin.users")
                ->with(
                    "success",
                    $user->is_admin
                        ? "Administrator {$user->name} wurde erfolgreich erstellt."
                        : "Benutzer {$user->name} wurde erfolgreich erstellt.",
                );
        } catch (\Exception $e) {
            Log::error("Error creating admin user: " . $e->getMessage());

            return back()
                ->with(
                    "error",
                    "Fehler beim Erstellen des Benutzers: {$e->getMessage()}",
                )
                ->withInput(
                    $request->except("password", "password_confirmation"),
                );
        }
    }

    /**
     * Reset a user's password (admin only)
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function resetPassword(Request $request, $id): RedirectResponse
    {
        // Check if user is admin
        if (!Auth::user() || !Auth::user()->is_admin) {
            abort(403, "Unauthorized");
        }

        $request->validate([
            "password" => "required|string|min:8|confirmed",
        ]);

        try {
            $user = User::findOrFail($id);
            $user->password = Hash::make($request->password);
            $user->save();

            return back()->with(
                "success",
                "Passwort für {$user->name} wurde zurückgesetzt.",
            );
        } catch (\Exception $e) {
            Log::error("Error resetting user password: " . $e->getMessage());

            return back()->with(
                "error",
                "Fehler beim Zurücksetzen des Passworts: {$e->getMessage()}",
            );
        }
    }
}
