# Booking & Scheduling

A premium booking and scheduling web app — Calendly/Stripe-style UI, provider-approval workflow, queued email notifications, and a scheduler that auto-expires stale requests.

**Stack:** Laravel 11 · Vue 3 (Composition API) · Vite · Tailwind CSS · MySQL · Pinia · Vue Router · Laravel Sanctum · Axios · lucide-vue-next.

---

## Features

- **Customer flow** — register, browse services, calendar-pick a slot (month/week/day, mobile-stacked), request booking, view & manage your bookings (reschedule, cancel).
- **Provider flow** — manage weekly availability + per-date overrides (blocked/custom-hours), review pending requests, approve/decline with optional reason, cancel approved bookings.
- **Approval workflow** — bookings are **pending → approved / declined**, with **cancelled** as a terminal state. Approving auto-declines other pending requests for the same slot inside a DB transaction.
- **Branded emails** — queued Mailables for `BookingRequested`, `BookingApproved`, `BookingDeclined`, `BookingCancelled`. Templates use inline styles for client-safe rendering.
- **Scheduler** — `bookings:expire-stale` auto-declines pending requests older than `BOOKING_PENDING_EXPIRY_HOURS` (default 24h), runs hourly.
- **Role-based access** — `customer`, `provider`, `admin`. Enforced via Sanctum + policies (`AvailabilityPolicy`, `BookingPolicy`).
- **Race-safe** — slot validation and approval use `DB::transaction` + `lockForUpdate()` so two requests can't both win the same approved slot.
- **Design system** — indigo accent (`#6366F1`/`#4F46E5`), Inter font, 8px grid, rounded-xl, soft shadows. Reusable base components: `BaseButton`, `BaseCard`, `BaseInput`, `BaseModal`, `BaseBadge`, `ToastContainer`, `BaseSkeleton`, `EmptyState`.

---

## Setup

### Prerequisites
- PHP 8.2+
- Composer 2.x
- Node 18+ / npm 9+
- MySQL 8.x (or MariaDB 10.4+)

### 1. Install dependencies
```bash
composer install
npm install
```

### 2. Configure environment
```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` and set your MySQL credentials:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=booking_scheduling
DB_USERNAME=your_user
DB_PASSWORD=your_password
```

Other relevant `.env` keys (defaults work for local dev):
```
QUEUE_CONNECTION=database          # queued Mailables are persisted to the DB jobs table
MAIL_MAILER=log                    # writes emails to storage/logs/laravel.log; switch to smtp / mailgun / etc. for prod
SANCTUM_STATEFUL_DOMAINS=localhost,localhost:5173,127.0.0.1,127.0.0.1:8000
BOOKING_PENDING_EXPIRY_HOURS=24    # threshold for scheduler auto-decline
```

### 3. Create the database, run migrations + seeders
```bash
mysql -u your_user -p -e "CREATE DATABASE booking_scheduling;"
php artisan migrate:fresh --seed
```

Seeded data:
- 1 admin, 1 provider, 2 customers (passwords below).
- 3 services owned by the provider, with prices (`$0`, `$149`, `$249`) and durations (30/60/90 min).
- Mon–Fri 09:00–17:00 availability for the provider.

### 4. Run it

You need **four** processes for the full experience. Run each in its own terminal.

```bash
# Backend HTTP
php artisan serve

# Frontend dev server (Vite)
npm run dev

# Queue worker — drains queued Mailables out of the DB
php artisan queue:work

# Scheduler — drives bookings:expire-stale (and any other future schedules)
php artisan schedule:work
```

Or run them all together with the bundled Composer script:
```bash
composer run dev
```

Visit **http://127.0.0.1:8000**.

### Production scheduler note
On a real server, replace `schedule:work` with a system cron entry:
```
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
```

---

## Seeded login credentials

| Role | Email | Password |
|---|---|---|
| Admin | `admin@example.com` | `password` |
| Provider | `provider@example.com` | `password` |
| Customer | `customer1@example.com` | `password` |
| Customer | `customer2@example.com` | `password` |

After signing in:
- **Customer** lands on `/book` (service picker → calendar). `My bookings` is in the sidebar.
- **Provider / Admin** lands on `/dashboard`. Sidebar: Dashboard / Bookings / Availability / Services / Settings.

---

## Useful commands

```bash
# Run the stale-pending sweep manually
php artisan bookings:expire-stale

# Same, with a custom threshold for that run
php artisan bookings:expire-stale --hours=1

# Dry run — report what would be expired without writing changes
php artisan bookings:expire-stale --dry-run

# Build the SPA for production
npm run build

# Inspect routes
php artisan route:list
```

---

## API surface (Sanctum SPA, cookie auth)

```
# Auth
POST   /api/auth/register
POST   /api/auth/login
POST   /api/auth/logout
GET    /api/auth/me

# Catalog
GET    /api/services
GET    /api/services/{service}
GET    /api/slots?service_id=&from=YYYY-MM-DD&to=YYYY-MM-DD

# Availability (provider/admin)
GET|POST|PUT|DELETE   /api/availability
GET|POST|PUT|DELETE   /api/availability-exceptions

# Customer bookings
GET|POST|PUT|DELETE   /api/bookings
GET                   /api/bookings/{booking}

