---
name: Odoo Integration Pattern
description: >
  Rules for integrating with Odoo ERP inside a Laravel (Pop-App) project.
  Use this skill BEFORE writing any code that touches Odoo — including new
  sync features, report downloads, domain actions, job dispatching, or
  payload mapping. Stack: Laravel 13+, Livewire, Laravel Queues, Odoo 19.x
  (JSON-2 External API + Web Session for reports).
---

# Odoo Integration Skill — Pop-App (Laravel)

> **Non-Negotiable Principles**
> 1. Odoo is the canonical data source — all primary business records live in Odoo.
> 2. Pop-App is middleware, not a replacement for Odoo.
> 3. All PUSH operations to Odoo **must** go through a queued Laravel Job — never directly from a Controller or Livewire component.
> 4. Every payload pushed to Odoo must carry a `pop_app_ref` for idempotency.
> 5. Reference data (journals, customers, pricelists) must be cached — never fetched in real-time during UI rendering.
> 6. Odoo quota is a hard constraint: **100,000 requests/month**. Every call must be justified.
> 7. The source of references you can look `.agents/skills/odoo-integration/references` for more information
> 8. instead of opening odoo documentation online, you can see `.agents/skills/odoo-integration/references`, for example if I reference Odoo documentation via online such as `https://www.odoo.com/documentation/19.0/applications/finance/accounting.html` then you only need to see `.agents/skills/odoo-integration/references/odoo documentation 19.0 content/applications/finance/accounting.rst`, instead of online will cause latency, you only need to see this documentation directly.
> 9. Make sure you dont waste time to read everytime, if you was understand then you just specifically refer to the file documentation. REMEMBER: Dont waste time to read again and again.

---

## Navigation

| Section | Topic |
|---|---|
| **1** | Architecture Overview & Mental Model |
| **2** | Directory Structure |
| **3** | Transport Layer — `OdooClient` & `OdooGateway` |
| **4** | Domain Integration Layer |
| **5** | Job System (Async Push & Pull) |
| **6** | Report Engine (Synchronous PDF Download) |
| **7** | Sync State Machine |
| **8** | Events & Broadcasting |
| **9** | Idempotency Rules |
| **10** | Cache Strategy |
| **11** | Naming Conventions |
| **12** | Anti-Patterns (What NOT to Do) |
| **13** | Circuit Breaker |

---

## 1. Architecture Overview & Mental Model

### Three Integration Modes

| Mode | Mechanism | When |
|---|---|---|
| **PULL** | JSON-2 API via Queue Job | Sync reference data (products, customers, pricelists, journals) |
| **PUSH** | JSON-2 API via Queue Job | Create/update records in Odoo (SO, Invoice, Payment, etc.) |
| **REPORT** | Web Session + HTTP stream | Download PDF reports directly to browser |

### Request Flow

```
[ Livewire Component / Controller ]
           │
           │  dispatches
           ▼
[ Laravel Queue Job ]          ← all PUSH/PULL go through here
           │
           │  calls
           ▼
[ Domain Integration Layer ]   ← business logic & payload building
           │
           │  delegates to
           ▼
[ OdooGateway ]                ← stable entry point, no business logic
           │
           │  uses
           ▼
[ OdooClient ]                 ← pure HTTP transport (JSON-2 API)
           │
           ▼
[ Odoo API → /json/2/<model>/<method> ]
```

### Laravel ↔ Odoo Concept Mapping

> This project was originally designed with Elixir/Phoenix/Oban in mind.
> All concepts have been translated to their Laravel equivalents below.

