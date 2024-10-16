<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ArchiveOldReports extends Command
{
    protected $signature = 'archive:old-reports';

    protected $description = 'Move old reports to the archive table';

    public function handle()
    {

        //Define the threshold date (e.g., reports older than 1 month)
        //$thresholdDate = now()->subMonth();  // Adjust this value as needed
        $thresholdDate = now()->subDays(20);

        // Process records in chunks to avoid memory overload
        DB::table('reports')
            ->where('created_on', '<', $thresholdDate)
            ->orderBy('id')  // Ensure stable sorting
            ->chunk(1000, function ($oldReports) {
                // Insert the chunk of old reports into the report_archives table
                DB::transaction(function () use ($oldReports) {
                    // Convert stdClass objects to associative arrays and insert into report_archives
                    DB::table('report_archives')->insert(
                        $oldReports->map(function ($report) {
                            return (array) $report;  // Convert stdClass objects to associative arrays
                        })->toArray()
                    );

                    // Fetch the IDs of the old reports that were archived
                    $reportIds = $oldReports->pluck('id')->toArray();

                    // Automatically disable and re-enable foreign key checks around the delete operation
                    DB::statement('SET FOREIGN_KEY_CHECKS=0;'); // Disable foreign key checks
                    DB::table('reports')->whereIn('id', $reportIds)->delete(); // Delete archived records
                    DB::statement('SET FOREIGN_KEY_CHECKS=1;'); // Re-enable foreign key checks

                    // Output progress message
                    $this->info(count($oldReports).' reports have been archived and deleted successfully.');
                });
            });

        $this->info('Archiving complete.');
    }

    //     public function handle()
    // {
    //     // Define the threshold date (e.g., reports older than 1 month)
    //     //$thresholdDate = now()->subMonth();  // Adjust this value as needed
    //     $thresholdDate = now()->subDays(20);

    //     // Process records in chunks to avoid memory overload
    //     DB::table('reports')
    //         ->where('created_on', '<', $thresholdDate)
    //         ->orderBy('id')  // Ensure stable sorting
    //         ->chunk(1000, function ($oldReports) {
    //             // Insert the chunk of old reports into the report_archives table
    //             DB::table('report_archives')->insert(
    //                 $oldReports->map(function($report) {
    //                     return (array) $report;  // Convert stdClass objects to associative arrays
    //                 })->toArray()
    //             );

    //             // After successfully inserting, delete the old records from the reports table
    //             $reportIds = $oldReports->pluck('id')->toArray();
    //             DB::table('reports')->whereIn('id', $reportIds)->delete();

    //             // Output progress message
    //             $this->info(count($oldReports) . ' reports have been archived and deleted successfully.');
    //         });

    //     $this->info('Archiving complete.');
    // }

    //     public function handle()
    // {
    //     // Define the threshold date (e.g., reports older than 1 month)
    //     //$thresholdDate = now()->subMonth();  // Adjust this value as needed
    //     $thresholdDate = now()->subDays(20);

    //     // Fetch old reports with the exact columns required for the insert
    //     $oldReports = DB::table('reports')
    //         ->select(
    //             'member_name',
    //             'wager_id',
    //             'product_code',
    //             'game_type_id',
    //             'game_name',
    //             'game_round_id',
    //             'valid_bet_amount',
    //             'bet_amount',
    //             'payout_amount',
    //             'commission_amount',
    //             'jack_pot_amount',
    //             'jp_bet',
    //             'status',
    //             'created_on',
    //             'settlement_date',
    //             'modified_on',
    //             'agent_id',
    //             'agent_commission',
    //             'created_at',
    //             'updated_at'
    //         )
    //         ->where('created_on', '<', $thresholdDate)  // Filter based on the date
    //         ->get();

    //     // Check if there are reports to archive
    //     if ($oldReports->isNotEmpty()) {
    //         // Insert old reports into the report_archives table
    //         DB::table('report_archives')->insert(
    //             $oldReports->map(function($report) {
    //                 return (array) $report;  // Convert stdClass objects to associative arrays
    //             })->toArray()
    //         );

    //         // After successfully inserting, delete the old records from the reports table
    //         DB::table('reports')
    //             ->where('created_on', '<', $thresholdDate)
    //             ->delete();

    //         // Output success message
    //         $this->info(count($oldReports) . ' reports have been archived and deleted successfully.');
    //     } else {
    //         // Output a message if no reports were found to archive
    //         $this->info('No old reports found to archive.');
    //     }
    // }

    //     public function handle()
    // {
    //     // Define the threshold date (e.g., reports older than 20 days)
    //     $thresholdDate = now()->subDays(20);  // Adjust this value as needed

    //     $oldReports = DB::table('reports')->where('created_on', '<', $thresholdDate)->get();

    //     if ($oldReports->isEmpty()) {
    //         $this->info('No old reports found to archive.');
    //     } else {
    //         DB::statement('SET FOREIGN_KEY_CHECKS=0;');
    //         DB::table('report_archives')->insert($oldReports->toArray());
    //         DB::statement('SET FOREIGN_KEY_CHECKS=1;');

    //         // Delete old records from the reports table
    //         DB::table('reports')->where('created_on', '<', $thresholdDate)->delete();

    //         $this->info(count($oldReports) . ' reports have been archived successfully.');
    //     }
    // }

    // public function handle()
    // {
    //     // Define the threshold date (e.g., reports older than 1 year)
    //     //$thresholdDate = now()->subYear();
    //     $thresholdDate = now()->subMonth();  // This sets the threshold to 1 month ago

    //     DB::transaction(function () use ($thresholdDate) {
    //         // Move old reports to the archive table
    //         DB::table('report_archives')->insert(
    //             DB::table('reports')->where('created_on', '<', $thresholdDate)->get()->toArray()
    //         );

    //         // Delete the old reports from the reports table
    //         DB::table('reports')->where('created_on', '<', $thresholdDate)->delete();
    //     });

    //     $this->info('Old reports have been archived successfully.');
    // }
}
