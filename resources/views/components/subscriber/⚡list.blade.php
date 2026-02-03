<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Subscriber;
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

    public function getSubscribersProperty()
    {
        return Subscriber::where('user_id', Auth::id())
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('email', 'like', '%' . $this->search . '%');
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
        Subscriber::where('id', $id)
            ->where('user_id', Auth::id())
            ->delete();

        session()->flash('success', 'Subscriber deleted successfully.');
    }

    public function unsubscribe(int $id): void
    {
        Subscriber::where('id', $id)
            ->where('user_id', Auth::id())
            ->update(['status' => Subscriber::STATUS_UNSUBSCRIBED]);

        session()->flash('success', 'Subscriber unsubscribe successfully.');
    }

};
?>

<div>
    <div class="flex justify-between items-center mb-4">
        <p class="text-gray-600">{{ __('Manage your subscribers.') }}</p>

<div class="flex flex-col sm:flex-row gap-3">
    <form wire:submit="applySearch" class="flex gap-2 w-full sm:w-auto">
        <input
            type="text"
            wire:model.defer="search"
            placeholder="Search subscribers..."
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


        <a href="{{ route('subscribers.create') }}"
           class="px-4 py-2 rounded bg-black text-white">
            New Subscriber
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
            <th>Email</th>
            <th>Status</th>
            <th></th>
        </tr>
        </thead>

        <tbody>
        @forelse ($this->subscribers as $subscriber)
            <tr class="border-b">
                <td class="py-2">
                    <a href="{{ route('subscribers.edit', $subscriber->id) }}" class="text-blue-600">
                        {{ empty($subscriber->name) ? '[Empty Name]' : $subscriber->name }}
                    </a>
                </td>

                <td class="py-2">{{ $subscriber->email }}</td>

                <td>
                    <span class="px-2 py-1 text-sm rounded 
                        @class([
                            'bg-gray-200' => $subscriber->status === 'unsubscribed',
                            'bg-green-200' => $subscriber->status === 'active',
                        ])">
                        {{ ucfirst($subscriber->status) }}
                    </span>
                </td>

                <td class="text-right space-x-2">
                    @if ($subscriber->status === 'active')
                        <button
                            wire:click="unsubscribe({{ $subscriber->id }})"
                            wire:confirm="Are you sure?"
                            class="text-yellow-600">
                            Unsubscribe
                        </button>
                    @else
                    @endif
                    <button
                        wire:click="delete({{ $subscriber->id }})"
                        wire:confirm="Are you sure?"
                        class="text-red-600">
                        Delete
                    </button>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="3" class="py-4 text-center text-gray-500">
                    No subscribers yet.
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>

    <div class="mt-4">
        {{ $this->subscribers->links() }}
    </div>
</div>