| Elixir / Oban Concept | Laravel Equivalent |
|---|---|
| `Oban Worker` | `Illuminate\Queue\Jobs` / Laravel Queue Job (`dispatch()`) |
| `GenServer` (session state) | `OdooSessionManager` (singleton service + Cache) |
| `ETS cache` | `Cache::remember()` (Redis or database driver) |
| `Phoenix PubSub` | Laravel Broadcasting + `event()` + Echo (Livewire) |
| `LiveView handle_event` | Livewire `action()` method |
| `Ash state machine` | Custom state machine or `laravel-state-machine` package |
| `ash_paper_trail` | Custom audit log or `OwenIt\Auditing` |
| `ash_events` | Laravel `Event` + `Listener` |
| `Oban priority queue` | Laravel Queue `onQueue('critical')` / `onQueue('default')` |
| `mix deps` | `composer.json` |
| `Application.get_env` | `config()` / `.env` |
| `Repo.insert` | Eloquent `Model::create()` |
| `handle_info/cast` | Laravel `event()` + Listener |

---

## 2. Directory Structure

### 2.1 Sync Engine (Async — PULL & PUSH via Laravel Queue)

```
app/
└── Engine/
    └── Odoo/
        │   SyncEvents.php                       # # Event broadcasting helper (entry poin
        ├── Client/                              # Transport layer — pure HTTP, zero business logic
        │   ├── OdooClient.php                   # JSON-2 API HTTP adapter (retry, timeout, logging)
        │   └── OdooGateway.php                  # Stable façade / delegator (entry point for all callers)
        │
        ├── Contracts/
        │   ├── OdooIntegrationInterface.php     # Contract: push() / pull() methods
        │   ├── SyncJobInterface.php             # Contract: every sync job must implement this
        │   └── SyncActionInterface.php          # Contract: every action class must implement this
        │
        ├── Domains/                             # Business domains — NOT Odoo model names
        │   │
        │   ├── Sales/
        │   │   ├── Customers/
        │   │   │   ├── CustomerIntegration.php  # Orchestrates push/pull for customers
        │   │   │   ├── Actions/
        │   │   │   │   ├── PushCustomer.php
        │   │   │   │   └── UpdateCustomer.php
        │   │   │   └── Mappers/
        │   │   │       └── CustomerMapper.php   # Maps local model → Odoo payload
        │   │   │
        │   │   └── Orders/
        │   │       ├── OrderIntegration.php
        │   │       └── Actions/
        │   │           ├── PushDraftOrder.php
        │   │           ├── ConfirmOrder.php
        │   │           └── CancelOrder.php
        │   │
        │   ├── Finance/
        │   │   └── Invoices/
        │   │       ├── InvoiceIntegration.php
        │   │       └── Actions/
        │   │           ├── SyncInvoice.php
        │   │           └── PushInvoice.php
        │   │
        │   ├── SupplyChain/
        │   │   ├── Products/
        │   │   │   ├── ProductIntegration.php
        │   │   │   └── Actions/
        │   │   │       └── SyncProduct.php
        │   │   │
        │   │   └── Inventory/
        │   │       └── InventoryIntegration.php
        │   │
        │   ├── Essentials/
        │   │   └── BankAccounts/
        │   │       ├── BankAccountIntegration.php
        │   │       ├── Actions/
        │   │       │   ├── SyncBankAccount.php
        │   │       │   ├── ArchiveBankAccount.php
        │   │       │   └── FindExistingBank.php
        │   │       └── Mappers/
        │   │           └── BankAccountMapper.php
        │   │
        │   └── Shared/
        │       ├── ResolveOdooId.php            # Idempotency helper: find existing record by pop_app_ref
        │       └── OdooPayload.php              # Payload builder utility
        │
        ├── Jobs/                                # Async execution via Laravel Queue
        │   ├── Sales/
        │   │   ├── SyncCustomersJob.php
        │   │   ├── PushCustomerJob.php
        │   │   └── SyncOrdersJob.php
        │   │
        │   ├── Finance/
        │   │   └── SyncInvoicesJob.php
        │   │
        │   ├── SupplyChain/
        │   │   └── SyncProductsJob.php
        │   │
        │   └── Essentials/
        │       └── SyncBankAccountsJob.php
        │
        ├── Events/                              # Laravel Events for sync lifecycle
        │   ├── OdooSyncRequested.php
        │   ├── OdooSyncStarted.php
        │   ├── OdooSyncProgress.php
        │   ├── OdooSyncCompleted.php
        │   └── OdooSyncFailed.php
        │
        ├── Listeners/
        │   └── DispatchOdooSyncJob.php          # Registry: resolves Domain → correct Job class
        │
        ├── Trackers/
        │   └── SyncTracker.php                  # Persists sync lifecycle state (DB + Cache)
        │
        ├── Models/
        │   └── OdooSyncTask.php                 # Eloquent model for sync tracking table
        │
        └── Support/
            ├── SyncContext.php                  # Value object: domain, intent, metadata
            ├── SyncResult.php                   # Result wrapper (success/failure + payload)
            └── SyncDomainMap.php                # Domain string → Job class registry
```

