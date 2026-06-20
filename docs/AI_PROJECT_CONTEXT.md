# AI Project Context

## 1. Project Summary

This repository is a Laravel 12 marketplace application for veterinary and agricultural products. The storefront is customer-facing, while the same Laravel codebase also provides:

- an admin back office
- a vendor/merchant dashboard
- a syndicate dashboard
- JSON APIs for the web frontend and external/mobile clients

The current product name in the UI is mostly `Vetora`, while several repository artifacts still reference `SyriaZone` or `MSZ`. Functionally, the project behaves as a multi-role e-commerce marketplace with category-driven browsing, vendor onboarding, product management, favourites, checkout, orders, coupons, reviews, contact messages, real-time notifications, and analytics dashboards.

Expected user groups:

- Guests browsing products and categories
- Registered customers purchasing products
- Vendors/merchants listing and managing products
- Admins managing users, vendors, products, orders, categories, coupons, footer/about content, notifications, and syndicates
- Syndicate users viewing filtered analytics and records by business type

Business goal:

- Provide a centralized online store and marketplace for agricultural and veterinary goods
- Separate catalog browsing by product type (`agriculture` vs `veterinary`)
- Allow merchants to self-register or be created by admins
- Support operational oversight through admin and syndicate dashboards

## 2. Technology Stack

- Backend: PHP 8.3, Laravel 12
- Auth/API auth: Laravel Sanctum
- Realtime: Laravel Reverb + Laravel Echo + `pusher-js`
- Frontend rendering: Blade templates with inline JavaScript and Axios
- Styling: Tailwind CSS v4, custom theme in [resources/css/app.css](/abs/path/C:/Users/impos/OneDrive/Desktop/Syria_Zone/Vitoria/resources/css/app.css:1)
- Bundler: Vite via [vite.config.js](/abs/path/C:/Users/impos/OneDrive/Desktop/Syria_Zone/Vitoria/vite.config.js:1)
- Queue/cache support: database queue by default, Redis-ready via `predis/predis`
- Testing: Pest 3 / PHPUnit 11

Frontend architecture notes:

- The app does **not** use Livewire, Inertia, Vue, or React.
- It uses Blade pages as shells plus JavaScript that calls `/api/*` endpoints via Axios.
- Many interactive behaviors are implemented inline inside Blade files rather than as separate JS modules.

## 3. Folder Structure

Main Laravel folders:

- [app](/abs/path/C:/Users/impos/OneDrive/Desktop/Syria_Zone/Vitoria/app:1): application code
- [app/Http/Controllers](/abs/path/C:/Users/impos/OneDrive/Desktop/Syria_Zone/Vitoria/app/Http/Controllers:1): web and API controllers
- [app/Http/Requests](/abs/path/C:/Users/impos/OneDrive/Desktop/Syria_Zone/Vitoria/app/Http/Requests:1): validation request classes
- [app/Http/Middleware](/abs/path/C:/Users/impos/OneDrive/Desktop/Syria_Zone/Vitoria/app/Http/Middleware:1): role, locale, timezone, cache, and product-type middleware
- [app/Http/Resources](/abs/path/C:/Users/impos/OneDrive/Desktop/Syria_Zone/Vitoria/app/Http/Resources:1): API resource transformers
- [app/Models](/abs/path/C:/Users/impos/OneDrive/Desktop/Syria_Zone/Vitoria/app/Models:1): domain models
- [app/Services](/abs/path/C:/Users/impos/OneDrive/Desktop/Syria_Zone/Vitoria/app/Services:1): business/service layer
- [app/Observers](/abs/path/C:/Users/impos/OneDrive/Desktop/Syria_Zone/Vitoria/app/Observers:1): cache invalidation observers
- [app/Policies](/abs/path/C:/Users/impos/OneDrive/Desktop/Syria_Zone/Vitoria/app/Policies:1): authorization policy for product reviews
- [app/Console/Commands](/abs/path/C:/Users/impos/OneDrive/Desktop/Syria_Zone/Vitoria/app/Console/Commands:1): discount/coupon scheduler commands
- [bootstrap/app.php](/abs/path/C:/Users/impos/OneDrive/Desktop/Syria_Zone/Vitoria/bootstrap/app.php:1): Laravel 12 routing/middleware bootstrap
- [config](/abs/path/C:/Users/impos/OneDrive/Desktop/Syria_Zone/Vitoria/config:1): framework and integration config
- [database/migrations](/abs/path/C:/Users/impos/OneDrive/Desktop/Syria_Zone/Vitoria/database/migrations:1): schema history
- [database/seeders](/abs/path/C:/Users/impos/OneDrive/Desktop/Syria_Zone/Vitoria/database/seeders:1): demo/Arabic/city/category/coupon/syndicate seeders
- [resources/views](/abs/path/C:/Users/impos/OneDrive/Desktop/Syria_Zone/Vitoria/resources/views:1): Blade pages, layouts, and components
- [resources/js](/abs/path/C:/Users/impos/OneDrive/Desktop/Syria_Zone/Vitoria/resources/js:1): Axios/bootstrap/Reverb notification scripts
- [resources/css](/abs/path/C:/Users/impos/OneDrive/Desktop/Syria_Zone/Vitoria/resources/css:1): Tailwind theme and component styles
- [routes](/abs/path/C:/Users/impos/OneDrive/Desktop/Syria_Zone/Vitoria/routes:1): web, api, admin, vendor, syndicate, channels, console routes
- [tests](/abs/path/C:/Users/impos/OneDrive/Desktop/Syria_Zone/Vitoria/tests:1): Pest feature/unit tests

