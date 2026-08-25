<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'transaction_type' => $this->transaction_type,
            'amount' => $this->amount,
            'status' => $this->status,
            'channel' => $this->channel,
            'description' => $this->description,
            'recipient_phone' => $this->recipient_phone,
            'completed_at' => $this->completed_at,
            'created_at' => $this->created_at,
        ];
    }
}
