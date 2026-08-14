<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProfil
{
    public function handle(Request $request, Closure $next, string $profilAttendu): Response
    {
        $user = $request->user();

        if (!$user || $user->role !== $profilAttendu) {
            return response()->json([
                'message' => "Accès réservé au profil : {$profilAttendu}",
            ], 403);
        }

        return $next($request);
    }
}