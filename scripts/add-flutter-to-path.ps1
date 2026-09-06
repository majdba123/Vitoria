param(
    [Parameter(Mandatory = $true)]
    [string]$FlutterSdkPath
)

$resolvedSdkPath = [System.IO.Path]::GetFullPath($FlutterSdkPath)
$flutterBin = Join-Path $resolvedSdkPath 'bin'
$flutterExecutable = Join-Path $flutterBin 'flutter.bat'

if (-not (Test-Path $flutterExecutable)) {
    throw "Flutter SDK was not found at '$resolvedSdkPath'. Expected '$flutterExecutable'."
}

$userPath = [Environment]::GetEnvironmentVariable('Path', 'User')
$pathEntries = @($userPath -split ';' | Where-Object { $_ })

if ($pathEntries -contains $flutterBin) {
    Write-Host "Flutter is already present in the user PATH: $flutterBin"
    exit 0
}

$newPath = (@($pathEntries) + $flutterBin) -join ';'
[Environment]::SetEnvironmentVariable('Path', $newPath, 'User')

Write-Host "Flutter added to the user PATH: $flutterBin"
Write-Host 'Open a new terminal and run: flutter doctor'
