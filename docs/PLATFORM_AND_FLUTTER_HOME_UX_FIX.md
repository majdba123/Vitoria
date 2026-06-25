# Platform And Flutter Home UX Fix

## Laravel platform improvements

- Refined shared public card behavior so category and product cards keep stable height, cleaner hover states, and better focus feedback.
- Improved home/category/subcategory browsing links so they preserve the active marketplace type while moving between sections.
- Kept the public catalog URL in sync with filters for a more predictable browse experience.

## Category and subcategory navigation fixes

- Home category cards now keep the selected `type` while opening the category-focused home state.
- Home subcategory cards now open the matching public subcategory page with the current `type`.
- Category and subcategory detail pages now preserve the selected `type` in product/category links and breadcrumbs.
- Public products API now honors an explicit empty `category_type` filter so “all types” works instead of falling back to the saved preference.

## Card fixes

- Product cards now use the effective display price when adding discounted products to cart.
- Category and subcategory cards now have stronger tap/focus affordances and more consistent layout behavior.
- Empty image cases remain safe by continuing to use placeholders or icon fallbacks.

## Flutter home improvements

- Home hero actions now navigate to real customer browsing screens.
- Home category, subcategory, and product cards now perform useful navigation instead of acting like static cards.
- Home navigation now passes the selected marketplace type into category, subcategory, product, and filtered product listing screens.
- Flutter detail/listing screens now request Laravel API data with the active category type when provided.

## APIs used

- `GET /api/categories`
- `GET /api/categories/{id}`
- `GET /api/subcategories/{id}`
- `GET /api/products`
- `GET /api/products/{id}`
- `POST /api/contact`

## Tests added

- Laravel feature test for explicit “all types” public product filtering.
- Laravel feature test for preserving selected type in category “View All” links.
- Flutter model parsing test for category/subcategory image payloads.
- Flutter widget test for product card rendering and tap behavior.

## Commands run and results

- `vendor/bin/pint --dirty --format agent` — passed
- `php artisan route:list` — passed
- `php artisan test --compact` — passed (`92` tests)
- `npm run build` — passed
- `flutter pub get` — passed
- `flutter analyze` — passed
- `flutter test` — passed
- `flutter build apk --debug` — passed

## Known limitations

- Flutter work in this pass is intentionally limited to the customer home flow and the detail/listing navigation it touches.
- Existing route-based Flutter navigation outside the home flow still uses the app’s broader routing setup and was not redesigned here.
