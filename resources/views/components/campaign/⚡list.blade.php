<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Campaign;
use Illuminate\Support\Facades\Auth;

new class extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'tailwind';

    public string $search = '';

    public function applySearch(): void
    {
        // Reset pagination when search changes
        $this->resetPage();
    }

    public function getCampaignsProperty()
    {
        return Campaign::where('user_id', Auth::id())
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('subject', 'like', '%' . $this->search . '%')
                      ->orWhere('body', 'like', '%' . $this->search . '%');
                });
            })
            ->latest()
            ->paginate(10);
    }

    public function clearSearch(): void
    {
        $this->search = '';
    }

    public function delete(int $id): void
    {
        Campaign::where('id', $id)
            ->where('user_id', Auth::id())
            ->delete();

        session()->flash('success', 'Campaign deleted successfully.');
    }

};
?>

<div>
    <div class="flex justify-between items-center mb-4">
        <p class="text-gray-600">{{ __('Manage your email campaigns.') }}</p>

        <form wire:submit="applySearch" class="flex items-center gap-2 w-full sm:w-auto">
            <x-input.text
                type="text"
                wire:model.defer="search"
                placeholder="Search campaigns..."
                class="w-full sm:w-64"
            />

            <x-button.primary>
                    {{ __('Search') }}
            </x-button.primary>
            @if ($search)
                <x-button.secondary
                    wire:click="clearSearch"
                >
                        {{ __('Clear') }}
                </x-button.secondary>
            @endif
        </form>


        <x-button.primary href="{{ route('campaigns.create') }}">
            {{ __('New Campaign') }}
        </x-button.primary>
    </div>

    @if (session('success'))
        <x-message severity="success" class="mb-4">
            {{ session('success') }}
        </x-message>
    @endif

    <table class="w-full border-collapse">
        <thead>
        <tr class="border-b text-left">
            <th class="py-2">Name</th>
            <th>Status</th>
            <th>Scheduled At</th>
            <th></th>
        </tr>
        </thead>

        <tbody>
        @forelse ($this->campaigns as $campaign)
            <tr class="border-b">
                <td class="py-2">
                    <a href="{{ route('campaigns.edit', $campaign->id) }}"
                       class="text-blue-600">
                       {{ $campaign->name }}
                    </a>
                </td>

                <td>
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
                </td>

                <td class="py-2">
                    @if($campaign->scheduled_at)
                        {{ $campaign->scheduled_at }} 
                        ({{ $campaign->scheduled_at->diffForHumans(now(), ['parts' => 2]) }})
                    @endif
                </td>

                <td class="text-right space-x-2">

                    <button
                        wire:click="delete({{ $campaign->id }})"
                        wire:confirm="Are you sure?"
                        class="text-red-600">
                        Delete
                    </button>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="3" class="py-4 text-center text-gray-500">
                    No campaigns yet.
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>

    <div class="mt-4">
        {{ $this->campaigns->links() }}
    </div>
</div>
