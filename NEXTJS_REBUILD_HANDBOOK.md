# Next.js Rebuild Handbook
## Rebuilding the Laravel Reference as a Professional Rcentz Product

> Purpose: preserve useful product behavior from the PHP/Laravel reference while redesigning the architecture, data model, security boundaries, forms, theming, and developer experience for a reusable Next.js system.

---

## 1. The rule for this rebuild

Do **not** translate PHP files into TypeScript files one-for-one.

The Laravel application is now a **behavioral reference**. We study it for:

- what users can do
- what administrators can do
- what data exists
- what workflows exist
- what pages and forms exist
- what background jobs exist
- what integrations exist
- where the original architecture became inconsistent

Then the Next.js version is designed around those truths.

The rebuild sequence is:

```text
REFERENCE BEHAVIOR
      ↓
DOMAIN MODEL
      ↓
PERMISSIONS / BUSINESS RULES
      ↓
PRISMA DATA CONTRACT
      ↓
SERVER SERVICES / ACTIONS
      ↓
FORM CONTRACTS
      ↓
CLIENT + ADMIN UI
      ↓
ASYNC JOBS / INTEGRATIONS
      ↓
AUDIT + DEPLOYMENT
```

That order prevents the mistake seen in the reference project: individual pages inventing their own colors, routes, assumptions, and data behavior.

---

# PART I — PRODUCT INVENTORY

## 2. What the reference application actually contains

The Laravel project is more than a vehicle website. It combines several domains.

### Public/customer acquisition

- homepage
- about
- contact
- terms/privacy
- vehicle inventory browsing
- vehicle detail/purchase flow
- account registration/login
- Google OAuth
- password reset
- email verification

### Customer account

- dashboard
- profile
- KYC
- wallet
- wallet deposits
- wallet withdrawals
- crypto payment details
- wallet transactions
- notifications
- support/contact
- purchase/order history

### Investments

- categories
- investment plans
- plan detail
- purchase/invest
- sell/redeem
- holdings
- transactions
- portfolio analytics
- watchlist
- automatic investment plans
- automatic NAV updates

### Stocks/trading

- stock catalogue
- quote/detail
- gainers
- losers
- most active
- search/filter
- buy
- sell
- stock holdings
- stock transactions
- stock watchlist
- quote/history/news synchronization

### Administration

- overview
- customer management
- administrator/profile management
- KYC review
- vehicle management
- purchase management
- wallet transaction review
- payment-method management
- investment plan management
- investment holdings
- investment transactions
- NAV update management
- stocks
- stock holdings
- stock transactions
- email composition/templates
- settings
- cron/operations documentation
- user impersonation

### Background/integration behavior

- stock quote refresh
- stock history fetch
- stock news processing
- automatic NAV processing
- cleanup jobs
- currency/market APIs
- mail
- broadcasting
- scheduled tasks

This inventory becomes the **feature map** for the Next.js product.

---

# PART II — TARGET ARCHITECTURE

## 3. Recommended stack

For the Rcentz rebuild:

```text
Framework        Next.js App Router + TypeScript
Database         PostgreSQL preferred for new Rcentz build
ORM              Prisma
Authentication   Better Auth or another mature session-based auth layer
Validation       Zod
Forms            React Hook Form where client interaction is useful
Styling          Tailwind + semantic CSS variables
UI primitives    shared reusable component layer
Charts           Recharts or equivalent
Icons            Lucide
Storage          S3-compatible/object storage for production files
Email            provider adapter behind a mail service
Jobs             durable job/queue strategy for market sync and finance jobs
Deployment       chosen independently of business architecture
```

If importing the existing MySQL dataset is the immediate goal, Prisma can also target MySQL. For a fresh Rcentz product, PostgreSQL gives us a strong default relational foundation.

The important point is not MySQL versus PostgreSQL. The important point is that **Prisma becomes the single explicit data contract**.

---

## 4. Proposed project structure

A feature/domain-oriented structure is better than one giant `app/` tree full of business logic.

