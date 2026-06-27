# Tocaan — Extendable Order & Payment Management API

A clean, extensible Laravel API for managing a product catalog, carts, orders, and
payments. New payment gateways drop in with a single class plus one config line
(strategy + driver-manager pattern). Separate Member (customer) and User (admin) JWT
guards, idempotent order creation, dedicated filter classes, secured with JWT, fully
validated, tested (119 feature/unit tests, PHPStan level 5 clean), and documented
(OpenAPI + Postman, importable into Apidog).

## Tech stack

- **Laravel 13** (PHP 8.4+)
- **JWT auth** — `tymon/jwt-auth`
- **DTOs** — `spatie/laravel-data`
- **Roles** — `spatie/laravel-permission`
- **Audit log** — `spatie/laravel-activitylog`
- **Settings** — `spatie/laravel-settings`
- **Inbound webhooks** — `spatie/laravel-webhook-client`
- **Query filtering** — `tucker-eric/eloquentfilter` (dedicated `*Filter` classes)
- **Admin panel** — **Filament v4** (translatable: English + Arabic/RTL)
- **API docs** — `knuckleswtf/scribe` (OpenAPI + Postman)
- **Tooling** — Pest, Larastan, Pint

## Architecture

```
HTTP → Route (RESTful, /api/v1) → FormRequest (validation) → DTO (spatie-data)
     → thin Controller → Action (DB::transaction, lockForUpdate, fires Event)
     → Model (enum status, SoftDeletes, audit) → API Resource (JSON)

Payments → PaymentManager::driver($gateway) → PaymentGateway::charge() → ChargeResult
Events   → PaymentEventSubscriber → Listeners (orders, payments, notifications, webhooks)
Admin    → Filament panel (Products, Orders, Payments, Webhook Bounces)
```

### Event-driven checkout (CartPaid)

Paying a cart is the commit point. `POST /cart/checkout` charges the gateway against the
cart total and, on success, fires a single **`CartPaid`** event that fans out to
independent listeners — each one side effect, the way a mature system keeps checkout
extensible:

```
POST /cart/checkout {method}
  → CheckoutCartAction: lock cart → guard → charge gateway
     → success → event(CartPaid)
        → CreateOrderFromCart   (snapshot items into a confirmed Order)
        → RecordPayment         (create the Payment record)
        → SendOrderNotification (queueable customer notification)
        → MarkAndDeleteCart     (mark checked-out + soft-delete the cart)
     → failure → event(CartPaymentFailed) + 402 (whole transaction rolls back)
```

Adding a new checkout side effect = add one listener to `CartPaid` in
`PaymentEventSubscriber` — no change to the action or controller.

Key design choices:

- **Two auth audiences, two JWT guards**: **Members** (customers who shop, own carts &
  orders) authenticate on the `member-api` guard; **Users** (admins/staff) authenticate
  on the `api` guard and have **roles & permissions** (spatie-permission). Each has its
  own model, table, and login.
- **Split route files**: `routes/api/v1/member.php` (member-api) and
  `routes/api/v1/admin.php` (api, permission-gated). Authorization is enforced by **route
  middleware** (`can:` for member ownership, `permission:` for admin), not inline in
  controllers.
- **Thin controllers**: every write delegates to a single-purpose **Action** class.
- **Server-side pricing**: order/cart prices always come from the catalog, never the
  client — line items snapshot the product name + price at purchase time for
  immutable order history.
- **State guard**: `OrderStatus` enum owns the allowed transitions; business rules
  (no delete with payments, pay only when confirmed) live in Actions + exceptions.
- **Clean cart↔order link**: a cart references the order it became (`carts.order_id`,
  null-on-delete) — a single source of truth, not a bidirectional relationship.

## Setup

Requirements: PHP 8.4+, Composer, MySQL 8 (or compatible), Node (for Filament assets).

```bash
# 1. Install dependencies
composer install
npm install && npm run build

# 2. Environment
cp .env.example .env
php artisan key:generate
php artisan jwt:secret

# 3. Configure the database in .env (defaults to MySQL `tocaan`)
#    DB_CONNECTION=mysql  DB_DATABASE=tocaan  DB_USERNAME=root  DB_PASSWORD=

# 4. Migrate + seed (creates roles, an admin, a customer, products & sample orders)
php artisan migrate --seed

# 5. Serve (Laravel Herd at http://tocaan.test, or:)
php artisan serve
```

Seeded accounts: `admin@tocaan.test` / `customer@tocaan.test` (password: `password`).

## Running the API

Endpoints live under two prefixes: **`/api/v1/member/*`** (customers, `member-api` guard)
and **`/api/v1/admin/*`** (staff, `api` guard). Authenticate with `Authorization: Bearer {jwt}`.

