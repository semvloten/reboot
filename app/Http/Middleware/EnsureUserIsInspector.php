<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Onderdeel: Weigert keurmeesterpagina's en acties voor gebruikers zonder de juiste rol.
 * Eisen: FE-13, RV-02, RV-07.
 * Ontwerp: T-24 (roltoegang).
 * Bouw: T-25 (roltoegang).
 * Geplande controle: T-26.
 */
class EnsureUserIsInspector
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || $request->user()->role !== 'inspector') {
            abort(403, 'Deze pagina is alleen toegankelijk voor keurmeesters.');
        }

        return $next($request);
    }
}