Non-core folders worth noting:

- [docs](/abs/path/C:/Users/impos/OneDrive/Desktop/Syria_Zone/Vitoria/docs:1): project docs and setup notes
- [flutter](/abs/path/C:/Users/impos/OneDrive/Desktop/Syria_Zone/Vitoria/flutter:1) and [mobile](/abs/path/C:/Users/impos/OneDrive/Desktop/Syria_Zone/Vitoria/mobile:1): indicate mobile-related work exists in the repo, but they were not required for this Laravel-context document
- [postman](/abs/path/C:/Users/impos/OneDrive/Desktop/Syria_Zone/Vitoria/postman:1): API collection

Custom modules/services/helpers/traits/jobs/events/policies/middleware:

- Services:
  - `App\Services\Auth\AuthService`
  - `App\Services\ProductService`
  - `App\Services\NotificationService`
  - `App\Services\SelectedProductTypeService`
  - `App\Services\ApplicationCacheService`
  - `App\Services\Admin\VendorService`
  - `App\Services\Admin\UserService`
  - `App\Services\Admin\SyndicateService`
  - `App\Services\Syndicate\SyndicateDashboardService`
- Middleware:
  - `EnsureUserIsAdmin`
  - `EnsureUserIsVendor`
  - `EnsureUserIsSyndicate`
  - `EnsureProductTypeSelected`
  - `SetLocale`
  - `ApplyUserTimezone`
  - `CacheResponse`
- Event:
  - `AdminNotificationSent`
- Policy:
  - `ProductReviewPolicy`
- Rule:
  - `CategoriesMatchBusinessType`
- Observers:
  - `CategoryObserver`, `ProductObserver`, `VendorObserver`, `SyndicateObserver`, `OrderObserver`, `FooterSettingObserver`
- Jobs:
  - No custom queue job classes were found
- Traits/helpers:
  - No custom reusable traits/helpers folder was found

## 4. Main Features

Implemented or partially implemented features detected from code:

- Authentication and registration:
  - API auth endpoints under `/api/auth/*`
  - login uses `phone_number + password`
  - registration supports `user` and `vendor` account types
  - Sanctum token issuance plus web session login in the same flow
- Product type preference:
  - customers/guests choose `agriculture` or `veterinary`
  - enforced by middleware and route filtering
- Public storefront:
  - home page with type selector, categories, subcategories, featured product sections, contact form, cart modal
  - product listing and product detail pages
- Categories and subcategories:
  - categories have `type`, media/icon fields, and commission
  - subcategories belong to categories
- Vendor onboarding:
  - self-registration with commercial register file upload
  - admin-created vendor accounts
  - vendor approval flow with `pending/active/inactive`
- Product management:
  - admin and vendor CRUD
  - vendor product access restricted to owned products
  - category/subcategory validation against vendor assignments
- Product images:
  - multiple photos per product via `product_photos`
  - primary photo via `is_primary`
  - separate `icon` and `image` display assets on product record
- Product approval/status:
  - product statuses: `pending`, `approved`, `rejected`
  - admin can toggle active status and approval status
- Discounts:
  - per-product discount percentage, schedule window, status tracking
  - scheduled activation/expiration via console commands
- Stock management:
  - quantity-based stock on `products.quantity`
  - decremented on checkout, restored on order cancellation
- Favourites / wishlist:
  - authenticated customer favourites via `favourites` pivot
- Reviews and ratings:
  - product reviews with 1-5 rating and optional body
  - public review listing
  - customer create/delete own reviews
  - admin/vendor review viewing and deletion
- Cart:
  - client-side cart stored in browser `localStorage`
  - no database-backed cart table found
- Checkout and orders:
  - checkout endpoint creates one order per vendor
  - coupon support during checkout
  - payment method currently limited to `cash`
  - order history for customers
  - order list/detail/cancel for vendors
  - order list/detail/complete for admins
- Coupons:
  - fixed or percentage discount coupons
  - active/pending/expired lifecycle
  - usage limit and used count
- Notifications:
  - admin-created public/private notifications
  - automatic notifications for new orders, order status changes, approved products, and product discounts
  - real-time broadcasting via Reverb/Echo
- Contact / support:
  - public contact message submission
  - customer contact-message history
  - admin inbox and reply flow
- Footer/about-us settings:
  - singleton footer settings record editable by admin
- Admin dashboard:
  - vendor/category/product/syndicate metrics and recent records
