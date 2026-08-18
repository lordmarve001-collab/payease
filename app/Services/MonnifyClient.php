<?php

namespace App\Services;

use App\Contracts\MmoClientInterface;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class MonnifyClient implements MmoClientInterface
{
    public function __construct(
        protected array $credentials,
        protected string $environment = 'sandbox',
    ) {
    }

    public function testConnection(): array
    {
        $auth = $this->authenticate();

        return [
            'status' => 'success',
            'message' => 'Authentication successful.',
            'access_token' => (string) ($auth['accessToken'] ?? ''),
            'expires_in' => $auth['expiresIn'] ?? null,
        ];
    }

    public function createWallet(User $user): array
    {
        $this->assertConfigured();

        $identity = trim((string) ($user->bvn ?: $user->nin));

        if ($identity === '') {
            throw new RuntimeException('Monnify reserved-account creation requires the customer BVN or NIN.');
        }

        $response = $this->authorizedRequest()
            ->post($this->baseUrl() . '/api/v2/bank-transfer/reserved-accounts', [
                'accountReference' => $this->buildAccountReference($user),
                'accountName' => (string) $user->full_name,
                'currencyCode' => 'NGN',
                'contractCode' => $this->credential('contract_code'),
                'customerEmail' => $this->resolveCustomerEmail($user),
                'customerName' => (string) $user->full_name,
                'getAllAvailableBanks' => true,
                $user->bvn ? 'bvn' : 'nin' => $identity,
            ]);

        $payload = $this->extractResponseBody($response->json());
        $primaryAccount = $payload['accounts'][0] ?? [];
        $accountNumber = (string) ($primaryAccount['accountNumber'] ?? '');

        if ($accountNumber === '') {
            throw new RuntimeException('Monnify did not return a reserved account number.');
        }

        return [
            'mmo_wallet_id' => (string) ($payload['reservationReference'] ?? $payload['accountReference'] ?? $accountNumber),
            'account_number' => $accountNumber,
            'wallet_account_number' => $accountNumber,
            'provider_reference' => (string) ($payload['accountReference'] ?? ''),
            'status' => strtolower((string) ($payload['status'] ?? 'active')),
            'provider_metadata' => [
                'reservation_reference' => $payload['reservationReference'] ?? null,
                'account_name' => $payload['accountName'] ?? null,
                'accounts' => $payload['accounts'] ?? [],
                'collection_channel' => $payload['collectionChannel'] ?? null,
            ],
        ];
    }

    public function credit(string $mmoWalletId, float $amount, string $reference): array
    {
        throw new RuntimeException('Monnify credits for customer bank transfers are webhook-driven. Use the Monnify webhook to credit local wallets instead of calling credit().');
    }

    public function debit(string $mmoWalletId, float $amount, string $reference): array
    {
        throw new RuntimeException('Monnify disbursement requires destination bank details and OTP authorization. Use initiateDisbursement() for outbound transfers.');
    }

    public function getBalance(string $accountReference): float
    {
        $this->assertConfigured();

        $response = $this->authorizedRequest()
            ->get($this->baseUrl() . '/api/v1/bank-transfer/reserved-accounts/transactions', [
                'accountReference' => $accountReference,
                'page' => 0,
                'size' => 500,
            ]);

        $data = $response->json();
        $content = $data['responseBody']['content'] ?? [];
        $balance = 0.0;

        foreach ($content as $txn) {
            if (strtolower($txn['paymentStatus'] ?? '') === 'paid') {
                $balance += (float) ($txn['amountPaid'] ?? $txn['amount'] ?? 0);
            }
        }

        Log::channel('monnify')->info('Monnify balance calculated from transactions', [
            'account_reference' => $accountReference,
            'transaction_count' => count($content),
            'balance' => $balance,
        ]);

        return $balance;
    }

    public function getReservedAccountDetails(string $accountReference): array
    {
        $this->assertConfigured();

        $response = $this->authorizedRequest()
            ->get($this->baseUrl() . "/api/v2/bank-transfer/reserved-accounts/{$accountReference}");

        $payload = $response->json();

        if (!($payload['requestSuccessful'] ?? false)) {
            throw new RuntimeException($payload['responseMessage'] ?? 'Failed to fetch reserved account details');
        }

        return $payload['responseBody'] ?? [];
    }

    public function resolveBankAccountName(string $bankCode, string $accountNumber): string
    {
        $this->assertConfigured();

        $response = $this->authorizedRequest()
            ->get($this->baseUrl() . '/api/v1/disbursements/account/validate', [
                'bankCode' => $bankCode,
                'accountNumber' => $accountNumber,
            ]);

        $payload = $response->json();

        if (!($payload['requestSuccessful'] ?? false)) {
            throw new RuntimeException($payload['responseMessage'] ?? 'Could not resolve account name.');
        }

        $name = trim((string) ($payload['responseBody']['accountName'] ?? ''));

        if ($name === '') {
            throw new RuntimeException('Account name is empty.');
        }

        Log::channel('monnify')->info('Monnify name enquiry resolved', [
            'bank_code' => $bankCode,
            'account_number' => $accountNumber,
            'account_name' => $name,
        ]);

        return $name;
    }

    public function initiateDisbursement(
        string $destinationBankCode,
        string $destinationAccountNumber,
        string $destinationAccountName,
        float $amount,
        string $reference,
        string $narration = '',
    ): array {
        $this->assertConfigured();

        $response = $this->authorizedRequest()
            ->post($this->baseUrl() . '/api/v2/disbursements/single', [
                'amount' => $amount,
                'reference' => $reference,
                'narration' => $narration !== '' ? $narration : "PayEase disbursement {$reference}",
                'destinationBankCode' => $destinationBankCode,
                'destinationAccountNumber' => $destinationAccountNumber,
                'destinationAccountName' => $destinationAccountName,
                'currency' => 'NGN',
                'sourceAccountNumber' => $this->disbursementWalletId(),
            ]);

        $body = $response->json();
        $responseBody = (array) ($body['responseBody'] ?? []);
        $requestSuccessful = ($body['requestSuccessful'] ?? false) === true;

        Log::channel('monnify')->info('Monnify disbursement initiated', [
            'reference' => $reference,
            'amount' => $amount,
            'destination_account' => $destinationAccountNumber,
            'destination_bank' => $destinationBankCode,
            'request_successful' => $requestSuccessful,
            'response_message' => $body['responseMessage'] ?? null,
            'response_body' => $responseBody,
        ]);

        if (!$requestSuccessful) {
            throw new RuntimeException((string) ($body['responseMessage'] ?? 'Monnify disbursement initiation failed.'));
        }

        return [
            'status' => strtolower((string) ($responseBody['status'] ?? 'pending')),
            'transaction_reference' => (string) ($responseBody['transactionReference'] ?? $reference),
            'external_reference' => (string) ($responseBody['externalReference'] ?? ''),
            'requires_otp' => $this->isOtpRequired($responseBody),
            'otp_reference' => (string) ($responseBody['otpReference'] ?? $responseBody['transactionReference'] ?? ''),
        ];
    }

    public function completeDisbursementOtp(string $otpReference, string $otp): array
    {
        $this->assertConfigured();

        $response = $this->authorizedRequest()
            ->post($this->baseUrl() . '/api/v2/disbursements/single/validate-otp', [
                'otpReference' => $otpReference,
                'otp' => $otp,
            ]);

        $body = $response->json();
        $responseBody = (array) ($body['responseBody'] ?? []);
        $requestSuccessful = ($body['requestSuccessful'] ?? false) === true;

        Log::channel('monnify')->info('Monnify disbursement OTP validation', [
            'otp_reference' => $otpReference,
            'request_successful' => $requestSuccessful,
            'response_message' => $body['responseMessage'] ?? null,
        ]);

        if (!$requestSuccessful) {
            throw new RuntimeException((string) ($body['responseMessage'] ?? 'Monnify OTP validation failed.'));
        }

        return [
            'status' => strtolower((string) ($responseBody['status'] ?? 'completed')),
            'transaction_reference' => (string) ($responseBody['transactionReference'] ?? ''),
            'external_reference' => (string) ($responseBody['externalReference'] ?? ''),
        ];
    }

    public function verifyWebhookSignature(?string $signature, string $payload): bool
    {
        if ($this->isLiveEnvironment() && blank($signature)) {
            return false;
        }

        if (blank($signature)) {
            return true;
        }

        $computed = hash_hmac('sha512', $payload, $this->credential('secret_key'));

        return hash_equals(strtolower((string) $signature), strtolower($computed));
    }

    public function isAllowedWebhookIp(?string $ip): bool
    {
        if (!$this->isLiveEnvironment()) {
            return true;
        }

        return in_array(trim((string) $ip), ['35.242.133.146'], true);
    }

    public function authenticate(): array
    {
        $this->assertConfigured();

        $response = Http::acceptJson()
            ->withBasicAuth($this->credential('api_key'), $this->credential('secret_key'))
            ->post($this->baseUrl() . '/api/v1/auth/login');

        return $this->extractResponseBody($response->json());
    }

    protected function authorizedRequest()
    {
        $accessToken = (string) ($this->authenticate()['accessToken'] ?? '');

        if ($accessToken === '') {
            throw new RuntimeException('Monnify did not return an access token.');
        }

        return Http::acceptJson()
            ->asJson()
            ->withToken($accessToken);
    }

    protected function extractResponseBody(?array $payload): array
    {
        $payload ??= [];

        if (($payload['requestSuccessful'] ?? false) !== true) {
            throw new RuntimeException((string) ($payload['responseMessage'] ?? 'Monnify request failed.'));
        }

        return (array) ($payload['responseBody'] ?? []);
    }

    protected function assertConfigured(): void
    {
        foreach (['api_key', 'secret_key', 'contract_code'] as $key) {
            if (trim((string) ($this->credentials[$key] ?? '')) === '') {
                throw new RuntimeException('Monnify credentials are incomplete. Save the API key, secret key, and contract code first.');
            }
        }
    }

    protected function disbursementWalletId(): string
    {
        $walletId = trim((string) ($this->credentials['wallet_id'] ?? ''));

        if ($walletId === '') {
            throw new RuntimeException('Monnify disbursement wallet ID (wallet_id) is not configured. Add your disbursement wallet account number from Monnify dashboard Settings > Disbursement.');
        }

        return $walletId;
    }

    protected function credential(string $key): string
    {
        return trim((string) ($this->credentials[$key] ?? ''));
    }

    public function initiateTransaction(
        float $amount,
        string $customerName,
        string $customerEmail,
        string $paymentReference,
        string $paymentDescription,
        string $redirectUrl,
        array $metadata = [],
    ): array {
        $response = $this->authorizedRequest()
            ->post($this->baseUrl() . '/api/v1/merchant/transactions/init-transaction', [
                'amount' => $amount,
                'customerName' => $customerName,
                'customerEmail' => $customerEmail,
                'paymentReference' => $paymentReference,
                'paymentDescription' => $paymentDescription,
                'redirectUrl' => $redirectUrl,
                'currencyCode' => 'NGN',
                'contractCode' => $this->credential('contract_code'),
                'paymentMethods' => ['CARD', 'ACCOUNT_TRANSFER'],
                'metadata' => $metadata,
            ])->json();

        return $this->extractResponseBody($response);
    }

    public function getBanks(): array
    {
        $response = $this->authorizedRequest()
            ->get($this->baseUrl() . '/api/v1/banks')->json();

        return $this->extractResponseBody($response);
    }

    public function getTransactionStatusByPaymentReference(string $paymentReference): array
    {
        $this->assertConfigured();

        $response = $this->authorizedRequest()
            ->get($this->baseUrl() . '/api/v2/merchant/transactions/query', [
                'paymentReference' => $paymentReference,
            ])->json();

        return $this->extractResponseBody($response);
    }

    protected function baseUrl(): string
    {
        return $this->isLiveEnvironment()
            ? 'https://api.monnify.com'
            : 'https://sandbox.monnify.com';
    }

    protected function isLiveEnvironment(): bool
    {
        return strtolower(trim($this->environment)) === 'live';
    }

    protected function buildAccountReference(User $user): string
    {
        return Str::upper('PAYEASE-' . substr(str_replace('-', '', (string) $user->id), 0, 20));
    }

    protected function resolveCustomerEmail(User $user): string
    {
        $localPart = preg_replace('/\D+/', '', (string) $user->phone_number) ?: Str::lower(Str::substr(str_replace('-', '', (string) $user->id), 0, 12));

        return $localPart . '@payease.local';
    }

    protected function isOtpRequired(array $responseBody): bool
    {
        $status = strtolower((string) ($responseBody['status'] ?? ''));

        return $status === 'pending' || str_contains($status, 'otp');
    }
}
