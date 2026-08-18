<?php

namespace App\Livewire\Admin;

use App\Services\AdminService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Overview extends Component
{
    public function render()
    {
        $adminUser = Auth::user();
        /** @var AdminService $adminService */
        $adminService = app(AdminService::class);

        return view('livewire.admin.overview', [
            'adminUser' => $adminUser,
            'kpis' => $adminService->getOverviewKpis(),
            'transactionVolumeChart' => $adminService->getTransactionVolumeChart(),
            'agentPerformanceChart' => $adminService->getAgentPerformanceChart(),
            'alerts' => $adminService->getRecentAlerts(),
            'kycCounts' => $adminService->getPendingKycCount(),
        ])->layout('components.layouts.admin');
    }
}
