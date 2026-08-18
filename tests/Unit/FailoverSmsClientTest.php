<?php

namespace Tests\Unit;

use App\Contracts\SmsClientInterface;
use App\Services\FailoverSmsClient;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FailoverSmsClientTest extends TestCase
{
    #[Test]
    public function it_returns_success_when_primary_succeeds(): void
    {
        $primary = $this->createMock(SmsClientInterface::class);
        $primary->method('send')->willReturn(['status' => 'sent', 'provider_id' => 'primary-1']);

        $fallback = $this->createMock(SmsClientInterface::class);
        $fallback->expects($this->never())->method('send');

        $client = new FailoverSmsClient([$primary, $fallback]);
        $result = $client->send('08012345678', 'Hello');

        $this->assertSame('sent', $result['status']);
        $this->assertSame('primary-1', $result['provider_id']);
    }

    #[Test]
    public function it_falls_back_when_primary_fails(): void
    {
        $primary = $this->createMock(SmsClientInterface::class);
        $primary->method('send')->willReturn(['status' => 'failed', 'provider_id' => null, 'error' => 'timeout']);

        $fallback = $this->createMock(SmsClientInterface::class);
        $fallback->method('send')->willReturn(['status' => 'sent', 'provider_id' => 'fallback-1']);

        $client = new FailoverSmsClient([$primary, $fallback]);
        $result = $client->send('08012345678', 'Hello');

        $this->assertSame('sent', $result['status']);
        $this->assertSame('fallback-1', $result['provider_id']);
    }

    #[Test]
    public function it_returns_failed_when_all_providers_fail(): void
    {
        $primary = $this->createMock(SmsClientInterface::class);
        $primary->method('send')->willReturn(['status' => 'failed', 'provider_id' => null, 'error' => 'timeout']);

        $fallback = $this->createMock(SmsClientInterface::class);
        $fallback->method('send')->willReturn(['status' => 'failed', 'provider_id' => null, 'error' => 'rate limited']);

        $client = new FailoverSmsClient([$primary, $fallback]);
        $result = $client->send('08012345678', 'Hello');

        $this->assertSame('failed', $result['status']);
        $this->assertSame('rate limited', $result['error']);
    }

    #[Test]
    public function it_falls_back_when_primary_throws(): void
    {
        $primary = $this->createMock(SmsClientInterface::class);
        $primary->method('send')->willThrowException(new \RuntimeException('connection error'));

        $fallback = $this->createMock(SmsClientInterface::class);
        $fallback->method('send')->willReturn(['status' => 'sent', 'provider_id' => 'fallback-1']);

        $client = new FailoverSmsClient([$primary, $fallback]);
        $result = $client->send('08012345678', 'Hello');

        $this->assertSame('sent', $result['status']);
        $this->assertSame('fallback-1', $result['provider_id']);
    }
}
