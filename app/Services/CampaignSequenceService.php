<?php

namespace App\Services;

use App\Models\Campaign;
use App\Jobs\SendCampaignEmail;
use Illuminate\Support\Facades\Log;

class CampaignSequenceService
{
    /**
     * Launch a campaign by dispatching emails for all leads
     */
    public function launch(Campaign $campaign): void
    {
        try {
            // Update campaign status to active
            $campaign->update(['status' => 'active']);

            // Get all sequence steps ordered by step number
            $sequenceSteps = $campaign->sequenceSteps()->orderBy('step_number')->get();

            if ($sequenceSteps->isEmpty()) {
                Log::warning("Campaign {$campaign->id} has no sequence steps");
                return;
            }

            // Get all leads for the campaign
            $leads = $campaign->leads()->where('status', '!=', 'contacted')->get();

            if ($leads->isEmpty()) {
                Log::info("Campaign {$campaign->id} has no leads to contact");
                return;
            }

            // Dispatch jobs for each lead and sequence step
            foreach ($leads as $lead) {
                foreach ($sequenceSteps as $sequenceStep) {
                    // Calculate delay in seconds (delay_days * 24 * 60 * 60)
                    $delayInSeconds = $sequenceStep->delay_days * 24 * 60 * 60;

                    // Dispatch the job with delay
                    SendCampaignEmail::dispatch($lead, $sequenceStep)
                        ->delay(now()->addSeconds($delayInSeconds));
                }
            }

            Log::info("Campaign {$campaign->id} launched with " . $leads->count() . " leads");

        } catch (\Exception $e) {
            Log::error("Failed to launch campaign {$campaign->id}: " . $e->getMessage());
            throw $e;
        }
    }
}
