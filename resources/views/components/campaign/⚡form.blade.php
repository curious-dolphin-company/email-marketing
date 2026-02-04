<?php

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

use App\Models\Campaign;
use App\Models\Subscriber;
use App\Models\SendCampaignEmail;


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
            $this->name = $campaign->name;
            $this->subject = $campaign->subject;
            $this->body = $campaign->body;
            $this->status = $campaign->status;
            $this->scheduled_at = $campaign->scheduled_at;
        }
    }
    
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:10000'],
            'scheduled_at' => ['required'],
        ];
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
            $campaign = Campaign::create([
                'user_id' => Auth::id(),
                'name' => $this->name,
                'subject' => $this->subject,
                'body' => $this->body,
                'scheduled_at' => $this->scheduled_at,
                'status' => Campaign::STATUS_DRAFT,
            ]);

            $this->savedCampaignId = $campaign->id;

            session()->flash('created', 'Campaign created successfully.');
            $this->reset(['name', 'subject', 'body', 'scheduled_at']);
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
        $campaign = Campaign::where('id', $this->campaign->id)
        ->where('user_id', auth()->id())
        ->firstOrFail();
    
        $subscribers = Subscriber::where('user_id', auth()->id())
        ->where('status', Subscriber::STATUS_ACTIVE)
        ->get();
    
        $campaign->update([
            'scheduled_at' => now(),
            'status' => Campaign::STATUS_SENDING,
            'total_recipients' => $subscribers->count(),
            'sent_count' => 0,
            'failed_count' => 0,
        ]);
            
        foreach ($subscribers as $subscriber) {
            SendCampaignEmail::dispatch($campaign, $subscriber);
        }
        
        session()->flash('success', 'Campaign is being sent.');        
    }
}
?>

<div>
    @if (session()->has('created'))
        <div class="mb-4 rounded bg-green-100 text-green-800 px-4 py-2">
            {{ session('created') }}
        </div>
        <div class="flex flex-col sm:flex-row gap-3">
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
        </div>
        
    @elseif (session()->has('success'))
        <div class="mb-4 rounded bg-green-100 text-green-800 px-4 py-2">
            {{ session('success') }}
        </div>
    @endif

    @if (!session()->has('created'))
        <form wire:submit="save" class="space-y-6">
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
                @error('subject')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
            
            {{-- Actions --}}
            <div class="flex items-center gap-4">
                @if ($status != 'sending' && $status != 'sent')
                <button
                    type="submit"
                    class="bg-black text-white px-4 py-2 rounded"
                >
                    {{ $campaign ? 'Update Campaign' : 'Create Campaign' }}
                </button>
                <button
                    type="button"
                    wire:click="sendCampaign"
                    wire:confirm="Are you sure to send campaign email to all active subscribers?"
                    class="bg-red-600 text-white px-4 py-2 rounded"
                >
                    Send Campaign Now
                </button>
                @endif

                <a href="/campaigns" class="border px-4 py-2 rounded text-center">
                    Cancel
                </a>
            </div>
        </form>
    @endif
</div>