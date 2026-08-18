<?php

namespace Tests\Unit;

use App\Helpers\PinSecurity;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PinSecurityTest extends TestCase
{
    #[Test]
    public function it_rejects_common_weak_pins(): void
    {
        $this->assertTrue(PinSecurity::isWeak('000000'));
        $this->assertTrue(PinSecurity::isWeak('111111'));
        $this->assertTrue(PinSecurity::isWeak('123456'));
        $this->assertTrue(PinSecurity::isWeak('654321'));
        $this->assertTrue(PinSecurity::isWeak('121212'));
    }

    #[Test]
    public function it_rejects_sequential_pins(): void
    {
        $this->assertTrue(PinSecurity::isWeak('234567'));
        $this->assertTrue(PinSecurity::isWeak('987654'));
    }

    #[Test]
    public function it_rejects_repeated_pins(): void
    {
        $this->assertTrue(PinSecurity::isWeak('444444'));
    }

    #[Test]
    public function it_rejects_invalid_length_pins(): void
    {
        $this->assertTrue(PinSecurity::isWeak('12345'));
        $this->assertTrue(PinSecurity::isWeak('1234567'));
        $this->assertTrue(PinSecurity::isWeak('abcdef'));
    }

    #[Test]
    public function it_accepts_strong_pins(): void
    {
        $this->assertFalse(PinSecurity::isWeak('584927'));
        $this->assertFalse(PinSecurity::isWeak('193847'));
        $this->assertFalse(PinSecurity::isWeak('482901'));
    }
}
