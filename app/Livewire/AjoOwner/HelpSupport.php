<?php

namespace App\Livewire\AjoOwner;

use Livewire\Component;

class HelpSupport extends Component
{
    public function render()
    {
        return view('livewire.ajo-owner.help-support')
            ->layout('components.layouts.ajo-owner');
    }
}
