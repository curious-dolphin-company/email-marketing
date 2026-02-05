<?php

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

use App\Models\Campaign;
use App\Models\Subscriber;
use App\Models\CampaignSend;
use App\Jobs\SendCampaignEmail;


new class extends Component
{
    public ?Campaign $campaign = null;

    public string $name = '';
    public string $subject = '';
    public string $body = '';
    public string $status = Campaign::STATUS_DRAFT;
    public ?string $scheduled_at = null;

    public ?int $savedCampaignId = null;

    public function mount(?int $campaignId = null): void
    {
        if ($campaignId) {
            $campaign = Campaign::where('id', $campaignId)
                ->where('user_id', Auth::id())
                ->firstOrFail();
    
            $this->campaign = $campaign;
            $this->populateData($campaign);
        }
    }
    
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:10000'],
        ];
    }

    public function populateData($campaign) : void
    {
        \Log::debug(['campaign' => $campaign]);
        $this->name = $campaign->name;
        $this->subject = $campaign->subject;
        $this->body = $campaign->body;
        $this->scheduled_at = $campaign->scheduled_at;
    }

    public function save(): void
    {
        $this->validate();

        if ($this->campaign) {
            $this->campaign->update([
                'name' => $this->name,
                'subject' => $this->subject,
                'body' => $this->body,
                'scheduled_at' => $this->scheduled_at,
            ]);

            session()->flash('success', 'Campaign updated successfully.');
        } else {
            $this->campaign = $campaign = Campaign::create([
                'user_id' => Auth::id(),
                'name' => $this->name,
                'subject' => $this->subject,
                'body' => $this->body,
                'scheduled_at' => $this->scheduled_at,
                'status' => Campaign::STATUS_DRAFT,
            ]);

            $this->savedCampaignId = $campaign->id;
            $this->populateData($campaign);

            session()->flash('created', 'Campaign created successfully.');
        }
    }

    public function createAnother(): void
    {
        $this->reset([
            'name',
            'subject',
            'body',
            'scheduled_at',
            'savedCampaignId',
        ]);
    }

    public function sendCampaign(): void 
    {
        $this->campaign->refresh();
    
        $subscribers = Subscriber::where('user_id', Auth::id())
        ->where('status', Subscriber::STATUS_ACTIVE)
        ->get();
    
        \Log::debug(['$subscribers' => $subscribers->count()]);

        $this->campaign->scheduled_at = now();
        $this->campaign->status = Campaign::STATUS_SENDING;
        $this->campaign->total_recipients = $subscribers->count();
        $this->campaign->sent_count = 0;
        $this->campaign->failed_count = 0;
        $this->campaign->save();
        $this->populateData($this->campaign);
            
        foreach ($subscribers as $subscriber) {
            SendCampaignEmail::dispatch(
                $this->campaign->id,
                $subscriber->id
            );
        }
        
        session()->flash('success', 'Campaign is being sent.');        
    }

    public function retryFailed(): void
    {
        $this->campaign->refresh();
    
        $failedSends = CampaignSend::where('campaign_id', $this->campaign->id)
            ->where('status', 'failed')
            ->get();
    
        if ($failedSends->isEmpty()) {
            return;
        }
    
        $this->campaign->scheduled_at = now();
        $this->campaign->status = Campaign::STATUS_SENDING;
        $this->campaign->failed_count = 0;
        $this->campaign->save();
        $this->populateData($this->campaign);
    
        foreach ($failedSends as $send) {
            SendCampaignEmail::dispatch(
                $send->campaign_id,
                $send->subscriber_id
            );
        }
    
        session()->flash('success', 'Retrying failed emails.');
    }
    
}

?>