- Syndicate dashboard:
  - analytics filtered by syndicate type (`agriculture` or `veterinary`)
  - categories/vendors/products/orders/reports endpoints
  - podcast sections currently placeholder/empty

Features not found as implemented:

- Brands: no `Brand` model/table/controller detected
- Product variations/options: no SKU/variant model detected
- Delivery user role: not found
- Shipping rate/integration engine: not found
- External payment gateway: not found
- Public vendor storefront pages: explicitly disabled in `Api\VendorController`

Partially implemented / notable gaps:

- Order status includes `confirmed`, but no obvious controller action currently sets orders to `confirmed`
- Product review controller still contains duplicate-review fallback messaging even though a migration removed the unique constraint
- Syndicate `podcasts`/`reports` areas exist, but podcasts currently return empty data

## 5. User Roles

Role system is numeric on `users.type` in [app/Models/User.php](/abs/path/C:/Users/impos/OneDrive/Desktop/Syria_Zone/Vitoria/app/Models/User.php:1):

- `0` = regular customer/user
- `1` = admin
- `2` = vendor
- `3` = syndicate

Practical roles:

- Guest:
  - can browse public pages and product APIs
  - can submit contact form
  - cannot use favourites, checkout, notifications, or review submission
- Customer (`TYPE_USER`):
  - can register/login
  - can save preferred product type
  - can favourite products
  - can checkout and manage own orders
  - can submit/delete own reviews
  - can view notifications and contact history
- Admin (`TYPE_ADMIN`):
  - full access to `/admin/*` views and `/api/admin/*`
  - manages vendors, users, syndicates, products, categories, subcategories, coupons, orders, notifications, footer settings, and contact messages
- Vendor (`TYPE_VENDOR`):
  - requires active vendor profile to access vendor area
  - manages own products, photos, profile, orders, commission stats, notifications
  - can view/delete reviews on own products
- Syndicate (`TYPE_SYNDICATE`):
  - requires active syndicate profile
  - can view type-filtered analytics and records

## 6. Database Overview

No soft deletes were found in the inspected models/migrations.

Core tables:

- `users`
  - important fields: `name`, `phone_number`, `national_id`, `age`, `membership_number`, `city_id`, `latitude`, `longitude`, `timezone`, `preferred_product_type`, `type`, `email`, `avatar`, `password`
  - meaning: all human accounts across customer/admin/vendor/syndicate roles
- `vendors`
  - fields: `user_id`, `store_name`, `business_type`, `description`, `address`, `city_id`, `latitude`, `longitude`, `logo`, `is_active`, `status`, `registration_source`, `commercial_register_file`, `paid_amount`
  - meaning: merchant storefront/profile attached to a vendor user
- `syndicates`
  - fields: `user_id`, `name`, `type`, `phone`, `email`, `status`, `logo`
  - meaning: role-specific organizational/analytics account tied to a user
- `categories`
  - fields: `name`, `type`, `logo`, `icon`, `icon_class`, `commission`
  - meaning: top-level business categories split by agriculture/veterinary
- `subcategories`
  - fields: `category_id`, `name`, `image`, `icon_class`
  - meaning: second-level catalog grouping
- `products`
  - fields: `vendor_id`, `subcategory_id`, `name`, `description`, `icon`, `image`, `price`, `discount_percentage`, `quantity`, `is_active`, `discount_is_active`, `discount_starts_at`, `discount_ends_at`, `discount_status`, `status`
  - meaning: sellable catalog items
- `product_photos`
  - fields: `product_id`, `path`, `sort_order`, `is_primary`
  - meaning: gallery images for products
- `orders`
  - fields: `order_number`, `user_id`, `vendor_id`, `coupon_id`, `coupon_code`, `coupon_type`, `coupon_value`, `status`, `payment_way`, `items_count`, `subtotal_amount`, `coupon_discount_amount`, `total_amount`
  - meaning: vendor-scoped order header created during checkout
- `order_items`
  - fields: `order_id`, `product_id`, `product_name`, `original_unit_price`, `has_discount`, `applied_discount_percentage`, `unit_price`, `quantity`, `line_total`, `discount_amount`
  - meaning: historical line items copied from products at checkout time
- `coupons`
  - fields: `code`, `title`, `description`, `discount_type`, `discount_value`, `starts_at`, `ends_at`, `is_active`, `status`, `usage_limit`, `used_count`, `created_by_user_id`
  - meaning: checkout discount campaigns
- `favourites`
  - fields: `user_id`, `product_id`
  - meaning: customer wishlist/favourites pivot
- `product_reviews`
  - fields: `product_id`, `user_id`, `rating`, `body`
  - meaning: user reviews for products
- `contact_messages`
  - fields: `user_id`, `name`, `email`, `message`, `status`, `admin_reply`, `replied_at`
  - meaning: support/contact inbox
- `footer_settings`
  - fields: `about_description`, social URLs, `contact_email`, `contact_address`, `default_timezone`
  - meaning: singleton site/footer/about-us settings row
