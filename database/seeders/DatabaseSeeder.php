<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Team;
use App\Models\EmailAccount;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'info@mohamedibrahim.biz'],
            [
                'name' => 'Mohamed Ibrahim',
                'password' => bcrypt('changeme123'),
                'email_verified_at' => now(),
            ]
        );

        $team = Team::firstOrCreate(
            ['owner_id' => $user->id],
            [
                'name' => 'Social Mavin',
                'personal_team' => true,
                'plan' => 'free',
            ]
        );

        $user->update(['current_team_id' => $team->id]);

        EmailAccount::firstOrCreate(
            ['team_id' => $team->id],
            [
                'email' => 'info@socialmavin.com',
                'smtp_host' => 'smtp.resend.com',
                'smtp_port' => 465,
                'warmup_enabled' => false,
                'warmup_score' => 0,
            ]
        );
    }
}
