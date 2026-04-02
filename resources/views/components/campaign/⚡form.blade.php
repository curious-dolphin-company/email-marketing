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
            $this->campaign->name = $this->name;
            $this->campaign->subject = $this->subject;
            $this->campaign->body = $this->body;
            $this->campaign->scheduled_at = $this->scheduled_at;
            if ($this->campaign->status == Campaign::STATUS_DRAFT && $this->campaign->scheduled_at) {
                $this->campaign->status = Campaign::STATUS_SCHEDULED;
            }
            $this->campaign->save();

            session()->flash('success', 'Campaign updated successfully.');
        } else {
            $this->campaign = $campaign = Campaign::create([
                'user_id' => Auth::id(),
                'name' => $this->name,
                'subject' => $this->subject,
                'body' => $this->body,
                'scheduled_at' => $this->scheduled_at,
                'status' => $this->scheduled_at ? Campaign::STATUS_SCHEDULED : Campaign::STATUS_DRAFT,
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
        <x-message severity="success" class="mb-4">
            {{ session('created') }}
        </x-message>
        
    @elseif (session()->has('success'))
        <x-message severity="success" class="mb-4">
            {{ session('success') }}
        </x-message>
    @endif

    <form wire:submit="save" class="space-y-6">
        @if ((!$campaign || $campaign->isEditable()) && !session()->has('created'))
            {{-- Name --}}
            <div>
                <x-input.label for="name">{{ __('Campaign Name') }}</x-input.label>
                <x-input.text id="name" class="block mt-1 w-full" wire:model.defer="name" autofocus />
                <x-input.error :messages="$errors->get('name')" class="mt-1" />
            </div>

            {{-- Subject --}}
            <div>
                <x-input.label for="subject">{{ __('Email Subject') }}</x-input.label>
                <x-input.text id="subject" class="block mt-1 w-full" wire:model.defer="subject" />
                <x-input.error :messages="$errors->get('subject')" class="mt-1" />
            </div>

            {{-- Body --}}
            <div>
                <x-input.label for="body">{{ __('Email Body (Plain Text)') }}</x-input.label>
                <x-input.textarea
                    id="body"
                    wire:model.defer="body"
                    rows="10"
                    class="w-full font-mono"
                ></x-input.textarea>
                <x-input.error :messages="$errors->get('body')" class="mt-1" />
            </div>

            {{-- Scheduled At --}}
            <div>
                <x-input.label for="scheduled_at">{{ __('Scheduled At') }}</x-input.label>
                <x-input.text 
                    id="scheduled_at" 
                    class="block mt-1 w-full" 
                    type="datetime-local"
                    step="60"
                    wire:model.defer="scheduled_at" />
                <x-input.error :messages="$errors->get('scheduled_at')" class="mt-1" />
            </div>
        @else
            {{-- Name --}}
            <div>
                <label class="block text-sm font-medium mb-1">
                    Campaign Name
                </label>
                <p>{{ $campaign->name }}</p>
            </div>

            {{-- Subject --}}
            <div>
                <label class="block text-sm font-medium mb-1">
                    Email Subject
                </label>
                <p>{{ $campaign->subject }}</p>
            </div>

            {{-- Body --}}
            <div>
                <label class="block text-sm font-medium mb-1">
                    Email Body
                </label>
                <p>{{ $campaign->body }}</p>
            </div>

            {{-- Scheduled At --}}
            <div>
                <label class="block text-sm font-medium mb-1">
                    Scheduled At
                </label>
                <p>
                    {{ $campaign->scheduled_at }} 
                    ({{ $campaign->scheduled_at->diffForHumans(now(), ['parts' => 2]) }})
                </p>
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
                            'bg-gray-500' => $campaign->isDraft(),
                            'bg-yellow-500' => $campaign->isScheduled(),
                            'bg-green-500' => $campaign->isSent(),
                            'bg-blue-500' =>  $campaign->isSending(),
                            'bg-red-500' =>  $campaign->isFailed(),
                        ])
                    >
                        {{ ucfirst($campaign->status) }}
                    </span>
                </p>
            </div>
            @if ($campaign->isSending())
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
                href="/campaigns/{{ $savedCampaignId }}/"
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

                    <x-button.primary> Create Campaign </x-button.primary>

                @elseif($campaign->isEditable())

                    <x-button.primary> Update Campaign </x-button.primary>

                @elseif($campaign->isFailed())

                    <x-button.danger
                        type="button"
                        wire:click="retryFailed"
                        wire:confirm="Are you sure to retry the failed sends?"
                    >
                        {{ __('Retry Send') }}
                    </x-button.danger>

                @endif

                <x-button.secondary href="/campaigns"> {{ __('Close') }} </x-button.secondary>

            @endif
        </div>


    </form>
</div>