- `cities`
  - fields: `name`
  - meaning: normalized city list for users/vendors
- `admin_notifications`
  - fields: `title`, `body`, `type`, `action_type`, `action_id`, `sent_by`
  - meaning: stored notification records
- `admin_notification_recipients`
  - fields: `admin_notification_id`, `user_id`
  - meaning: recipient pivot for private notifications
- `admin_notification_reads`
  - fields: `user_id`, `admin_notification_id`, `read_at`
  - meaning: read-state pivot

Infrastructure/default Laravel tables:

- `personal_access_tokens`
- `cache`, `cache_locks`
- `jobs`, `job_batches`, `failed_jobs`
- `sessions`
- `password_reset_tokens`

Important foreign keys and pivots:

- `vendors.user_id -> users.id`
- `syndicates.user_id -> users.id`
- `users.city_id -> cities.id`
- `vendors.city_id -> cities.id`
- `subcategories.category_id -> categories.id`
- `products.vendor_id -> vendors.id`
- `products.subcategory_id -> subcategories.id`
- `product_photos.product_id -> products.id`
- `orders.user_id -> users.id`
- `orders.vendor_id -> vendors.id`
- `orders.coupon_id -> coupons.id`
- `order_items.order_id -> orders.id`
- `order_items.product_id -> products.id` nullable historical link
- `favourites.user_id -> users.id`
- `favourites.product_id -> products.id`
- `product_reviews.user_id -> users.id`
- `product_reviews.product_id -> products.id`
- `contact_messages.user_id -> users.id`
- `coupons.created_by_user_id -> users.id`
- `category_vendor.category_id <-> vendor_id`
- `admin_notification_recipients.admin_notification_id <-> user_id`
- `admin_notification_reads.admin_notification_id <-> user_id`

Status/enums/business fields:

- `users.type`: `0 user`, `1 admin`, `2 vendor`, `3 syndicate`
- `categories.type`: `agriculture`, `veterinary`
- `vendors.business_type`: `agriculture`, `veterinary`, `both`
- `vendors.status`: `pending`, `active`, `inactive`
- `vendors.registration_source`: `admin`, `self`
- `products.status`: `pending`, `approved`, `rejected`
- `products.discount_status`: `pending`, `active`, `expired`
- `orders.status`: `pending`, `confirmed`, `completed`, `cancelled`
- `orders.payment_way`: currently only `cash` is validated
- `coupons.status`: `pending`, `active`, `expired`
- `contact_messages.status`: `pending`, `replied`
- `admin_notifications.type`: `public`, `private`
- `syndicates.status`: `active`, `inactive`

## 7. Models and Relationships

### User

- Purpose: universal account model
- Fillable: identity, contact, location, role, avatar, auth, preferred product type
- Relationships:
  - `city()`
  - `vendor()`
  - `syndicate()`
  - `favouriteProducts()`
  - `orders()`
  - `notificationReads()`
  - `contactMessages()`
- Methods:
  - `isAdmin()`, `isVendor()`, `isSyndicate()`
- Workflow role:
  - base actor for customer ordering, vendor ownership, admin actions, and syndicate dashboards

### Vendor

- Purpose: merchant/store profile
- Fillable: store info, business type, location, status, registration source, commission paid, commercial register file
- Relationships:
  - `city()`
  - `user()`
  - `products()`
  - `orders()`
  - `categories()`
- Methods:
  - `isPending()`
  - `businessTypeLabels()`
- Workflow role:
  - owns products and receives vendor-split orders
  - can be self-registered then approved by admin

### Syndicate

- Purpose: type-scoped oversight/analytics account
- Fillable: `user_id`, `name`, `type`, `phone`, `email`, `status`, `logo`
- Relationships:
  - `user()`
- Scopes/helpers:
  - `agriculture()`, `veterinary()`, `forType()`
  - `isAgriculture()`, `isVeterinary()`, `isActive()`
- Workflow role:
  - accesses analytics filtered by agriculture or veterinary data

### Category

- Purpose: top-level catalog grouping with business type
- Fillable: `name`, `type`, `logo`, `icon`, `icon_class`, `commission`
- Relationships:
  - `subcategories()`
  - `vendors()`
- Scopes/helpers:
  - `agriculture()`, `veterinary()`, `forType()`
  - `isAgriculture()`, `isVeterinary()`
- Workflow role:
  - controls customer browsing and vendor category eligibility

### Subcategory

- Purpose: second-level grouping under a category
- Fillable: `category_id`, `name`, `image`, `icon_class`
- Relationships:
  - `category()`
  - `products()`
- Scope:
  - `forCategoryType()`
- Workflow role:
  - directly attached to products and used in filters/navigation

### Product

- Purpose: sellable marketplace item
- Fillable: vendor/subcategory, content/media, price, discount, stock, active state, approval status
- Relationships:
  - `vendor()`
  - `photos()`
  - `subcategory()`
  - `favouritedBy()`
  - `orderItems()`
  - `reviews()`
