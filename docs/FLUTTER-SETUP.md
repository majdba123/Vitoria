# Flutter setup (step by step)

You downloaded Flutter to:
`C:\Users\impos\Downloads\flutter_windows_3.41.2-stable\flutter`

---

## Step 1: Add Flutter to PATH

So you can run `flutter` from any terminal:

1. **Option A – Run the script (easiest)**  
   In PowerShell (Run as current user is enough):
   ```powershell
   cd C:\Users\impos\Desktop\Projects\MSZ
   .\scripts\add-flutter-to-path.ps1
   ```
   Then **close and reopen** Cursor (or any terminal) so the new PATH is loaded.

2. **Option B – Add manually**
   - Press `Win + R`, type `sysdm.cpl`, Enter.
   - **Advanced** → **Environment Variables**.
   - Under **User variables**, select **Path** → **Edit** → **New**.
   - Add: `C:\Users\impos\Downloads\flutter_windows_3.41.2-stable\flutter\bin`
   - OK all dialogs, then restart Cursor/terminal.

---

## Step 2: Check installation

In a **new** terminal (after PATH is set):

```powershell
flutter doctor
```

Fix any issues it reports (e.g. accept Android licenses with `flutter doctor --android-licenses`).

---

## Step 3: Flutter project in this repo

The Flutter app lives in the **`flutter`** folder.

If the project was not created yet, run:

```powershell
cd C:\Users\impos\Desktop\Projects\MSZ\flutter
flutter create . --project-name msz_app --org com.msz
flutter pub get
```

---

## Step 4: Run the app

From the `flutter` folder:

```powershell
cd C:\Users\impos\Desktop\Projects\MSZ\flutter
flutter run -d chrome
```

Or run on Windows desktop:

```powershell
flutter run -d windows
```

Use `flutter devices` to see all available targets.

---

## Step 5: Cursor/VS Code extensions

Open the project in Cursor and install the recommended extensions when prompted, or install manually:

- **Flutter** (Dart-Code.flutter)
- **Dart** (Dart-Code.dart-code)

They are already listed in `.vscode/extensions.json`.

---

## Quick reference

| Task              | Command                    |
|-------------------|----------------------------|
| Check setup       | `flutter doctor`           |
| Get packages      | `flutter pub get`          |
| Run (Chrome)      | `flutter run -d chrome`    |
| Run (Windows)     | `flutter run -d windows`   |
| List devices      | `flutter devices`          |
