# FIX_LOG

## F-SHARD1-01 (P2, CONFIRMED → FIXED)
- File: app/Http/Requests/Admin/UpdateUserRequest.php
- Patch: closure rule on `type` rejects TYPE_VENDOR/TYPE_SYNDICATE unless equal to the target user's current type.
- Regression test: tests/Feature/AdminUserManagementTest.php — 'rejects widening a plain user into a vendor-type account through the generic admin edit endpoint'
- Targeted test run: php artisan test --filter=AdminUserManagementTest → 4 passed, 13 assertions
- Full suite re-run after fix: php artisan test --compact → 390 passed, 2797 assertions
- Pint: `vendor/bin/pint --dirty --format agent` → pass
- Build: `npm run build` → pass
- Committed: 2450cb8 "feat: add vendor map/table view and vendor analytics dashboards" (bundled with the already-reviewed, already-passing vendor map/analytics feature this session's checkpoints 0-3 audited)
- Pushed: origin/main, 1a4f1ed..2450cb8
- Ledger status: app/Http/Requests/Admin/UpdateUserRequest.php and tests/Feature/AdminUserManagementTest.php marked REVIEW_AGAIN → re-read post-fix → REVIEWED (see FILE_LEDGER.tsv)
