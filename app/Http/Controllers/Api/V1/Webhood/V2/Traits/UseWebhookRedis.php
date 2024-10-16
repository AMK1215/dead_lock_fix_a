<?php

namespace App\Http\Controllers\Api\V1\Webhood\V2\Traits;

use App\Enums\TransactionName;
use App\Enums\TransactionStatus;
use App\Enums\WagerStatus;
use App\Http\Requests\Slot\SlotWebhookRequest;
use App\Models\Admin\GameType;
use App\Models\Admin\GameTypeProduct;
use App\Models\Admin\Product;
use App\Models\SeamlessEvent;
use App\Models\SeamlessTransaction;
use App\Models\User;
use App\Models\Wager;
use App\Services\Slot\Dto\RequestTransaction;
use App\Services\WalletService;
use Exception;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

trait UseWebhookRedis
{
    /**
     * Cache event and store in database.
     */
    public function createEvent(SlotWebhookRequest $request): SeamlessEvent
    {
        // Cache event in Redis
        $ttl = 600; // Time-to-live (in seconds)
        Redis::setex('event:'.$request->getMessageID(), $ttl, json_encode($request->all()));

        // Store event in the database
        return SeamlessEvent::create([
            'user_id' => $request->getMember()->id,
            'message_id' => $request->getMessageID(),
            'product_id' => $request->getProductID(),
            'request_time' => $request->getRequestTime(),
            'raw_data' => $request->all(),
        ]);
    }

    /**
     * Create wager transactions from request transactions.
     *
     * @param  array<int, RequestTransaction>  $requestTransactions
     * @return array<int, SeamlessTransaction>
     *
     * @throws MassAssignmentException
     */
    public function createWagerTransactions($requestTransactions, SeamlessEvent $event, bool $refund = false)
    {
        $seamless_transactions = [];

        foreach ($requestTransactions as $requestTransaction) {
            DB::transaction(function () use (&$seamless_transactions, $event, $requestTransaction, $refund) {
                $wager = Wager::firstOrCreate(
                    ['seamless_wager_id' => $requestTransaction->WagerID],
                    [
                        'user_id' => $event->user_id,
                        'seamless_wager_id' => $requestTransaction->WagerID,
                    ]
                );

                if ($refund) {
                    $wager->update(['status' => WagerStatus::Refund]);
                } elseif (! $wager->wasRecentlyCreated) {
                    $wager->update([
                        'status' => $requestTransaction->TransactionAmount > 0 ? WagerStatus::Win : WagerStatus::Lose,
                    ]);
                }

                $game_type = GameType::where('code', $requestTransaction->GameType)->first();
                $product = Product::where('code', $requestTransaction->ProductID)->first();
                $game_type_product = GameTypeProduct::where('game_type_id', $game_type->id)
                    ->where('product_id', $product->id)
                    ->first();

                if (! $game_type_product) {
                    throw new Exception('Game type product combination not found');
                }

                $rate = $game_type_product->rate;

                $seamless_transactions[] = $event->transactions()->create([
                    'user_id' => $event->user_id,
                    'wager_id' => $wager->id,
                    'game_type_id' => $game_type->id,
                    'product_id' => $product->id,
                    'seamless_transaction_id' => $requestTransaction->TransactionID,
                    'rate' => $rate,
                    'transaction_amount' => $requestTransaction->TransactionAmount,
                    'bet_amount' => $requestTransaction->BetAmount,
                    'valid_amount' => $requestTransaction->ValidBetAmount,
                    'status' => $requestTransaction->Status,
                ]);
            }, 3); // Retry 3 times if deadlock occurs
        }

        return $seamless_transactions;
    }

    /**
     * Process wallet transfer between users.
     */
    public function processTransfer(User $from, User $to, TransactionName $transactionName, float $amount, int $rate, array $meta)
    {
        DB::transaction(function () use ($from, $to, $transactionName, $amount, $meta) {
            // Transfer the amount between wallets
            app(WalletService::class)->transfer(
                $from,
                $to,
                abs($amount),
                $transactionName,
                $meta
            );
        }, 5); // Retry 5 times in case of deadlock
    }
}
