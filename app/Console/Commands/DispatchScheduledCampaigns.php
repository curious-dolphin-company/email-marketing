<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Campaign;
use App\Models\Subscriber;
use App\Jobs\SendCampaignEmail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DispatchScheduledCampaigns extends Command
{
    protected $signature = 'campaigns:dispatch-scheduled';
    protected $description = 'Dispatch scheduled campaigns for sending';

    public function handle(): void
    {
        Campaign::where('status', 'scheduled')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->chunkById(10, function ($campaigns) {
                foreach ($campaigns as $campaign) {
                    $this->dispatchCampaign($campaign);
                }
            });
    }

    protected function dispatchCampaign(Campaign $campaign): void
    {
        Log::debug('dispatching campaign ID: ' . $campaign->id);

        DB::transaction(function () use ($campaign) {
            // Prevent double-dispatch
            $campaign->refresh();

            if ($campaign->status !== 'scheduled') {
                return;
            }

            $subscribers = Subscriber::where('user_id', $campaign->user_id)
                ->where('status', Subscriber::STATUS_ACTIVE)
                ->get();

            $campaign->update([
                'status' => 'sending',
                'total_recipients' => $subscribers->count(),
                'sent_count' => 0,
                'failed_count' => 0,
            ]);

            foreach ($subscribers as $subscriber) {
                SendCampaignEmail::dispatch(
                    $campaign->id,
                    $subscriber->id
                );
            }
        });

        Log::debug('campaign dispatched successfully. ID: ' . $campaign->id);
    }
}
