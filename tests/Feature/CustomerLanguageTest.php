<?php

namespace Tests\Feature;

use App\Livewire\Customer\Language;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerLanguageTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'phone_number' => '2348012345678',
            'full_name' => 'Test User',
            'kyc_level' => 1,
            'status' => 'active',
        ]);

        $this->actingAs($this->user);
    }

    public function test_language_renders_with_default_english(): void
    {
        Livewire::test(Language::class)
            ->assertSet('selectedLanguage', 'en');
    }

    public function test_set_language_updates_session_and_locale(): void
    {
        Livewire::test(Language::class)
            ->call('setLanguage', 'ha')
            ->assertSet('selectedLanguage', 'ha');

        $this->assertEquals('ha', session('locale'));
        $this->assertEquals('ha', app()->getLocale());
    }

    public function test_set_language_yoruba(): void
    {
        Livewire::test(Language::class)
            ->call('setLanguage', 'yo')
            ->assertSet('selectedLanguage', 'yo');

        $this->assertEquals('yo', session('locale'));
    }

    public function test_set_language_igbo(): void
    {
        Livewire::test(Language::class)
            ->call('setLanguage', 'ig')
            ->assertSet('selectedLanguage', 'ig');

        $this->assertEquals('ig', session('locale'));
    }

    public function test_set_language_pidgin(): void
    {
        Livewire::test(Language::class)
            ->call('setLanguage', 'pcm')
            ->assertSet('selectedLanguage', 'pcm');

        $this->assertEquals('pcm', session('locale'));
    }

    public function test_languages_options_are_rendered(): void
    {
        Livewire::test(Language::class)
            ->assertSee('English')
            ->assertSee('Hausa')
            ->assertSee('Yoruba')
            ->assertSee('Igbo')
            ->assertSee('Pidgin');
    }
}
