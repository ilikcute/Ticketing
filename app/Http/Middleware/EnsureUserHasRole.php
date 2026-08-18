<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403, 'Anda belum login.');
        }

        $roleValue = is_object($user->role) ? $user->role->value : $user->role;

        // Admin selalu memiliki akses penuh ke semua rute
        if ($roleValue === 'admin') {
            return $next($request);
        }

        // Cek hak akses berdasarkan permission matriks yang disimpan Admin
        foreach ($roles as $roleOrPerm) {
            // Konversi nama role legacy ke permission code
            $permCode = match ($roleOrPerm) {
                'loket' => 'access-loket',
                'undian' => 'access-bib-check',
                'viewer' => 'access-bib-check',
                default => $roleOrPerm,
            };

            if ($user->hasPermission($permCode)) {
                return $next($request);
            }
        }

        abort(403, 'Anda tidak memiliki hak akses ke modul/halaman ini.');
    }
}