### 2.2 Report Engine (Synchronous — Web Session + HTTP Stream)

```
app/
└── Engine/
    └── Odoo/
        ├── Session/
        │   └── OdooSessionManager.php          # Manages session_id + CSRF token + cache + auto-refresh
        │                                        # Implemented as a singleton service bound in AppServiceProvider
        │
        ├── Reports/
        │   ├── OdooReportService.php            # High-level API: download() / stream()
        │   ├── OdooReportClient.php             # Low-level HTTP to /report/download endpoint
        │   │
        │   ├── Domains/
        │   │   ├── Sales/
        │   │   │   ├── DownloadQuotation.php
        │   │   │   ├── DownloadOrder.php
        │   │   │   └── DownloadInvoice.php
        │   │   │
        │   │   └── Finance/
        │   │       └── DownloadInvoiceWithPayments.php
        │   │
        │   └── Support/
        │       ├── ReportNameMap.php            # type string → Odoo report identifier
        │       └── StreamResponse.php           # Wraps streamDownload() for browser delivery
        │
        └── Http/
            └── Controllers/
                └── OdooReportController.php    # Direct download endpoint — NOT queued
```

---

## 3. Transport Layer

### 3.1 `OdooClient.php`

The lowest-level HTTP adapter. Responsible for:
- Making `POST` requests to `/json/2/<model>/<method>` using **Laravel HTTP Client** (`Http::post()`)
- Handling retries with exponential backoff (use `Http::retry()`)
- Logging every request/response for quota tracking
- Throwing typed exceptions on failure (never silently swallow errors)

**Rules:**
- No business logic here. Zero domain knowledge.
- Never call this directly from a Job or Integration class — always go through `OdooGateway`.

```php
// Example shape
class OdooClient
{
    public function call(string $model, string $method, array $args = [], array $kwargs = []): array
    {
        return Http::retry(3, 500)
            ->post("{$this->baseUrl}/json/2/{$model}/{$method}", [
                'args'   => $args,
                'kwargs' => $kwargs,
            ])
            ->throw()
            ->json();
    }
}
```

### 3.2 `OdooGateway.php`

The stable public entry point for all Odoo communication. Domain classes call this — never `OdooClient` directly.

**Responsibilities:**
- Delegates to `OdooClient`
- Provides semantic methods: `search()`, `read()`, `create()`, `write()`, `execute()`, `callMethod()`
- Injects credentials and database name from config
- Centralizes request quota logging

```php
// Example shape
class OdooGateway
{
    public function search(string $model, array $domain, array $options = []): array { ... }
    public function create(string $model, array $values): int { ... }
    public function write(string $model, array $ids, array $values): bool { ... }
    public function execute(string $model, string $method, array $args = []): mixed { ... }
}
```

---

## 4. Domain Integration Layer

### 4.1 Domain Naming — Business First, Not Odoo Model Names

Odoo model names are implementation details. They belong **only** in:
- `OdooClient` / `OdooGateway` (the transport layer)
- Mapper classes

They must **never** appear in Domain class names, Job names, or directory names.

| ❌ Wrong (Odoo model-based) | ✅ Correct (business domain) |
|---|---|
| `ResPartnerService` | `CustomerIntegration` |
| `SaleOrderSync` | `OrderIntegration` |
| `SyncResPartnerJob` | `SyncCustomersJob` |
| `AccountMoveJob` | `SyncInvoicesJob` |

### 4.2 Integration vs Action vs Mapper

