# MonkeyPunch merged baseline

This package uses **CoolFrog-clicker-game** as the gameplay base and keeps:
- Tap farming
- Missions
- Earn
- Boost
- Referral
- Blackjack
- Spin

Merged additions from the MonkeyPunch launch pass include:
- Wallet page
- Withdraw request flow
- Admin pages for withdraw requests / reward config / referral tasks
- TON wallet fields on the user model
- API routes for wallet connect and withdraw requests
- Basic tap throttle middleware usage

## What you still need to do after unzip
1. In `api`:
   - `composer install`
   - create `.env` from `.env.example`
   - create the database file if using sqlite
   - `php artisan key:generate`
   - `php artisan migrate`
   - `php artisan serve`
2. In `coolfrog`:
   - `npm install`
   - create `.env` from `.env.example`
   - `npm run dev`

## Current status
This is a **merged baseline**, not a fully QA-tested production build.
The core clicker flow should remain intact, while wallet / withdraw / admin additions are included from the second package.
