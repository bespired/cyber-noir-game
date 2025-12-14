<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PersonageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $personages = [
            [
                'naam'              => 'Elias Vane',
                'rol'               => 'Antiquair',
                'beschrijving'      => 'Eigenaar van een stoffige, donkere winkel vol pre-oorlogse analoge technologie. Spreekt traag en is obsessief nostalgisch.',

                // Grijze Zone: Aanwijzingen die hij geeft
                'menselijke_status' => 'Lijdt aan chronische bloedarmoede, waardoor hij extreem bleek is. Toont echte haat tegen de Corporation.',
                'replicant_status'  => 'Zijn obsessie met herinneringen is te perfect en feitelijk. Hij kan geen eigen jeugdherinneringen reproduceren.',
                'motief'            => 'Zijn verzameling is een dekmantel om gevoelige informatie uit het verleden te verhandelen of te verbergen.',

                // Game Logica: Hij is de Replicant in dit scenario
                'is_replicant'      => true,
                'created_at'        => now(),
                'updated_at'        => now(),
            ],
            [
                'naam'              => 'Kaelen "Kae" Rui',
                'rol'               => 'Nachtclubzangeres',
                'beschrijving'      => 'Vurige, chaotische performer in een underground club. Cynisch, maar heeft een diep emotioneel bereik op het podium.',

                // Grijze Zone: Aanwijzingen die ze geeft
                'menselijke_status' => 'Heeft een fysiek litteken dat wijst op een jeugdtrauma. Is zeer bang om oud te worden of haar stem te verliezen.',
                'replicant_status'  => 'Ondanks ongezonde levensstijl (roken, drinken) is haar fysieke conditie perfect. Haar emoties lijken soms overgeacteerd.',
                'motief'            => 'Ze verbergt een relatie met het slachtoffer en is bang dat de waarheid haar carrière verwoest.',

                // Game Logica: Zij is een Mens
                'is_replicant'      => false,
                'created_at'        => now(),
                'updated_at'        => now(),
            ],
            [
                'naam'              => 'Julian Dex',
                'rol'               => 'Corporate Junior Manager',
                'beschrijving'      => 'Nerveuze, overenthousiaste en onhandige man die wanhopig loyaal is aan zijn logistieke bedrijf.',

                // Grijze Zone: Aanwijzingen die hij geeft
                'menselijke_status' => 'Zichtbare, ongecontroleerde zenuwtrillingen en sociale onhandigheid. Puur gemotiveerd door angst om zijn baan te verliezen.',
                'replicant_status'  => 'Heeft een te perfect alibi dat door het bedrijf is bevestigd. Zijn kennis over bedrijfsprotocollen is machinaal accuraat.',
                'motief'            => 'Hij heeft onbewust of onder dwang logistieke componenten geleverd die met het incident te maken hebben.',

                // Game Logica: Hij is een Mens
                'is_replicant'      => false,
                'created_at'        => now(),
                'updated_at'        => now(),
            ],
        ];

        DB::table('personages')->insert($personages);
    }
}
