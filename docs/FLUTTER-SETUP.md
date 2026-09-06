# Flutter setup

The Flutter client lives under `flutter/`. Keep the Flutter SDK outside the repository and configure it through your operating-system PATH.

## Install Flutter

Install a supported stable Flutter SDK using the official Flutter installation instructions for your operating system. After installation, verify the toolchain:

```powershell
flutter doctor
```

Resolve any reported platform requirements before running the application.

## Add Flutter to PATH on Windows

You can add the SDK's `bin` directory through Windows Environment Variables, or use the repository helper with an explicit SDK path:

```powershell
.\scripts\add-flutter-to-path.ps1 -FlutterSdkPath "C:\path\to\flutter"
```

Open a new terminal after updating PATH, then run:

```powershell
flutter doctor
```

## Prepare the client

From the repository root:

```powershell
cd flutter
flutter pub get
```

## Run the client

Chrome:

```powershell
flutter run -d chrome --dart-define=API_BASE_URL=http://localhost:8000
```

Windows desktop:

```powershell
flutter run -d windows --dart-define=API_BASE_URL=http://localhost:8000
```

List available targets with:

```powershell
flutter devices
```

For staging or production, supply the public backend URL through `API_BASE_URL`; do not commit environment-specific server addresses or credentials into application source.
