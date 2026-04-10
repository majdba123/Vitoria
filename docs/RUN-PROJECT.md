# Run the project step by step

This guide runs the **Laravel** app (with Login and Register Blade pages) and optionally the **Flutter** app.

---

## Part 1: Laravel (backend + web with Login/Register)

### Step 1: Environment and dependencies

1. Open a terminal in the project root:
   ```powershell
   cd C:\Users\impos\Desktop\Projects\MSZ
   ```

2. Copy the environment file (if you don’t have `.env` yet):
   ```powershell
   copy .env.example .env
   ```

3. Generate app key:
   ```powershell
   php artisan key:generate
   ```

4. Edit `.env` and set your database (e.g. `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`).

5. Install PHP dependencies:
   ```powershell
   composer install
   ```

6. Install Node dependencies:
   ```powershell
   npm install
   ```

### Step 2: Database

7. Run migrations:
   ```powershell
   php artisan migrate
   ```

   (Optional) Seed data:
   ```powershell
   php artisan db:seed
   ```

### Step 3: Start Laravel and frontend

8. Start the Laravel server:
   ```powershell
   php artisan serve
   ```
   Leave this running. The app will be at **http://localhost:8000**.

9. In a **second** terminal, start the frontend (Vite):
   ```powershell
   cd C:\Users\impos\Desktop\Projects\MSZ
   npm run dev
   ```
   Leave this running so CSS/JS load correctly.

### Step 4: Open Login and Register (Blade)

10. In your browser go to:
    - **Login:** http://localhost:8000/login  
    - **Register:** http://localhost:8000/register  
    - **Home:** http://localhost:8000  

Login and Register use the existing Blade views and the API (`/api/auth/login`, `/api/auth/register`).

---

## Part 2: Flutter app (optional)

The Flutter project lives in the **`flutter`** folder and is **already created**. You don’t need to add Flutter to PATH.

### Run the Flutter app

From the `flutter` folder, use the helper script:

```powershell
cd C:\Users\impos\Desktop\Projects\MSZ\flutter
.\run-flutter.ps1 run -d chrome
```

Or for Windows desktop:

```powershell
.\run-flutter.ps1 run -d windows
```

To get/update packages first:

```powershell
.\run-flutter.ps1 pub get
```

---

## Quick reference

| What              | Command / URL |
|-------------------|----------------|
| Laravel server    | `php artisan serve` → http://localhost:8000 |
| Frontend (Vite)   | `npm run dev` (in a second terminal) |
| Login (Blade)     | http://localhost:8000/login |
| Register (Blade)  | http://localhost:8000/register |
| Flutter app       | `cd flutter` then `.\run-flutter.ps1 run -d chrome` or `.\run-flutter.ps1 run -d windows` |

---

## Troubleshooting

- **Blank or broken CSS/JS:** Make sure `npm run dev` is running.
- **Database errors:** Check `.env` and run `php artisan migrate` again.
- **Flutter not found:** Add Flutter to PATH and open a new terminal (`docs/FLUTTER-SETUP.md`).