```text
app/
├── (public)/
│   ├── page.tsx
│   ├── about/page.tsx
│   ├── contact/page.tsx
│   ├── inventory/page.tsx
│   └── inventory/[carId]/page.tsx
│
├── (auth)/
│   ├── login/page.tsx
│   ├── register/page.tsx
│   ├── forgot-password/page.tsx
│   └── reset-password/[token]/page.tsx
│
├── adminlogin/
│   └── login/page.tsx
│
├── (customer)/
│   ├── dashboard/page.tsx
│   ├── wallet/
│   ├── investments/
│   ├── stocks/
│   ├── portfolio/
│   ├── watchlist/
│   ├── orders/
│   ├── kyc/
│   ├── notifications/
│   └── profile/
│
├── admin/
│   ├── layout.tsx
│   ├── page.tsx
│   ├── users/
│   ├── kyc/
│   ├── vehicles/
│   ├── purchases/
│   ├── wallets/
│   ├── investments/
│   ├── stocks/
│   ├── payments/
│   ├── email/
│   └── settings/
│
└── api/
    ├── webhooks/
    ├── auth/
    └── jobs/

features/
├── auth/
├── users/
├── kyc/
├── wallets/
├── payments/
├── vehicles/
├── purchases/
├── investments/
├── stocks/
├── portfolio/
├── notifications/
├── support/
└── settings/

server/
├── db/
│   └── prisma.ts
├── auth/
├── permissions/
├── services/
├── jobs/
├── integrations/
├── money/
├── audit/
└── errors/

components/
├── ui/
├── forms/
├── layout/
├── data-display/
└── feedback/

prisma/
├── schema.prisma
├── migrations/
└── seed.ts
```

### Folder rule

`app/` defines **navigation and composition**.

`features/` defines **domain-facing application behavior/UI**.

`server/` owns **trusted business logic, database access, permissions, integrations, jobs, and financial rules**.

A page should not become a 600-line business engine.

---

## 5. Route groups are presentation boundaries, not security boundaries

Use route groups to organize layouts:

```text
(public)
(auth)
(customer)
```

But authorization must happen on the server.

Do not assume:

```text
"The page is inside /admin, therefore it is secure."
```

Every privileged read/mutation must confirm the authenticated user's role/permission.

---

# PART III — AUTHENTICATION AND ADMIN ROUTING

## 6. Preserve the intentional admin route

The rebuilt product should intentionally retain:

```text
Customer login: /login
Admin login:    /adminlogin/login
Admin home:     /admin
```

Both login surfaces may use the same underlying identity/session system, but their **entry contracts differ**.

### Customer login

```text
credentials
   ↓
authenticate
   ↓
active user?
   ↓
customer/admin session created
   ↓
role-based destination
```

### Admin login

```text
credentials
   ↓
authenticate
   ↓
ADMIN permission?
   ├── no → reject admin access
   └── yes → create/continue session → /admin
```

Do not merely authenticate and then visually hide admin links.

---

## 7. Roles and permissions

Start with explicit roles:

```ts
ADMIN
CUSTOMER
```

If the platform grows, move toward permissions rather than adding endless role flags:

```text
users.read
users.manage
kyc.review
wallet.adjust
investment.manage
stock.manage
settings.manage
email.send
```

A future support agent should not automatically receive every administrator power.

---

## 8. Session protection

Every customer route should require an authenticated user.

Every admin mutation should require:

1. authenticated session
2. active account
3. admin/permission check
4. validated input
5. audit event for sensitive changes

For very sensitive administrator actions—manual wallet adjustment, KYC approval, role elevation—consider step-up confirmation or MFA in the final product.

---

# PART IV — PRISMA DATA DESIGN

## 9. Do not copy the current tables blindly

The existing tables tell us what data the product needs, but the Next.js version should strengthen the model.

Primary domains:

```text
Identity
KYC
Wallet/Ledger
Payments
Vehicles/Purchases
Investments
Stocks/Trading
Notifications
Support
Settings
Audit
Jobs/Integrations
```

---

## 10. Identity models

Baseline:

```prisma
model User {
  id              String   @id @default(cuid())
  name            String
  email           String   @unique
  emailVerifiedAt DateTime?
  role            UserRole @default(CUSTOMER)
  countryCode     String?
  currency        String   @default("USD")
  profileImageUrl String?
  status          UserStatus @default(ACTIVE)
  createdAt       DateTime @default(now())
  updatedAt       DateTime @updatedAt

  wallet          Wallet?
  kyc             KycProfile?
  purchases       Purchase[]
  investmentHoldings InvestmentHolding[]
  stockHoldings   StockHolding[]
}

enum UserRole {
  CUSTOMER
  ADMIN
}

enum UserStatus {
  ACTIVE
  SUSPENDED
  CLOSED
}
```

