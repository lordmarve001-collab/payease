<?php

namespace App\Services;

use App\Contracts\BillPaymentClientInterface;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class VTPassClient implements BillPaymentClientInterface
{
    public function __construct(
        protected string $apiKey,
        protected string $username,
        protected string $environment = 'sandbox'
    ) {
    }

    public function testConnection(): array
    {
        try {
            $this->assertConfigured();
            $resp = $this->signedGet('/api/version');
            if ($this->isStatusOk($resp)) {
                return ['status' => 'success', 'message' => 'VTPass connection successful.'];
            }
            return ['status' => 'failed', 'message' => $this->getMsg($resp)];
        } catch (\Throwable $e) {
            return ['status' => 'failed', 'message' => $e->getMessage()];
        }
    }

    public function purchaseAirtime(string $phoneNumber, string $network, float $amount, string $reference): array
    {
        return $this->send('Airtime', $reference, $this->networkCode($network), [
            'phoneNumber' => $this->phone($phoneNumber),
            'amount' => $amount,
        ]);
    }

    public function purchaseData(string $phoneNumber, string $network, string $bundleCode, string $reference): array
    {
        return $this->send('Data', $reference, $bundleCode, [
            'phoneNumber' => $this->phone($phoneNumber),
        ]);
    }

    public function purchaseCableSubscription(string $smartCardNumber, string $packageCode, string $provider, string $reference): array
    {
        return $this->send('Cable', $reference, $this->cableCode($provider), [
            'phoneNumber' => '',
            'smartCardNumber' => $smartCardNumber,
            'cardNumber' => $smartCardNumber,
        ], $packageCode);
    }

    public function purchaseElectricity(string $meterNumber, string $disco, float $amount, string $reference, string $meterType = 'prepaid'): array
    {
        return $this->send('Pay', $reference, $this->networkCode($disco), [
            'phoneNumber' => '',
            'cardNumber' => $meterNumber,
            'amount' => $amount,
            'parentCode' => $this->networkCode($disco),
            'studentReg' => $meterType === 'prepaid' ? '01' : '02',
            'agent' => '000003',
        ], '00000');
    }

    public function purchaseEducation(string $studentId, string $examType, float $amount, string $reference): array
    {
        return $this->send('Education', $reference, $this->educationCode($examType), [
            'phoneNumber' => '',
            'studentReg' => $studentId,
            'amount' => $amount,
        ]);
    }

    public function queryTransaction(string $requestId): array
    {
        try {
            $path = '/api/trans/reqidRefer/' . $this->username . '/' . $requestId . '/' . $this->apiKey;
            $response = $this->signedGet($path);
            if ($this->isStatusOk($response)) {
                $ct = $this->content($response);
                return [
                    'status' => 'success',
                    'transaction_id' => $requestId,
                    'amount' => isset($ct['amount']) ? (float) $ct['amount'] : null,
                    'receipt' => $ct['receipt_number'] ?? null,
                ];
            }
            return ['status' => 'failed', 'error' => $this->getMsg($response)];
        } catch (\Throwable $e) {
            return ['status' => 'failed', 'error' => $e->getMessage()];
        }
    }

    public function getDataBundles(string $network): array
    {
        return ['bundles' => $this->defaultBundles(strtoupper(trim($network)))];
    }

    private function assertConfigured(): void
    {
        if (empty(trim($this->username)) || empty(trim($this->apiKey))) {
            throw new RuntimeException('VTPass is not configured. Username and API key required.');
        }
    }

    private function baseUrl(): string
    {
        return $this->isLive() ? 'https://api-prox.vtpass.com' : 'https://sandbox.vtpass.com';
    }

    private function isLive(): bool
    {
        return $this->environment === 'live';
    }

    private function phone(string $number): string
    {
        $number = trim($number);
        if (str_starts_with($number, '+')) {
            $number = substr($number, 1);
        }
        if (str_starts_with($number, '0')) {
            $number = '234' . substr($number, 1);
        } elseif (strlen($number) === 10) {
            $number = '234' . $number;
        }
        return $number;
    }

    private function networkCode(string $network): string
    {
        $map = ['MTN' => '1', 'AIRTEL' => '2', 'GLO' => '3', '9MOBILE' => '4'];
        return $map[strtoupper(trim($network))] ?? $network;
    }

    private function cableCode(string $provider): string
    {
        $map = ['DSTV' => '1', 'GOTV' => '2', 'STARTIMES' => '3'];
        return $map[strtoupper(trim($provider))] ?? $provider;
    }

    private function educationCode(string $examType): string
    {
        $map = ['WAEC' => 'WAEC', 'JAMB' => 'JAMB', 'NECO' => 'NECO', 'SCHOOL_FEES' => 'SCHOOLFEES'];
        return $map[strtoupper(trim($examType))] ?? $examType;
    }

    private function send(string $type, string $reference, string $code, array $attributes, ?string $subCode = null): array
    {
        $this->assertConfigured();

        $data = array_merge($attributes, [
            'agentUsername' => $this->username,
            'password' => $this->apiKey,
            'reference' => $reference,
            'type' => $type,
            'code' => $code,
        ]);

        if ($subCode !== null) {
            $data['sub_code'] = $subCode;
        }

        try {
            $response = Http::timeout(30)
                ->connectTimeout(10)
                ->withBasicAuth($this->username, $this->apiKey)
                ->asJson()
                ->post($this->baseUrl() . '/api/payments', $data)
                ->json();

            if ($this->isStatusOk($response)) {
                $ct = $this->content($response);
                return [
                    'status' => 'success',
                    'transaction_id' => $reference,
                    'amount' => isset($ct['amount']) ? (float) $ct['amount'] : (isset($data['amount']) ? (float) $data['amount'] : 0),
                    'receipt' => $ct['receipt_number'] ?? null,
                    'code' => '000',
                    'message' => $this->getMsg($response),
                    'meta' => $ct,
                ];
            }

            return [
                'status' => 'failed',
                'error' => $this->getMsg($response),
                'code' => $this->getStatus($response),
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    private function signedGet(string $path, array $data = []): array
    {
        $this->assertConfigured();
        $url = $this->baseUrl() . $path;
        $response = Http::timeout(30)
            ->connectTimeout(10)
            ->withBasicAuth($this->username, $this->apiKey)
            ->asJson()
            ->get($url, $data);

        if (!$response->successful()) {
            return [
                'status' => (string) $response->status(),
                'response_description' => $response->body() ?: 'HTTP request failed',
            ];
        }
        return $response->json() ?: ['status' => '400'];
    }

    private function isStatusOk(?array $r): bool
    {
        return isset($r['status']) && $r['status'] === '000';
    }

    private function getMsg(?array $r): string
    {
        return $r['response_description'] ?? $r['message'] ?? 'Unknown error';
    }

    private function getStatus(?array $r): string
    {
        return $r['status'] ?? '-1';
    }

    private function content(?array $r): array
    {
        return is_array($r['content'] ?? null) ? $r['content'] : [];
    }

    private function defaultBundles(string $network): array
    {
        $bundles = [
            'MTN' => [
                ['code' => 'MTN10', 'name' => '10 MB', 'price' => 10, 'validity' => '1 day'],
                ['code' => 'MTN30', 'name' => '30 MB', 'price' => 20, 'validity' => '1 day'],
                ['code' => 'MTN75', 'name' => '75 MB', 'price' => 50, 'validity' => '1 day'],
                ['code' => 'MTN200', 'name' => '200 MB', 'price' => 100, 'validity' => '7 days'],
                ['code' => 'MTN500', 'name' => '500 MB', 'price' => 200, 'validity' => '7 days'],
                ['code' => 'MTN1000', 'name' => '1 GB', 'price' => 300, 'validity' => '30 days'],
                ['code' => 'MTN2000', 'name' => '2 GB', 'price' => 500, 'validity' => '30 days'],
            ],
            'AIRTEL' => [
                ['code' => 'AIRT100', 'name' => '100 MB', 'price' => 100, 'validity' => '28 days'],
                ['code' => 'AIRT300', 'name' => '300 MB', 'price' => 200, 'validity' => '28 days'],
                ['code' => 'AIRT500', 'name' => '500 MB', 'price' => 300, 'validity' => '30 days'],
                ['code' => 'AIRT1G', 'name' => '1 GB', 'price' => 500, 'validity' => '30 days'],
            ],
            'GLO' => [
                ['code' => 'GLO100', 'name' => '1.3 GB', 'price' => 100, 'validity' => '1 day'],
                ['code' => 'GLO200', 'name' => '2.8 GB', 'price' => 200, 'validity' => '7 days'],
                ['code' => 'GLO500', 'name' => '5.5 GB', 'price' => 500, 'validity' => '30 days'],
            ],
            '9MOBILE' => [
                ['code' => '9MB100', 'name' => '100 MB', 'price' => 25, 'validity' => '1 day'],
                ['code' => '9MB250', 'name' => '250 MB', 'price' => 50, 'validity' => '7 days'],
                ['code' => '9MB500', 'name' => '500 MB', 'price' => 100, 'validity' => '30 days'],
                ['code' => '9MB1G', 'name' => '1 GB', 'price' => 150, 'validity' => '30 days'],
            ],
        ];

        return $bundles[$network] ?? [];
    }
}
