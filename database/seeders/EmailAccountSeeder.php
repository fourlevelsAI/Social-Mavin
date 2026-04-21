<?php

namespace Database\Seeders;

use App\Models\EmailAccount;
use App\Models\Team;
use Illuminate\Database\Seeder;

class EmailAccountSeeder extends Seeder
{
    public function run(): void
    {
        $team = Team::first();

        if (!$team) {
            $this->command->warn('No team found — skipping EmailAccountSeeder.');
            return;
        }

        EmailAccount::firstOrCreate(
            ['team_id' => $team->id, 'email' => 'info@mohamedibrahim.biz'],
            [
                'smtp_host'      => 'smtp.resend.com',
                'smtp_port'      => 465,
                'warmup_enabled' => false,
                'warmup_score'   => 0,
            ]
        );
    }
}
