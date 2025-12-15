<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Conversation;

class ConversationSeeder extends Seeder
{
    use LoadsJsonData;

    public function run(): void
    {
        if ($this->loadJson('conversations', Conversation::class)) {
            return;
        }
    }
}