Each domain follows a three-layer pattern:

| Class Type | Responsibility |
|---|---|
| `*Integration` | Orchestrates Odoo interaction for a domain. Entry point for Jobs. |
| `Actions/*` | Single-purpose operation: `PushCustomer`, `ConfirmOrder`, etc. |
| `Mappers/*` | Translates local Eloquent model → Odoo API payload. |

**Example — Order domain:**

```php
// OrderIntegration.php — orchestrates
class OrderIntegration implements OdooIntegrationInterface
{
    public function pushDraft(SaleOrder $order): SyncResult
    {
        return app(PushDraftOrder::class)->execute($order);
    }

    public function confirm(SaleOrder $order): SyncResult
    {
        return app(ConfirmOrder::class)->execute($order);
    }
}

// PushDraftOrder.php — single action
class PushDraftOrder implements SyncActionInterface
{
    public function execute(SaleOrder $order): SyncResult
    {
        $existing = app(ResolveOdooId::class)->find('sale.order', $order->pop_app_ref);

        $payload = app(OrderMapper::class)->toOdoo($order);

        $odooId = $existing
            ? $this->gateway->write('sale.order', [$existing], $payload)
            : $this->gateway->create('sale.order', $payload);

        return SyncResult::ok($odooId);
    }
}
```

---

## 5. Job System (Async Queue)

### 5.1 Rules

- **All PUSH and PULL operations must be dispatched as queued Jobs.**
- Never call `OdooGateway` or any Integration class directly from a Controller or Livewire component.
- Jobs update the record's `sync_state` at each lifecycle stage.

### 5.2 Queue Priority

Use named queues to control priority:

| Queue Name | Operations |
|---|---|
| `odoo-critical` | Confirm SO, Post Invoice, Validate Payment |
| `odoo-default` | Create/update draft SO, create draft Invoice |
| `odoo-low` | Sync reference data (products, pricelists), pull reports |

Dispatch example:

```php
// From Livewire component — always dispatch, never call directly
PushDraftOrderJob::dispatch($order)
    ->onQueue('odoo-default');

ConfirmOrderJob::dispatch($order)
    ->onQueue('odoo-critical');
```

### 5.3 Retry Policy

```php
class PushDraftOrderJob implements ShouldQueue
{
    public int $tries = 20;
    public int $maxExceptions = 5;

    public function backoff(): array
    {
        // Exponential: 2, 4, 8, 16 ... seconds, capped at 3600
        return array_map(fn($i) => min(2 ** $i, 3600), range(1, 20));
    }

    public function failed(Throwable $e): void
    {
        $this->order->update(['sync_state' => 'failed']);
        event(new OdooSyncFailed($this->order, $e->getMessage()));
    }
}
```

After max retries → `sync_state = 'failed'` → User sees **[Retry Manually]** or **[Duplicate & Clean]** options in the UI.

### 5.4 Optimistic UI State

When a Job is dispatched, immediately set `sync_state = 'syncing'` **before** the Job runs:

```php
// Livewire component
public function confirmOrder()
{
    $this->order->update(['sync_state' => 'syncing']); // optimistic
    ConfirmOrderJob::dispatch($this->order)->onQueue('odoo-critical');
}
```

---

## 6. Report Engine (Synchronous)

Reports are **not queued**. They are downloaded directly to the browser via an HTTP stream.

### 6.1 Session Management

`OdooSessionManager` maintains a valid Odoo web session (session_id + CSRF token). Register it as a **singleton** in `AppServiceProvider`:

```php
$this->app->singleton(OdooSessionManager::class, function () {
    return new OdooSessionManager();
});
```

It must:
- Authenticate with Odoo's `/web/session/authenticate` on first use
- Cache `session_id` and `csrf_token` using `Cache::remember()` (TTL: e.g., 30 minutes)
- Auto-refresh when session expires (detect 401 or session error response)

### 6.2 Downloading a Report

```php
// OdooReportController.php
public function download(Request $request, string $type, int $odooId)
{
    $reportName = ReportNameMap::resolve($type); // e.g., 'sale.report_saleorder'

    return $this->reportService->stream($reportName, [$odooId]);
}
```

