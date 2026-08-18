<?php

namespace App\Services;

use App\Contracts\IdentityVerificationClientInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class YouverifyClient implements IdentityVerificationClientInterface
{
    protected string $baseUrl;

    public function __construct(
        protected string $apiKey,
        protected string $environment = 'sandbox',
    ) {
        $this->baseUrl = $environment === 'live'
            ? 'https://api.youverify.co/api/v2'
            : 'https://sandbox.youverify.co/api/v2';
    }

    public function verifyNin(string $nin, string $fullName, bool $consent): array
    {
        if (!$consent) {
            throw new RuntimeException('User consent is required for identity verification.');
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/verifications/identity/nin', [
                'id' => $nin,
                'isSubjectConsent' => true,
            ]);

            if (!$response->successful()) {
                Log::warning('Youverify NIN verification failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [
                    'verified' => false,
                    'match_confidence' => null,
                    'provider_reference' => $response->json('reference') ?? '',
                    'raw_response' => $response->json(),
                    'error' => $response->json('message') ?? 'NIN verification failed.',
                ];
            }

            $data = $response->json('data') ?? [];
            $reference = $response->json('reference') ?? '';
            $returnedName = trim($data['firstName'] ?? '') . ' ' . trim($data['lastName'] ?? '');
            $nameMatch = $this->fuzzyNameMatch($fullName, $returnedName);

            $verified = ($data['status'] ?? '') === 'found' && $nameMatch;

            return [
                'verified' => $verified,
                'match_confidence' => $nameMatch ? 100.0 : 0.0,
                'provider_reference' => $reference,
                'raw_response' => $response->json(),
            ];
        } catch (\Throwable $e) {
            Log::error('Youverify NIN verification threw exception', [
                'error' => $e->getMessage(),
                'nin' => substr($nin, 0, 3) . '*****',
            ]);

            return [
                'verified' => false,
                'match_confidence' => null,
                'provider_reference' => '',
                'raw_response' => [],
                'error' => 'Provider error: ' . $e->getMessage(),
            ];
        }
    }

    public function verifyBvn(string $bvn, string $fullName, bool $consent): array
    {
        if (!$consent) {
            throw new RuntimeException('User consent is required for identity verification.');
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/verifications/identity/bvn', [
                'id' => $bvn,
                'isSubjectConsent' => true,
            ]);

            if (!$response->successful()) {
                Log::warning('Youverify BVN verification failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [
                    'verified' => false,
                    'match_confidence' => null,
                    'provider_reference' => $response->json('reference') ?? '',
                    'raw_response' => $response->json(),
                    'error' => $response->json('message') ?? 'BVN verification failed.',
                ];
            }

            $data = $response->json('data') ?? [];
            $reference = $response->json('reference') ?? '';
            $returnedName = trim($data['firstName'] ?? '') . ' ' . trim($data['lastName'] ?? '');
            $nameMatch = $this->fuzzyNameMatch($fullName, $returnedName);

            $verified = ($data['status'] ?? '') === 'found' && $nameMatch;

            return [
                'verified' => $verified,
                'match_confidence' => $nameMatch ? 100.0 : 0.0,
                'provider_reference' => $reference,
                'raw_response' => $response->json(),
            ];
        } catch (\Throwable $e) {
            Log::error('Youverify BVN verification threw exception', [
                'error' => $e->getMessage(),
                'bvn' => substr($bvn, 0, 3) . '*****',
            ]);

            return [
                'verified' => false,
                'match_confidence' => null,
                'provider_reference' => '',
                'raw_response' => [],
                'error' => 'Provider error: ' . $e->getMessage(),
            ];
        }
    }

    public function verifyBvnFaceMatch(string $bvn, string $selfieImageBase64, bool $consent): array
    {
        return [
            'verified' => false,
            'match_confidence' => null,
            'provider_reference' => '',
            'raw_response' => [],
            'error' => 'Biometric face match not supported by Youverify. Use Prembly for face validation.',
        ];
    }

    protected function fuzzyNameMatch(string $submitted, string $returned): bool
    {
        $normalize = fn (string $name): array => array_values(
            array_unique(
                array_filter(
                    array_map('strtolower', preg_split('/\s+/', trim($name))),
                    fn (string $p): bool => !in_array($p, ['mr', 'mrs', 'ms', 'dr', 'prof', 'i', 'ii', 'iii'], true)
                )
            )
        );

        $submittedParts = $normalize($submitted);
        $returnedParts = $normalize($returned);

        if (empty($submittedParts) || empty($returnedParts)) {
            return false;
        }

        $intersection = array_intersect($submittedParts, $returnedParts);
        $matchRatio = count($intersection) / max(count($submittedParts), count($returnedParts));

        return $matchRatio >= 0.5;
    }
}
