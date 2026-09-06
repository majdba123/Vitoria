# Run the project

This guide covers the Laravel application and the optional Flutter client without relying on machine-specific paths.

## Laravel application

From the repository root:

```powershell
Copy-Item .env.example .env
composer install
npm install
php artisan key:generate
```

Configure the database and other environment-specific services in `.env`, then run:

```powershell
php artisan migrate
# Optional development/demo data:
php artisan db:seed
```

Start Laravel:

```powershell
php artisan serve
```

In a second terminal from the repository root, start Vite:

```powershell
npm run dev
```

The default local application URL is `http://localhost:8000` unless overridden in `.env`.

Common local routes include:

- `/`
- `/login`
- `/register`

## Flutter client

The Flutter client lives under `flutter/`.

```powershell
cd flutter
.\run-flutter.ps1 pub get
.\run-flutter.ps1 run -d chrome --dart-define=API_BASE_URL=http://localhost:8000
```

For Windows desktop:

```powershell
.\run-flutter.ps1 run -d windows --dart-define=API_BASE_URL=http://localhost:8000
```

For another backend environment, provide its public HTTPS base URL through `API_BASE_URL` rather than hardcoding infrastructure addresses in source control.

## Troubleshooting

- If frontend assets are missing, confirm `npm run dev` is running during local development.
- If database operations fail, verify the `.env` database configuration and migrations.
- If Flutter is unavailable, run `flutter doctor` and follow [`FLUTTER-SETUP.md`](FLUTTER-SETUP.md).
- Keep credentials and environment-specific secrets outside source control.
