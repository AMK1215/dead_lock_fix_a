<?php

namespace App\Http\Controllers\Api\V1\Webhook;

use App\Enums\SlotWebhookResponseCode;
use App\Enums\TransactionName;
use App\Http\Controllers\Api\V1\Webhook\Traits\UseWebhook;
use App\Http\Controllers\Controller;
use App\Http\Requests\Slot\SlotWebhookRequest;
use App\Models\SeamlessEvent;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wager;
use App\Services\Slot\SlotWebhookService;
use App\Services\Slot\SlotWebhookValidator;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log as FacadesLog;
use Log;

class PlaceBetController extends Controller
{
    use UseWebhook;

    public function placeBet(SlotWebhookRequest $request)
    {
        $retryCount = 3; // Number of retries for handling deadlocks

        for ($attempt = 0; $attempt < $retryCount; $attempt++) {
            try {
                // Begin the transaction after validation succeeds
                return DB::transaction(function () use ($request) {
                    // Validate the request
                    $validator = $request->check();
                    if ($validator->fails()) {
                        return $validator->getResponse();
                    }

                    // Get member's initial balance
                    $before_balance = $request->getMember()->balanceFloat;

                    // Create event for the wager
                    $event = $this->createEvent($request);

                    // Create wager transactions
                    $seamless_transactions = $this->createWagerTransactions(
                        $validator->getRequestTransactions(),
                        $event
                    );

                    // Process each seamless transaction
                    foreach ($seamless_transactions as $seamless_transaction) {
                        $this->processTransfer(
                            $request->getMember(),
                            User::adminUser(),
                            TransactionName::Stake,
                            $seamless_transaction->transaction_amount,
                            $seamless_transaction->rate,
                            [
                                'wager_id' => $seamless_transaction->wager_id,
                                'event_id' => $request->getMessageID(),
                                'seamless_transaction_id' => $seamless_transaction->id,
                            ]
                        );
                    }

                    // Lock the member's wallet for updating the balance
                    $request->getMember()->wallet()->lockForUpdate()->refreshBalance();

                    // Get member's final balance
                    $after_balance = $request->getMember()->balanceFloat;

                    // Return success response with the updated balance
                    return SlotWebhookService::buildResponse(
                        SlotWebhookResponseCode::Success,
                        $after_balance,
                        $before_balance
                    );
                });
            } catch (\Illuminate\Database\QueryException $e) {
                if ($e->getCode() === '40001') { // 40001 indicates a deadlock
                    // Log the deadlock error
                    FacadesLog::warning('Deadlock encountered during placeBet: Retrying...');

                    // Retry if deadlock occurs
                    continue;
                }

                // Re-throw if it's not a deadlock error
                throw $e;
            } catch (\Exception $e) {
                // Rollback the transaction and return an error response in case of other exceptions
                DB::rollBack();

                return response()->json([
                    'message' => $e->getMessage(),
                ]);
            }
        }

        // If all retry attempts fail, return an error response
        return response()->json([
            'message' => 'Failed to place bet after multiple attempts due to deadlocks.',
        ]);
    }

    // public function placeBet(SlotWebhookRequest $request)
    // {
    //     DB::beginTransaction();
    //     try {
    //         $validator = $request->check();

    //         if ($validator->fails()) {
    //             return $validator->getResponse();
    //         }

    //         $before_balance = $request->getMember()->balanceFloat;

    //         $event = $this->createEvent($request);

    //         $seamless_transactions = $this->createWagerTransactions($validator->getRequestTransactions(), $event);

    //         foreach ($seamless_transactions as $seamless_transaction) {
    //             $this->processTransfer(
    //                 $request->getMember(),
    //                 User::adminUser(),
    //                 TransactionName::Stake,
    //                 $seamless_transaction->transaction_amount,
    //                 $seamless_transaction->rate,
    //                 [
    //                     'wager_id' => $seamless_transaction->wager_id,
    //                     'event_id' => $request->getMessageID(),
    //                     'seamless_transaction_id' => $seamless_transaction->id,
    //                 ]
    //             );
    //         }

    //         $request->getMember()->wallet()->lockForUpdate()->refreshBalance();

    //         $after_balance = $request->getMember()->balanceFloat;

    //         DB::commit();

    //         return SlotWebhookService::buildResponse(
    //             SlotWebhookResponseCode::Success,
    //             $after_balance,
    //             $before_balance
    //         );
    //     } catch (\Exception $e) {
    //         DB::rollBack();

    //         return response()->json([
    //             'message' => $e->getMessage(),
    //         ]);
    //     }
    // }
}
