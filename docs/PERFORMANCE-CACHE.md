# Performance And Cache Notes

## Startup Preferences

- Public startup endpoints live under `/api/startup/preferences`.
- Guests store timezone in session and the `sz_timezone` cookie.
- Logged-in users also persist timezone to `users.timezone`.
- `ApplyUserTimezone` applies the selected timezone per request when a valid PHP timezone identifier is available.

## Cache Keys

- `settings:website`: shared footer/website settings.
- `dashboard:admin:stats`: optimized admin dashboard statistics.
- `admin_dashboard_overview`: legacy admin dashboard cache key kept for compatibility.
- `categories:all`, `categories:agriculture`, `categories:veterinary`: category group keys.
- `products:filters:{hash}`: product filter cache key format for normalized filter payloads.

## Observers

Registered in `AppServiceProvider`:

- `CategoryObserver`: clears category, product, vendor, and dashboard cache groups.
- `ProductObserver`: clears product and dashboard cache groups.
- `VendorObserver`: clears vendor, product, and dashboard cache groups.
- `OrderObserver`: clears order, product, and dashboard cache groups.
- `FooterSettingObserver`: clears website settings cache.

## Rate Limiters

Configured in `AppServiceProvider`:

- `auth.strict`: login, registration, contact, and startup writes.
- `public.browse`: startup status and general public browsing.
- `search.filters`: public product filtering/search.
- `orders.write`: checkout/order writes.
- `dashboard.stats`: admin and syndicate dashboard statistics.