```php
// OdooReportService.php
public function stream(string $reportName, array $ids): StreamedResponse
{
    $session = app(OdooSessionManager::class)->getSession();

    return $this->reportClient->downloadPdf($reportName, $ids, $session);
}
```

---

## 7. Sync State Machine

Every record that syncs with Odoo must have a `sync_state` column. Valid states:

| State | Badge Color | Meaning |
|---|---|---|
| `local_draft` | Grey | Created locally, never pushed to Odoo |
| `syncing` | Blue (spinner) | A queue Job is currently running |
| `pending_retry` | Yellow | Previous attempt failed, job is queued for retry |
| `dirty` | Orange | Local record has changes not yet reflected in Odoo |
| `ok` | Green | Odoo confirmed, data is valid and in sync |
| `failed` | Red | Max retries exhausted — manual intervention required |

**Rule:** Users cannot proceed to the next workflow step until `sync_state = 'ok'` (except for locally-scoped actions that do not depend on Odoo data).

### State Transition Flow

```
local_draft
    │  Job dispatched
    ▼
syncing
    │  Job succeeds            │  Job fails (retry available)
    ▼                          ▼
  ok                     pending_retry
    │  Local edit made              │  Max retries hit
    ▼                               ▼
dirty                           failed
    │  New job dispatched
    ▼
syncing → ok
```

---

## 8. Events & Broadcasting

### 8.1 Sync Lifecycle Events

