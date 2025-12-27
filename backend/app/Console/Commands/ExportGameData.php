<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;

class ExportGameData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:backup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export game data (Personages, Locations, etc) to JSON seeders';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting Game Data Backup...');

        $exports = [
            'instellingen'     => \App\Models\Instelling::all(),
            'personages'       => \App\Models\Personage::all(),
            'locaties'         => \App\Models\Locatie::all(),
            'aanwijzingen'     => \App\Models\Aanwijzing::all(),
            'sectoren'         => \App\Models\Sector::all(),
            'scenes'           => \App\Models\Scene::all(),
            'notities'         => \App\Models\Notitie::all(),
            'dialogen'         => \App\Models\Dialoog::all(),
            'afbeeldingen'     => \App\Models\Afbeelding::all(),
            'gedragingen'      => \App\Models\Gedrag::all(),
            'scene_personages' => \App\Models\ScenePersonage::all(),

        ];

        $directory = database_path('seeders/data');
        if (! file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        foreach ($exports as $key => $data) {
            $path = "$directory/{$key}.json";
            file_put_contents($path, $data->toJson(JSON_PRETTY_PRINT));
            $this->info("Exported " . count($data) . " records to {$key}.json");
        }

        $this->info('Backup Complete!');
    }
}
