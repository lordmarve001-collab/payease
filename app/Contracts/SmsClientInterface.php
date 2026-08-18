<?php

namespace App\Contracts;

interface SmsClientInterface
{
    /**
     * @return array{status:string,provider_id:string|null,error?:string|null}
     */
    public function send(string $phoneNumber, string $message): array;
}
