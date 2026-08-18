<?php

namespace App\Livewire\Admin;

use App\Services\PlatformLiquidityService;
use Livewire\Component;

class Liquidity extends Component
{
    public function render(PlatformLiquidityService $liquidityService)
    {
        return view('livewire.admin.liquidity', [
            'snapshot' => $liquidityService->getLiquiditySnapshot(),
        ])->layout('components.layouts.admin');
    }
}
