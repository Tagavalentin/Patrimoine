<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Garde d'accès aux pages : exige un JWT en session
 * (posé par AuthController après login via la gateway).
 */
class EnsureFrontendAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = session('jwt_token');
        $expiresAt = (int) session('jwt_expires_at', 0);

        if (! is_string($token) || $token === '' || $expiresAt <= time()) {
            if (is_string($token) && $token !== '') {
                session()->forget(['jwt_token', 'jwt_user', 'jwt_expires_at']);
            }

            return redirect()->route('login');
        }

        return $next($request);
    }
}
