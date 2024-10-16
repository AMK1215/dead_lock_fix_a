<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class DownloadedImageUpdateToDB extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'download:game-images-to-db';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Download images for games from a JSON file and save them with their respective game names, and update DB image URLs';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Path to the JSON file
        $jsonFilePath = base_path('app/Console/Commands/json_data/Jili.json');

        // Check if the JSON file exists
        if (! File::exists($jsonFilePath)) {
            $this->error('JSON file not found at '.$jsonFilePath);

            return 1;
        }

        // Read the JSON file
        $jsonData = File::get($jsonFilePath);
        $gamesData = json_decode($jsonData, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('Error decoding JSON: '.json_last_error_msg());

            return 1;
        }

        // Access the games from the JSON file
        $games = $gamesData['ProviderGames'];

        // Create directory to store images
        $directoryPath = public_path('assets/slot_app/jili');
        if (! File::exists($directoryPath)) {
            File::makeDirectory($directoryPath, 0755, true);
        }

        // Loop through games, download images, and update database
        foreach ($games as $game) {
            $this->downloadImage($game['ImageUrl'], $game['GameName'], $directoryPath);

            // Update game_lists table
            $this->updateGameImageInDatabase($game['GameCode'], $game['ImageUrl']);
        }

        $this->info('All images have been downloaded and database updated.');

        return 0;
    }

    /**
     * Download the image and save it with the game name.
     *
     * @param  string  $url
     * @param  string  $gameName
     * @param  string  $directory
     * @return void
     */
    private function downloadImage($url, $gameName, $directory)
    {
        $response = Http::get($url);

        if ($response->successful()) {
            $fileName = str_replace(' ', '_', $gameName).'.png';
            File::put($directory.'/'.$fileName, $response->body());
            $this->info($fileName.' downloaded successfully.');
        } else {
            $this->error('Failed to download image for '.$gameName);
        }
    }

    /**
     * Update the game image URL in the game_lists table.
     *
     * @param  string  $gameCode
     * @param  string  $imageUrl
     * @return void
     */
    private function updateGameImageInDatabase($gameCode, $imageUrl)
    {
        // Log the GameCode and product_id being checked
        $this->info("Checking game with GameCode: $gameCode and product_id: 31.");

        $game = DB::table('game_lists')
            ->where('code', $gameCode)
            ->where('product_id', 31)
            ->first();

        if (! $game) {
            $this->error("No game found with GameCode: $gameCode and product_id = 35.");

            return;
        }

        // If game exists, update image_url
        DB::table('game_lists')
            ->where('code', $gameCode)
            ->where('product_id', 35)
            ->update(['image_url' => $imageUrl]);

        $this->info("Database updated successfully for GameCode: $gameCode.");
    }

    // private function updateGameImageInDatabase($gameCode, $imageUrl)
    // {
    //     $updatedRows = DB::table('game_lists')
    //         ->where('code', $gameCode)  // Assuming `code` corresponds to the `GameCode` in your JSON file
    //         ->where('product_id', 35)   // Ensure it's for product_id = 31
    //         ->update(['image_url' => $imageUrl]);

    //     if ($updatedRows) {
    //         $this->info("Database updated successfully for GameCode: $gameCode.");
    //     } else {
    //         $this->error("No game found with GameCode: $gameCode and product_id = 35.");
    //     }
    // }
}
