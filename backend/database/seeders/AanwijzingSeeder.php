<?php
namespace Database\Seeders;

use App\Models\Locatie;
use App\Models\Personage; // Importeer de modellen
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
// Zorg dat deze bestaat

class AanwijzingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Zoek de IDs van de gerelateerde items
        $elias           = Personage::where('naam', 'Elias Vane')->first();
        $kaelen          = Personage::where('naam', 'Kaelen "Kae" Rui')->first();
        $nexus_hq        = Locatie::where('naam', 'Nexus Logistics HQ')->first();
        $slachtoffer_apt = Locatie::where('naam', 'Slachtoffer Appartement')->first();
        $analog_archive  = Locatie::where('naam', 'The Analog Archive')->first();

        DB::table('aanwijzingen')->insert([
            // 1. De Valse Aanwijzing (Wijst naar Julian)
            [
                'titel'              => 'Logistiek Order Formulier',
                'beschrijving'       => 'Een orderformulier van Nexus Logistics dat wijst op een illegale zending naar het slachtoffer, ondertekend door Julian Dex.',
                'type'               => 'Data File',
                'locatie_id'         => $slachtoffer_apt->id ?? null,
                'personage_id'       => Personage::where('naam', 'Julian Dex')->first()->id ?? null,
                'is_kritisch'        => false, // Valse route
                'moeilijkheidsgraad' => 1,
                'created_at'         => now(),
                'updated_at'         => now(),
            ],

            // 2. De Aanwijzing die Kaelen vrijpleit
            [
                'titel'              => 'Tickets voor de show',
                'beschrijving'       => 'Een reeks VIP tickets van Kaelen Rui’s show. De datum van het incident staat omcirkeld met de aantekening: "Belangrijk! Waarschuwing."',
                'type'               => 'Fysiek Object',
                'locatie_id'         => $slachtoffer_apt->id ?? null,
                'personage_id'       => $kaelen->id ?? null,
                'is_kritisch'        => false,
                'moeilijkheidsgraad' => 2,
                'created_at'         => now(),
                'updated_at'         => now(),
            ],

            // 3. De Kritische Aanwijzing (Wijst naar Elias, de Replicant)
            [
                'titel'              => 'Zeldzame Chip Module',
                'beschrijving'       => 'Een hyper-geavanceerde, unieke geheugenmodule verstopt in een oude, analoge videocamera. Deze module bevat de ware data die het slachtoffer zocht.',
                'type'               => 'Technisch Bewijs',
                'locatie_id'         => $analog_archive->id ?? null,
                'personage_id'       => $elias->id ?? null,
                'is_kritisch'        => true, // Het "smoking gun"
                'moeilijkheidsgraad' => 3,
                'created_at'         => now(),
                'updated_at'         => now(),
            ],

            // 4. Algemene Aanwijzing
            [
                'titel'              => 'Security Log (Nexus)',
                'beschrijving'       => 'De logboeken tonen aan dat Julian Dex op de avond van het incident 15 minuten te laat was met uitklokken, wat zijn alibi schendt.',
                'type'               => 'Data File',
                'locatie_id'         => $nexus_hq->id ?? null,
                'personage_id'       => null, // Heeft betrekking op Julian, maar is niet direct gekoppeld in de database
                'is_kritisch'        => false,
                'moeilijkheidsgraad' => 2,
                'created_at'         => now(),
                'updated_at'         => now(),
            ],
        ]);
    }
}