All events live in `App\Engine\Odoo\Events\`:

```php
OdooSyncRequested::class   // A job has been dispatched
OdooSyncStarted::class     // Job is now processing
OdooSyncProgress::class    // For batch operations — broadcasts percentage
OdooSyncCompleted::class   // Job succeeded — triggers Livewire refresh
OdooSyncFailed::class      // Job failed — triggers error UI
```

### 8.2 Domain Events (Business-Level)

These are separate from sync lifecycle events. They represent business facts:

```php
SaleOrderConfirmed::class
InvoicePosted::class
PaymentRegistered::class
PaymentValidated::class
SurveyAssigned::class
RABSubmitted::class
RABApproved::class
ProjectTaskAssigned::class
ProjectHandoverSubmitted::class
```

Each domain event is a trigger for: Livewire UI refresh (via `$refresh` or Echo), follow-up Job dispatch, and Audit log write.

### 8.3 Livewire Integration

Use Laravel Echo to listen for broadcast events in Livewire components. When `OdooSyncCompleted` is broadcast on the record's channel, the component auto-refreshes — no manual polling required.

```php
// In Livewire component
protected $listeners = [
    'echo:sync.order.{order.id},OdooSyncCompleted' => '$refresh',
    'echo:sync.order.{order.id},OdooSyncFailed'    => 'handleSyncFailed',
];
```

---

## 9. Idempotency Rules

Every push payload **must** include `pop_app_ref` — a unique, stable string tied to the local record (e.g., UUID or prefixed ID).

### The Idempotency Checker — `ResolveOdooId`

Before every `create` in Odoo:

```php
class ResolveOdooId
{
    public function find(string $model, string $popAppRef): ?int
    {
        $result = $this->gateway->search($model, [
            ['pop_app_ref', '=', $popAppRef]
        ]);

        return $result[0] ?? null;
    }
}
```

**Logic:**
- If `pop_app_ref` already exists in Odoo → call `write()` (update)
- If not found → call `create()`
- Never skip this check — failed retries can cause duplicate records without it

---

## 10. Cache Strategy

### Rules

| Data Type | Method | TTL |
|---|---|---|
| Journals, Accounts | `Cache::remember()` | 1 hour |
| Customers, Pricelists | `Cache::remember()` | 1 hour |
| Products, Variants | `Cache::remember()` | 1 hour |
| Odoo Web Session | `Cache::remember()` | 30 minutes |
| Stock levels | `Cache::remember()` | 5 minutes (minimum) |

**Never** perform a live Odoo API call to populate a `<select>` dropdown or form option during an HTTP request cycle. All reference data must come from cache or a local reference table populated by a background Pull Job.

### Pull Job Pattern

```php
// SyncCustomersJob — runs on schedule or on-demand
class SyncCustomersJob implements ShouldQueue
{
    public function handle(CustomerIntegration $integration): void
    {
        $customers = $integration->pullAll();

        Cache::put('odoo.customers', $customers, now()->addHour());

        // Optionally persist to local reference table
        foreach ($customers as $customer) {
            OdooCustomer::updateOrCreate(['odoo_id' => $customer['id']], $customer);
        }
    }
}
```

---

## 11. Naming Conventions

### Summary Table

| Layer | Naming Pattern | Example |
|---|---|---|
| Integration | `{Domain}Integration` | `CustomerIntegration` |
| Action | `{Verb}{Domain}` | `PushCustomer`, `ConfirmOrder` |
| Mapper | `{Domain}Mapper` | `CustomerMapper`, `OrderMapper` |
| Pull Job | `Sync{Domain}sJob` | `SyncCustomersJob` |
| Push Job | `Push{Domain}Job` | `PushCustomerJob` |
| Event | `Odoo{State}` or business fact | `OdooSyncCompleted`, `SaleOrderConfirmed` |
| Listener | `Dispatch{Domain}SyncJob` | `DispatchOrderSyncJob` |

### Naming Rules — Enforced

1. **Business language only** — never use Odoo model names in class names outside Client/Mapper.
2. **Verb + Domain** pattern for Actions — `PushDraftOrder`, not `CreateSaleOrder`.
3. **Plural for Jobs** that process collections — `SyncCustomersJob`, not `SyncCustomerJob`.
4. **Singular for Jobs** that process a single record — `PushCustomerJob`.

---

## 12. Anti-Patterns — What NOT To Do

| ❌ Anti-Pattern | ✅ Correct Approach |
|---|---|
| Calling `OdooGateway` directly from a Livewire action | Dispatch a queued Job; set `sync_state = 'syncing'` optimistically |
| Calling `OdooClient` directly from a Domain class | Always go through `OdooGateway` |
| Fetching reference data (products, customers) inside a Controller | Use `Cache::remember()` populated by a background Pull Job |
| Creating a record in Odoo without checking `pop_app_ref` first | Always call `ResolveOdooId::find()` before any `create` call |
| Naming a class `ResPartnerService` or `SaleOrderSync` | Use business-domain names: `CustomerIntegration`, `OrderIntegration` |
| Leaking Odoo model names (`sale.order`, `res.partner`) into Domain directories | Odoo model names belong only in Client, Gateway, and Mapper layers |
| Queuing report downloads | Reports must stream synchronously via `OdooReportController` |
| Storing primary business data only in Pop-App without syncing to Odoo | Odoo is the canonical source — every key entity must have an Odoo representation |

---

## 13. Circuit Breaker

### Circuit Breaker Strategy (Odoo Integration)

To ensure resilience and stability when communicating with Odoo (both JSON-2 API and web session endpoints), this system implements a **Circuit Breaker pattern** at the integration layer.

This prevents cascading failures when Odoo is slow, rate-limited, or temporarily unavailable.

---

### Why Circuit Breaker is Required

Odoo is an external system with characteristics:
- Network latency and intermittent failures
- Rate limiting (`HTTP 429`)
- Occasional downtime (`5xx`)
- Session invalidation (for report engine)

Without protection:
- Queue workers can flood Odoo
- Retries amplify failure (thundering herd)
- UI becomes inconsistent due to repeated failures

Circuit Breaker introduces **controlled degradation**.

---

### Core Concept

Each Odoo interaction domain (`customers`, `products`, `bank_accounts`, etc.) has its own circuit state:

States:
- **CLOSED** → Normal operation (requests allowed)
- **OPEN** → Requests blocked (fail fast)
- **HALF-OPEN** → Trial requests to check recovery

---

### State Transitions
CLOSED
│
├── (failure threshold exceeded)
▼
OPEN
│
├── (cooldown period elapsed)
▼
HALF-OPEN
│
├── (success) → CLOSED
└── (failure) → OPEN

---

### Failure Signals

The following conditions increment failure count:

- HTTP `429` (rate limit)
- HTTP `5xx` (server error)
- Network errors / timeouts
- Invalid session (report engine)

The following are **NOT failures**:
- Business errors (e.g. validation failed)
- `404` (record not found)
- Idempotent no-op results

---

### Integration Points

#### 1. Sync Engine (Async Jobs)

Circuit breaker is enforced inside:
- `OdooClient.php`
- `OdooGateway.php`

Before making a request:
```php
if (CircuitBreaker::isOpen($domain)) {
    return SyncResult::fail('circuit_open');
}
After response:

