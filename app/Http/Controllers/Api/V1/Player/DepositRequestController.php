<?php

namespace App\Http\Controllers\Api\V1\Player;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\DepositRequest;
use App\Http\Resources\DepositLogResource;
use App\Models\DepositRequest as ModelsDepositRequest;
use App\Traits\HttpResponses;
use Exception;
use Illuminate\Support\Facades\Auth;

class DepositRequestController extends Controller
{
    use HttpResponses;

    public function deposit(DepositRequest $request)
    {
        try {
            $player = Auth::user();
            $deposit = ModelsDepositRequest::create([
                'agent_payment_type_id' => $request->agent_payment_type_id,
                'user_id' => $player->id,
                'agent_id' => $player->agent_id,
                'amount' => $request->amount,
            ]);

            return $this->success($deposit, 'Deposit Request Success');
        } catch (Exception $e) {
            $this->error('', $e->getMessage(), 401);
        }
    }

    public function log()
    {
        $deposit = ModelsDepositRequest::with('paymentType')->where('user_id', Auth::id())->get();

        return $this->success(DepositLogResource::collection($deposit));
    }
}