The auth provider may create its own session/account tables. Do not duplicate those responsibilities manually unless required.

---

## 11. Money must use decimal/integer-safe storage

Never use JavaScript floating-point numbers as the authoritative representation of money.

Prefer Prisma `Decimal` for monetary/database amounts:

```prisma
balance Decimal @db.Decimal(20, 8)
```

or store smallest units as integers when appropriate.

Examples that must avoid floating-point mistakes:

- wallet balance
- transaction amount
- purchase amount
- stock quantity/value
- investment units
- NAV
- fees
- exchange rates

Display formatting belongs to UI helpers; accounting precision belongs to the data/service layer.

---

## 12. Wallet architecture: use a ledger mindset

The PHP project has wallet and wallet transactions. The Next.js version should make transaction history the source of financial truth.

Suggested entities:

```text
Wallet
LedgerEntry
WalletTransaction
PaymentInstruction
```

A wallet balance must not be casually changed with:

```ts
wallet.balance += amount
```

Instead:

```text
validate operation
      ↓
create immutable transaction
      ↓
create balanced ledger entries
      ↓
update cached balance atomically
      ↓
audit event
```

Sensitive money operations should run inside a database transaction.

---

## 13. Wallet transaction state machine

Use explicit statuses:

```text
PENDING
PROCESSING
COMPLETED
FAILED
REJECTED
CANCELLED
```

Example deposit:

```text
REQUESTED
   ↓
PAYMENT INSTRUCTION CREATED
   ↓
PROVIDER/ADMIN CONFIRMATION
   ↓
ATOMIC LEDGER POST
   ↓
COMPLETED
```

Never infer completion solely from a frontend redirect.

---

## 14. Investment model

Suggested core models:

```text
InvestmentCategory
InvestmentPlan
InvestmentHolding
InvestmentTransaction
InvestmentWatchlist
AutomaticInvestmentPlan
NavRecord
```

Important separation:

```text
Plan       = product definition
Holding    = what a user owns
Transaction= how the holding changed
NAV record = historical valuation event
```

Do not merge those into one giant “investment” record.

---

## 15. Investment transaction types

Use enums rather than free text:

```text
SUBSCRIPTION
REDEMPTION
DIVIDEND
FEE
ADJUSTMENT
```

For administrator adjustment, record:

- actor
- reason
- before value
- after value
- transaction reference
- timestamp

---

## 16. Stocks/trading model

Core models:

```text
Stock
StockQuote
StockPriceHistory
StockNews
StockHolding
StockTransaction
StockWatchlist
MarketApiRequestLog
```

Separate market data from user ownership.

A quote update should never mutate a user's transaction history.

---

## 17. Vehicle commerce models

The reference has cars and purchases. Improve the names to fit the business domain:

```text
Vehicle
VehicleImage
VehicleInventory
Purchase
PurchaseStatusHistory
PaymentInstruction
```

If one vehicle can only be sold once, enforce inventory/state constraints at the database/service level—not only by disabling a button.

---

## 18. KYC model

Suggested shape:

```text
KycProfile
KycDocument
KycReview
```

Statuses:

```text
NOT_SUBMITTED
PENDING
APPROVED
REJECTED
NEEDS_RESUBMISSION
```

Never overwrite the history of a review. Keep review events so the administrator decision can be explained later.

Documents should live in private object storage with signed/authorized access, not public URLs.

---

## 19. Settings model

The PHP reference stores general settings as key/value rows. That can remain useful, but divide settings into two categories.

### Runtime/business settings

Safe for admin UI:

- site name
- public contact information
- KYC enabled
- email verification enabled
- maintenance banner
- display branding

### Secrets/infrastructure

Never editable as ordinary database settings:

- database password
- OAuth secret
- payment secret
- market API secret
- cron secret
- mail password

Those belong in environment/secret management.

---

## 20. Audit log

Add a first-class audit model:

```text
AuditEvent
- id
- actorUserId
- action
- entityType
- entityId
- metadata JSON
- ipAddress
- userAgent
- createdAt
```

Use it for:

- KYC decisions
- role changes
- wallet adjustments
- transaction approvals/rejections
- administrator impersonation
- settings changes
- manual NAV updates
- account suspension

This is one of the biggest professional improvements over relying only on application logs.

---

# PART V — SERVER ARCHITECTURE

