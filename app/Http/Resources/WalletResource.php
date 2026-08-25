<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WalletResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'balance' => $this->balance,
            'available_balance' => $this->available_balance,
            'wallet_type' => $this->wallet_type,
            'currency' => $this->currency,
            'status' => $this->status,
            'daily_limit' => $this->daily_limit,
            'single_txn_limit' => $this->single_txn_limit,
            'account_number' => $this->account_number,
        ];
    }
}