- Scopes/helpers:
  - `visible()`
  - `forCategoryType()`
  - `hasActiveDiscount()`
  - `resolveDiscountStatus()`
  - `getDiscountedPrice()`
  - `primaryPhoto()`
- Workflow role:
  - central catalog entity for browsing, cart, checkout, order history, reviews, and notifications

### ProductPhoto

- Purpose: product gallery image
- Fillable: `product_id`, `path`, `sort_order`, `is_primary`
- Relationship: `product()`
- Workflow role:
  - supports multi-image products and primary-image selection

### Order

- Purpose: vendor-specific order header
- Fillable: order number, user/vendor, coupon snapshot, status, payment, totals
- Relationships:
  - `user()`
  - `vendor()`
  - `coupon()`
  - `items()`
- Workflow role:
  - created at checkout after grouping cart items by vendor
  - status tracked for customer, vendor, and admin actions

### OrderItem

- Purpose: immutable commercial snapshot of purchased line items
- Fillable: product snapshot fields, discount info, quantity, totals
- Relationships:
  - `order()`
  - `product()`
- Workflow role:
  - preserves product pricing/discount state at order time

### Coupon

- Purpose: discount code
- Fillable: code, title, description, type/value, schedule, usage fields, creator
- Relationships:
  - `creator()`
  - `orders()`
- Helper:
  - `resolveStatus()`
- Workflow role:
  - validated during checkout and allocated proportionally across vendor-split orders

### ProductReview

- Purpose: customer review/rating
- Fillable: `product_id`, `user_id`, `rating`, `body`
- Relationships:
  - `product()`
  - `user()`
- Workflow role:
  - drives public ratings and review displays

### AdminNotification

- Purpose: stored internal/user notification record
- Fillable: title/body/type/action metadata/sender
- Relationships:
  - `sender()`
  - `recipients()`
  - `reads()`
- Workflow role:
  - powers public/private notifications and Reverb broadcasts

### ContactMessage

- Purpose: support/contact ticket
- Fillable: user/contact text/status/reply fields
- Relationship: `user()`
- Workflow role:
  - captures support messages and admin replies

### FooterSetting

- Purpose: singleton website/footer/about-us configuration
- Fillable: about text, socials, contact info, default timezone
- Helper:
  - `instance()` ensures a row exists
- Workflow role:
  - fills site footer and startup timezone default

### City

- Purpose: normalized city lookup
- Relationships:
  - `users()`
  - `vendors()`

## 8. Routes and Controllers

### Web routes

Defined in [routes/web.php](/abs/path/C:/Users/impos/OneDrive/Desktop/Syria_Zone/Vitoria/routes/web.php:1).

Main public Blade routes:

- `/` -> `home`
- `/product-type/select`
- `/login`, `/register`
- `/profile`
- `/products`, `/products/{id}`
- `/categories`, `/categories/{id}`
- `/subcategories/{id}`
- `/orders/{id}` for authenticated users

Role-based Blade route groups:

- `/vendor/*` protected by `auth` + `vendor`
- `/syndicate/*` protected by `auth` + `syndicate`
- `/admin/*` protected by `auth` + `admin`

Important notes:

- many web routes simply return Blade views
- data loading is usually delegated to JS hitting `/api/*`
- public vendor pages are intentionally redirected/disabled

### API routes

Base API in [routes/api.php](/abs/path/C:/Users/impos/OneDrive/Desktop/Syria_Zone/Vitoria/routes/api.php:1).

Public API groups:

- `/api/auth/*` via `AuthController`
- `/api/startup/preferences`
- `/api/products/*`
- `/api/contact`
- `/api/cities`
- `/api/categories/*`
- `/api/subcategories/*`
- `/api/vendors/*` currently disabled at controller level

Authenticated customer API:

- `/api/user`
- `/api/profile`
- `/api/favourites/*`
- `/api/orders`, `/api/orders/{id}`, `/api/orders/{id}/cancel`, `/api/orders/checkout`
- `/api/notifications/*`
- `/api/contact-messages`
- `/api/products/{product}/reviews` POST/DELETE
- `/api/broadcasting/auth`

Admin API in [routes/api_admin.php](/abs/path/C:/Users/impos/OneDrive/Desktop/Syria_Zone/Vitoria/routes/api_admin.php:1):

- dashboard overview/stats
- `apiResource` controllers for `syndicates`, `vendors`, `users`, `products`, `categories`, `subcategories`, `coupons`
- vendor commission and approval routes
- orders index/show/complete
- notifications send
- contact message inbox/reply
- footer settings show/update
- product photo management

Vendor API in [routes/api_vendor.php](/abs/path/C:/Users/impos/OneDrive/Desktop/Syria_Zone/Vitoria/routes/api_vendor.php:1):

- product CRUD
- product reviews for owned products
- allowed categories
- profile show/update
- orders index/show/cancel
- commission stats
- product photo management

