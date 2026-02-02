<?php

use Livewire\Component;
use App\Models\Subscriber;
use Illuminate\Support\Facades\Auth;


new class extends Component
{
    public ?Subscriber $subscriber = null;

    public string $name = '';
    public string $subject = '';
    public string $email = '';
    public string $status = Subscriber::STATUS_ACTIVE;

    public ?int $savedSubscriberId = null;

    public function mount(?int $subscriberId = null): void
    {
        if ($subscriberId) {
            $subscriber = Subscriber::where('id', $subscriberId)
                ->where('user_id', Auth::id())
                ->firstOrFail();

            abort_unless($subscriber->user_id === Auth::id(), 403);
    
            $this->subscriber = $subscriber;
            $this->name = $subscriber->name;
            $this->email = $subscriber->email;
            $this->status = $subscriber->status;
        }
    }
    
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:10000'],
            'status' => ['required'],
        ];
    }

    public function save(): void
    {
        $this->validate();

        if ($this->subscriber) {
            $this->subscriber->update([
                'name' => $this->name,
                'email' => $this->email,
                'status' => $this->status,
            ]);

            session()->flash('updated', 'Subscriber updated successfully.');
        } else {
            $subscriber = Subscriber::create([
                'user_id' => Auth::id(),
                'name' => $this->name,
                'email' => $this->email,
                'status' => 'draft',
            ]);

            $this->savedSubscriberId = $subscriber->id;

            session()->flash('created', 'Subscriber created successfully.');
            $this->reset(['name', 'email', 'status']);
        }
    }

    public function createAnother(): void
    {
        $this->reset([
            'name',
            'email',
            'status',
            'savedSubscriberId',
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
                Create Another Subscriber
            </button>

            <a
                href="/subscribers/{{ $savedSubscriberId }}/edit"
                class="border px-4 py-2 rounded text-center"
            >
                Edit Subscriber
            </a>

            <a
                href="/subscribers"
                class="border px-4 py-2 rounded text-center"
            >
                View All Subscribers
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
                    Subscriber Name
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

            {{-- Email --}}
            <div>
                <label class="block text-sm font-medium mb-1">
                    Email
                </label>
                <input
                    type="email"
                    wire:model.defer="email"
                    class="w-full border rounded px-3 py-2"
                >
                @error('email')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
            
            {{-- Actions --}}
            <div class="flex items-center gap-4">
                <button
                    type="submit"
                    class="bg-black text-white px-4 py-2 rounded"
                >
                    {{ $subscriber ? 'Update Subscriber' : 'Create Subscriber' }}
                </button>

                <a href="/subscribers" class="border px-4 py-2 rounded text-center">
                    Cancel
                </a>
            </div>
        </form>
    @endif
</div>