<?php

namespace App\Services;

use App\Models\Campaign;
use App\Jobs\SendCampaignEmail;
use Illuminate\Support\Facades\Log;
use Exception;

class CampaignSequenceService
{
    /**
     * Launch a campaign by dispatching emails for all leads
     * @throws Exception
     */
    public function launch(Campaign $campaign): array
    {
        try {
            // Validate campaign prerequisites
            $this->validateCampaign($campaign);

            // Update campaign status to active
            $campaign->update(['status' => 'active']);

            // Get all sequence steps ordered by step number
            $sequenceSteps = $campaign->sequenceSteps()->orderBy('step_number')->get();

            if ($sequenceSteps->isEmpty()) {
                throw new Exception('Campaign has no email sequence steps configured');
            }

            // Get all leads for the campaign
            $leads = $campaign->leads()->where('status', '!=', 'contacted')->get();

            if ($leads->isEmpty()) {
                throw new Exception('Campaign has no leads to send emails to');
            }

            // Verify team has at least one email account configured
            $emailAccounts = $campaign->team->emailAccounts()->where('warmup_enabled', false)->get();
            if ($emailAccounts->isEmpty()) {
                throw new Exception('Team has no email accounts configured for sending');
            }

            $jobsDispatched = 0;

            // Dispatch jobs for each lead and sequence step
            foreach ($leads as $lead) {
                foreach ($sequenceSteps as $sequenceStep) {
                    // Calculate delay in seconds (delay_days * 24 * 60 * 60)
                    $delayInSeconds = $sequenceStep->delay_days * 24 * 60 * 60;

                    // Dispatch the job with delay
                    SendCampaignEmail::dispatch($lead, $sequenceStep)
                        ->delay(now()->addSeconds($delayInSeconds));
                    
                    $jobsDispatched++;
                }
            }

            Log::info("Campaign {$campaign->id} launched: {$jobsDispatched} emails scheduled for {$leads->count()} leads");

            return [
                'success' => true,
                'message' => 'Campaign launched successfully',
                'campaign_id' => $campaign->id,
                'leads_count' => $leads->count(),
                'steps_count' => $sequenceSteps->count(),
                'jobs_dispatched' => $jobsDispatched,
            ];

        } catch (Exception $e) {
            Log::error("Failed to launch campaign {$campaign->id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Validate campaign is ready to launch
     * @throws Exception
     */
    private function validateCampaign(Campaign $campaign): void
    {
        // Check if campaign exists
        if (!$campaign) {
            throw new Exception('Campaign not found');
        }

        // Check if campaign has a team
        if (!$campaign->team) {
            throw new Exception('Campaign has no associated team');
        }

        // Check if campaign is already active
        if ($campaign->status === 'active') {
            throw new Exception('Campaign is already active');
        }

        // Check if campaign has leads
        $leadCount = $campaign->leads()->count();
        if ($leadCount === 0) {
            throw new Exception('Campaign has no leads. Please add leads before launching');
        }

        // Check if campaign has sequence steps
        $stepCount = $campaign->sequenceSteps()->count();
        if ($stepCount === 0) {
            throw new Exception('Campaign has no email sequence steps. Please add email steps before launching');
        }

        // Check if team has email accounts
        $emailAccountCount = $campaign->team->emailAccounts()->count();
        if ($emailAccountCount === 0) {
            throw new Exception('Your team has no email accounts configured. Please add and verify an email account before launching');
        }
    }
}
