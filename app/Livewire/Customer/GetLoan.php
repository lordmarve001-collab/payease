<?php

namespace App\Livewire\Customer;

use Livewire\Component;

class GetLoan extends Component
{
    public function render()
    {
        return view('livewire.customer.get-loan')
            ->layout('components.layouts.customer');
    }
}
