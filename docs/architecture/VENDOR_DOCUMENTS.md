# Vetora — Vendor Verification Documents

Scope: what is **implemented and tested**, covering spec §24. See
[D16](IMPLEMENTATION_DECISIONS.md#d16--vendor-documents-additive-table-existing-field-untouched-no-new-permission)
for the reasoning; this document is the reference for the schema and endpoints.

---

## 1. Schema

```
vendor_documents
  vendor_id      ─► vendors.id
  type           commercial_registration | business_license | tax_registration
                 | industry_license | other
  file_path      server-generated path on the private `local` disk
  original_filename, mime_type, file_size   (display metadata only)
  issued_at, expires_at                     (nullable)
  status         pending_review | verified | rejected | expired | suspended
  reviewed_by_user_id, reviewed_at, rejection_reason
```

`vendors.commercial_register_file` is unchanged and still works exactly as it did — see D16
for why it wasn't migrated into this table.

`unique(vendor_id, type)`: one current document per type. Resubmitting replaces the file and
resets `status` to `pending_review`, clearing any prior review — the old file is deleted from
the `local` disk once the new one is confirmed stored.

---

## 2. Lifecycle

```
pending_review ──▶ verified ──▶ suspended
       │                └────▶ expired (lazy, see §4)
       └──▶ rejected
```

`draft` (listed in spec §24) is not reachable — every row is created together with an
uploaded file, so there is no documentless draft state any workflow in this repository
produces.

Review (`VendorDocumentService::review()`) is a conditional `UPDATE ... WHERE status =
'pending_review'`, so reviewing the same document twice — accidentally or concurrently — finds
nothing left to claim on the second call and rejects with a conflict message rather than
silently overwriting the first reviewer's decision.

---

## 3. File security (spec §55)

- Stored on the `local` disk (`storage/app/private`) — never the `public` disk that's
  symlinked to `/storage`. There is no public URL for a `vendor_documents` file.
- The stored path is server-generated (`Storage::putFile()`); the client's original filename
  is kept only as a display label (`original_filename`), never used to build a path.
- The only way to read a file back is `Storage::download()` inside an authorized controller
  action (`VendorDocumentPolicy::view` for the vendor side; admin routes are gated by the
  `admin` middleware already wrapping `routes/api_admin.php`).
- Upload validation matches the existing convention (`RegisterRequest`'s
  `commercial_register_file` rule): `file`, `mimes:pdf,doc,docx,jpg,jpeg,png`, `max:5120` (5MB).

---

## 4. Expiry

`VendorDocumentService::expireOverdue()` runs a conditional `UPDATE` (`verified` documents
whose `expires_at` has passed → `expired`) at the top of the admin review queue
(`Admin\VendorDocumentController::index`). There is no scheduled job — this repository has no
configured scheduler, and the queue is the only place an accurate `expired` status is actually
needed for filtering.

---

## 5. Endpoints

| Method | Path | Requires |
|---|---|---|
| GET | `/api/vendor/documents` | active staff or owner (read is not sensitive) |
| POST | `/api/vendor/documents` | `documents.manage` |
| GET | `/api/vendor/documents/{id}` | `view` (owns vendor) |
| GET | `/api/vendor/documents/{id}/download` | `view` |
| GET | `/api/admin/vendor-documents` | admin |
| GET | `/api/admin/vendor-documents/{id}` | admin |
| GET | `/api/admin/vendor-documents/{id}/download` | admin |
| PATCH | `/api/admin/vendor-documents/{id}/review` | admin |
| PATCH | `/api/admin/vendor-documents/{id}/suspend` | admin |

`documents.manage` is granted to Owner and Manager only — the same tier as `profile.manage`
(decision D15's role matrix), reused rather than duplicated with a document-specific
permission.

---

## 6. Self-registration integration

`AuthService::register()` additionally creates a `commercial_registration` row (via
`VendorDocumentService::upload()`) whenever a self-registering vendor supplies a commercial
register file — the same `UploadedFile` already written to `commercial_register_file`. This
is wrapped in its own try/catch: a failure to create the `vendor_documents` row is logged as a
warning but never fails account creation, since the account and the (unchanged)
`commercial_register_file` field are the parts of registration that actually matter.

Storing the same upload twice (once via the legacy field's `putFile`, once via the service's
own `putFile`) is a deliberate, small duplication — building a code path that shares one
stored file between the old field and the new table would couple two systems the migration is
explicitly trying to keep independent (D16).
