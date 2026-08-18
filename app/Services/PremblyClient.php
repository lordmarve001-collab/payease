<?php

namespace App\Services;

use App\Contracts\IdentityVerificationClientInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class PremblyClient implements IdentityVerificationClientInterface
{
    protected string $baseUrl;

    public function __construct(
        protected string $apiKey,
        protected string $appId = '',
        protected string $environment = 'sandbox',
    ) {
        $this->baseUrl = $environment === 'live'
            ? 'https://api.prembly.com/v1'
            : 'https://sandbox.prembly.com/v1';
    }

    public function verifyNin(string $nin, string $fullName, bool $consent): array
    {
        if (!$consent) {
            throw new RuntimeException('User consent is required for identity verification.');
        }

        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'app_id' => $this->appId,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/identity/ng/nin', [
                'nin' => $nin,
                'consent' => $consent,
            ]);

            if (!$response->successful()) {
                Log::warning('Prembly NIN verification failed', [
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

            $data = $response->json('data') ?? $response->json('result') ?? [];
            $reference = $response->json('reference') ?? $response->json('id') ?? '';
            $status = $data['status'] ?? $response->json('status') ?? '';

            $verified = strtolower((string) $status) === 'found' || ($response->successful() && !empty($data['nin']));

            return [
                'verified' => $verified,
                'match_confidence' => $verified ? 100.0 : 0.0,
                'provider_reference' => $reference,
                'raw_response' => $response->json(),
            ];
        } catch (\Throwable $e) {
            Log::error('Prembly NIN verification threw exception', [
                'error' => $e->getMessage(),
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
                'x-api-key' => $this->apiKey,
                'app_id' => $this->appId,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/identity/ng/bvn', [
                'bvn' => $bvn,
                'consent' => $consent,
            ]);

            if (!$response->successful()) {
                Log::warning('Prembly BVN verification failed', [
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

            $data = $response->json('data') ?? $response->json('result') ?? [];
            $reference = $response->json('reference') ?? $response->json('id') ?? '';
            $status = $data['status'] ?? $response->json('status') ?? '';

            $verified = strtolower((string) $status) === 'found' || ($response->successful() && !empty($data['bvn']));

            return [
                'verified' => $verified,
                'match_confidence' => $verified ? 100.0 : 0.0,
                'provider_reference' => $reference,
                'raw_response' => $response->json(),
            ];
        } catch (\Throwable $e) {
            Log::error('Prembly BVN verification threw exception', [
                'error' => $e->getMessage(),
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
        if (!$consent) {
            throw new RuntimeException('User consent is required for identity verification.');
        }

        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'app_id' => $this->appId,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/biometrics/ng/bvn/face', [
                'bvn' => $bvn,
                'selfie_image' => $selfieImageBase64,
                'consent' => $consent,
            ]);

            if (!$response->successful()) {
                Log::warning('Prembly BVN face match failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [
                    'verified' => false,
                    'match_confidence' => null,
                    'provider_reference' => $response->json('reference') ?? '',
                    'raw_response' => $response->json(),
                    'error' => $response->json('message') ?? 'BVN face match failed.',
                ];
            }

            $data = $response->json('data') ?? $response->json('result') ?? [];
            $reference = $response->json('reference') ?? $response->json('id') ?? '';
            $confidence = (float) ($data['confidence'] ?? $data['matchScore'] ?? $data['similarity'] ?? 0);
            $verified = $confidence >= 85.0;

            return [
                'verified' => $verified,
                'match_confidence' => $confidence,
                'provider_reference' => $reference,
                'raw_response' => $response->json(),
            ];
        } catch (\Throwable $e) {
            Log::error('Prembly BVN face match threw exception', [
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
}
