<?php

namespace App\Services;

use App\Contracts\IdentityVerificationClientInterface;

class MockIdentityVerificationClient implements IdentityVerificationClientInterface
{
    public function verifyNin(string $nin, string $fullName, bool $consent): array
    {
        if (! $consent) {
            return [
                'verified' => false,
                'match_confidence' => null,
                'provider_reference' => '',
                'raw_response' => [],
                'error' => 'User consent is required.',
            ];
        }

        if ($nin !== '12345678901') {
            return [
                'verified' => false,
                'match_confidence' => null,
                'provider_reference' => 'MOCK-NIN-' . substr($nin, 0, 3),
                'raw_response' => [],
                'error' => 'NIN not found.',
            ];
        }

        $nameMatch = $this->nameMatches($fullName);

        return [
            'verified' => $nameMatch,
            'match_confidence' => $nameMatch ? 100.0 : 0.0,
            'provider_reference' => 'MOCK-NIN-REF-001',
            'raw_response' => [
                'firstName' => 'Customer',
                'lastName' => 'Name',
                'status' => 'found',
            ],
            'error' => $nameMatch ? null : 'Name mismatch.',
        ];
    }

    public function verifyBvn(string $bvn, string $fullName, bool $consent): array
    {
        return [
            'verified' => true,
            'match_confidence' => 100.0,
            'provider_reference' => 'MOCK-BVN-REF-001',
            'raw_response' => [],
        ];
    }

    public function verifyBvnFaceMatch(string $bvn, string $selfieImageBase64, bool $consent): array
    {
        return [
            'verified' => true,
            'match_confidence' => 95.0,
            'provider_reference' => 'MOCK-FACE-REF-001',
            'raw_response' => [],
        ];
    }

    protected function nameMatches(string $fullName): bool
    {
        $normalized = strtolower(trim($fullName));

        return str_contains($normalized, 'customer') || str_contains($normalized, 'two visit') || str_contains($normalized, 'already verified');
    }
}
