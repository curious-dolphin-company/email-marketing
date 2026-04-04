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

        <form wire:submit="applySearch" class="flex items-center gap-2 w-full sm:w-auto">
            <x-input.text
                type="text"
                wire:model.defer="search"
                placeholder="Search subscribers..."
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

        <x-button.primary
            href="{{ route('subscribers.create') }}"
        >
                {{ __('New Subscriber') }}
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
                    <x-status-badge :status="$subscriber->status" >
                        {{ ucfirst($subscriber->status) }}
                    </x-status-badge>
                </td>

                <td class="text-right space-x-2">
                    @if ($subscriber->isActive())
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
