# OTP Slingshot

A small game I built during a lunch break. Instead of typing a one-time code, you sling it in one digit at a time with a slingshot. Fewer mistakes and a faster time put you higher on the public leaderboard.

Laravel, plain Blade, one `<canvas>`. No frontend build step.

## Features

- Slingshot code entry with a dotted aim preview
- 6-digit code displayed on screen
- Mistakes counted for wrong digits, wrong boxes and misses
- Public leaderboard ranked by fewest mistakes, then time
- Server-side scoring, so results can't be faked from the browser
- Admin panel at `/admin`

## Requirements

- PHP 8.2+
- Composer
- Any database Laravel supports (SQLite is easiest)
- Laravel 11 or 12

## Installation

This repo contains only the app files. Drop them into a fresh Laravel project.

```bash
composer create-project laravel/laravel otp-slingshot
cd otp-slingshot
```

Copy `app/`, `database/`, `resources/` and `routes/` from this repo into the project root, merging or overwriting.

Add an admin password to `.env`:

```env
ADMIN_PASSWORD=choose-something-strong
```

Then migrate and run:

```bash
php artisan migrate
php artisan serve
```

Open http://127.0.0.1:8000.

The admin password defaults to `admin` if unset, so change it before going public. Don't run `config:cache`, as the password is read with `env()`.

## How to play

1. Enter your email. It is used as your name on the leaderboard.
2. Your 6-digit code appears at the top.
3. Tap a number on the floor to load the slingshot.
4. Drag the pouch back to aim, then release.
5. Fill all six boxes in order. Every wrong digit or miss adds a mistake.

## Admin

Sign in at `/admin` with `ADMIN_PASSWORD` to view the top 50, see attempt totals and clear the leaderboard. The SMTP settings there are optional and unused by the game, since the code is shown on screen.

## Configuration

| Setting | Location |
| --- | --- |
| Code length | `CODE_LENGTH` in `OtpController` (also update the `code` column in the migration) |
| Count misses as mistakes | `COUNT_MISSES` in `OtpController` |
| Code expiry (10 min) | `addMinutes(10)` in `OtpController@send` |
| Mask emails on the leaderboard | Use `$row->masked_email` in `partials/leaderboard.blade.php` |
| Sling feel | `GRAVITY`, `POWER`, `MAX_PULL` in `resources/views/verify.blade.php` |
| Colors | CSS variables in `resources/views/layout.blade.php` |

## Structure

```
app/Http/Controllers/OtpController.php     code generation, throw validation, scoring
app/Http/Controllers/AdminController.php   admin login, settings, leaderboard reset
app/Http/Middleware/AdminAuth.php
app/Models/OtpAttempt.php
app/Models/Setting.php
database/migrations/                       settings and otp_attempts tables
resources/views/                           layout, home, verify (game), admin, partials
routes/web.php
```

## Notes

This is a demo, not real authentication. The code is shown to the player, so don't use it to protect anything. Emails appear on the public leaderboard unless you enable masking.