## 21. Server services own business rules

A route/page should not implement complex finance logic directly.

Example:

```text
features/wallet/actions/request-deposit.ts
                ↓
server/services/wallet/request-deposit.ts
                ↓
server/db/prisma.ts
```

The service should be reusable from:

- Server Action
- route handler
- background job
- admin operation
- test

---

## 22. Server Actions vs Route Handlers

Use **Server Actions** for first-party form mutations that originate inside the Next.js application.

Examples:

```text
update profile
add watchlist item
submit KYC metadata
create investment instruction
```

Use **Route Handlers** when an HTTP endpoint itself is the contract.

Examples:

```text
payment webhook
OAuth callback
external API endpoint
scheduled-job endpoint
```

The rule is not “Server Actions are backend and routes are old.” The rule is **choose the boundary that matches the caller**.

---

## 23. DTOs prevent accidental data leakage

Do not return raw Prisma user records to client components.

Instead:

```ts
export type UserProfileDTO = {
  id: string;
  name: string;
  email: string;
  countryCode: string | null;
  currency: string;
  profileImageUrl: string | null;
};
```

Private/internal fields stay on the server.

---

## 24. Error architecture

Create typed domain errors:

```text
AuthenticationError
AuthorizationError
ValidationError
InsufficientBalanceError
InvalidStateTransitionError
ProviderUnavailableError
NotFoundError
```

UI receives a safe message. Logs receive deeper technical context.

Do not send third-party stack traces to users.

---

## 25. Idempotency

Financial and external operations must defend against duplicate submissions.

Examples:

- payment webhook arrives twice
- user double-clicks Invest
- browser retries a request
- cron job reruns

Use unique provider references/idempotency keys and transactional checks.

```text
same external reference
        ↓
record already processed?
   yes → return existing result
   no  → process atomically
```

---

# PART VI — UI AND THEME SYSTEM

## 26. One visual system

The PHP project demonstrated why visual tokens matter. The Next.js product must never redefine a semantic color name to secretly mean another color.

Bad:

```text
blue-600 = brand red
```

Good:

```text
brand / primary = Rcentz/product accent
blue             = actual blue
success          = success
warning          = warning
error            = error
muted            = neutral supporting content
```

---

## 27. CSS variable foundation

Use semantic variables:

```css
:root {
  --background: ...;
  --foreground: ...;
  --card: ...;
  --card-foreground: ...;
  --muted: ...;
  --muted-foreground: ...;
  --border: ...;
  --input: ...;
  --primary: ...;
  --primary-foreground: ...;
  --destructive: ...;
  --ring: ...;
}

.dark {
  /* same semantic names, dark values */
}
```

Every page consumes tokens. Pages do not invent theme logic.

---

## 28. Core UI primitives

Build/reuse these before feature pages:

```text
Button
Input
Textarea
Select
Checkbox
Radio
FormField
FormMessage
Card
Panel
Badge
Alert
Dialog
Sheet
DropdownMenu
Table
DataTable
Pagination
Tabs
EmptyState
Skeleton
StatCard
PageHeader
SectionHeader
```

The result is that fixing one form style fixes the whole product.

---

## 29. Forms should not own arbitrary white backgrounds

This exact inconsistency existed in the reference project.

A form is not automatically a white rectangle.

Use:

```text
Page background → background token
Form panel      → card token
Input           → input/background token
Border          → border token
Text            → foreground token
Help/error text → semantic token
```

In dark mode, those tokens change together.

---

# PART VII — FORM SYSTEM

## 30. Shared form contract

Every form follows the same sequence:

```text
schema
  ↓
default values
  ↓
render fields
  ↓
client validation (UX)
  ↓
server validation (authority)
  ↓
permission check
  ↓
service call
  ↓
transaction if required
  ↓
result / error mapping
  ↓
cache revalidation / navigation
```

Client validation never replaces server validation.

---

## 31. Authentication forms

### Customer login

Fields:

```text
email
password
remember me
```

Actions:

```text
forgot password
Google sign-in
register
```

### Administrator login

Fields:

```text
email
password
remember me
```

Differences:

- no normal registration CTA
- admin access requirement
- dedicated route `/adminlogin/login`
- clear “Administrator access” identity

Do not duplicate the authentication engine; duplicate only the entry experience/authorization rule.

---

## 32. Registration form

Fields from the reference:

