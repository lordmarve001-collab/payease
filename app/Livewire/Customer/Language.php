<?php

namespace App\Livewire\Customer;

use Livewire\Component;

class Language extends Component
{
    public string $selectedLanguage = 'en';

    public function mount()
    {
        $this->selectedLanguage = app()->getLocale();
    }

    public function setLanguage(string $locale)
    {
        $this->selectedLanguage = $locale;
        session(['locale' => $locale]);
        app()->setLocale($locale);
        $this->dispatch('notify-success', message: 'Language updated!');
    }

    public function render()
    {
        return view('livewire.customer.language', [
            'languages' => [
                'en' => 'English',
                'ha' => 'Hausa',
                'yo' => 'Yoruba',
                'ig' => 'Igbo',
                'pcm' => 'Pidgin',
            ],
        ])->layout('components.layouts.customer');
    }
}
