<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use RuntimeException;

class EnsureKycTier
{
    public function handle(Request $request, Closure $next, int $requiredLevel = 0): Response
    {
        $user = $request->user();

        if ($user instanceof User && $user->kyc_level < $requiredLevel) {
            abort(Response::HTTP_FORBIDDEN, 'Please complete KYC verification to access this feature.');
        }

        return $next($request);
    }

    public static function ensureTransferAllowed(User $user, float $amount): void
    {
        if ($user->kyc_level < 1) {
            throw new RuntimeException('Complete your registration to send money.');
        }
    }
}
