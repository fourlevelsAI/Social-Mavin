<?php

namespace App\Services;

use App\Models\EmailAccount;
use App\Jobs\WarmupEmail;
use Illuminate\Support\Facades\Log;

class WarmupService
{
    /**
     * Run warmup for all enabled email accounts
     */
    public function runWarmup(): void
    {
        try {
            $emailAccounts = EmailAccount::where('warmup_enabled', true)->get();

            if ($emailAccounts->isEmpty()) {
                Log::info("No email accounts enabled for warmup");
                return;
            }

            foreach ($emailAccounts as $emailAccount) {
                // Dispatch warmup email job for each account
                WarmupEmail::dispatch($emailAccount)
                    ->delay(now()->addSeconds(rand(5, 30)));
            }

            Log::info("Dispatched warmup jobs for " . $emailAccounts->count() . " email accounts");

        } catch (\Exception $e) {
            Log::error("Warmup service error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get health score status for an email account
     * Returns: green (80-100), amber (40-79), red (0-39)
     */
    public function getHealthScore(EmailAccount $emailAccount): array
    {
        $score = $emailAccount->warmup_score;

        if ($score >= 80) {
            $status = 'green';
            $label = 'Excellent';
        } elseif ($score >= 40) {
            $status = 'amber';
            $label = 'Good';
        } else {
            $status = 'red';
            $label = 'Warming Up';
        }

        return [
            'email' => $emailAccount->email,
            'score' => $score,
            'status' => $status,
            'label' => $label,
            'warmup_enabled' => $emailAccount->warmup_enabled,
        ];
    }

    /**
     * Get health scores for all accounts in a team
     */
    public function getTeamHealthScores(int $teamId): array
    {
        $emailAccounts = EmailAccount::where('team_id', $teamId)->get();

        return $emailAccounts->map(function ($account) {
            return $this->getHealthScore($account);
        })->toArray();
    }
}
