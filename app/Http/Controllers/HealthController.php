<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $status = 200;
        $checks = [];

        // Database check
        try {
            DB::connection()->getPdo();
            $checks['database'] = ['status' => 'healthy', 'driver' => DB::connection()->getDriverName()];
        } catch (\Throwable $e) {
            $checks['database'] = ['status' => 'unhealthy', 'error' => $e->getMessage()];
            $status = 503;
        }

        // Redis check (only if the application is configured to use Redis)
        $usesRedis = in_array('redis', [config('cache.default'), config('session.driver'), config('queue.default')], true);
        if ($usesRedis) {
            try {
                Redis::connection()->ping();
                $checks['redis'] = ['status' => 'healthy'];
            } catch (\Throwable $e) {
                $checks['redis'] = ['status' => 'unhealthy', 'error' => $e->getMessage()];
                $status = 503;
            }
        } else {
            $checks['redis'] = ['status' => 'not_configured'];
        }

        // Cache check
        try {
            $key = 'health_check_' . time();
            Cache::put($key, true, 1);
            $cached = Cache::get($key);
            Cache::forget($key);
            $checks['cache'] = ['status' => $cached ? 'healthy' : 'unhealthy'];
            if (!$cached) {
                $status = 503;
            }
        } catch (\Throwable $e) {
            $checks['cache'] = ['status' => 'unhealthy', 'error' => $e->getMessage()];
            $status = 503;
        }

        // Queue connection check
        try {
            $connection = config('queue.default');
            if ($connection === 'sync') {
                $checks['queue'] = ['status' => 'warning', 'message' => 'Queue is running synchronously.'];
            } else {
                Queue::connection()->size();
                $checks['queue'] = ['status' => 'healthy', 'connection' => $connection];
            }
        } catch (\Throwable $e) {
            $checks['queue'] = ['status' => 'unhealthy', 'error' => $e->getMessage()];
            $status = 503;
        }

        $data = [
            'status' => $status === 200 ? 'healthy' : 'degraded',
            'timestamp' => now()->toIso8601String(),
            'checks' => $checks,
        ];

        if (! app()->environment('production')) {
            $data['environment'] = app()->environment();
        }

        return response()->json($data, $status);
    }
}
