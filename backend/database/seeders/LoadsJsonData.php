<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

trait LoadsJsonData
{
    /**
     * Load JSON data from seeders/data/{file}.json into a model.
     */
    protected function loadJson(string $filename, string $modelClass)
    {
        $path = database_path("seeders/data/{$filename}.json");

        if (!File::exists($path)) {
            $this->command->warn("File not found: {$path}. Skipping JSON seed.");
            return false;
        }

        $json = File::get($path);
        $records = json_decode($json, true);

        if ($records === null) {
            $this->command->warn("Invalid JSON in {$filename}.json");
            return false;
        }

        $this->command->info("Seeding " . count($records) . " records from {$filename}.json...");

        // Instantiate model to check casts (to distinguish JSON columns from Relations)
        $instance = new $modelClass;
        $casts = $instance->getCasts();

        foreach ($records as $record) {
            // Filter out relations (arrays/objects that are NOT cast to array/json)
            foreach ($record as $key => $value) {
                if (is_array($value) || is_object($value)) {
                    $isJsonColumn = isset($casts[$key]) && in_array($casts[$key], ['array', 'json', 'object', 'collection']);

                    if (!$isJsonColumn) {
                        unset($record[$key]);
                    }
                }
            }

            // Remove timestamps if you want fresh ones, or keep them if you want to preserve history
            // Usually for backup restoration we might want to keep IDs to preserve relations

            // We use updateOrCreate/upsert to handle existing IDs
            // Assumes 'id' is present in JSON
            if (isset($record['id'])) {
                $modelClass::updateOrCreate(['id' => $record['id']], $record);
            } else {
                $modelClass::create($record);
            }
        }

        return true;
    }
}