Syndicate API in [routes/api_syndicate.php](/abs/path/C:/Users/impos/OneDrive/Desktop/Syria_Zone/Vitoria/routes/api_syndicate.php:1):

- overview
- categories/vendors/products/orders
- podcasts
- reports

### Middleware used on important routes

- `auth:sanctum`: authenticated API access
- `auth`: session-authenticated web access
- `admin`, `vendor`, `syndicate`: custom role guards
- `product.type.selected`: forces product-type selection for shopper pages
- `cache.response:120`: adds cache headers for some GET endpoints
- `web`: used for stateful API groups like auth/startup/public browse
- rate limiters:
  - `auth.strict`
  - `public.browse`
  - `search.filters`
  - `orders.write`
  - `dashboard.stats`

### Controller responsibility summary

- `Api\Auth\AuthController`: register/login/logout
- `Api\ProductController`: public/admin/vendor product listing, detail, CRUD, status toggles
- `Api\OrderController`: customer order history, checkout, cancellation
- `Api\FavouriteController`: wishlist
- `Api\NotificationController`: read user notifications
- `Api\ProductReviewController`: review listing/create/delete
- `Api\ProductPhotoController`: image upload/delete/update
- `Api\ContactMessageController`: submit/list support messages
- `Api\Admin\*`: admin CRUD/analytics/operations
- `Api\Vendor\*`: vendor profile, order, category, commission flows
- `Api\Syndicate\DashboardController`: type-filtered analytics API
- `ProductTypePreferenceController` and `Api\StartupPreferenceController`: shopper preference flows

## 9. Frontend Structure

Frontend is Blade-first with Vite assets.

Layouts:

- [resources/views/layouts/app.blade.php](/abs/path/C:/Users/impos/OneDrive/Desktop/Syria_Zone/Vitoria/resources/views/layouts/app.blade.php:1): main public storefront layout
- [resources/views/layouts/admin.blade.php](/abs/path/C:/Users/impos/OneDrive/Desktop/Syria_Zone/Vitoria/resources/views/layouts/admin.blade.php:1): admin shell
- [resources/views/layouts/vendor.blade.php](/abs/path/C:/Users/impos/OneDrive/Desktop/Syria_Zone/Vitoria/resources/views/layouts/vendor.blade.php:1): vendor shell
- [resources/views/layouts/syndicate.blade.php](/abs/path/C:/Users/impos/OneDrive/Desktop/Syria_Zone/Vitoria/resources/views/layouts/syndicate.blade.php:1): syndicate shell

Main public pages:

- `home.blade.php`
- `products/index.blade.php`
- `products/show.blade.php`
- `categories/index.blade.php`
- `categories/show.blade.php`
- `subcategories/show.blade.php`
- `auth/login.blade.php`
- `auth/register.blade.php`
- `profile.blade.php`
- `orders/show.blade.php`
- `preferences/product-type.blade.php`

Reusable Blade components:

- `components/navbar.blade.php`
- `components/language-switcher.blade.php`
- `components/home/*`
- `components/form/*`
- `components/admin/sidebar.blade.php`
- `components/vendor/sidebar.blade.php`
- `components/products/*`

Frontend behavior patterns:

- Blade renders page chrome and placeholders
- inline scripts fetch JSON from APIs and build cards/tables in the browser
- cart logic is global JS in `layouts/app.blade.php`
- favourites logic is also global JS
- startup modal saves timezone/location preferences
- notifications can update in real time through Echo/Reverb

Assets and JS:

- entrypoints: `resources/css/app.css`, `resources/js/app.js`
- `resources/js/bootstrap.js`: Axios setup, API error handling, token/session utilities
- `resources/js/echo.js`: Reverb/Echo setup
- `resources/js/notifications.js`: realtime toast notifications

Styling:

- Tailwind v4 with custom theme tokens and a substantial custom component layer in [resources/css/app.css](/abs/path/C:/Users/impos/OneDrive/Desktop/Syria_Zone/Vitoria/resources/css/app.css:1)
- fonts loaded from Bunny Fonts
- Font Awesome CDN used for icons

## 10. Business Workflows

### Customer registration/login

1. User opens registration or login Blade page.
2. Frontend submits to `/api/auth/register` or `/api/auth/login`.
3. On success, backend creates Sanctum token and also logs the user into the web session.
4. Redirect URL depends on role:
   - admin -> admin dashboard
   - vendor -> vendor dashboard
   - syndicate -> syndicate dashboard
   - customer -> home or product-type selection

### Browsing products

1. Guest/customer selects `agriculture` or `veterinary`.
2. Selection is stored in session/cookie and optionally on user profile.
3. Home page and product/category routes use that preference to filter visible data.
4. Public product APIs only return visible/approved/in-stock products with active vendors.

### Searching/filtering products

1. Product listing page loads categories and optional preferred type.
2. User can filter by category type, category, subcategory, and discount.
3. Frontend requests `/api/products` with query parameters.
4. Backend applies type/category/subcategory/discount/sort filters through `ProductService`.

### Adding products to cart

