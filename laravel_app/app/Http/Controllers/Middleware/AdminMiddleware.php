<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Autorise uniquement les utilisateurs marqués admin
     * On suppose une colonne booléenne `is_admin` sur users.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->is_admin) {
            // Option : redirect()->route('home') avec flash
            abort(403, 'Accès réservé aux administrateurs.');
        }

        return $next($request);
    }
}
