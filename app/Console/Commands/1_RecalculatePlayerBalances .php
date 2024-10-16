<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RecalculatePlayerBalances extends Command
{
    protected $signature = 'players:recalculate-balances';

    protected $description = 'Recalculate player balances based on remaining transactions after deletion';

    public function handle()
    {
        // Get all users whose balances might have been affected by the transaction deletions
        $users = User::all(); // Adjust this query based on your user model and setup

        foreach ($users as $user) {
            // Recalculate the balance by summing all the transactions related to the player
            $newBalance = DB::table('transactions')
                ->where('payable_id', $user->id)
                ->where('payable_type', 'App\Models\User')
                ->sum(DB::raw("CASE WHEN type = 'deposit' THEN amount ELSE -amount END"));

            // Update the user's wallet with the recalculated balance
            DB::table('wallets')
                ->where('user_id', $user->id)
                ->update(['balance' => $newBalance]);

            $this->info('Recalculated balance for user '.$user->id.': '.$newBalance);
        }

        $this->info('All player balances have been recalculated.');
    }
}
