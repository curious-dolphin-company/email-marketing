<?php

use Livewire\Component;
use App\Models\Subscriber;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;


new class extends Component
{
    public ?Subscriber $subscriber = null;

    public ?string $name = '';
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
            'email' => [
                'required',
                'email',
                Rule::unique('subscribers')
                    ->where('user_id', auth()->id())
                    ->ignore($this->subscriber?->id),
            ],
            'name' => ['nullable', 'string', 'max:255'],
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
                'unsubscribe_token' => Str::uuid(),
                'status' => Subscriber::STATUS_ACTIVE,
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
        <x-message severity="success" class="mb-4">
            {{ session('created') }}
        </x-message>
        <div class="flex flex-col sm:flex-row gap-3">
            <x-button.secondary wire:click="createAnother"> {{ __('Create Another Subscriber') }} </x-button.secondary>
            <x-button.secondary href="/subscribers/{{ $savedSubscriberId }}/"> {{ __('Edit Subscriber') }} </x-button.secondary>
            <x-button.secondary href="/subscribers"> {{ __('View All Subscribers') }} </x-button.secondary>
        </div>
        
    @elseif (session()->has('updated'))
        <x-message severity="success" class="mb-4">
            {{ session('updated') }}
        </x-message>
    @endif

    @if (!session()->has('created'))
        <form wire:submit="save" class="space-y-6">
            {{-- Name --}}
            <div>
                <x-input.label for="subscriber-name">{{ __('Subscriber Name') }}</x-input.label>
                <x-input.text id="subscriber-name" class="block mt-1 w-full" wire:model.defer="name" autofocus />
                <x-input.error :messages="$errors->get('name')" class="mt-1" />
            </div>

            {{-- Email --}}
            <div>
                <x-input.label for="email">{{ __('Email') }}</x-input.label>
                <x-input.text id="email" type="email" class="block mt-1 w-full" wire:model.defer="email" />
                <x-input.error :messages="$errors->get('email')" class="mt-1" />
            </div>
            
            {{-- Actions --}}
            <div class="flex items-center gap-4">
                <x-button.primary>
                    {{ $subscriber ? 'Update Subscriber' : 'Create Subscriber' }}
                </x-button.primary>

                <x-button.secondary href="/subscribers"> {{ __('Close') }} </x-button.secondary>
            </div>
        </form>
    @endif
</div>