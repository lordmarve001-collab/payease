<?php

namespace App\Contracts;

interface IdentityVerificationClientInterface
{
    public function verifyNin(string $nin, string $fullName, bool $consent): array;

    public function verifyBvn(string $bvn, string $fullName, bool $consent): array;

    public function verifyBvnFaceMatch(string $bvn, string $selfieImageBase64, bool $consent): array;
}
