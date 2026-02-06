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

<div class="flex flex-col sm:flex-row gap-3">
    <form wire:submit="applySearch" class="flex gap-2 w-full sm:w-auto">
        <input
            type="text"
            wire:model.defer="search"
            placeholder="Search campaigns..."
            class="border rounded px-3 py-2 w-full sm:w-64"
        >

        <button
            type="submit"
            class="bg-black text-white px-4 py-2 rounded"
        >
            Search
        </button>
        @if ($search)
            <button
                type="button"
                wire:click="clearSearch"
                class="border px-4 py-2 rounded"
            >
                Clear
            </button>
        @endif
    </form>
</div>


        <a href="{{ route('campaigns.create') }}"
           class="px-4 py-2 rounded bg-black text-white">
            New Campaign
        </a>
    </div>

    @if (session('success'))
        <div class="mb-4 px-4 py-2 bg-green-100 text-green-700 rounded">
            {{ session('success') }}
        </div>
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

                <td class="py-2">{{ $campaign->scheduled_at }}</td>

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
