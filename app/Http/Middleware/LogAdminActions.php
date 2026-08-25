<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LogAdminActions
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if ($response->isSuccessful() && Auth::check()) {
            $user = Auth::user();

            if ($user->hasRole(['admin', 'super_admin']) && $request->isMethod('POST')) {
                AuditLog::create([
                    'user_id' => $user->id,
                    'action' => 'admin_action',
                    'entity_type' => 'admin',
                    'new_values' => [
                        'method' => $request->method(),
                        'path' => $request->path(),
                        'input' => collect($request->input())->except(['password', 'pin', 'otp'])->toArray(),
                    ],
                    'ip_address' => $request->ip(),
                ]);
            }
        }

        return $response;
    }
}
