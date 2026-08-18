<?php

namespace App\Livewire\Customer;

use Livewire\Component;

class HelpSupport extends Component
{
    public function render()
    {
        return view('livewire.customer.help-support')
            ->layout('components.layouts.customer');
    }
}
