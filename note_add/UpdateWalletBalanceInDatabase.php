<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class UpdateWalletBalanceInDatabase implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $userId;

    protected $amount;

    /**
     * Create a new job instance.
     */
    public function __construct(int $userId, float $amount)
    {
        $this->userId = $userId;
        $this->amount = $amount;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Define the Redis key for the user
        $walletKey = "wallet_balance_user_{$this->userId}";

        // Start a database transaction
        DB::transaction(function () use ($walletKey) {
            // Retrieve the current balance from Redis
            $currentBalance = Redis::get($walletKey);

            if ($currentBalance === null) {
                Log::warning("User ID {$this->userId} balance not found in Redis, fetching from database.");

                // Fallback to the database if the balance is not found in Redis
                $wallet = DB::table('wallets')->where('holder_id', $this->userId)->first();

                if ($wallet) {
                    $currentBalance = $wallet->balance;
                    // Update the Redis cache with the fetched balance
                    Redis::setex($walletKey, 600, $currentBalance);
                } else {
                    Log::error("Wallet not found for user ID {$this->userId}.");

                    return; // Exit if wallet not found
                }
            }

            // Update the database with the new balance
            $newBalance = (float) $currentBalance + (float) $this->amount;

            // Update the balance in the database
            DB::table('wallets')->where('holder_id', $this->userId)->update(['balance' => $newBalance]);

            // Update the Redis cache with the new balance
            Redis::setex($walletKey, 600, $newBalance);

            Log::info("Updated wallet balance for user ID {$this->userId} to {$newBalance}.");
        });
    }
}
