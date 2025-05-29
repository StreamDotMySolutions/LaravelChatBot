<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\ExternalApp;

class VerifyApiToken
{
    public function handle(Request $request, Closure $next)
    {

        $headers = $request->headers->all();
        //\Log::info($headers);
        $token = $request->bearerToken();
        //\Log::info($request->bearerToken());
        $app = ExternalApp::where('api_token', $token)
            ->where('is_active', true)
            ->where(function($q) {
                $q->whereNull('token_expires_at')
                ->orWhere('token_expires_at', '>', now());
            })
            ->first();

        if (!$app) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Simpan app info dalam request untuk log atau kegunaan lain
        $request->merge(['external_app' => $app]);

        return $next($request);
    }
}
