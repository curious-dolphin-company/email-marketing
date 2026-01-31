# Laravel + Breeze Blade Setup

## Requirements

- **PHP** 8.2+
- **Composer**
- **MariaDB** (or MySQL) — MySQL driver works with both
- **Node.js** 18+ (for Vite/Tailwind asset build)

---

## Required Composer Commands

```bash
# 1. Create Laravel project (from empty directory)
composer create-project laravel/laravel . --prefer-dist

# 2. Install Laravel Breeze (Blade mode)
composer require laravel/breeze --dev

# 3. Run Breeze installer
php artisan breeze:install blade --no-interaction

# 4. Install dependencies & build assets
npm install
npm run build
```

---

## Auth Routes (from Breeze Blade)

| Method | URI | Name | Controller |
|--------|-----|------|------------|
| GET | `/login` | login | AuthenticatedSessionController@create |
| POST | `/login` | — | AuthenticatedSessionController@store |
| GET | `/register` | register | RegisteredUserController@create |
| POST | `/register` | — | RegisteredUserController@store |
| POST | `/logout` | logout | AuthenticatedSessionController@destroy |
| GET | `/forgot-password` | password.request | PasswordResetLinkController@create |
| POST | `/forgot-password` | password.email | PasswordResetLinkController@store |
| GET | `/reset-password/{token}` | password.reset | NewPasswordController@create |
| POST | `/reset-password` | password.store | NewPasswordController@store |
| GET | `/verify-email` | verification.notice | EmailVerificationPromptController |
| GET | `/verify-email/{id}/{hash}` | verification.verify | VerifyEmailController |
| POST | `/email/verification-notification` | verification.send | EmailVerificationNotificationController@store |
| GET | `/confirm-password` | password.confirm | ConfirmablePasswordController@show |
| POST | `/confirm-password` | — | ConfirmablePasswordController@store |
| PUT | `/password` | password.update | PasswordController@update |

**Login/Register:** `/login` and `/register` work at those URLs.

---

## Config Changes for MariaDB

### 1. `.env` (and `.env.example`)

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=email_marketing
DB_USERNAME=root
DB_PASSWORD=your_password
```

### 2. Create MariaDB database

```bash
mysql -u root -p -e "CREATE DATABASE email_marketing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

**Quick local test without MariaDB:** Use SQLite by setting in `.env`:

```env
DB_CONNECTION=sqlite
# Comment out or remove DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD
```

### 3. Run migrations

```bash
php artisan migrate
```

### 4. Session driver

Breeze Blade uses session auth. `SESSION_DRIVER=database` is supported and uses the `sessions` table created by the default migrations. No extra config needed.

---

## Auth Stack Summary

| Component | Value |
|-----------|-------|
| Guard | `web` |
| Driver | `session` |
| Sanctum | **Not added** — Breeze Blade uses session auth only; Sanctum exists only as a framework transitive dep, unused |
| API tokens | **Not used** |

---

## Outcome: Login/Register at /login

1. `php artisan migrate` — run migrations
2. `npm install && npm run build` — build frontend (Node 18+)
3. `php artisan serve` — start server
4. Visit `http://localhost:8000/login` and `http://localhost:8000/register`
