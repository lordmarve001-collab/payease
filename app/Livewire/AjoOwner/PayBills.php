<?php

namespace App\Livewire\AjoOwner;

use App\Services\BillPaymentService;
use App\Services\WalletService;
use Illuminate\Support\Facades\Auth;
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

    // Education
    public string $educationStudentId = '';
    public string $educationExamType = '';
    public string $educationAmount = '';

    public string $validationMessage = '';
    public bool $isProcessing = false;
    public string $resultState = '';
    public string $resultMessage = '';
    public string $resultReference = '';

    protected BillPaymentService $billPaymentService;
    protected WalletService $walletService;

    public function boot(BillPaymentService $billPaymentService, WalletService $walletService): void
    {
        $this->billPaymentService = $billPaymentService;
        $this->walletService = $walletService;
    }

    public function selectCategory(string $category): void
    {
        $this->category = $category;
        $this->step = 'details';
        $this->validationMessage = '';
    }

    public function selectDataNetwork(string $network): void
    {
        $this->dataNetwork = $network;
        $this->dataBundleCode = '';
        $this->dataBundleName = '';
        $this->dataPrice = 0;
        $this->validationMessage = '';
    }

    public function selectDataBundle(string $code, string $name, float $price): void
    {
        $this->dataBundleCode = $code;
        $this->dataBundleName = $name;
        $this->dataPrice = $price;
        $this->validationMessage = '';
    }

    public function selectCableProvider(string $provider): void
    {
        $this->cableProvider = $provider;
        $this->cablePackageCode = '';
        $this->cablePackageName = '';
        $this->cablePrice = 0;
        $this->validationMessage = '';
    }

    public function selectCablePackage(string $code, string $name, float $price): void
    {
        $this->cablePackageCode = $code;
        $this->cablePackageName = $name;
        $this->cablePrice = $price;
        $this->validationMessage = '';
    }

    public function goToConfirm(): void
    {
        $this->validationMessage = '';

        if ($this->category === 'airtime') {
            $phone = trim($this->airtimePhone);
            if ($phone === '' || strlen($phone) < 10) {
                $this->validationMessage = 'Enter a valid phone number.';
                return;
            }
            if ($this->airtimeNetwork === '') {
                $this->validationMessage = 'Select a network.';
                return;
            }
            if ($this->airtimeAmount <= 0) {
                $this->validationMessage = 'Enter a valid amount.';
                return;
            }
        } elseif ($this->category === 'data') {
            $phone = trim($this->dataPhone);
            if ($phone === '' || strlen($phone) < 10) {
                $this->validationMessage = 'Enter a valid phone number.';
                return;
            }
            if ($this->dataNetwork === '') {
                $this->validationMessage = 'Select a network.';
                return;
            }
            if ($this->dataBundleCode === '') {
                $this->validationMessage = 'Select a data bundle.';
                return;
            }
        } elseif ($this->category === 'cable') {
            if ($this->cableProvider === '') {
                $this->validationMessage = 'Select a cable provider.';
                return;
            }
            if (trim($this->cableSmartCard) === '') {
                $this->validationMessage = 'Enter your smart card number.';
                return;
            }
            if ($this->cablePackageCode === '') {
                $this->validationMessage = 'Select a cable package.';
                return;
            }
        } elseif ($this->category === 'electricity') {
            if ($this->electricDisco === '') {
                $this->validationMessage = 'Select your electricity distributor.';
                return;
            }
            if (trim($this->electricMeterNumber) === '') {
                $this->validationMessage = 'Enter your meter number.';
                return;
            }
            if ($this->electricAmount === '' || (float) $this->electricAmount <= 0) {
                $this->validationMessage = 'Enter a valid amount.';
                return;
            }
        } elseif ($this->category === 'education') {
            if ($this->educationExamType === '') {
                $this->validationMessage = 'Select an exam type.';
                return;
            }
            if (trim($this->educationStudentId) === '') {
                $this->validationMessage = 'Enter your student ID.';
                return;
            }
            if ($this->educationAmount === '' || (float) $this->educationAmount <= 0) {
                $this->validationMessage = 'Enter a valid amount.';
                return;
            }
        }

        $this->step = 'confirm';
    }

    public function purchase(): void
    {
        if ($this->isProcessing) return;
        $this->isProcessing = true;

        $result = [];

        if ($this->category === 'airtime') {
            $result = $this->billPaymentService->purchaseAirtime(
                trim($this->airtimePhone),
                $this->airtimeNetwork,
                $this->airtimeAmount,
                'web'
            );
        } elseif ($this->category === 'data') {
            $result = $this->billPaymentService->purchaseData(
                trim($this->dataPhone),
                $this->dataNetwork,
                $this->dataBundleCode,
                $this->dataPrice,
                'web'
            );
        } elseif ($this->category === 'cable') {
            $result = $this->billPaymentService->purchaseCable(
                trim($this->cableSmartCard),
                $this->cablePackageCode,
                $this->cableProvider,
                $this->cablePrice,
                'web'
            );
        } elseif ($this->category === 'electricity') {
            $result = $this->billPaymentService->purchaseElectricity(
                trim($this->electricMeterNumber),
                $this->electricDisco,
                (float) $this->electricAmount,
                'web',
                $this->electricMeterType
            );
        } elseif ($this->category === 'education') {
            $result = $this->billPaymentService->purchaseEducation(
                trim($this->educationStudentId),
                $this->educationExamType,
                (float) $this->educationAmount,
                'web'
            );
        }

        $this->isProcessing = false;

        if (($result['status'] ?? '') === 'success') {
            $this->resultState = 'success';
            $this->resultMessage = ucfirst($this->category) . ' purchase was successful.';
            $this->resultReference = $result['reference'] ?? '';
        } else {
            $this->resultState = 'failed';
            $this->resultMessage = $result['error'] ?? ucfirst($this->category) . ' purchase failed.';
        }

        $this->step = 'result';
    }

    public function goBack(): void
    {
        if ($this->step === 'details') {
            $this->step = 'select';
            $this->category = '';
        } elseif ($this->step === 'confirm') {
            $this->step = 'details';
        } elseif ($this->step === 'result') {
            $this->step = 'select';
            $this->category = '';
            $this->resultState = '';
            $this->resultMessage = '';
        }
    }

    public function render()
    {
        $user = Auth::user();
        $wallet = $this->walletService->getCustomerWallet($user);

        $dataBundles = [];
        if ($this->dataNetwork) {
            $dataBundles = $this->billPaymentService->getDataBundles($this->dataNetwork)['bundles'] ?? [];
        }

        return view('livewire.ajo-owner.pay-bills', [
            'wallet' => $wallet,
            'dataBundles' => $dataBundles,
            'networks' => ['MTN', 'Airtel', 'Glo', '9mobile'],
            'discos' => [
                'AEDC' => 'Abuja Disco',
                'EKEDC' => 'Eko Disco',
                'IKEDC' => 'Ikeja Disco',
                'IBEDC' => 'Ibadan Disco',
                'PHED' => 'Port Harcourt Disco',
                'KEDCO' => 'Kano Disco',
                'JED' => 'Jos Disco',
                'KAEDCO' => 'Kaduna Disco',
            ],
            'cableProviders' => ['DSTV', 'GOtv', 'StarTimes'],
            'cablePackages' => [
                'DSTV' => [
                    ['code' => 'DSTV-PREMIUM', 'name' => 'Premium', 'price' => 24500],
                    ['code' => 'DSTV-COMPACT', 'name' => 'Compact Plus', 'price' => 18500],
                    ['code' => 'DSTV-FAMILY', 'name' => 'Compact', 'price' => 12500],
                    ['code' => 'DSTV-YANGA', 'name' => 'Yanga', 'price' => 4500],
                ],
                'GOtv' => [
                    ['code' => 'GOTV-MAX', 'name' => 'Max', 'price' => 6200],
                    ['code' => 'GOTV-PREMIUM', 'name' => 'Premium', 'price' => 4000],
                    ['code' => 'GOTV-SUPER', 'name' => 'Super', 'price' => 3100],
                ],
                'StarTimes' => [
                    ['code' => 'ST-PREMIUM', 'name' => 'Premium', 'price' => 3500],
                    ['code' => 'ST-CLASSIC', 'name' => 'Classic', 'price' => 2000],
                    ['code' => 'ST-BASIC', 'name' => 'Basic', 'price' => 1200],
                ],
            ],
        ])->layout('components.layouts.ajo-owner');
    }
}
