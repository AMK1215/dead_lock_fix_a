<?php

namespace App\Services\Slot;

use App\Enums\SlotWebhookResponseCode;

class SlotWebhookService
{
    public static function buildResponse(SlotWebhookResponseCode $responseCode, $balance, $before_balance)
    {
        return [
            'ErrorCode' => $responseCode->value,
            'ErrorMessage' => $responseCode->name,
            'Balance' => $balance,
            'BeforeBalance' => $before_balance,
            //'Balance' => number_format($balance, 2, '.', ''), // Ensure two decimal places
            //'BeforeBalance' => number_format($before_balance, 2, '.', ''),  // Ensure two decimal places

        ];
    }
}
