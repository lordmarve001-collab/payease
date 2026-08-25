<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AjoGroupResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'contribution_amount' => $this->contribution_amount,
            'frequency' => $this->frequency,
            'cycle_count' => $this->cycle_count,
            'total_members' => $this->total_members,
            'status' => $this->status,
            'created_at' => $this->created_at,
        ];
    }
}