```text
full name
email
password
password confirmation
country
preferred currency
```

Improvements:

- countries should come from a shared data module, not a giant inline JavaScript array inside one page
- supported currencies should come from product configuration
- password requirements displayed before submission
- email normalization server-side
- terms acceptance recorded if legally required

---

## 33. Profile form

Sections:

```text
Personal details
Avatar
Country/currency preferences
Security/password
Account actions
```

Do not make one giant form if the sections mutate independently.

---

## 34. KYC form

Multi-step flow:

```text
Identity
   ↓
Address/details
   ↓
Document upload
   ↓
Review
   ↓
Submit
```

Upload documents immediately to private storage using a controlled upload flow; store document metadata in Prisma.

---

## 35. Wallet deposit form

Potential fields:

```text
payment method
amount
currency
provider-specific details
```

Server validates:

- method active
- method supports deposits
- min/max amount
- currency support
- account status
- KYC rule if required

Then creates a **pending transaction**, not an immediate balance mutation.

---

## 36. Wallet withdrawal form

Server validates:

```text
KYC status
available balance
withdrawal limits
payment method
beneficiary details
fees
duplicate/idempotency key
```

Balance check and debit reservation should be atomic.

---

## 37. Investment purchase form

Fields:

```text
plan
amount
optional auto-invest settings
confirmation
```

Derived values are shown to the user but recomputed on the server:

```text
units
fees
NAV
estimated value
```

Never trust a hidden input containing a price/NAV from the browser.

---

## 38. Stock buy/sell form

Fields:

```text
stock
quantity or amount
order confirmation
```

The reference appears closer to simulated/internal trading than a connected broker. Before production, explicitly decide whether the Rcentz product is:

1. portfolio simulation/tracking,
2. internal virtual trading,
3. or a regulated real brokerage integration.

Those are materially different systems. Do not represent one as another.

---

## 39. Vehicle purchase form

Suggested steps:

```text
vehicle
   ↓
buyer details
   ↓
billing/payment selection
   ↓
review
   ↓
purchase record
   ↓
payment state
```

The vehicle price must be loaded server-side by ID during order creation.

---

## 40. Admin forms

Use the same `FormField` primitives as customer forms, but compose domain-specific forms:

```text
UserForm
KycReviewForm
VehicleForm
PaymentMethodForm
InvestmentPlanForm
NavUpdateForm
StockForm
SettingsForm
EmailComposeForm
```

This prevents each admin page from inventing another input/button theme.

---

# PART VIII — ADMIN SYSTEM

## 41. Admin shell

The admin area should have one consistent shell:

```text
Admin sidebar
Admin topbar
Page header
Content canvas
Global feedback/toasts
Theme control
Account/logout
```

Admin does not need a completely unrelated visual identity. It is another operating surface of the same product.

---

## 42. Admin dashboard

Dashboard widgets should come from server queries, not from page-local database calls scattered across components.

Example metrics:

```text
users
pending KYC
wallet deposits pending
withdrawals pending
active investment holdings
investment AUM/value
stock holdings/value
vehicle orders
recent admin activity
```

Create a dashboard service/DTO that returns the complete view model.

---

## 43. Data tables

One reusable data-table pattern should support:

```text
search
filters
sorting
pagination
row actions
bulk actions where safe
loading state
empty state
error state
responsive mobile representation
```

Do not build 15 unrelated tables.

---

## 44. Destructive admin actions

Actions such as:

```text
delete user
reject KYC
cancel transaction
remove vehicle
reset settings
```

require:

- confirmation dialog
- server permission check
- safe state-transition check
- audit log

For financial records, prefer reversal/status transition over deletion.

---

# PART IX — MARKET DATA AND JOBS

## 45. Provider adapters

Do not call Finnhub/Yahoo directly from random controllers/pages.

Create an interface:

```ts
interface MarketDataProvider {
  getQuote(symbol: string): Promise<QuoteResult>;
  getHistory(symbol: string, range: Range): Promise<HistoryResult>;
  getNews(symbol: string): Promise<NewsResult[]>;
}
```

Then implementations:

```text
FinnhubProvider
YahooProvider
MockMarketDataProvider
```

This lets us test without consuming live API quotas.

---

## 46. Scheduled jobs

Equivalent job families from the reference:

```text
refreshStockQuotes
fetchStockHistory
refreshStockNews
processAutomaticNavUpdates
cleanupOldData
```

