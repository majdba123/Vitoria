# Client-side checklist & run guide

## Client-side coverage

### Laravel customer-facing application

| Item | Route / View | Status |
| --- | --- | --- |
| Home | `/` → `home.blade.php` | Implemented |
| Login | `/login` → `auth.login` | Implemented |
| Register | `/register` → `auth.register` | Implemented |
| Profile | `/profile` → `profile.blade.php` | Implemented |
| Products | `/products` → `products.index` | Implemented |
| Product detail | `/products/{id}` → `products.show` | Implemented |
| Categories | `/categories` → `categories.index` | Implemented |
| Category products | `/categories/{id}` → `categories.show` | Implemented |
| Subcategory products | `/subcategories/{id}` → `subcategories.show` | Implemented |
| Vendors | `/vendors` → `vendors.index` | Implemented |
| Vendor detail | `/vendors/{id}` → `vendors.show` | Implemented |
| Order detail | `/orders/{id}` → `orders.show` | Authenticated |

### Flutter client

The Flutter application contains authentication, customer home, product/category/vendor flows, API services, models, and theme configuration. Backend endpoints are selected through runtime configuration rather than committed infrastructure addresses.

## Run against a local backend

### Laravel

From the repository root:

```powershell
Copy-Item .env.example .env
composer install
npm install
php artisan key:generate
php artisan migrate
php artisan serve
```

In a second terminal:

```powershell
npm run dev
```

Default local URL: `http://localhost:8000`.

### Flutter

```powershell
cd flutter
.\run-flutter.ps1 pub get
.\run-flutter.ps1 run -d chrome --dart-define=API_BASE_URL=http://localhost:8000
```

## Run against another environment

Pass the environment's public HTTPS API URL at runtime:

```powershell
cd flutter
.\run-flutter.ps1 run -d chrome --dart-define=API_BASE_URL=https://api.example.com
```

Do not hardcode private hosts, raw server IPs, credentials, or environment-specific secrets into client source or documentation.

## Checklist before run

- [ ] `.env` exists locally and contains a generated Laravel `APP_KEY`.
- [ ] Database configuration is valid and migrations have been applied.
- [ ] Backend is reachable from the selected client environment.
- [ ] Flutter dependencies have been installed with `pub get`.
- [ ] `API_BASE_URL` targets the intended local, staging, or production endpoint.
- [ ] Production credentials remain outside source control.
