<?php

namespace App\Services;

use App\Contracts\BillPaymentClientInterface;

class MockBillPaymentClient implements BillPaymentClientInterface
{
    public function testConnection(): array
    {
        return ['status' => 'success', 'message' => 'Mock VTPass connection successful.'];
    }

    public function purchaseAirtime(string $phoneNumber, string $network, float $amount, string $reference): array
    {
        return $this->successResponse($reference, $amount);
    }

    public function purchaseData(string $phoneNumber, string $network, string $bundleCode, string $reference): array
    {
        return $this->successResponse($reference, 0);
    }

    public function purchaseCableSubscription(string $smartCardNumber, string $packageCode, string $provider, string $reference): array
    {
        return $this->successResponse($reference, 0);
    }

    public function purchaseElectricity(string $meterNumber, string $disco, float $amount, string $reference, string $meterType = 'prepaid'): array
    {
        return $this->successResponse($reference, $amount);
    }

    public function purchaseEducation(string $studentId, string $examType, float $amount, string $reference): array
    {
        return $this->successResponse($reference, $amount);
    }

    public function queryTransaction(string $requestId): array
    {
        return [
            'status' => 'success',
            'transaction_id' => $requestId,
            'amount' => 100.00,
            'receipt' => 'MOCK-RCPT-001',
        ];
    }

    public function getDataBundles(string $network): array
    {
        return ['bundles' => []];
    }

    private function successResponse(string $reference, float $amount): array
    {
        return [
            'status' => 'success',
            'transaction_id' => $reference,
            'amount' => $amount,
            'receipt' => 'MOCK-RCPT-' . strtoupper(substr(md5($reference), 0, 8)),
            'code' => '000',
            'message' => 'Mock successful transaction',
        ];
    }
}