Each job needs:

- idempotency
- structured logging
- retry policy
- provider rate-limit awareness
- last-success timestamp
- failure visibility

A cron request should enqueue work; it should not perform enormous synchronous loops until HTTP timeout.

---

## 47. Job observability

Add an admin operations page showing:

```text
job name
last started
last completed
status
records processed
failure message
next expected run
```

This is better than wondering whether cron is “working.”

---

# PART X — NOTIFICATIONS AND EMAIL

## 48. Notification model

Separate:

```text
NotificationEvent     business event
InAppNotification     user inbox representation
EmailDelivery         delivery attempt/result
```

A business operation emits an event; delivery channels react to it.

Examples:

```text
KYC_APPROVED
DEPOSIT_APPROVED
WITHDRAWAL_REJECTED
INVESTMENT_CREATED
PRICE_ALERT_TRIGGERED
ORDER_STATUS_CHANGED
```

---

## 49. Email templates

Admin-editable email templates can be useful, but template variables must be controlled.

Do not allow arbitrary server-side code inside database templates.

Use a known variable contract such as:

```text
{{user_name}}
{{transaction_reference}}
{{amount}}
```

Render with an allowlist.

---

# PART XI — SECURITY

## 50. Secrets

Never expose in client bundles or database-managed public settings:

```text
DATABASE_URL
AUTH_SECRET
OAuth client secret
payment secret
market API secret
mail password
cron/job secret
```

Only variables intentionally prefixed for client exposure may cross into the browser.

---

## 51. CSRF/session protection

Use the protections provided by the selected auth/framework architecture. Do not recreate hand-rolled CSRF tokens without need.

External webhooks instead require signature verification.

---

## 52. File uploads

KYC/profile/vehicle images require:

```text
MIME validation
size limits
extension normalization
random storage keys
private/public bucket decision
malware scanning where appropriate
authorization on download
```

Never trust the original filename as the storage path.

---

## 53. Rate limiting

Apply limits to:

```text
login
password reset
registration
contact/support
admin login
financial instruction creation
external API endpoints
```

---

## 54. Impersonation

The reference includes administrator impersonation. If retained:

- only authorized admins may start it
- visually show an unmistakable impersonation banner
- block especially dangerous operations while impersonating, or require explicit elevation
- log start/stop with actor and target

---

# PART XII — TESTING

## 55. Test pyramid

### Unit tests

Business rules:

```text
fees
balance calculations
investment units
NAV calculations
state transitions
permissions
```

### Integration tests

Prisma/database services:

```text
wallet posting
transaction idempotency
KYC review
purchase creation
investment subscription/redemption
```

### Route/action tests

```text
unauthenticated redirects
admin/customer authorization
validation failures
successful mutations
```

### End-to-end tests

Critical journeys:

```text
register → verify → dashboard
admin login → admin dashboard
submit KYC → admin approve
wallet deposit request → approve
investment purchase
vehicle purchase
```

---

# PART XIII — BUILD PHASES

## 56. Phase 0 — Product truth

Before coding:

- confirm what is real money versus demo/simulation
- confirm payment providers
- confirm stock-data source
- confirm KYC requirements
- confirm vehicle-commerce role
- confirm user/admin roles
- decide PostgreSQL vs MySQL

Deliverable:

```text
PRODUCT_CONTRACT.md
```

---

## 57. Phase 1 — Foundation

Build:

```text
Next.js shell
TypeScript strict config
Tailwind semantic theme
Prisma
DB connection
Auth/session
roles/permissions
logging/error layer
shared UI primitives
```

No advanced dashboard yet.

---

## 58. Phase 2 — Identity and customer shell

Build:

```text
register
/login
/adminlogin/login
password reset
verification
profile
customer layout
admin layout
```

This proves auth boundaries before money features.

---

## 59. Phase 3 — KYC

Build complete customer submission + admin review + audit history.

Do not begin financial approval workflows before identity/account-state rules are stable.

---

## 60. Phase 4 — Wallet/ledger

Build:

```text
wallet
ledger
transactions
deposit request
withdrawal request
admin approval/rejection
transaction history
```

This becomes the financial foundation used by investments and any internal trading.

---

## 61. Phase 5 — Vehicle commerce

Build:

```text
vehicle catalog
vehicle detail
inventory state
purchase flow
admin vehicle CRUD
admin purchases
```

