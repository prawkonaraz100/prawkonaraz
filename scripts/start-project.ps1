[CmdletBinding()]
param(
    [switch]$SkipBuild,
    [switch]$OpenBrowser
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$repoRoot = Split-Path -Parent $PSScriptRoot
$composeProjectName = 'serwistestyprawojazdy_local'
Set-Location $repoRoot

function Write-Step {
    param([string]$Message)

    Write-Host ""
    Write-Host "==> $Message" -ForegroundColor Cyan
}

function Assert-Command {
    param(
        [string]$Name,
        [string]$Hint
    )

    if (-not (Get-Command $Name -ErrorAction SilentlyContinue)) {
        throw "Brak wymaganego polecenia '$Name'. $Hint"
    }
}

function Invoke-Checked {
    param(
        [Parameter(ValueFromRemainingArguments = $true)]
        [string[]]$Command
    )

    $flattened = @()

    foreach ($part in $Command) {
        if ($part -is [System.Array]) {
            $flattened += $part
        }
        else {
            $flattened += $part
        }
    }

    & $flattened[0] $flattened[1..($flattened.Length - 1)]

    if ($LASTEXITCODE -ne 0) {
        throw "Polecenie zakonczone bledem: $($flattened -join ' ')"
    }
}

Write-Step "Sprawdzanie wymagan"

Assert-Command -Name 'docker' -Hint 'Uruchom Docker Desktop i sprobuj ponownie.'

try {
    & docker info | Out-Null
}
catch {
    throw "Docker Desktop nie dziala albo daemon nie jest gotowy. Uruchom Docker Desktop i sprobuj ponownie."
}

if (-not (Test-Path '.env')) {
    Write-Step "Tworzenie .env z .env.example"
    Copy-Item '.env.example' '.env'
}

if (-not (Test-Path 'database')) {
    New-Item -ItemType Directory -Path 'database' | Out-Null
}

if (-not (Test-Path 'database\database.sqlite')) {
    Write-Step "Tworzenie pliku SQLite"
    New-Item -ItemType File -Path 'database\database.sqlite' | Out-Null
}

if (-not (Test-Path 'vendor\autoload.php')) {
    throw "Brakuje vendor/autoload.php. Ten launcher zaklada, ze zaleznosci Composer sa juz obecne w repo."
}

if (-not (Test-Path '.tools\php83\php.exe')) {
    throw "Brakuje lokalnego PHP pod .tools\\php83\\php.exe."
}

$envRaw = Get-Content '.env' -Raw

if ($envRaw -match '(?m)^APP_KEY=\s*$') {
    Write-Step "Generowanie APP_KEY"
    Invoke-Checked '.tools\php83\php.exe' 'artisan' 'key:generate'
}

if (Test-Path 'public\hot') {
    Write-Step "Usuwanie stalego znacznika Vite dev server (public\\hot)"
    Remove-Item 'public\hot' -Force
}

if (-not $SkipBuild) {
    Write-Step "Budowanie frontendu"

    if (-not (Test-Path 'node_modules\.bin\vite.cmd')) {
        throw "Brakuje node_modules\\.bin\\vite.cmd. Uruchom npm install i sprobuj ponownie."
    }

    Invoke-Checked 'node_modules\.bin\vite.cmd' 'build'
}

Write-Step "Uruchamianie kontenerow Docker"
try {
    & docker compose -p $composeProjectName up -d --build
    if ($LASTEXITCODE -ne 0) {
        throw "Polecenie zakonczone bledem: docker compose -p $composeProjectName up -d --build"
    }
}
catch {
    Write-Step "Docker compose up nie udal sie za pierwszym razem, proba cleanup i retry"
    & docker compose -p $composeProjectName down --remove-orphans | Out-Null
    & docker compose -p $composeProjectName up -d --build
    if ($LASTEXITCODE -ne 0) {
        throw "Polecenie zakonczone bledem: docker compose -p $composeProjectName up -d --build"
    }
}

Write-Step "Migracje bazy"
& docker compose -p $composeProjectName exec -T app php artisan migrate --force
if ($LASTEXITCODE -ne 0) {
    throw "Polecenie zakonczone bledem: docker compose -p $composeProjectName exec -T app php artisan migrate --force"
}

Write-Step "Status kontenerow"
& docker compose -p $composeProjectName ps
if ($LASTEXITCODE -ne 0) {
    throw "Polecenie zakonczone bledem: docker compose -p $composeProjectName ps"
}

Write-Host ""
Write-Host "Projekt dziala." -ForegroundColor Green
Write-Host "App:   http://127.0.0.1:8000" -ForegroundColor Green
Write-Host "Nauka: http://127.0.0.1:8000/nauka" -ForegroundColor Green
Write-Host "Media: http://127.0.0.1:8081" -ForegroundColor Green
Write-Host ""
Write-Host "Panel admina: http://127.0.0.1:8000/admin" -ForegroundColor Yellow
Write-Host "Jesli nie masz konta admina, utworz je komenda:" -ForegroundColor Yellow
Write-Host "  php artisan app:make-admin admin@local.test --name=""Admin"" --password=""Start123!Admin""" -ForegroundColor Yellow

if ($OpenBrowser) {
    Start-Process 'http://127.0.0.1:8000/nauka'
}
