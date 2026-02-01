# Authentication Guide

## Overview

The app uses **Laravel Breeze (Blade)** for authentication:

- **Email + password** login
- **Registration**
- **Password reset** (forgot password flow)
- **Email verification** (enforced via `verified` middleware on dashboard)

## User Model

| Field | Type | Notes |
|-------|------|-------|
| `name` | string | Required |
| `email` | string | Unique, used for login |
| `password` | string | Hashed (bcrypt) |
| `email_verified_at` | timestamp | Null until verified |
| `is_admin` | boolean | Default `false`; set via `$user->update(['is_admin' => true])` — **not** in `$fillable` to prevent mass-assignment escalation |

## Auth-Protected Routes

| Route | Middleware | Purpose |
|-------|------------|---------|
| `/dashboard` | `auth`, `verified` | Main app; requires login + verified email |
| `/profile/*` | `auth` | Profile edit; requires login only |
| `/login`, `/register`, etc. | `guest` | Only accessible when logged out |

## Livewire + Auth

### How Livewire Handles Auth State

Livewire **does not maintain separate auth state**. It runs within the same HTTP request/session as Laravel:

1. **Same session** — When a user visits a page, Laravel's session middleware runs first. By the time a Livewire component renders or handles an action, `auth()->user()` is already available.

2. **Route protection first** — Protect the **route** that renders the page. If the route uses `auth` middleware, the Livewire component on that page only runs for authenticated users.

3. **Direct access** — Use `auth()->user()`, `auth()->check()`, or `@auth` / `@guest` in Livewire views exactly as in Blade.

### Example

```blade
{{-- In a Livewire component view --}}
@auth
    <p>Hello, {{ auth()->user()->name }}</p>
@endauth
```

```php
// In a Livewire component class
public function doSomething()
{
    if (! auth()->check()) {
        return $this->redirect(route('login'));
    }
    $user = auth()->user();
    // ...
}
```

### Gotchas

1. **Public pages with Livewire** — If a Livewire component is on a **public** page (no `auth` middleware), it can be reached by anyone. Livewire requests are normal HTTP requests. Don't assume auth; check `auth()->check()` before acting on sensitive data.

2. **Middleware runs per request** — Each Livewire wire:click, wire:submit, etc. triggers a new request. Middleware runs on each. If the route is protected, unauthenticated users get redirected before the component runs.

3. **Session expiry** — If the session expires during a long-lived Livewire interaction, the next request will redirect to login. Handle this in your UI (e.g. show a message after redirect).

4. **`is_admin`** — Never trust client input. Always check `$user->isAdmin()` or `$user->is_admin` in backend code. Use policy/middleware for admin-only actions.

## Email Verification

- New users receive a verification email after registration.
- The `verified` middleware redirects unverified users from `/dashboard` to `/verify-email`.
- Configure `MAIL_*` in `.env` for production. Use `MAIL_MAILER=log` locally to inspect emails in `storage/logs/laravel.log`.

## Setting an Admin

```php
// In tinker or a seeder
User::where('email', 'admin@example.com')->update(['is_admin' => true]);
```

`is_admin` is not in `$fillable`, so it cannot be set via mass assignment (e.g. registration).
