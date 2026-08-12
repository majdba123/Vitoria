# Vetora — Product Documents

Scope: what is **implemented and tested**, covering spec §25. See
[D17](IMPLEMENTATION_DECISIONS.md#d17--product-documents-a-separate-table-from-vendor_documents-public-once-approved)
for the reasoning; this document is the reference for the schema and endpoints.

---

## 1. Why a separate table from `vendor_documents`

`vendor_documents` (spec §24) is private compliance material — only the owning vendor and
admin ever see it, at any status. `product_documents` (spec §25) is catalog content —
leaflets, labels, safety data sheets — that becomes **publicly downloadable once approved**.
Conflating the two models would risk exactly the mistake §25 explicitly warns against: "do
not expose private vendor documents." They share no code beyond the upload-validation
convention and the private `local` disk.

Unlike `vendor_documents`, there is **no** `unique(product_id, type)` — a product can
legitimately carry several documents of the same type (an Arabic leaflet and an English one),
so each upload is its own row.

---

## 2. Lifecycle

```
pending_review ──▶ approved ──▶ disabled
       └──▶ rejected
```

Same conditional-`UPDATE` idempotency as every other review flow in this codebase: a document
can't be reviewed twice, and disabling only applies from `approved`.

---

## 3. Public visibility — the load-bearing property

An approved document is downloadable by **any visitor, no authentication required** — that's
the entire point of a "Documents & Downloads" section. A pending, rejected, or disabled one
must stay unreachable even if its id is known.

This is enforced by checking live `status = 'approved'` inside the public controller
(`Api\ProductDocumentController`) on every request — not by where the file is stored. Files
always live on the private `local` disk, exactly like `vendor_documents`; nothing is ever
written to the `public` disk. The public `index`/`download` actions simply query
`WHERE status = 'approved'` before doing anything else, so a document that is later disabled
stops being downloadable on the very next request, with no cache or public URL left pointing
at it.

`ProductController::publicShow()` (the cached public product payload) was deliberately **not**
modified to embed documents — that response is cached via `Cache::tags(['products'])`, and
threading document data through it would complicate invalidation for no real benefit. The
storefront calls the dedicated `GET /api/products/{product}/documents` endpoint instead.

---

## 4. Permissions

Reuses `products.manage` (Owner/Manager/Catalog Manager) — no new RBAC migration. Product
documents are catalog content, the same category of thing `products.manage` already gates
for photos and listings.

---

## 5. Endpoints

| Method | Path | Requires |
|---|---|---|
| GET | `/api/products/{product}/documents` | public — approved only |
| GET | `/api/products/{product}/documents/{id}/download` | public — approved only |
| GET · POST | `/api/vendor/products/{product}/documents` | owns product; POST needs `products.manage` |
| GET | `/api/vendor/products/{product}/documents/{id}/download` | owns product (any status) |
| PATCH | `/api/vendor/products/{product}/documents/{id}/disable` | `products.manage` |
| GET | `/api/admin/product-documents` | admin (review queue, filterable by status/type/vendor/product) |
| GET | `/api/admin/product-documents/{id}/download` | admin (any status) |
| PATCH | `/api/admin/product-documents/{id}/review` | admin |
| PATCH | `/api/admin/product-documents/{id}/disable` | admin |

`source` (`vendor` | `admin`) is set automatically from the uploader's account type — never a
client-supplied value.
