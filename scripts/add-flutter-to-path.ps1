# Add Flutter to your user PATH (run this once in PowerShell).
# Flutter SDK path: C:\Users\impos\Downloads\flutter_windows_3.41.2-stable\flutter
$flutterBin = "C:\Users\impos\Downloads\flutter_windows_3.41.2-stable\flutter\bin"
$userPath = [Environment]::GetEnvironmentVariable("Path", "User")

if ($userPath -notlike "*flutter*") {
    [Environment]::SetEnvironmentVariable("Path", "$userPath;$flutterBin", "User")
    Write-Host "Flutter added to PATH. Restart Cursor/terminal for it to take effect."
} else {
    Write-Host "Flutter is already in your PATH."
}
