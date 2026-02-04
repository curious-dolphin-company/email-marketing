<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use App\Mail\CampaignEmail;
use App\Models\Campaign;
use App\Models\Subscriber;

class SendCampaignEmail implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Campaign $campaign,
        public Subscriber $subscriber
    )
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Mail::to($subscriber->email)
                ->send(new CampaignEmail($campaign, $subscriber));
        
            $campaignSend->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);
            DB::transaction(function () use ($campaign) {
                $campaign->increment('sent_count');
            });
            
        } catch (\Throwable $e) {
            $campaignSend->update([
                'status' => 'failed',
                'error' => $e->getMessage(),
            ]);
            DB::transaction(function () use ($campaign) {
                $campaign->increment('failed_count');
            });            
        }

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
