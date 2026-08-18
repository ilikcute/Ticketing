<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Log;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = $request->user();

        // Debug: catat role & permissions saat login untuk membantu investigasi
        try {
            Log::info('auth.login.info', [
                'id' => $user?->id,
                'email' => $user?->email,
                'role' => $user?->role,
                'permissions' => $user?->getAllPermissions(),
            ]);
        } catch (\Throwable $e) {
            // jangan ganggu alur login jika logging gagal
        }

        // Redirect dinamis berdasarkan hak akses modul user
        if ($user->hasPermission('access-dashboard')) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        if ($user->hasPermission('access-loket')) {
            return redirect()->intended('/loket');
        }

        if ($user->hasPermission('access-bib-check')) {
            return redirect()->intended('/bib-check');
        }

        if ($user->hasPermission('access-import')) {
            return redirect()->intended('/import');
        }

        if ($user->hasPermission('access-users')) {
            return redirect()->intended('/users');
        }

        return redirect()->intended('/bib-check');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