```bash
# Member: register (returns a JWT)
curl -X POST http://tocaan.test/api/v1/member/auth/register \
  -H 'Accept: application/json' \
  -d 'name=Jane&email=jane@example.com&password=Password123!&password_confirmation=Password123!'

# Member: add to cart, then pay (checkout charges the gateway and creates a confirmed, paid order)
curl -X POST http://tocaan.test/api/v1/member/cart/items -H "Authorization: Bearer $TOKEN" -d 'product_id=1&quantity=2'
curl -X POST http://tocaan.test/api/v1/member/cart/checkout -H "Authorization: Bearer $TOKEN" -d 'method=credit_card'

# Member: orders can also be created directly and paid through the order endpoint
curl -X POST http://tocaan.test/api/v1/member/orders -H "Authorization: Bearer $TOKEN" -d 'items[0][product_id]=1&items[0][quantity]=2'
curl -X POST http://tocaan.test/api/v1/member/orders/{uuid}/payments -H "Authorization: Bearer $TOKEN" -d 'method=credit_card'

# Admin: view orders / payments / manage catalog (requires the matching permission)
curl http://tocaan.test/api/v1/admin/orders -H "Authorization: Bearer $ADMIN_TOKEN"
```

### Member endpoints (`/api/v1/member`, guard `member-api`)

| Group | Endpoints |
|-------|-----------|
| Auth | `POST /auth/register`, `POST /auth/login`, `GET /auth/me`, `POST /auth/refresh`, `POST /auth/logout` |
| Products | `GET /products`, `GET /products/{id}` (read-only) |
| Cart | `GET /cart`, `POST /cart/items`, `DELETE /cart/items/{product}`, `POST /cart/checkout` (pays + creates order) |
| Orders | `GET/POST /orders`, `GET/PUT/DELETE /orders/{uuid}`, `POST /orders/{uuid}/confirm`, `POST /orders/{uuid}/cancel` |
| Payments | `GET /orders/{uuid}/payments`, `POST /orders/{uuid}/payments`, `GET /payments`, `GET /payments/{uuid}`, `POST /payments/{uuid}/refund` |

### Admin endpoints (`/api/v1/admin`, guard `api`, permission-gated)

| Group | Endpoints | Permission |
|-------|-----------|------------|
| Auth | `POST /auth/login`, `GET /auth/me`, `POST /auth/refresh`, `POST /auth/logout` | — |
| Products | `GET /products`, `GET /products/{id}` | `products.view` |
| Products | `POST /products`, `PUT/DELETE /products/{id}` | `products.manage` |
| Orders | `GET /orders`, `GET /orders/{uuid}`, `PUT /orders/{uuid}`, `GET /orders/{uuid}/payments` | `orders.view` |
| Payments | `GET /payments`, `GET /payments/{uuid}`, `POST /payments/{uuid}/refund` | `payments.view` |

Roles seeded on the `api` guard: **admin** (all permissions), **manager** (view-only).

### Other

| Endpoint | Notes |
|----------|-------|
| `POST /api/v1/webhooks/payments` | signed inbound gateway reconciliation |

Business rules:
- Payments can only be processed for **confirmed** orders.
- Orders **cannot be deleted** if they have associated payments.

### Refunds (gateway strategy extension)

Refunds use an **opt-in `Refundable` interface** — a gateway supports refunds only if it
implements it (`CreditCardGateway`, `PaypalGateway` do). `RefundPaymentAction` locks the
payment, validates the amount against the refundable balance, calls the gateway, records an
`OrderRefund`, and flips the order to **refunded** once fully refunded. Supports partial +
repeated refunds; rejects over-refunds (422) and non-refundable gateways (422).

```bash
curl -X POST http://tocaan.test/api/v1/member/payments/{uuid}/refund -H "Authorization: Bearer $TOKEN" -d 'amount=25'
curl -X POST http://tocaan.test/api/v1/admin/payments/{uuid}/refund -H "Authorization: Bearer $ADMIN_TOKEN"
```

### Admin order management

`PUT /api/v1/admin/orders/{uuid}` edits any order's totals. The **Filament panel** order page
exposes Confirm / Cancel (state-guarded) + Edit Totals; the payment page exposes a Refund
action (shown only when the gateway is refundable and a balance remains).

### Concurrency & pricing internals

- **Inventory safety**: stock is reserved through an `InventoryManager` that `lockForUpdate`s
  product rows, re-checks availability under the lock, then decrements — preventing oversell
  under concurrent checkouts.
- **Pricing pipeline**: totals are computed by a `PriceCalculator` running a Laravel `Pipeline`
  of stages (`ApplyTax` → `ApplyDiscount`). New rules (coupons, shipping) slot in as a stage.

### Filtering & sorting

List endpoints use dedicated **filter classes** (`app/Filters/*Filter.php`, one method per
filter — the MSAAQ/`EloquentFilter` pattern). Pass filters as flat query params:

```
GET /api/v1/member/orders?status=confirmed&sort_by[total]=desc
GET /api/v1/admin/products?name=keyboard&is_active=1&sort_by[price]=asc
GET /api/v1/admin/payments?gateway=credit_card&status=successful
```

Adding a filter = add one method to the relevant `*Filter` class.

### Idempotency

`POST /member/orders` and `POST /member/cart/checkout` are **idempotent**. Send an
`Idempotency-Key` header; a repeated request with the same key **replays the original
response** instead of creating a duplicate order (Stripe-style). Reusing a key with a
different request body returns `422`. Keys are stored in the cache with a 24h TTL
(`payments.idempotency_ttl_hours`); a duplicate that arrives while the original is still
in flight returns `409`.

```bash
curl -X POST http://tocaan.test/api/v1/member/orders \
  -H "Authorization: Bearer $TOKEN" -H "Idempotency-Key: $(uuidgen)" \
  -d 'items[0][product_id]=1&items[0][quantity]=2'
```

## Adding a new payment gateway (3 steps)

The payment layer uses a **strategy pattern** behind a Laravel `Manager` driver factory,
so a new gateway needs **no changes to controllers or actions**.

**1. Create the gateway class** implementing the contract (`app/Payments/Gateways/StripeGateway.php`):

```php
namespace App\Payments\Gateways;

use App\Models\Payment;
use App\Payments\Results\ChargeResult;

class StripeGateway extends AbstractGateway
{
    public const NAME = 'stripe';

    public function name(): string
    {
        return self::NAME;
    }

    public function charge(Payment $payment): ChargeResult
    {
        $this->requireConfig(['secret_key']);

        // ... call the real gateway SDK here ...

        return ChargeResult::successful($this->name(), $reference, $rawResponse);
        // or ChargeResult::failed($this->name(), $message);
    }
}
```

**2. Register it in `config/payments.php`:**

```php
'gateways' => [
    // ...existing...
    'stripe' => [
        'driver' => \App\Payments\Gateways\StripeGateway::class,
        'secret_key' => env('STRIPE_SECRET_KEY'),
    ],
],
```

**3. Add credentials to `.env`:**

```dotenv
STRIPE_SECRET_KEY=sk_live_xxx
```

That's it. `PaymentManager::driver('stripe')` now resolves the gateway, and clients can
pay with `{"method": "stripe"}`. Gateway config can come from `.env` **or** the database
via `spatie/laravel-settings`.

## API documentation (Apidog / Postman / OpenAPI)

Documentation is generated **from the code** with Scribe (annotations live only on
controllers — business code stays comment-free):

```bash
php artisan scribe:generate
```

This produces, under `docs/` (committed) and `storage/app/private/scribe/`:

- `docs/openapi.yaml` — OpenAPI 3.x spec
- `docs/postman_collection.json` — Postman collection

**To use in Apidog**: Import → OpenAPI → select `docs/openapi.yaml` (or import the
Postman collection). Browsable HTML docs are also served at `http://tocaan.test/docs`.

## Admin panel

A translatable **Filament v4** panel at `/admin` (login with the seeded admin):

- **Products** — full CRUD catalog management
- **Orders** / **Payments** — view, filter by status
- **Webhook Bounces** — failed inbound webhooks (with a one-click **Retry** action)
- **Dashboard** — orders, revenue, failed payments, webhook bounces

Switch language (English ⇄ Arabic, with RTL) using the toggle in the panel header.

## Testing

```bash
php artisan test                  # 119 tests
vendor/bin/pint --test            # code style
vendor/bin/phpstan analyse        # static analysis (Larastan)
```

Tests run on an in-memory SQLite database (configured in `phpunit.xml`), so they never
touch your MySQL dev data.

## Assumptions & notes

- **Product catalog (deliberate deviation)**: the task describes free-form items
  (`product_name`, `quantity`, `price`). We instead model a **product catalog** and accept
  `product_id` + `quantity`, pricing **server-side** from the catalog. This is a conscious
  senior choice for data integrity and security (clients can't set their own prices) and to
  showcase catalog ↔ order snapshotting — order items still store the name + price captured
  at purchase time, so order history stays immutable.
- **Single-tenant**: there is no multi-tenancy layer.
- **Simulated gateways**: `CreditCardGateway` and `PaypalGateway` simulate processing
  (they don't call real APIs) so the flow is testable end-to-end. Swap in real SDK calls
  inside `charge()` to go live.
- **Money** is stored as `decimal(12,2)` and returned as JSON numbers.
- **Soft deletes** on products, orders, and payments; deletions are recorded in the audit
  log (`spatie/laravel-activitylog`).
- **Queues**: notification/webhook listeners are queued (`notifications`, `webhooks`);
  run `php artisan queue:work` to process them (or `QUEUE_CONNECTION=sync` locally).
