<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DepositLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'payment_type' => $this->paymentType->name,
            'account_name' => $this->user->account_name,
            'account_number' => $this->user->account_number,
            'amount' => $this->amount,
            'status' => $this->status === 0 ? 'Pending' : ($this->status === 1 ? 'Success' : 'Reject'),  // Converts the 'status' property to a human-readable string
            'datetime' => $this->created_at->format('Y-m-d H:i:s'),  // Formats the 'created_at' property as a string
        ];
    }
}