CircuitBreaker::recordSuccess($domain);
// or
CircuitBreaker::recordFailure($domain);

If circuit is OPEN:

Job should release with delay (backoff)
Avoid immediate retry
2. Report Engine (Session-based)

Circuit breaker is applied to:

OdooReportClient.php
OdooSessionManager.php

Special handling:

Session errors → treated as failure
Auto-refresh session does NOT bypass breaker

If OPEN:

Report request fails fast
Controller returns appropriate error response
Storage Strategy

Circuit state is stored in:

Cache (Redis recommended)
Key: odoo:circuit:{domain}
Fields:
failures
last_failure_at
state

Optional fallback:

Database (for audit/history)
---

### Failure Signals

The following conditions increment failure count:

- HTTP `429` (rate limit)
- HTTP `5xx` (server error)
- Network errors / timeouts
- Invalid session (report engine)

The following are **NOT failures**:
- Business errors (e.g. validation failed)
- `404` (record not found)
- Idempotent no-op results

---

### Integration Points

#### 1. Sync Engine (Async Jobs)

Circuit breaker is enforced inside:
- `OdooClient.php`
- `OdooGateway.php`

Before making a request:
```php
if (CircuitBreaker::isOpen($domain)) {
    return SyncResult::fail('circuit_open');
}

Configuration

Example thresholds:

return [
    'failure_threshold' => 5,
    'cooldown_seconds' => 60,
    'half_open_max_requests' => 2,
];

Meaning:

5 consecutive failures → OPEN
Wait 60 seconds → HALF-OPEN
Allow 2 test requests
Domain Isolation

Each domain has its own circuit:

customers
products
bank_accounts
reports

Failure in one domain does NOT affect others.

Interaction with Queue (Laravel)

When circuit is OPEN:

public function handle()
{
    if (CircuitBreaker::isOpen($this->domain)) {
        return $this->release(60); // backoff
    }

    // proceed normally
}

This ensures:

Workers do not overload Odoo
System stabilizes automatically
Observability

Recommended logging:

Circuit opened
Circuit closed
Half-open transitions
Failure reasons

Example:

[Odoo Circuit] OPEN domain=customers failures=5
[Odoo Circuit] HALF_OPEN domain=customers
[Odoo Circuit] CLOSED domain=customers
Summary

Circuit Breaker provides:

Fail-fast mechanism
Protection against cascading failures
Better queue stability
Controlled retry behavior
Isolation per integration domain

This is a mandatory layer for any production-grade Odoo integration.
---

## Quick Reference — Integration Checklist

Before implementing any new Odoo-touching feature:

- [ ] Is the primary data stored (or to be stored) in Odoo?
- [ ] Does the push payload include `pop_app_ref`?
- [ ] Does the action check for an existing record before `create`?
- [ ] Is the push going through a queued Job (not directly from Livewire/Controller)?
- [ ] Is the UI setting `sync_state = 'syncing'` before dispatching?
- [ ] Is reference data being cached with appropriate TTL?
- [ ] Is the Job using the correct priority queue (`odoo-critical` / `odoo-default` / `odoo-low`)?
- [ ] Does the Job implement a `failed()` method that sets `sync_state = 'failed'` and fires an event?
- [ ] Are class names using business-domain language, not Odoo model names?
- [ ] Is this feature in Scope for the current development phase?
