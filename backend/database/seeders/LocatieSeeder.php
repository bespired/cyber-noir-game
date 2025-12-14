<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LocatieSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('locaties')->insert([
            [
                'naam'              => 'The Analog Archive',
                'beschrijving'      => 'Het antiquariaat van Elias Vane. Vochtig en stoffig, vol met verboden pre-oorlogse media en kapotte analoge machines. Ruikt naar ozon en oude boeken.',
                'adres'             => 'Sector 4, Lower Level Access 18B',
                'veiligheidsniveau' => 1, // Laag: alleen gesloten deur
                'created_at'        => now(),
                'updated_at'        => now(),
            ],
            [
                'naam'              => 'Neon Serenade Club',
                'beschrijving'      => 'Een duistere, overbevolkte nachtclub. Het geluid is te hard en het neonlicht verbergt de vervallen muren. Hoofdwerkplek van Kaelen Rui.',
                'adres'             => 'The Grids, Entertainment Zone 9',
                'veiligheidsniveau' => 0, // Openbaar
                'created_at'        => now(),
                'updated_at'        => now(),
            ],
            [
                'naam'              => 'Nexus Logistics HQ',
                'beschrijving'      => 'Klinische, witte en blauwe high-rise lobby. Overdreven beveiligd en onpersoonlijk. Werkplek van Julian Dex.',
                'adres'             => 'Corporate Towers, Central Hub 1',
                'veiligheidsniveau' => 3, // Zwaar bewaakt
                'created_at'        => now(),
                'updated_at'        => now(),
            ],
            [
                'naam'              => 'Slachtoffer Appartement',
                'beschrijving'      => 'Een high-end residentie op de 40e verdieping, nu een plaats delict. De deur is geforceerd, en de plaats is doorzocht door onbekenden.',
                'adres'             => 'Skytop Condos, Unit 4001',
                'veiligheidsniveau' => 2, // Onderzoeksteam aanwezig
                'created_at'        => now(),
                'updated_at'        => now(),
            ],
        ]);
    }
}
