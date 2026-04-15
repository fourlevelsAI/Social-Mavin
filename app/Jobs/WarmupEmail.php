<?php

namespace App\Jobs;

use App\Models\EmailAccount;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class WarmupEmail implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(private EmailAccount $emailAccount)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Get random other email accounts in the pool to send to
            $recipientAccount = EmailAccount::where('id', '!=', $this->emailAccount->id)
                ->where('team_id', $this->emailAccount->team_id)
                ->inRandomOrder()
                ->first();

            if (!$recipientAccount) {
                Log::warning("No other email accounts found for warmup from {$this->emailAccount->email}");
                return;
            }

            // Realistic warmup email templates
            $templates = [
                [
                    'subject' => 'Quick question about your recent post',
                    'body' => 'Hey! I came across your content and thought it was really insightful. Would love to connect and chat more about what you\'re working on. What\'s the best way to reach you?',
                ],
                [
                    'subject' => 'Love what you\'re building',
                    'body' => 'Really impressed by the work you\'re doing in your space. Your perspective on this topic is unique and valuable. Would be great to stay connected!',
                ],
                [
                    'subject' => 'Thought of you',
                    'body' => 'Saw something today that reminded me of a conversation I had recently. Would be great to catch up. How have you been?',
                ],
                [
                    'subject' => 'Let\'s stay in touch',
                    'body' => 'Been meaning to reach out! Think we could collaborate on something interesting. Let me know if you\'re open to chatting.',
                ],
            ];

            $template = $templates[array_rand($templates)];

            // Send the warmup email
            Mail::to($recipientAccount->email)
                ->send(new \App\Mail\WarmupEmailMessage(
                    $this->emailAccount->email,
                    $template['subject'],
                    $template['body']
                ));

            // Increment warmup score (cap at 100)
            $newScore = min($this->emailAccount->warmup_score + 1, 100);
            $this->emailAccount->update(['warmup_score' => $newScore]);

            Log::info("Warmup email sent from {$this->emailAccount->email} to {$recipientAccount->email}. Score: {$newScore}");

        } catch (\Exception $e) {
            Log::error("Warmup email failed for {$this->emailAccount->email}: " . $e->getMessage());
        }
    }
}
