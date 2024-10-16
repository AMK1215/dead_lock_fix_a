<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class DownloadGameImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'download:game-images';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Download images for games from a JSON file and save them with their respective game names';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Path to the JSON file
        $jsonFilePath = base_path('app/Console/Commands/json_data/pg_soft.json');

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
        $games = $gamesData['data'];

        // Create directory to store images
        $directoryPath = public_path('assets/slot_app/pg_soft');
        if (! File::exists($directoryPath)) {
            File::makeDirectory($directoryPath, 0755, true);
        }

        // Loop through games and download images
        foreach ($games as $game) {
            $this->downloadImage($game['image_url'], $game['name'], $directoryPath);
        }

        $this->info('All images have been downloaded.');

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
}