<div>
    @if (session()->has('created'))
        <div class="mb-4 rounded bg-green-100 text-green-800 px-4 py-2">
            {{ session('created') }}
        </div>
        
    @elseif (session()->has('success'))
        <div class="mb-4 rounded bg-green-100 text-green-800 px-4 py-2">
            {{ session('success') }}
        </div>
    @endif

    <form wire:submit="save" class="space-y-6">
        @if ((!$campaign || $campaign->status == 'draft') && !session()->has('created'))
            {{-- Name --}}
            <div>
                <label class="block text-sm font-medium mb-1">
                    Campaign Name
                </label>
                <input
                    type="text"
                    wire:model.defer="name"
                    class="w-full border rounded px-3 py-2"
                >
                @error('name')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Subject --}}
            <div>
                <label class="block text-sm font-medium mb-1">
                    Email Subject
                </label>
                <input
                    type="text"
                    wire:model.defer="subject"
                    class="w-full border rounded px-3 py-2"
                >
                @error('subject')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Body --}}
            <div>
                <label class="block text-sm font-medium mb-1">
                    Email Body (Plain Text)
                </label>
                <textarea
                    wire:model.defer="body"
                    rows="10"
                    class="w-full border rounded px-3 py-2 font-mono"
                ></textarea>
                @error('body')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Scheduled At --}}
            <div>
                <label class="block text-sm font-medium mb-1">
                    Scheduled At
                </label>
                <input
                    type="datetime-local"
                    wire:model.defer="scheduled_at"
                    class="w-full border rounded px-3 py-2"
                >
                @error('scheduled_at')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
        @elseif ($campaign?->status != 'draft')
            {{-- Name --}}
            <div>
                <label class="block text-sm font-medium mb-1">
                    Campaign Name
                </label>
                <p>{{ $name }}</p>
            </div>

            {{-- Subject --}}
            <div>
                <label class="block text-sm font-medium mb-1">
                    Email Subject
                </label>
                <p>{{ $subject }}</p>
            </div>

            {{-- Body --}}
            <div>
                <label class="block text-sm font-medium mb-1">
                    Email Body
                </label>
                <p>{{ $body }}</p>
            </div>

            {{-- Scheduled At --}}
            <div>
                <label class="block text-sm font-medium mb-1">
                    Scheduled At
                </label>
                <p>{{ $scheduled_at }}</p>
            </div>
        
            {{-- Status --}}
            <div>
                <label class="block text-sm font-medium mb-1">
                    Status
                </label>
                <p>
                    <span 
                        @class([
                            'px-2 py-1 text-sm rounded text-white',
                            'bg-gray-500' => $campaign->status === 'draft',
                            'bg-green-500' => $campaign->status === 'sent',
                            'bg-blue-500' => $campaign->status === 'sending',
                            'bg-red-500' => $campaign->status === 'failed',
                        ])
                    >
                        {{ ucfirst($campaign->status) }}
                    </span>
                </p>
            </div>
            @if ($campaign->status === 'sending')
                <div wire:poll.2s>
                
                    <div class="mt-2 space-y-1">
                        <div class="text-sm text-gray-600">
                            Sending… {{ $campaign->sent_count + $campaign->failed_count }}
                            / {{ $campaign->total_recipients }}
                        </div>

                        {{-- Progress bar --}}
                        <div class="w-full bg-gray-200 rounded h-2 overflow-hidden">
                            <div
                                class="bg-blue-600 h-2 transition-all duration-500"
                                style="width: {{ $campaign->progress_percent }}%"
                            ></div>
                        </div>

                        @if ($campaign->failed_count > 0)
                            <div class="text-sm text-red-600">
                                {{ $campaign->failed_count }} failed
                            </div>
                        @endif
                    </div>
                
                </div>
            @endif
        @endif

        {{-- Actions --}}
        <div class="flex items-center gap-4">
            @if (session()->has('created'))
            <button
            wire:click="createAnother"
            class="bg-black text-white px-4 py-2 rounded"
            >
                Create Another Campaign
            </button>

            <a
                href="/campaigns/{{ $savedCampaignId }}/edit"
                class="border px-4 py-2 rounded text-center"
            >
                Edit Campaign
            </a>

            <a
                href="/campaigns"
                class="border px-4 py-2 rounded text-center"
            >
                View All Campaigns
            </a>
            @else
                @if (!$campaign)
                    <button type="submit" class="bg-black text-white px-4 py-2 rounded" > Create Campaign </button>

                @elseif($campaign->status == 'draft')

                    <button type="submit" class="bg-black text-white px-4 py-2 rounded" > Update Campaign </button>
                    <button
                        type="button"
                        wire:click="sendCampaign"
                        wire:confirm="Are you sure to send campaign email to all active subscribers?"
                        class="bg-red-600 text-white px-4 py-2 rounded"
                    >
                        Send Campaign Now
                    </button>

                @elseif($campaign->status == 'draft')

                    <button type="submit" class="bg-black text-white px-4 py-2 rounded" > Update Campaign </button>
                    <button
                        type="button"
                        wire:click="sendCampaign"
                        wire:confirm="Are you sure to send campaign email to all active subscribers?"
                        class="bg-red-600 text-white px-4 py-2 rounded"
                    >
                        Send Campaign Now
                    </button>

                @elseif($campaign->status == 'failed')

                    <button
                        type="button"
                        wire:click="retryFailed"
                        wire:confirm="Are you sure to retry the failed sends?"
                        class="bg-red-600 text-white px-4 py-2 rounded"
                    >
                        Retry Send
                    </button>

                @endif

            <a href="/campaigns" class="border px-4 py-2 rounded text-center">
                {{ $campaign?->status == 'sending' || $campaign?->status == 'sent' ? 'Close' : 'Cancel' }}
            </a>
            @endif
        </div>


    </form>
</div>