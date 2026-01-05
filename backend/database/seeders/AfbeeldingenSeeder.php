<?php
namespace Database\Seeders;

use App\Models\Afbeelding;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AfbeeldingenSeeder extends Seeder
{
    use LoadsJsonData;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if ($this->loadJson('afbeeldingen', Afbeelding::class)) {
            return;
        }

        DB::table('afbeeldingen')->insert([
            [
                'id'             => 1,
                'bestandspad'    => 'artwork/locatie/Jqf8y1lebIwXSVJJN2ah58xIxrENEms4hQOdT8uj.jpg',
                'titel'          => 'Een duistere, overbevolkte nachtclub',
                'imageable_type' => 'App\\Models\\Locatie',
                'imageable_id'   => 2,
                'created_at'     => '2025-12-14 11:28:35',
                'updated_at'     => '2025-12-14 11:28:35',
            ],
            [
                'id'             => 2,
                'bestandspad'    => 'artwork/personage/b9fHrmsGDdXoji6XIaJA4sveeXNwC8QB1il41hve.png',
                'titel'          => 'Elias Vane',
                'imageable_type' => 'App\\Models\\Personage',
                'imageable_id'   => 1,
                'created_at'     => '2025-12-14 12:19:11',
                'updated_at'     => '2025-12-14 12:19:11',
            ],
            [
                'id'             => 3,
                'bestandspad'    => 'artwork/personage/bDDBX074HQ0j2dKMXmpVXEwXFRtXxGSejYuX1SjU.png',
                'titel'          => 'Kaelen',
                'imageable_type' => 'App\\Models\\Personage',
                'imageable_id'   => 2,
                'created_at'     => '2025-12-14 12:25:36',
                'updated_at'     => '2025-12-14 12:25:36',
            ],
            [
                'id'             => 5,
                'bestandspad'    => 'artwork/aanwijzing/5yVLN7yVlC09JHAbSM7ft3R0GisvwuMO8zrsMnhi.png',
                'titel'          => 'Logistiek Order Formulier',
                'imageable_type' => 'App\\Models\\Aanwijzing',
                'imageable_id'   => 1,
                'created_at'     => '2025-12-14 12:51:20',
                'updated_at'     => '2025-12-14 12:51:20',
            ],
            [
                'id'             => 6,
                'bestandspad'    => 'artwork/aanwijzing/s9orXy4sI6Qn5r7qd8kyRJeCdB8wLoamuDrrcPoO.png',
                'titel'          => 'Tickets voor de show',
                'imageable_type' => 'App\\Models\\Aanwijzing',
                'imageable_id'   => 2,
                'created_at'     => '2025-12-14 12:54:12',
                'updated_at'     => '2025-12-14 12:54:12',
            ],
            [
                'id'             => 8,
                'bestandspad'    => 'artwork/aanwijzing/WPdfaSLd7xhTMKxJ4Dd0nrkautP1hWEjs0hjNqe3.png',
                'titel'          => 'Zeldzame Chip Module',
                'imageable_type' => 'App\\Models\\Aanwijzing',
                'imageable_id'   => 3,
                'created_at'     => '2025-12-14 12:57:53',
                'updated_at'     => '2025-12-14 12:57:53',
            ],
            [
                'id'             => 9,
                'bestandspad'    => 'artwork/aanwijzing/cghf4SK1BZMFK1orEV1Gshw8RMNVlmti2jazl14N.png',
                'titel'          => 'Security Log',
                'imageable_type' => 'App\\Models\\Aanwijzing',
                'imageable_id'   => 4,
                'created_at'     => '2025-12-14 12:59:09',
                'updated_at'     => '2025-12-14 12:59:09',
            ],
            [
                'id'             => 10,
                'bestandspad'    => 'artwork/locatie/z1lQWeqf0gLiZ1wjAHYeaUmfzBJtB9Ng5dlXx8oB.png',
                'titel'          => 'Nexus Logistics HQ',
                'imageable_type' => 'App\\Models\\Locatie',
                'imageable_id'   => 3,
                'created_at'     => '2025-12-14 13:42:58',
                'updated_at'     => '2025-12-14 13:42:58',
            ],
            [
                'id'             => 11,
                'bestandspad'    => 'artwork/locatie/CLOWCUFVEa9eTQnDeov2Vn0d1MsCDCbMakKFulYr.jpg',
                'titel'          => 'Books',
                'imageable_type' => 'App\\Models\\Locatie',
                'imageable_id'   => 1,
                'created_at'     => '2025-12-14 13:43:41',
                'updated_at'     => '2025-12-14 13:43:41',
            ],
            [
                'id'             => 12,
                'bestandspad'    => 'artwork/personage/A71iLNWwO1TobTE8g15h30ZuD3TLsQ7H0Y020nM0.jpg',
                'titel'          => 'Clerk',
                'imageable_type' => 'App\\Models\\Personage',
                'imageable_id'   => 3,
                'created_at'     => '2025-12-14 13:44:12',
                'updated_at'     => '2025-12-14 13:44:12',
            ],
            [
                'id'             => 13,
                'bestandspad'    => 'artwork/locatie/hKGehqUFNEc7zUH7AlqPJsxKEM837rCh5qdkmxaX.jpg',
                'titel'          => 'Appartement',
                'imageable_type' => 'App\\Models\\Locatie',
                'imageable_id'   => 4,
                'created_at'     => '2025-12-14 13:49:05',
                'updated_at'     => '2025-12-14 13:49:05',
            ],
        ]);
    }
}
