# Data Retention and Archiving

Stakeholder requirement: "أرشفة المعلومات – احتياطي المعلومات – كيف؟" (data
archiving — data backup — how?). This document covers **archiving**: keeping
historical business records available but out of active operational views.
For **backup** (recoverable copies after loss/corruption), see
[`docs/operations/BACKUP_AND_RESTORE.md`](../operations/BACKUP_AND_RESTORE.md).
The two are deliberately not mixed.

## Policy

Financial and legal records in this system are **never hard-deleted**. There
is no destroy endpoint for any of them (verified by
`tests/Feature/FinancialRecordImmutabilityTest.php`). "Archiving" here does
not mean moving rows to a separate table or adding an `archived_at` column
across the schema — it means active screens filter by status/date scope and
paginate, while the full history stays queryable through admin
list/detail/report screens indefinitely. No new "archive" columns were added:
every entity below already had the field it needed (a status enum, or nothing
extra at all) before this task.

## Per-entity policy

### Orders (`orders`)

- **Active retention**: all orders remain in the primary table forever.
- **Archive behavior**: `status` (`pending` → ... → `completed`/`cancelled`)
  is the existing archival signal — completed/cancelled orders are historical
  but stay in the same table and the same admin screens
  (`Admin/Orders/Index.jsx` already filters by status via query params;
  no new "hide old orders" feature was added because the admin already
  controls this with the existing status filter and date-scoped queries).
- **Deletion policy**: no destroy route exists (`api_admin.php`). Deleting an
  order would break every invoice/refund/return/ledger entry that references
  it.
- **Legal/audit importance**: primary evidence of a sale; required for
  invoicing, tax, and dispute resolution.

### Invoices (`invoices`)

- **Active retention**: permanent. `app/Models/Invoice.php` already documents
  itself as "Immutable accounting snapshot of an order... never updated after
  creation — a correction is a new order, not an edited invoice."
- **Archive behavior**: none needed; invoices are already write-once and
  small relative to orders (one row per order).
- **Deletion policy**: no destroy route exists.
- **Legal/audit importance**: the accounting record itself. Must not be
  hard-deleted or mutated under any circumstance — this is the one entity in
  the system where "correct the record" already means "create a new order",
  never "edit this row."

### Refunds (`refunds`) and Returns (`order_returns`, `return_items`)

- **Active retention**: permanent, across all statuses (`pending` →
  `processing`/`completed`/`failed`/`cancelled` for refunds; similar
  lifecycle for returns).
- **Archive behavior**: `status` is the existing signal for "this is
  historical" vs. "this needs action now." Admin/vendor return and refund
  list screens already filter by status.
- **Deletion policy**: no destroy route exists for either.
- **Legal/audit importance**: proof of money returned to a customer;
  required to reconcile against vendor settlements and for dispute handling.

### Vendor ledger entries (`vendor_ledger_entries`)

- **Active retention**: permanent, append-only.
- **Archive behavior**: none — a ledger is a chronological record by
  definition; there is nothing to "archive" separately from the record
  itself. Corrections are new entries (documented in code as "decision D14"),
  the same convention `AuditLog` follows.
- **Deletion policy**: no destroy route exists. `VendorService::delete()`
  additionally refuses to delete a *vendor* while any ledger entry exists
  for it (`hasProtectedHistory()`), which transitively protects the ledger
  from being orphaned or deleted-by-cascade.
- **Legal/audit importance**: the financial source of truth for what a
  vendor is owed/has been paid; must be reconstructable at any point in time.

### Vendor settlements (`vendor_settlements`)

- **Active retention**: permanent.
- **Archive behavior**: none needed — settlements are inherently point-in-time
  events (a payout that happened on a date), not a growing "current state"
  table that needs pruning.
- **Deletion policy**: no destroy route exists.
- **Legal/audit importance**: proof of payout to a vendor; required to
  reconcile against the ledger and for tax/accounting purposes.

### Audit logs (`audit_logs`)

- **Active retention**: permanent.
- **Archive behavior**: none — `app/Models/AuditLog.php` already documents
  itself as "One immutable audit entry... Never updated or deleted — a
  correction is a new entry."
- **Deletion policy**: no destroy or update route exists
  (`api.admin.audit-logs.index` is the only registered route on this
  resource).
- **Legal/audit importance**: the record of who did what, when — the whole
  point of an audit log is that it cannot be altered after the fact,
  including by an administrator.

### Vendor documents (`vendor_documents`) and product documents
(`product_documents`)

- **Active retention**: while `status` is `pending_review` or `verified`.
- **Archive behavior**: `status` transitioning to `expired`, `rejected`, or
  `suspended` *is* the archival flag — these are compliance documents whose
  lifecycle already models "no longer active but still on file" without a
  separate archive column. This is the existing pattern the policy above
  reuses rather than reinventing.
- **Deletion policy**: no destroy route exists for either.
- **Legal/audit importance**: regulatory/compliance evidence (commercial
  registration, business licenses, product safety data sheets); a rejected
  or expired document is still evidence that a review happened and what its
  outcome was.

### Users (customers) and Vendors — account-level, not financial records

These are the one place hard deletion *is* possible, and it is intentionally
narrower than the entities above:

- A vendor cannot be deleted while it has any order or ledger history
  (`VendorService::hasProtectedHistory()`, enforced in
  `VendorController::destroy()`); the admin is told to deactivate it instead.
- A customer cannot be deleted while they have order or review history
  (`UserController::destroy()`).
- Deleting an account with **no** history at all (e.g., a duplicate
  registration that never transacted) is allowed, because at that point there
  is no financial or legal record attached to protect.

Both guards were already in place before this task; they are documented here
because they are the enforcement mechanism for the "never physically delete
historical financial records" policy above, and are covered by
`tests/Feature/FinancialRecordImmutabilityTest.php`.

## What was deliberately not done

- **No new `archived_at`/`is_archived` column** was added to any table. Every
  entity above either already has a status enum that serves the same purpose,
  or has no concept of "inactive" at all (ledger, settlements, audit logs) —
  adding a redundant flag would be a second source of truth for something the
  status column or the table's append-only nature already expresses.
- **No batch "archive old orders" job** was added. Admin list screens already
  paginate server-side and filter by status/date, so there was no performance
  problem to solve by moving rows out of the primary table — see
  `Admin/Orders/Index.jsx` and the equivalent controller's `paginate()` calls.
  If admin screens are later reported as slow due to table growth, the next
  step is targeted indexes and/or a read-replica for reporting, not deleting
  or relocating financial data.
- **No soft-delete (`SoftDeletes` trait) was added to Order/Invoice/Refund/
  VendorLedgerEntry/VendorSettlement/AuditLog.** Soft-delete implies "this row
  is conceptually gone, just recoverable" — none of these rows are ever
  conceptually gone; they are always live business history. Adding
  `deleted_at` and a global scope would ask every future report/query author
  to remember to include trashed rows, which is a foot-gun for financial
  reporting. `UserAddress` is the one model in the app that already uses
  `SoftDeletes`, and that is correct there: an address is genuinely removable
  from a user's active address book without affecting historical orders,
  because `Order` already stores its own flat delivery-address snapshot on
  the order row itself and never reads the (mutable) `user_addresses` row
  ("decision D5" in `app/Models/Order.php`) — so soft-deleting an address
  cannot silently corrupt a past order's shipping record.
