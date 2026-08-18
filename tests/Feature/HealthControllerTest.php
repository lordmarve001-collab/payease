<?php

namespace Tests\Feature;

use App\Http\Controllers\HealthController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HealthControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function health_endpoint_returns_healthy_when_services_are_ok(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertOk();
        $response->assertJsonPath('status', 'healthy');
        $response->assertJsonPath('checks.database.status', 'healthy');
        $response->assertJsonPath('checks.cache.status', 'healthy');
        $response->assertJsonPath('checks.queue.status', 'warning');
        $response->assertJsonPath('checks.queue.message', 'Queue is running synchronously.');
    }

    #[Test]
    public function health_endpoint_exposes_environment_outside_production(): void
    {
        $this->app->detectEnvironment(fn () => 'testing');

        $response = $this->getJson('/api/health');

        $response->assertOk();
        $response->assertJsonPath('environment', 'testing');
    }

    #[Test]
    public function health_endpoint_hides_environment_in_production(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $response = $this->getJson('/api/health');

        $response->assertOk();
        $response->assertJsonMissing(['environment']);
    }

    #[Test]
    public function health_endpoint_reports_redis_not_configured_when_not_used(): void
    {
        Config::set('cache.default', 'array');
        Config::set('session.driver', 'array');
        Config::set('queue.default', 'sync');

        $response = $this->getJson('/api/health');

        $response->assertOk();
        $response->assertJsonPath('checks.redis.status', 'not_configured');
    }

    #[Test]
    public function controller_reports_redis_unhealthy_when_ping_fails(): void
    {
        Config::set('cache.default', 'redis');
        Config::set('session.driver', 'redis');
        Config::set('queue.default', 'redis');

        Redis::shouldReceive('connection->ping')
            ->once()
            ->andThrow(new \Exception('Connection refused'));

        $controller = new HealthController();
        $response = $controller();

        $this->assertSame(503, $response->getStatusCode());
        $this->assertSame('unhealthy', $response->getData(true)['checks']['redis']['status']);
    }

    #[Test]
    public function controller_reports_database_unhealthy_when_pdo_fails(): void
    {
        DB::shouldReceive('connection->getPdo')
            ->once()
            ->andThrow(new \Exception('Connection refused'));

        DB::shouldReceive('connection->getDriverName')
            ->never();

        $controller = new HealthController();
        $response = $controller();

        $this->assertSame(503, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertSame('degraded', $data['status']);
        $this->assertSame('unhealthy', $data['checks']['database']['status']);
        $this->assertSame('Connection refused', $data['checks']['database']['error']);
    }

    #[Test]
    public function controller_reports_cache_unhealthy_when_cache_store_fails(): void
    {
        Cache::shouldReceive('put')
            ->once()
            ->andThrow(new \Exception('Cache store unreachable'));

        $controller = new HealthController();
        $response = $controller();

        $this->assertSame(503, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertSame('degraded', $data['status']);
        $this->assertSame('unhealthy', $data['checks']['cache']['status']);
        $this->assertSame('Cache store unreachable', $data['checks']['cache']['error']);
    }

    #[Test]
    public function controller_reports_queue_unhealthy_when_connection_fails(): void
    {
        Config::set('queue.default', 'database');
        Queue::shouldReceive('connection->size')
            ->once()
            ->andThrow(new \Exception('Queue connection failed'));

        $controller = new HealthController();
        $response = $controller();

        $this->assertSame(503, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertSame('degraded', $data['status']);
        $this->assertSame('unhealthy', $data['checks']['queue']['status']);
        $this->assertSame('Queue connection failed', $data['checks']['queue']['error']);
    }
}
