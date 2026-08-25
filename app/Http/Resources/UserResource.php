<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'phone_number' => $this->phone_number,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'kyc_level' => $this->kyc_level,
            'status' => $this->status,
            'referral_code' => $this->referral_code,
            'created_at' => $this->created_at,
        ];
    }
}
