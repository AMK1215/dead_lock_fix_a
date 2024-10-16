<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AgentResource extends JsonResource
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
            'name' => $this->name,
            'user_name' => $this->user_name,
            'phone' => $this->phone,
            'email' => $this->email,
            'agent_logo' => asset('assets/img/sitelogo/'.$this->agent_logo),
            'payment_type_id' => $this->payment_type_id,
            'payment_type_name' => $this->paymentType->name,
            'account_number' => $this->account_number,
            'account_name' => $this->account_name,
            'line_id' => $this->line_id,
        ];
    }
}
