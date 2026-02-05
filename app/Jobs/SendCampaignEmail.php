<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

use App\Mail\CampaignEmail;
use App\Models\Campaign;
use App\Models\Subscriber;
use App\Models\CampaignSend;

class SendCampaignEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */

    public function __construct(
        public int $campaignId,
        public int $subscriberId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $campaign = Campaign::find($this->campaignId);
        $subscriber = Subscriber::find($this->subscriberId);
    
        if (! $campaign || ! $subscriber) {
            return;
        }
    
        // Idempotency: avoid duplicate sends
        $campaignSend = CampaignSend::firstOrCreate(
            [
                'campaign_id'   => $campaign->id,
                'subscriber_id' => $subscriber->id,
            ],
            [
                'status' => 'pending',
            ]
        );
    
        // Prevent resending successful emails
        if ($campaignSend->status === 'sent') {
            return;
        }
    
        try {
            Mail::to($subscriber->email)
                ->send(new CampaignEmail($campaign, $subscriber));
    
            $campaignSend->update([
                'status'  => 'sent',
                'sent_at' => now(),
                'error'   => null,
            ]);
    
            $campaign->increment('sent_count');
        } catch (\Throwable $e) {
            $campaignSend->update([
                'status' => 'failed',
                'error'  => $e->getMessage(),
            ]);
    
            $campaign->increment('failed_count');
        }
    
        // Completion check
        $campaign->refresh();
    
        if (
            $campaign->sent_count + $campaign->failed_count
            >= $campaign->total_recipients
        ) {
            $campaign->update([
                'status' => $campaign->failed_count > 0 ? 'failed' : 'sent',
            ]);
        }
    }
}