1. User clicks add-to-cart from product cards or product detail.
2. Cart is stored in browser `localStorage`.
3. Cart modal computes totals client-side.
4. No server-side cart persistence was found.

### Checkout process

1. Authenticated customer opens cart modal and optionally enters coupon code.
2. Frontend sends `items[]`, `coupon_code`, and `payment_way=cash` to `/api/orders/checkout`.
3. Backend validates stock, vendor activeness, product approval, and coupon validity.
4. Cart items are grouped by `vendor_id`.
5. One `orders` row is created per vendor.
6. `order_items` rows snapshot product name, pricing, quantity, and discounts.
7. Product stock is decremented.
8. Coupon usage count is incremented if used.
9. Product cache is flushed and notifications are broadcast.

### Order creation/management

- Customer:
  - sees own order history and detail
  - can cancel non-completed orders
- Vendor:
  - sees only own vendor orders
  - can cancel non-completed own orders
- Admin:
  - sees all orders with filters
  - can mark non-cancelled orders as completed

### Admin product management

1. Admin visits `/admin/products` Blade view.
2. Frontend calls `/api/admin/products`.
3. Admin can create products for a vendor, edit, delete, toggle active, change approval status, and manage photos.
4. Product category must belong to the vendor and subcategory must belong to the selected category.

### Admin order management

1. Admin uses `/api/admin/orders` for listing/filtering.
2. Admin can inspect line items and related user/vendor/category info.
3. Admin can mark orders completed.
4. Completion triggers notification broadcast.

### Vendor workflow

1. Vendor self-registers or is created by admin.
2. Self-registered vendors start with inactive/pending status.
3. Once active, vendor accesses `/vendor/*`.
4. Vendor can update profile, manage products and photos, read notifications, and review orders/commission stats.

### Syndicate workflow

1. Admin creates a syndicate account with a specific `type`.
2. Syndicate logs in and accesses `/syndicate/*`.
3. Dashboard and lists are filtered to matching category/product/order/vendor type only.

## 11. Admin Panel

Admin UI exists under [resources/views/admin](/abs/path/C:/Users/impos/OneDrive/Desktop/Syria_Zone/Vitoria/resources/views/admin:1).

Detected admin areas:

- dashboard
- vendors
- users
- syndicates
- products
- product reviews
- categories
- subcategories
- coupons
- orders
- notifications
- contact messages
- about-us/footer settings

Main admin capabilities:

- create/edit/delete vendors and users
- approve/toggle vendors
- create/edit/delete syndicates
- create/edit/delete products and manage their status
- view/delete reviews
- manage categories/subcategories with media/icons/type/commission
- manage coupons
- view/complete orders
- send public/private notifications
- review and reply to contact messages
- edit footer/about-us/contact/timezone settings

## 12. Customer Experience

Customer-facing experience currently includes:

- localized Blade storefront (`ar` and `en`)
- category-type guided browsing
- category/subcategory exploration
- product cards with rating, discount, stock state
- favourites
- local cart with checkout modal
- product reviews
- order history/details
- contact form and support history
- realtime notifications if authenticated and Reverb is enabled

Notable UX constraints:

- cart is local to the browser, not account-synced
- payment is cash only
- vendor public profile pages are disabled

## 13. Integrations and Configuration

Detected integrations/configuration:

- Realtime notifications:
  - Laravel Reverb configured in `composer.json` and `config/broadcasting.php`
  - Echo/Pusher JS client in `resources/js/echo.js`
- Auth:
  - Sanctum with stateful API enabled in `bootstrap/app.php`
- Storage:
  - `public` disk for product/vendor/category/subcategory/syndicate/user media
  - `local` private disk for vendor commercial register files
- Cache:
  - tag-based cache invalidation for products/categories/vendors/syndicates/settings/dashboard
  - fallback handling when cache driver does not support tags
- Queue:
  - queue default is `database`
  - no custom queued jobs found
- Mail/service config:
  - default Laravel config includes Postmark, Resend, SES, Slack placeholders in [config/services.php](/abs/path/C:/Users/impos/OneDrive/Desktop/Syria_Zone/Vitoria/config/services.php:1)
  - no concrete email-sending workflow was found in inspected app code
- Scheduled commands:
  - activate pending discounts/coupons daily
  - expire ended discounts/coupons daily
- Image/file handling:
  - uploaded via Laravel storage APIs
  - product photos and display assets stored separately

What was **not** found:

- external payment gateway integration
- shipping carrier integration
- SMS provider integration
- media library package
- ERP/accounting integration

## 14. Packages and Dependencies

### Important PHP/Laravel packages

- `laravel/framework`: core framework
- `laravel/sanctum`: API token authentication
- `laravel/reverb`: WebSocket broadcasting server
- `laravel/tinker`: local REPL support
- `predis/predis`: Redis client support
- `laravel/boost`: development tooling
- `laravel/pail`: log tailing
- `laravel/pint`: formatting
- `laravel/sail`: Docker/dev environment support
- `pestphp/pest` and `pestphp/pest-plugin-laravel`: testing

