<?php

namespace App\Jobs;

use App\Models\Lead;
use App\Models\SequenceStep;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendCampaignEmail implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private Lead $lead,
        private SequenceStep $sequenceStep
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Get the campaign and team to find email account
            $campaign = $this->sequenceStep->campaign;
            $team = $campaign->team;

            // Get the primary email account for the team
            $emailAccount = $team->emailAccounts()->first();
            
            if (!$emailAccount) {
                Log::error("No email account found for team {$team->id}");
                $this->fail(new \Exception("No email account configured for this team"));
                return;
            }

            // Personalize the email body
            $body = $this->personalizeContent($this->sequenceStep->body);
            $subject = $this->personalizeContent($this->sequenceStep->subject ?? '');

            // Send email via configured SMTP
            Mail::to($this->lead->email)
                ->queue(new \App\Mail\CampaignEmail(
                    $this->lead,
                    $subject,
                    $body,
                    $emailAccount
                ));

            // Update lead status
            $this->lead->update(['status' => 'contacted']);

            Log::info("Email sent to {$this->lead->email} for campaign {$campaign->id}");

        } catch (\Exception $e) {
            Log::error("Failed to send email to {$this->lead->email}: " . $e->getMessage());
            $this->fail($e);
        }
    }

    /**
     * Personalize email content with lead data
     */
    private function personalizeContent(string $content): string
    {
        return strtr($content, [
            '{{first_name}}' => $this->lead->first_name ?? 'there',
            '{{last_name}}' => $this->lead->last_name ?? '',
            '{{company}}' => $this->lead->company ?? '',
        ]);
    }
}

