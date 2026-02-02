<?php

use Livewire\Component;
use App\Models\Campaign;
use Illuminate\Support\Facades\Auth;


new class extends Component
{
    public ?Campaign $campaign = null;

    public string $name = '';
    public string $subject = '';
    public string $body = '';

    public ?int $savedCampaignId = null;

    public function mount(?int $campaignId = null): void
    {
        if ($campaignId) {
            $campaign = Campaign::where('id', $campaignId)
                ->where('user_id', Auth::id())
                ->firstOrFail();

            abort_unless($campaign->user_id === Auth::id(), 403);
    
            $this->campaign = $campaign;
            $this->name = $campaign->name;
            $this->subject = $campaign->subject;
            $this->body = $campaign->body;
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

    public function save(): void
    {
        $this->validate();

        if ($this->campaign) {
            $this->campaign->update([
                'name' => $this->name,
                'subject' => $this->subject,
                'body' => $this->body,
            ]);

            session()->flash('updated', 'Campaign updated successfully.');
        } else {
            $campaign = Campaign::create([
                'user_id' => Auth::id(),
                'name' => $this->name,
                'subject' => $this->subject,
                'body' => $this->body,
                'status' => 'draft',
            ]);

            $this->savedCampaignId = $campaign->id;

            session()->flash('created', 'Campaign created successfully.');
            $this->reset(['name', 'subject', 'body']);
        }
    }

    public function createAnother(): void
    {
        $this->reset([
            'name',
            'subject',
            'body',
            'savedCampaignId',
        ]);
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
        
    @elseif (session()->has('updated'))
        <div class="mb-4 rounded bg-green-100 text-green-800 px-4 py-2">
            {{ session('updated') }}
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

            {{-- Actions --}}
            <div class="flex items-center gap-4">
                <button
                    type="submit"
                    class="bg-black text-white px-4 py-2 rounded"
                >
                    {{ $campaign ? 'Update Campaign' : 'Create Campaign' }}
                </button>

                <a href="/campaigns" class="border px-4 py-2 rounded text-center">
                    Cancel
                </a>
            </div>
        </form>
    @endif
</div>