# Provider/admin bookings
GET    /api/provider/bookings?status=&from=&to=&sort=
GET    /api/provider/bookings/{booking}
POST   /api/provider/bookings/{booking}/approve
POST   /api/provider/bookings/{booking}/decline
DELETE /api/provider/bookings/{booking}
```

CSRF: every state-changing request must first call `GET /sanctum/csrf-cookie`. The SPA's Axios client handles this transparently via `withXSRFToken: true`.

---

## Project structure

```
booking-sheduling/
├── app/
│   ├── Console/Commands/
│   │   └── ExpireStalePendingBookings.php
│   ├── Http/
│   │   ├── Controllers/Api/
│   │   │   ├── AuthController.php
│   │   │   ├── ServiceController.php
│   │   │   ├── SlotsController.php
│   │   │   ├── Customer/BookingController.php
│   │   │   └── Provider/
│   │   │       ├── AvailabilityController.php
│   │   │       ├── AvailabilityExceptionController.php
│   │   │       └── BookingController.php
│   │   ├── Requests/                # Form Request validators
│   │   │   ├── Auth/
│   │   │   ├── Availability/
│   │   │   └── Booking/
│   │   └── Resources/               # API Resource transformers
│   ├── Mail/                        # 4 queued Mailables
│   ├── Models/                      # User, Service, Availability, AvailabilityException, Booking
│   ├── Policies/                    # AvailabilityPolicy, BookingPolicy
│   ├── Providers/
│   └── Services/                    # Business logic
│       ├── BookingService.php       # request / reschedule / approve+auto-decline / decline / cancel
│       └── SlotAvailabilityService.php  # weekly hours + exceptions − bookings → bookable slots
├── bootstrap/
│   └── app.php                      # statefulApi() + JSON 401 handler
├── config/
│   ├── bookings.php                 # pending_expiry_hours
│   ├── cors.php                     # credentialed CORS for SPA origins
│   └── sanctum.php
├── database/
│   ├── factories/
│   ├── migrations/                  # users (role enum) · services · availabilities ·
│   │                                # availability_exceptions · bookings
│   └── seeders/DatabaseSeeder.php
├── resources/
│   ├── css/app.css                  # Tailwind layers + design tokens
│   ├── js/
│   │   ├── App.vue
│   │   ├── app.js
│   │   ├── bootstrap.js
│   │   ├── components/
│   │   │   ├── base/                # BaseButton/Card/Input/Modal/Badge/Skeleton/EmptyState/ToastContainer
│   │   │   ├── booking/             # BookingCalendar (month/week/day, mobile-stacked)
│   │   │   └── layout/              # AppSidebar, AppTopbar
│   │   ├── layouts/                 # AuthLayout, DashboardLayout
│   │   ├── lib/                     # api.js (Axios + CSRF), calendar.js (date helpers)
│   │   ├── pages/
│   │   │   ├── auth/                # Login, Register
│   │   │   ├── customer/            # BookLanding, MyBookings
│   │   │   ├── dashboard/           # Dashboard
│   │   │   ├── provider/            # Availability, Bookings
│   │   │   └── NotFound.vue
│   │   ├── router/                  # routes + role-aware guards
│   │   └── stores/                  # Pinia: auth, toast, availability, bookings, providerBookings
│   └── views/
│       ├── emails/                  # branded HTML email templates
│       │   ├── layouts/main.blade.php
│       │   ├── partials/
│       │   └── booking-{requested,approved,declined,cancelled}.blade.php
│       └── welcome.blade.php        # SPA shell
├── routes/
│   ├── api.php
│   ├── console.php                  # Scheduled commands
│   └── web.php                      # SPA catch-all
├── tailwind.config.js               # brand palette, ink/surface scales, soft shadows
└── vite.config.js                   # laravel-vite-plugin + @vitejs/plugin-vue
```

---

## How the slot algorithm works

`App\Services\SlotAvailabilityService::slotsFor(provider, service, from, to)`:

1. Start with the provider's weekly availability windows for that weekday.
2. If a `custom` exception exists on that date → replace the weekly schedule with those custom windows.
3. Subtract any `blocked` exceptions (full-day or partial-window).
4. Subtract any pending or approved bookings (with the service's buffer applied on both sides).
5. Slice each remaining window into consecutive `duration_minutes` slots. Drop slots in the past.

For booking validation (`BookingService::assertWithinAvailability`), `slotsFor` is called with `ignoreBookings: true` so a customer can race for the same pending slot (the conflict is sorted out at approval time via auto-decline).

---

## Testing the full flow

1. Sign in as `customer1@example.com` → `/book` → pick a service → pick a slot → confirm.
2. Email lands in `storage/logs/laravel.log` (or your real inbox if MAIL_MAILER is set to smtp). Subject: *New booking request for …*
3. Sign in as `provider@example.com` → `/bookings` → click **Approve**.
4. Two emails dispatched: *You're confirmed* to the customer, plus any *Booking request update* (auto-decline) emails to other customers who were holding the same slot.
5. To test the scheduler: create a pending booking, then run `php artisan bookings:expire-stale --hours=0` — the pending row flips to `declined` and a *Booking request update* email is sent.

---

## License

MIT.
