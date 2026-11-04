# Check-in with History

A unified, production-grade Flarum 1.x extension providing daily check-in, automatic check-in, streak tracking, user profile history calendar, supplementary check-in, and Store check-in card integration.


- **Package**: `primexcalibur/checkin-with-history`
- **Extension ID**: `primexcalibur-checkin-with-history`
- **Requirements**: PHP 8.0+, Flarum 1.8+, and [`huoxin/money-with-history`](https://github.com/huoxin233/flarum-ext-money-with-history) 1.2.4+ (1.x).
- **Optional Integrations**: [`mattoid/flarum-ext-store`](https://github.com/mattoid/flarum-ext-store) (for purchasing check-in cards and backpack inventory) and [`flarum/suspend`](https://github.com/flarum/framework/tree/main/extensions/suspend).


---


## Features

- **Daily Check-in & Streaks**: One-click daily attendance with continuous streak tracking and optional money reward integration.
- **Profile History Calendar**: Interactive calendar on user profiles powered by FullCalendar, displaying normal check-ins and supplemented check-ins with customizable colors.
- **Supplementary Check-in**: Allows users to supplement missed dates within a configurable historical range, consuming Check-in Cards.
- **Store Backpack Integration**: Seamlessly integrates with `mattoid/flarum-ext-store` via `StoreExtend`. Check-in cards can be sold in the store, held in the user's backpack, and automatically consumed when supplementing attendance.
- **Smart Priority Card Consumption**: When users hold multiple check-in cards, time-limited cards expiring earliest are consumed first, preserving permanent cards.
- **Automatic Check-in**: Optional auto check-in with configurable delay upon forum visit.
- **Security & Integrity**: Integrated with `flarum/suspend` to block suspended users, with pre-flight eligibility checks and database transaction isolation.

---

## Installation

Install via Composer:

```sh
composer require primexcalibur/checkin-with-history:^1.0 -W
php flarum extension:enable huoxin-money-with-history
php flarum extension:enable primexcalibur-checkin-with-history
php flarum migrate
php flarum assets:publish
php flarum cache:clear
```



## Store Integration (Check-in Cards)

When [`mattoid/flarum-ext-store`](https://github.com/mattoid/flarum-ext-store) is installed, this extension automatically registers the `checkin-card` goods plugin:

1. In Flarum Admin, navigate to **Store** $\rightarrow$ **Goods** $\rightarrow$ **Add Goods**.
2. Select **Check-in Card** (code: `checkin-card`).
3. Set your preferred title and price.
4. Ensure the following settings:
   - **Usable (允许使用)**: `Yes` (so cards appear in user backpacks).
   - **Use Type (使用方式)**: `Consumable` (一次性道具).
   - **Validity (有效时间)**: `Permanent` or `Limit` (days).

When users click "Use" in their backpack, a friendly guidance dialog informs them to visit the check-in calendar. Clicking a missed date on the calendar consumes the card automatically.

---

## Upgrade from Legacy Extensions

This extension replaces and supersedes both `ziiven/flarum-daily-check-in` and `mattoid/flarum-daily-check-in-history`.

1. **Back up your database first.**
2. Disable the legacy extensions (**do not click Purge**, as legacy rollback migrations delete shared tables or columns):

```sh
php flarum extension:disable mattoid-daily-check-in-history
php flarum extension:disable ziiven-daily-check-in
composer remove ziiven/flarum-daily-check-in mattoid/flarum-daily-check-in-history --no-update
```


```sh
composer require primexcalibur/checkin-with-history:^1.0 -W
php flarum extension:enable primexcalibur-checkin-with-history
php flarum migrate
php flarum cache:clear
```

### What the Migration Does:

- **Consolidates History**: Migrates historical records from `user_checkin_history` and `users.last_checkin_time` into the unified `checkin_records` table with unique indexing `(user_id, checkin_date)`. Drops the legacy `user_checkin_history` table once migrated.
- **Migrates Cards**: If `store_cart` is present, converts legacy `users.checkin_card` counts into permanent backpack items and safely drops the legacy user column.
- **Preserves Settings**: Converts legacy `ziven-forum-checkin.*` and `mattoid-forum-checkin.*` settings keys into canonical `primexcalibur-checkin-with-history.*` keys.
- **Preserves Permissions**: Migrates and seeds member check-in permissions.



## API Routes

| Method | Route                     | Purpose                        |
| ------ | ------------------------- | ------------------------------ |
| POST   | `/api/checkin`            | Execute daily check-in         |
| GET    | `/api/checkin/history`    | Fetch history by user & date   |
| POST   | `/api/supplement/checkin` | Supplement a missed check-in   |


---


## Architecture & Events


- All PHP code resides under `Primexcalibur\CheckinWithHistory`.
- `Service\CheckInService` handles eligibility validation (including suspension checks), row locking, streak calculation, money reward transactions, and dispatching events.
- `Support\CheckinCardManager` encapsulates check-in card inventory queries and prioritized FIFO/earliest-expiry consumption.
- Both normal and supplementary check-ins dispatch the `Primexcalibur\CheckinWithHistory\Event\CheckedIn` event upon transaction commit. The event provides:
  - `$event->user`: The actor who checked in.
  - `$event->record`: The newly created `CheckinRecord` model.
  - `$event->supplementCount`: Number of days supplemented (`0` for normal check-in).


## Development

```sh
# Install PHP dependencies
composer install

# Set up test database (configure DB_* environment variables first)
composer test:setup

# Run unit and integration tests
composer test
composer test:unit
composer test:integration

# Frontend development
cd js
npm ci
npm run format-check
npm run check-typings
npm run build
```


## Sources and Licenses

This package unifies and refactors:

- [invites-fun/flarum-daily-check-in](https://github.com/invites-fun/flarum-daily-check-in), originally by Ziven, licensed under [MIT](LICENSE.md).
- [huoxin233/flarum-daily-check-in-history](https://github.com/huoxin233/flarum-daily-check-in-history/tree/huoxin/architecture-refactor), originally by mattoid and refactored by huoxin, licensed under [LPL-1.02](LICENSE-HISTORY.md).

Both licenses, notices, and attribution are preserved in [NOTICE](NOTICE), [LICENSE.md](LICENSE.md), and [LICENSE-HISTORY.md](LICENSE-HISTORY.md).
