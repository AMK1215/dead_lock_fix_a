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

class SyncWalletBalanceToDatabase implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        // Get all user IDs
        $users = DB::table('users')->pluck('id');

        Log::info('SyncWalletBalanceToDatabase job started', ['user_count' => count($users)]);

        foreach ($users as $userId) {
            $walletKey = "wallet_balance_user_{$userId}";

            // Fetch balance from Redis
            $balance = Redis::get($walletKey);

            if ($balance !== null) {
                // Update balance in the database
                DB::table('wallets')->where('holder_id', $userId)->update(['balance' => $balance]);

                Log::info('Wallet balance synced', [
                    'user_id' => $userId,
                    'balance' => $balance,
                ]);
            } else {
                // Log if the balance was not found in Redis
                Log::warning('No wallet balance found in Redis', ['user_id' => $userId]);
            }
        }

        Log::info('SyncWalletBalanceToDatabase job completed');
    }
}

// class SyncWalletBalanceToDatabase implements ShouldQueue
// {
//     use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

//     public function handle()
//     {
//         // Get all user IDs
//         $users = DB::table('users')->pluck('id');

//         foreach ($users as $userId) {
//             $walletKey = "wallet_balance_user_{$userId}";

//             // Fetch balance from Redis
//             $balance = Redis::get($walletKey);

//             // Update balance in the database
//             DB::table('wallets')->where('holder_id', $userId)->update(['balance' => $balance]);
//         }
//     }
// }