### Frontend packages

- `vite`: bundler
- `laravel-vite-plugin`: Laravel/Vite integration
- `tailwindcss` + `@tailwindcss/vite`: Tailwind v4
- `axios`: HTTP client for API-driven UI
- `laravel-echo`: realtime subscriptions
- `pusher-js`: client transport used by Echo/Reverb
- `concurrently`: `composer dev` multi-process script

## 15. Security and Permissions

Authentication:

- Sanctum bearer tokens for API access
- web session login also established on auth success
- `auth:sanctum` secures protected APIs
- custom role middleware protects admin/vendor/syndicate zones

Authorization:

- role checks mostly implemented via middleware and controller branching
- `ProductReviewPolicy` controls review deletion rights
- vendor product/photo/review/order access is scoped to owned vendor profile

Validation patterns:

- many write actions use dedicated `FormRequest` classes
- some controllers still use inline `$request->validate()` for context-dependent rules

CSRF/session:

- CSRF enabled for web routes
- `bootstrap/app.php` excludes `api/*` from CSRF validation
- API uses stateful Sanctum plus bearer token support

Rate limiting:

- auth, browse, search, order-write, and dashboard rate limiters configured in `AppServiceProvider`

Potential security or maintainability concerns visible from code:

- large inline JS blocks in Blade templates increase review complexity
- some logic is duplicated across controllers
- `api/*` CSRF exclusion is broad; acceptable for token APIs but worth understanding if more stateful browser APIs are added
- admin notification broadcasting is real-time, but there is no inspected evidence of notification content sanitization beyond frontend escaping

## 16. Code Quality Notes

Strengths:

- clear role separation across admin/vendor/syndicate/customer concerns
- good use of Laravel models, relationships, request validation, resources, and middleware
- service layer exists for auth, products, notifications, caching, vendor and syndicate logic
- caching and observer-based invalidation are intentionally designed
- database design is coherent for a marketplace with vendor-split orders

Areas that may need improvement:

- a lot of frontend logic lives inline inside Blade files, especially `layouts/app.blade.php`, `home.blade.php`, and product pages
- some business logic is duplicated:
  - order cancellation and stock restoration in both customer and vendor order controllers
  - repeated review mapping and response shaping
- naming is mixed across the codebase:
  - repository/docs reference `SyriaZone`, UI references `Vetora`
- some implementations look partial:
  - `confirmed` order status exists but is not clearly used
  - syndicate podcasts area is placeholder-only
  - vendor public pages are disabled while vendor listing views still exist
- documentation inside the repo is incomplete for the real business domain; `README.md` is still the default Laravel README

Specific risky or unclear patterns:

- `ProductReviewController::store()` still anticipates a duplicate-review DB constraint, but migration `2026_02_27_005011_*` removed that unique constraint
- vendor dashboard stats use only the first page of fetched products for some counts, so the displayed active-product count can be inaccurate
- many UI labels/messages are mixed between Arabic, English, and some encoding-corrupted text in inspected terminal output, so localization consistency should be verified directly in files/editor

## 17. Unknown / Needs Confirmation

- Whether there is a separate mobile app actively consuming these APIs from the `flutter` or `mobile` folders
- Whether any payment gateway or shipping integration exists outside the inspected Laravel app
- Whether order status `confirmed` is planned but not yet wired into UI/API
- Whether multiple reviews per user per product are intentionally allowed now, or the migration removing the unique constraint was temporary
- Whether `paid_amount` on vendors is intended for commission settlement tracking only, or part of a larger accounting flow
- Whether syndicate `podcasts` is a future roadmap module or an abandoned placeholder
- Whether the disabled public vendor pages should remain disabled long-term

## 18. Guidelines for Future AI Development

When extending this project later:

- Follow the existing Laravel 12 structure and conventions already used here.
- Reuse existing models, relationships, services, requests, resources, middleware, and layouts before adding new abstractions.
- Keep the current Blade + Axios + API pattern unless there is a strong reason to introduce a new frontend architecture.
- Preserve the existing role model based on `users.type` and profile tables (`vendors`, `syndicates`).
- Preserve the `agriculture` / `veterinary` type split across categories, products, vendor business types, and syndicate filtering.
- Reuse `SelectedProductTypeService` and current route/middleware patterns for any feature that depends on product type.
- Keep validation in `FormRequest` classes where the surrounding feature already uses them.
- Keep authorization consistent with the current middleware- and ownership-based approach.
- Do not introduce new naming conventions if existing domain names already exist for the same concept.
- Preserve current table names, foreign keys, and relationship naming unless a migration is explicitly required.
- Be careful with cache invalidation; existing observers and `ApplicationCacheService` are part of the current architecture.
- Keep public product APIs compatible with current Blade pages and likely mobile/API consumers.
- Treat cart behavior as client-side unless intentionally upgrading it to a server-side cart design.
- If adding payments, shipping, brands, or variants, document whether they are genuinely new modules because none are fully present today.
