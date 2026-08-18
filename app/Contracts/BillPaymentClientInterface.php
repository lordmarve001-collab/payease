<?php

namespace App\Contracts;

interface BillPaymentClientInterface
{
    /** @return array{status:string,transaction_id?:string,amount?:float,receipt?:string,error?:string,meta?:array} */
    public function purchaseAirtime(string $phoneNumber, string $network, float $amount, string $reference): array;

    /** @return array{status:string,transaction_id?:string,amount?:float,receipt?:string,error?:string,meta?:array} */
    public function purchaseData(string $phoneNumber, string $network, string $bundleCode, string $reference): array;

    /** @return array{status:string,transaction_id?:string,amount?:float,receipt?:string,error?:string,meta?:array} */
    public function purchaseCableSubscription(string $smartCardNumber, string $packageCode, string $provider, string $reference): array;

    /** @return array{status:string,transaction_id?:string,amount?:float,receipt?:string,error?:string,meta?:array} */
    public function purchaseElectricity(string $meterNumber, string $disco, float $amount, string $reference, string $meterType = 'prepaid'): array;

    /** @return array{status:string,transaction_id?:string,amount?:float,receipt?:string,error?:string,meta?:array} */
    public function purchaseEducation(string $studentId, string $examType, float $amount, string $reference): array;

    /** @return array{status:string,transaction_id?:string,amount?:float,receipt?:string,error?:string,meta?:array,code?:string} */
    public function queryTransaction(string $requestId): array;

    /** @return array{status:string,message?:string,bundles?:array} */
    public function getDataBundles(string $network): array;

    /** @return array{status:string,message?:string} */
    public function testConnection(): array;
}
