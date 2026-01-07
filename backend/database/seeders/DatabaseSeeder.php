<?php
namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */

    public function run(): void
    {

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'testL33uw',

            // Will be hashed by the User model mutator or factory if set up,
            // but standard factory usually hashes 'password'
        ]);

        // $this->call([
        //     // De volgorde is BELANGRIJK: Personages en Locaties eerst
        //     NotitieSeeder::class,
        //     PersonageSeeder::class,
        //     LocatieSeeder::class,
        //     DialoogSeeder::class,
        //     AanwijzingSeeder::class,
        //     SectorSeeder::class,
        //     SceneSeeder::class,
        //     AfbeeldingenSeeder::class,
        //     GedragSeeder::class,
        //     ScenePersonageSeeder::class,
        //     InstellingSeeder::class,
        // ]);
    }
}
