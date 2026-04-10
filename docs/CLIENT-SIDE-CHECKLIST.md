# Client-side checklist & run guide

## Client-side coverage

### Blade (Laravel) – customer-facing

| Item | Route / View | Status |
|------|----------------|--------|
| Home | `/` → `home.blade.php` | ✅ |
| Login | `/login` → `auth.login` | ✅ |
| Register | `/register` → `auth.register` | ✅ |
| Profile | `/profile` → `profile.blade.php` | ✅ |
| All products | `/products` → `products.index` | ✅ |
| Product detail | `/products/{id}` → `products.show` | ✅ |
| All categories | `/categories` → `categories.index` | ✅ |
| Category products | `/categories/{id}` → `categories.show` | ✅ |
| Subcategory products | `/subcategories/{id}` → `subcategories.show` | ✅ |
| All vendors | `/vendors` → `vendors.index` | ✅ |
| Vendor detail | `/vendors/{id}` → `vendors.show` | ✅ |
| Order detail (auth) | `/orders/{id}` → `orders.show` | ✅ |
| Layout + navbar + footer | `layouts.app`, `navbar`, cart modal | ✅ |
| Home sections | Hero, Categories, Subcategories, Promo, Vendors, Products, Best Selling, Most Favorited, Trust, Contact | ✅ |

### Flutter app – same client experience

| Item | Screen / File | Status |
|------|----------------|--------|
| Login | `screens/login_screen.dart` | ✅ |
| Register | `screens/register_screen.dart` | ✅ |
| Client home | `screens/client_home_screen.dart` | ✅ |
| Home sections | Hero, Categories, Subcategories, Promo, Vendors, Latest Products, Best Selling, Most Favorited, Trust, Contact | ✅ |
| API config | `config/api_config.dart` (http://62.84.188.239) | ✅ |
| Auth service | `services/auth_service.dart` | ✅ |
| Client API | `services/client_api_service.dart` (categories, products, vendors, contact) | ✅ |
| Models | Category, Subcategory, Product, Vendor, User | ✅ |
| Theme | `theme/app_theme.dart` (brand orange, Blade-like) | ✅ |

---

## How to run the project

### Option A: Backend on server (http://62.84.188.239)

You only need to run the **Flutter** app; the Blade site and API are already on the server.

**Run Flutter:**

```powershell
cd C:\Users\impos\Desktop\Projects\MSZ\flutter
.\run-flutter.ps1 pub get
.\run-flutter.ps1 run -d chrome
```

Or Windows desktop: `.\run-flutter.ps1 run -d windows`

---

### Option B: Run everything locally

**1. Laravel (backend + Blade)**

```powershell
cd C:\Users\impos\Desktop\Projects\MSZ
copy .env.example .env
php artisan key:generate
# Edit .env: set DB_* and APP_URL if needed
composer install
npm install
php artisan migrate
php artisan serve
```

In a **second** terminal (for frontend assets):

```powershell
cd C:\Users\impos\Desktop\Projects\MSZ
npm run dev
```

- Blade site: **http://localhost:8000**
- Login: http://localhost:8000/login  
- Register: http://localhost:8000/register  

**2. Flutter (point to local backend)**

Run Flutter with local API:

```powershell
cd C:\Users\impos\Desktop\Projects\MSZ\flutter
.\run-flutter.ps1 run -d chrome --dart-define=API_BASE_URL=http://localhost:8000
```

Or leave default (server): `.\run-flutter.ps1 run -d chrome`

---

## Quick run (server backend)

If your backend is already at **http://62.84.188.239**:

```powershell
cd C:\Users\impos\Desktop\Projects\MSZ\flutter
.\run-flutter.ps1 pub get
.\run-flutter.ps1 run -d chrome
```

Open the Blade site in the browser: **http://62.84.188.239** (login, register, home, products, etc.).

---

## Checklist before run

- [ ] Backend reachable (server or `php artisan serve`)
- [ ] `.env` exists and `APP_KEY` set (for Laravel)
- [ ] Database migrated (for Laravel local)
- [ ] Flutter: `.\run-flutter.ps1 pub get` run once
- [ ] Flutter API base URL matches backend (default: http://62.84.188.239)
