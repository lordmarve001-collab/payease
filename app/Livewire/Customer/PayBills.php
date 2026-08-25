<?php

namespace App\Livewire\Customer;

use App\Models\User;
use App\Services\BillPaymentService;
use App\Services\WalletService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class PayBills extends Component
{
    public string $step = 'select';
    public string $category = '';

    public string $dataPhone = '';
    public string $dataNetwork = '';
    public string $dataBundleCode = '';
    public string $dataBundleName = '';
    public float $dataPrice = 0;

    public string $cableProvider = '';
    public string $cableSmartCard = '';
    public string $cablePackageCode = '';
    public string $cablePackageName = '';
    public float $cablePrice = 0;

    public string $electricDisco = '';
    public string $electricMeterType = 'prepaid';
    public string $electricMeterNumber = '';
    public string $electricAmount = '';

    public string $educationStudentId = '';
    public string $educationExamType = '';
    public string $educationAmount = '';

    public string $validationMessage = '';
    public bool $isProcessing = false;
    public string $resultState = '';
    public string $resultMessage = '';
    public string $resultReference = '';

    public string $pin1 = '';
    public string $pin2 = '';
    public string $pin3 = '';
    public string $pin4 = '';
    public string $pin5 = '';
    public string $pin6 = '';
    public string $pinError = '';

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

    public function setElectricMeterType(string $type): void
    {
        $this->electricMeterType = $type;
    }

    public function goToConfirm(): void
    {
        $this->validationMessage = '';

        if (!$this->validateDetails()) {
            return;
        }

        $this->step = 'confirm';
    }

    public function goToPin(): void
    {
        $this->validationMessage = '';
        $this->pinError = '';
        $this->resetPinInputs();

        if (!$this->validateDetails()) {
            return;
        }

        $this->step = 'pin';
        $this->dispatch('focus-bill-pin');
    }

    protected function validateDetails(): bool
    {
        if ($this->category === 'data') {
            $phone = trim($this->dataPhone);
            if ($phone === '' || strlen($phone) < 10) {
                $this->validationMessage = 'Enter a valid phone number.';
                return false;
            }
            if ($this->dataNetwork === '') {
                $this->validationMessage = 'Select a network.';
                return false;
            }
            if ($this->dataBundleCode === '') {
                $this->validationMessage = 'Select a data bundle.';
                return false;
            }
        } elseif ($this->category === 'cable') {
            if ($this->cableProvider === '') {
                $this->validationMessage = 'Select a cable provider.';
                return false;
            }
            if (trim($this->cableSmartCard) === '') {
                $this->validationMessage = 'Enter your smart card number.';
                return false;
            }
            if ($this->cablePackageCode === '') {
                $this->validationMessage = 'Select a cable package.';
                return false;
            }
        } elseif ($this->category === 'electricity') {
            if ($this->electricDisco === '') {
                $this->validationMessage = 'Select your electricity distributor.';
                return false;
            }
            if (trim($this->electricMeterNumber) === '') {
                $this->validationMessage = 'Enter your meter number.';
                return false;
            }
            if ($this->electricAmount === '' || (float) $this->electricAmount <= 0) {
                $this->validationMessage = 'Enter a valid amount.';
                return false;
            }
        } elseif ($this->category === 'education') {
            if ($this->educationExamType === '') {
                $this->validationMessage = 'Select an exam type.';
                return false;
            }
            if (trim($this->educationStudentId) === '') {
                $this->validationMessage = 'Enter your student ID or registration number.';
                return false;
            }
            if ($this->educationAmount === '' || (float) $this->educationAmount <= 0) {
                $this->validationMessage = 'Enter a valid amount.';
                return false;
            }
        }

        return true;
    }

    public function updated($property): void
    {
        if (in_array($property, ['pin1', 'pin2', 'pin3', 'pin4', 'pin5', 'pin6'], true)) {
            $this->$property = preg_replace('/\D/', '', (string) $this->$property) ?? '';
            $this->pinError = '';
        }
    }

    public function confirmPin(): void
    {
        $this->pinError = '';

        $pin = $this->pin1 . $this->pin2 . $this->pin3 . $this->pin4 . $this->pin5 . $this->pin6;

        if (strlen($pin) !== 6) {
            $this->pinError = 'Enter your 6-digit transfer PIN.';
            $this->dispatch('focus-bill-pin');
            return;
        }

        /** @var User $user */
        $user = Auth::user();

        $pinLockout = app(\App\Services\PinLockoutService::class);

        if ($pinLockout->isLocked('transfer', $user->id)) {
            $this->pinError = 'Too many failed attempts. Please try again later.';
            $this->resetPinInputs();
            $this->dispatch('focus-bill-pin');
            return;
        }

        if (!$user->verifyTransferPin($pin)) {
            $result = $pinLockout->recordFailedAttempt('transfer', $user);
            $this->pinError = $result['message'];
            $this->resetPinInputs();
            $this->dispatch('focus-bill-pin');
            return;
        }

        $pinLockout->clearAttempts('transfer', $user->id);
        $this->pinError = '';

        $this->purchase();
    }

    public function purchase(): void
    {
        if ($this->isProcessing) return;
        $this->isProcessing = true;

        $result = [];

        try {
            if ($this->category === 'data') {
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
        } catch (\Throwable $e) {
            $this->isProcessing = false;
            $this->resetPinInputs();
            $this->resultState = 'failed';
            $this->resultMessage = $e->getMessage();
            $this->step = 'result';
            return;
        }

        $this->isProcessing = false;
        $this->resetPinInputs();

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
        } elseif ($this->step === 'pin') {
            $this->step = 'details';
            $this->resetPinInputs();
        } elseif ($this->step === 'result') {
            $this->reset();
        }
    }

    protected function resetPinInputs(): void
    {
        $this->pin1 = '';
        $this->pin2 = '';
        $this->pin3 = '';
        $this->pin4 = '';
        $this->pin5 = '';
        $this->pin6 = '';
    }

    public function render()
    {
        $user = Auth::user();
        $wallet = $this->walletService->getCustomerWallet($user);

        $dataBundles = [];
        if ($this->dataNetwork) {
            $dataBundles = $this->billPaymentService->getDataBundles($this->dataNetwork)['bundles'] ?? [];
        }

        return view('livewire.customer.pay-bills', [
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
        ])->layout('components.layouts.customer');
    }
}
