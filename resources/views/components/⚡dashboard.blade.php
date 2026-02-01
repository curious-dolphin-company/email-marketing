<?php

use Livewire\Component;

new class extends Component
{
    // Auth is automatic. Livewire shares the same session as the page.
    // Use auth()->user() anywhere; no extra setup needed.
};
?>

<div>
    @auth
        <p class="text-gray-600 mt-2">
            Welcome back, <strong>{{ auth()->user()->name }}</strong>.
            Livewire has full access to the authenticated user.
        </p>
    @endauth
</div>