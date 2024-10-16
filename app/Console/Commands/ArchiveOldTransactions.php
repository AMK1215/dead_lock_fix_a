<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ArchiveOldTransactions extends Command
{
    protected $signature = 'archive:old-transactions';

    protected $description = 'Archive old transactions to the transaction_archives table';

    public function handle()
    {
        // Define the start and end dates for the date range
        $startDate = '2024-09-19';  // Start date
        $endDate = '2024-10-13';    // End date

        // Process records in chunks to avoid memory overload
        DB::table('transactions')
            ->whereBetween('created_at', [$startDate, $endDate])  // Filter records between the dates
            ->orderBy('id')
            ->chunk(1000, function ($oldTransactions) {
                if ($oldTransactions->isEmpty()) {
                    $this->info('No transactions found for archiving.');

                    return;
                }

                $this->info(count($oldTransactions).' transactions found for archiving.');

                DB::transaction(function () use ($oldTransactions) {
                    // Insert the chunk of old transactions into the transaction_archives table
                    DB::table('transaction_archives')->insert(
                        $oldTransactions->map(function ($transaction) {
                            return (array) $transaction;
                        })->toArray()
                    );

                    // Fetch the IDs of the old transactions that were archived
                    $transactionIds = $oldTransactions->pluck('id')->toArray();

                    // Disable and re-enable foreign key checks
                    DB::statement('SET FOREIGN_KEY_CHECKS=0;');
                    DB::table('transactions')->whereIn('id', $transactionIds)->delete();
                    DB::statement('SET FOREIGN_KEY_CHECKS=1;');

                    $this->info(count($oldTransactions).' transactions have been archived and deleted successfully.');
                });
            });

        $this->info('Transaction archiving complete.');
    }

    // public function handle()
    // {
    //     // Define the threshold date (e.g., transactions older than 1 year)
    //     $thresholdDate = now()->subMonth();  // Adjust the timeframe as needed
    //     DB::table('transactions')
    // ->where('created_at', '<', $thresholdDate)
    // ->orderBy('id')  // Ensure stable sorting
    // ->chunk(1000, function ($oldTransactions) {
    //     if ($oldTransactions->isEmpty()) {
    //         $this->info('No transactions found to archive.');
    //         return;
    //     }

    //     $this->info(count($oldTransactions) . ' transactions found for archiving.');

    //     DB::transaction(function () use ($oldTransactions) {
    //         // Insert the chunk of old transactions into the transaction_archives table
    //         DB::table('transaction_archives')->insert(
    //             $oldTransactions->map(function($transaction) {
    //                 return (array) $transaction;  // Convert stdClass objects to associative arrays
    //             })->toArray()
    //         );

    //         // Fetch the IDs of the old transactions that were archived
    //         $transactionIds = $oldTransactions->pluck('id')->toArray();

    //         // Disable and re-enable foreign key checks
    //         DB::statement('SET FOREIGN_KEY_CHECKS=0;');
    //         DB::table('transactions')->whereIn('id', $transactionIds)->delete();
    //         DB::statement('SET FOREIGN_KEY_CHECKS=1;');

    //         $this->info(count($oldTransactions) . ' transactions have been archived and deleted successfully.');
    //     });
    // });

    // Process records in chunks to avoid memory overload
    // DB::table('transactions')
    //     ->where('created_at', '<', $thresholdDate)
    //     ->orderBy('id')  // Ensure stable sorting
    //     ->chunk(1000, function ($oldTransactions) {
    //         // Insert the chunk of old transactions into the transaction_archives table
    //         DB::transaction(function () use ($oldTransactions) {
    //             // Convert stdClass objects to associative arrays and insert into transaction_archives
    //             DB::table('transaction_archives')->insert(
    //                 $oldTransactions->map(function($transaction) {
    //                     return (array) $transaction;  // Convert stdClass objects to associative arrays
    //                 })->toArray()
    //             );

    //             // Fetch the IDs of the old transactions that were archived
    //             $transactionIds = $oldTransactions->pluck('id')->toArray();

    //             // Automatically disable and re-enable foreign key checks around the delete operation
    //             DB::statement('SET FOREIGN_KEY_CHECKS=0;');
    //             DB::table('transactions')->whereIn('id', $transactionIds)->delete();
    //             DB::statement('SET FOREIGN_KEY_CHECKS=1;');

    //             // Output progress message
    //             $this->info(count($oldTransactions) . ' transactions have been archived and deleted successfully.');
    //         });
    //     });

    // $this->info('Transaction archiving complete.');
    //}
}