---

## 62. Phase 6 — Investments

Build:

```text
categories
plans
holding engine
transactions
NAV history
watchlist
auto-invest
portfolio analytics
admin management
```

---

## 63. Phase 7 — Stocks

Build:

```text
market catalogue
quotes
history
news
watchlist
holding/trading rules
admin management
provider adapters
scheduled sync
```

---

## 64. Phase 8 — Communications/operations

Build:

```text
notifications
email templates
support
cron/job dashboard
settings
audit explorer
```

---

## 65. Phase 9 — Production hardening

Audit:

```text
authorization
financial transaction atomicity
idempotency
rate limiting
secrets
file access
logs
backups
queue durability
API quotas
mobile/responsive UI
accessibility
performance
```

---

# PART XIV — RCENTZ PROJECT INTEGRATION

## 66. How this belongs in Rcentz

The Next.js rebuild should be treated as a **new Rcentz product/project**, not as “the PHP website converted.”

Recommended Rcentz project metadata:

```text
Project type:       Financial / investment platform reference rebuild
Source reference:   Cleaned Laravel/PHP application
Implementation:     Next.js + TypeScript + Prisma
Status:             Architecture / rebuild
Primary objective:  Reproduce validated workflows with stronger architecture
```

Inside the Rcentz projects directory, store project-specific documentation such as:

```text
README.md
PRODUCT_CONTRACT.md
ARCHITECTURE.md
DATABASE.md
MILESTONES.md
SECURITY.md
REFERENCE_GAPS.md
```

Keep the borrowed PHP source as a **private reference artifact**, not as public Rcentz source code unless its owner has explicitly authorized publication.

---

## 67. Reusable Rcentz engines we should extract

This project can contribute reusable modules to later Rcentz products:

```text
AdminAuthEngine
RolePermissionEngine
ThemeEngine
FormEngine
DataTableEngine
AuditEngine
NotificationEngine
FileUploadEngine
SettingsEngine
TransactionStateEngine
JobMonitoringEngine
```

Financial domain logic stays domain-specific, but these infrastructure patterns can be reused.

---

# PART XV — WHAT WE LEARNED FROM THE PHP REFERENCE

## 68. PHP was not the core problem

The inconsistencies found in the reference were primarily architecture/governance problems:

- theme values duplicated across layers
- semantic colors misnamed
- controller actions referenced missing views
- stale installation documentation
- unrelated vendor branding remained in production UI
- server-specific PHP handler committed into source
- unsafe default seeded passwords
- cron documentation exposed configuration too casually
- old and new routes existed conceptually at the same time

Those problems can also be created in a Next.js codebase.

The lesson for the rebuild is therefore not:

```text
PHP = unprofessional
Next.js = professional
```

The useful lesson is:

```text
Uncontrolled duplication + weak contracts = drift
Explicit architecture + shared contracts + audits = robust system
```

Next.js and TypeScript give us tools that fit the Rcentz workflow well, but we still have to enforce the architecture.

---

## 69. The quality gate for the Next.js version

Before a milestone is called complete, require:

```text
[ ] Typecheck passes
[ ] Lint passes
[ ] Prisma schema/migrations valid
[ ] Route/navigation audit passes
[ ] No orphaned feature pages
[ ] No raw database calls from client components
[ ] No secret exposed client-side
[ ] Server validation exists for every mutation
[ ] Permission check exists for privileged mutations
[ ] Theme works light + dark
[ ] Mobile layout works
[ ] Forms use shared primitives
[ ] Loading/empty/error states exist
[ ] Critical state changes are audited
[ ] Financial mutations are atomic/idempotent where required
[ ] Production build passes
```

That is how we stop the Next.js rebuild from eventually developing the same inconsistencies we just cleaned from the reference.

---

# PART XVI — FIRST IMPLEMENTATION CHECKPOINT

## 70. The first code milestone when the rebuild begins

Do **not** start with stocks, wallet UI, or charts.

Start with:

```text
1. create Next.js project
2. establish semantic theme
3. create Prisma connection/schema foundation
4. add User/Role/auth models
5. create /login
6. create /adminlogin/login
7. create customer protected layout
8. create admin protected layout
9. implement permission helpers
10. create audit foundation
11. typecheck/build
12. commit
```

Only after this passes should the product domains begin.

That foundation will determine whether the project becomes a coherent system or another collection of individually functional pages.
