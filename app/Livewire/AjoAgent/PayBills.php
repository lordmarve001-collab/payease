<?php

namespace App\Livewire\AjoAgent;

use App\Models\Agent;
use App\Models\User;
use App\Services\BillPaymentService;
use App\Services\WalletService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class PayBills extends Component
{
    public string $step = 'select';
    public string $category = '';

    // Airtime
    public string $airtimePhone = '';
    public string $airtimeNetwork = '';
    public float $airtimeAmount = 0;

    // Data
    public string $dataPhone = '';
    public string $dataNetwork = '';
    public string $dataBundleCode = '';
    public string $dataBundleName = '';
    public float $dataPrice = 0;

    // Cable
    public string $cableProvider = '';
    public string $cableSmartCard = '';
    public string $cablePackageCode = '';
    public string $cablePackageName = '';
    public float $cablePrice = 0;

    // Electricity
    public string $electricDisco = '';
    public string $electricMeterType = 'prepaid';
    public string $electricMeterNumber = '';
    public string $electricAmount = '';

    // PIN
    public string $pin = '';
    public bool $processing = false;
    public string $resultState = '';
    public string $resultMessage = '';
    public string $resultReference = '';

    public function selectCategory(string $cat): void
    {
        $this->category = $cat;
        $this->step = 'details';
    }

    public function goBack(): void
    {
        $this->step = 'select';
        $this->category = '';
        $this->resetErrorBag();
    }

    public function submitBill(): void
    {
        $this->validate([
            'pin' => ['required', 'digits:6'],
        ]);

        /** @var User $user */
        $user = Auth::user();
        if (!$user->verifyTransferPin($this->pin)) {
            $this->addError('pin', 'Incorrect PIN.');
            return;
        }

        $this->processing = true;

        try {
            $billService = app(BillPaymentService::class);

            $result = match ($this->category) {
                'airtime' => $billService->purchaseAirtime($this->airtimePhone, $this->airtimeNetwork, (float) $this->airtimeAmount),
                'data' => $billService->purchaseData($this->dataPhone, $this->dataNetwork, $this->dataBundleCode, (float) ($this->dataBundlePrice ?? 0)),
                'cable' => $billService->purchaseCable($this->cableSmartCard, $this->cablePackageCode, $this->cableProvider, (float) ($this->cablePackagePrice ?? 0)),
                'electricity' => $billService->purchaseElectricity($this->electricMeterNumber, $this->electricDisco, (float) $this->electricAmount, 'web', $this->electricMeterType),
                default => throw new \RuntimeException('Unknown bill category.'),
            };

            $this->resultState = 'success';
            $this->resultMessage = $result['message'] ?? 'Bill payment successful!';
            $this->resultReference = $result['reference'] ?? $result['transaction_id'] ?? 'N/A';
            $this->step = 'result';
        } catch (\Throwable $e) {
            $this->resultState = 'error';
            $this->resultMessage = $e->getMessage();
            $this->step = 'result';
        } finally {
            $this->processing = false;
        }
    }

    public function resetForm(): void
    {
        $this->reset();
        $this->step = 'select';
    }

    public function render()
    {
        /** @var User $user */
        $user = Auth::user();
        /** @var Agent|null $agent */
        $agent = $user->agent;

        $walletService = app(WalletService::class);
        $wallet = $agent ? $walletService->getAgentWallet($user) : null;

        $networks = config('billpayments.networks', ['mtn' => 'MTN', 'airtel' => 'Airtel', 'glo' => 'Glo', '9mobile' => '9mobile']);
        $cableProviders = config('billpayments.cable_providers', ['dstv' => 'DStv', 'gotv' => 'GOtv', 'startimes' => 'StarTimes']);
        $discos = config('billpayments.discos', ['ikeja' => 'Ikeja Electric', 'eko' => 'Eko Electricity', 'abuja' => 'Abuja Electricity', 'ibadan' => 'Ibadan Electricity']);

        return view('livewire.ajo-agent.pay-bills', [
            'agent' => $agent,
            'wallet' => $wallet,
            'networks' => $networks,
            'cableProviders' => $cableProviders,
            'discos' => $discos,
        ])->layout('components.layouts.ajo-agent');
    }
}
