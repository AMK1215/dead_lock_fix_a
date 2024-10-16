<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class DeletePayoutAndStakeTransactions extends Command
{
    protected $signature = 'transactions:delete-payout-stake';

    protected $description = 'Delete transactions where name is payout or stake in chunks with retry logic';

    public function handle()
    {
        // Disable foreign key checks before the delete operation
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Define maximum number of retries in case of a lock timeout
        $maxRetries = 5;
        $retryCount = 0;

        // Process and delete records in smaller chunks to avoid memory overload and lock issues
        DB::table('transactions')
            ->whereIn('name', ['payout', 'stake', 'cancel', 'rollback', 'buy_in', 'buy_out'])
            ->orderBy('id')  // Ensure stable sorting to avoid missing records
            ->chunkById(100, function ($transactions) use (&$retryCount, $maxRetries) {
                $transactionIds = $transactions->pluck('id')->toArray();

                while ($retryCount < $maxRetries) {
                    try {
                        // Delete the chunk of transactions
                        DB::table('transactions')->whereIn('id', $transactionIds)->delete();

                        // Output progress
                        $this->info(count($transactions).' transactions deleted in this chunk where name == payout, stake, cancel and rollback.');
                        break; // Exit the loop if successful
                    } catch (QueryException $e) {
                        if ($e->getCode() == 1205) { // Lock wait timeout error code
                            $retryCount++;
                            $this->warn('Lock wait timeout exceeded, retrying... ('.$retryCount.'/'.$maxRetries.')');
                            sleep(2); // Wait for 2 seconds before retrying
                        } else {
                            throw $e; // Rethrow if it's not a lock timeout error
                        }
                    }
                }

                if ($retryCount >= $maxRetries) {
                    $this->error('Max retries reached. Failed to delete some transactions.');
                }
            });

        // Enable foreign key checks after the delete operation
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Output final message
        $this->info('All transactions with name "payout" or "stake" or "cancel", or "rollback" have been deleted.');
    }
}
