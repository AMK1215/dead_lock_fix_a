<?php

namespace App\Http\Controllers\Api\V1\Webhook;

use App\Enums\SlotWebhookResponseCode;
use App\Enums\TransactionName;
use App\Http\Controllers\Api\V1\Webhook\Traits\OptimizedBettingProcess;
use App\Http\Controllers\Controller;
use App\Http\Requests\Slot\SlotWebhookRequest;
use App\Models\User;
use App\Services\Slot\SlotWebhookService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class VersionNewPlaceBetController extends Controller
{
    use OptimizedBettingProcess;
    /**

   *  this method is not to reduce admin balance
 */
    //  public function placeBetNew(SlotWebhookRequest $request)
    // {
    //     $userId = $request->getMember()->id;

    //     // Retry logic for acquiring the Redis lock
    //     $attempts = 0;
    //     $maxAttempts = 3;
    //     $lock = false;

    //     while ($attempts < $maxAttempts && ! $lock) {
    //         $lock = Redis::set("wallet:lock:$userId", true, 'EX', 15, 'NX'); // 10 seconds lock
    //         $attempts++;

    //         if (! $lock) {
    //             sleep(1); // Wait for 1 second before retrying
    //         }
    //     }

    //     if (! $lock) {
    //         return response()->json([
    //             'message' => 'Another transaction is currently processing. Please try again later.',
    //             'userId' => $userId
    //         ], 409); // 409 Conflict
    //     }

    //     // Validate the structure of the request
    //     $validator = $request->check();

    //     if ($validator->fails()) {
    //         // Release Redis lock and return validation error response
    //         Redis::del("wallet:lock:$userId");

    //         return $validator->getResponse();
    //     }

    //     // Retrieve transactions from the request
    //     $transactions = $validator->getRequestTransactions();

    //     // Debugging: Log the transactions to check the structure
    //     //Log::info('Transactions received:', ['transactions' => $transactions]);

    //     // Check if the transactions are in the expected format
    //     if (!is_array($transactions) || empty($transactions)) {
    //         Redis::del("wallet:lock:$userId");

    //         return response()->json([
    //             'message' => 'Invalid transaction data format.',
    //             'details' => $transactions,  // Provide details about the received data for debugging
    //         ], 400);  // 400 Bad Request
    //     }

    //     $before_balance = $request->getMember()->balanceFloat;

    //     DB::beginTransaction();
    //     try {
    //         // Create and store the event in the database
    //         $event = $this->createEvent($request);

    //         // Insert bets using chunking for better performance
    //         $message = $this->insertBets($transactions, $event);  // Insert bets in chunks

    //         // Refresh balance after transactions
    //         $request->getMember()->wallet->refreshBalance();
    //         $after_balance = $request->getMember()->balanceFloat;

    //         DB::commit();

    //         Redis::del("wallet:lock:$userId");

    //         // Return success response
    //         return SlotWebhookService::buildResponse(
    //             SlotWebhookResponseCode::Success,
    //             $after_balance,
    //             $before_balance
    //         );
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         Redis::del("wallet:lock:$userId");
    //         Log::error('Error during placeBet', ['error' => $e]);

    //         return response()->json([
    //             'message' => $e->getMessage(),
    //         ], 500);
    //     }
    // }
    // current running method
    public function placeBetNew(SlotWebhookRequest $request)
    {
        $userId = $request->getMember()->id;

        // Retry logic for acquiring the Redis lock
        $attempts = 0;
        $maxAttempts = 3;
        $lock = false;

        while ($attempts < $maxAttempts && ! $lock) {
            $lock = Redis::set("wallet:lock:$userId", true, 'EX', 15, 'NX'); // 15 seconds lock
            $attempts++;

            if (! $lock) {
                sleep(1); // Wait for 1 second before retrying
            }
        }

        if (! $lock) {
            return response()->json([
                'message' => 'Another transaction is currently processing. Please try again later.',
                'userId' => $userId
            ], 409); // 409 Conflict
        }

        // Validate the structure of the request
        $validator = $request->check();

        if ($validator->fails()) {
            // Release Redis lock and return validation error response
            Redis::del("wallet:lock:$userId");
            return $validator->getResponse();
        }

        // Retrieve transactions from the request
        $transactions = $validator->getRequestTransactions();

        // Check if the transactions are in the expected format
        if (!is_array($transactions) || empty($transactions)) {
            Redis::del("wallet:lock:$userId");
            return response()->json([
                'message' => 'Invalid transaction data format.',
                'details' => $transactions,  // Provide details about the received data for debugging
            ], 400);  // 400 Bad Request
        }

        $before_balance = $request->getMember()->balanceFloat;

        DB::beginTransaction();
        try {
            // Create and store the event in the database
            $event = $this->createEvent($request);

            // Insert bets using chunking for better performance
            $message = $this->insertBets($transactions, $event);  // Insert bets in chunks

            // Process each transaction by transferring the amount
            foreach ($transactions as $transaction) {
                // Assuming 'from' user is the one placing the bet and 'to' is the admin or system wallet
                $fromUser = $request->getMember();
                $toUser = User::adminUser();  // Admin or central system wallet

                $meta = [
                    'wager_id' => $transaction->WagerID,               // Use object property access
                    'event_id' => $request->getMessageID(),
                    'seamless_transaction_id' => $transaction->TransactionID,  // Use object property access
                ];

                // Call processTransfer for each transaction
                $this->processTransfer(
                    $fromUser,                        // From user
                    $toUser,                          // To user (admin/system wallet)
                    TransactionName::Stake,           // Transaction name (e.g., Stake)
                    $transaction->TransactionAmount,  // Use object property access for TransactionAmount
                    $transaction->Rate,               // Use object property access for Rate
                    $meta                             // Meta data (wager id, event id, etc.)
                );
            }

            // Refresh balance after transactions
            $request->getMember()->wallet->refreshBalance();
            $after_balance = $request->getMember()->balanceFloat;

            DB::commit();

            Redis::del("wallet:lock:$userId");

            // Return success response
            return SlotWebhookService::buildResponse(
                SlotWebhookResponseCode::Success,
                $after_balance,
                $before_balance
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Redis::del("wallet:lock:$userId");
            Log::error('Error during placeBet', ['error' => $e]);

            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    // this method is no error for staging and production

//    public function placeBetNew(SlotWebhookRequest $request)
//     {
//         $userId = $request->getMember()->id;

//         // Try to acquire a Redis lock for the user's wallet
//         $lock = Redis::set("wallet:lock:$userId", true, 'EX', 30, 'NX'); // 10 seconds lock
//         if (! $lock) {
//             return response()->json([
//                 'message' => 'The wallet is currently being updated. Please try again later.',
//             ], 409); // 409 Conflict
//         }

//         // Validate the structure of the request
//         $validator = $request->check();

//         if ($validator->fails()) {
//             // Release Redis lock and return validation error response
//             Redis::del("wallet:lock::$userId");

//             return $validator->getResponse();
//         }

//         // Retrieve transactions from the request
//         $transactions = $validator->getRequestTransactions();

//         // Debugging: Log the transactions to check the structure
//         Log::info('Transactions received:', ['transactions' => $transactions]);

//         // Check if the transactions are in the expected format
//         if (!is_array($transactions) || empty($transactions)) {
//             Redis::del("wallet:lock::$userId");

//             return response()->json([
//                 'message' => 'Invalid transaction data format.',
//                 'details' => $transactions,  // Provide details about the received data for debugging
//             ], 400);  // 400 Bad Request
//         }

//         $before_balance = $request->getMember()->balanceFloat;

//         DB::beginTransaction();
//         try {
//             // Create and store the event in the database
//             $event = $this->createEvent($request);

//             // Insert bets using chunking for better performance
//             $message = $this->insertBets($transactions, $event);  // Insert bets in chunks

//             // Refresh balance after transactions
//             $request->getMember()->wallet->refreshBalance();
//             $after_balance = $request->getMember()->balanceFloat;

//             DB::commit();

//             Redis::del("wallet:lock::$userId");

//             // Return success response
//             return SlotWebhookService::buildResponse(
//                 SlotWebhookResponseCode::Success,
//                 $after_balance,
//                 $before_balance
//             );
//         } catch (\Exception $e) {
//             DB::rollBack();
//             Redis::del("wallet:lock::$userId");
//             Log::error('Error during placeBet', ['error' => $e]);

//             return response()->json([
//                 'message' => $e->getMessage(),
//             ], 500);
//         }
//     }

    // public function placeBetNew(SlotWebhookRequest $request)
    // {
    //     $userId = $request->getMember()->id;

    //     // Try to acquire a Redis lock for the user's wallet
    //     $lock = Redis::set("wallet:lock:$userId", true, 'EX', 10, 'NX'); // 10 seconds lock
    //     if (! $lock) {
    //         return response()->json([
    //             'message' => 'The wallet is currently being updated. Please try again later.',
    //         ], 409); // 409 Conflict
    //     }

    //     $validator = $request->check();

    //     if ($validator->fails()) {
    //         // Release Redis lock and return validation error response
    //         Redis::del("wallet:lock:$userId");

    //         return $validator->getResponse();
    //     }

    //     $before_balance = $request->getMember()->balanceFloat;

    //     DB::beginTransaction();
    //     try {
    //         // Create and store the event in the database
    //         $event = $this->createEvent($request);

    //         // Create wager transactions related to the event
    //         $seamless_transactions = $this->createWagerTransactions($validator->getRequestTransactions(), $event);

    //         // Process each seamless transaction
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

    //         // Refresh balance after transactions
    //         $request->getMember()->wallet->refreshBalance();
    //         $after_balance = $request->getMember()->balanceFloat;

    //         DB::commit();

    //         Redis::del("wallet:lock:$userId");

    //         // Return success response
    //         return SlotWebhookService::buildResponse(
    //             SlotWebhookResponseCode::Success,
    //             $after_balance,
    //             $before_balance
    //         );
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         Redis::del("wallet:lock:$userId");
    //         Log::error('Error during placeBet', ['error' => $e]);

    //         return response()->json([
    //             'message' => $e->getMessage(),
    //         ], 500);
    //     }
    // }
}