param(
    [switch] $Write,
    [switch] $OverwriteExisting,
    [int] $Limit = 0,
    [string[]] $ExternalId = @(),
    [string] $SummaryCopyPath = "output/analysis/pj360-compare/staging/pj360-apply-preview-summary.json"
)

$ErrorActionPreference = "Stop"

$repoRoot = Split-Path -Parent $PSScriptRoot
Set-Location $repoRoot

$resolvedSummaryCopyPath = if ([System.IO.Path]::IsPathRooted($SummaryCopyPath)) {
    $SummaryCopyPath
} else {
    Join-Path $repoRoot $SummaryCopyPath
}

$containerSummaryPath = "storage/app/tmp/pj360-apply-preview-summary.json"
$artisanCommand = @(
    "docker", "compose", "exec", "-T", "app", "php", "artisan", "pj360:apply-explanation-drafts",
    "--json=$containerSummaryPath"
)

if ($Write) {
    $artisanCommand += "--write"
}

if ($OverwriteExisting) {
    $artisanCommand += "--overwrite-existing"
}

if ($Limit -gt 0) {
    $artisanCommand += "--limit=$Limit"
}

foreach ($id in $ExternalId) {
    foreach ($part in ($id -split ",")) {
        $normalized = $part.Trim()

        if ($normalized -ne "") {
            $artisanCommand += "--external-id=$normalized"
        }
    }
}

& $artisanCommand[0] $artisanCommand[1..($artisanCommand.Length - 1)]

New-Item -ItemType Directory -Force -Path (Split-Path -Parent $resolvedSummaryCopyPath) | Out-Null
Copy-Item -LiteralPath (Join-Path $repoRoot $containerSummaryPath) -Destination $resolvedSummaryCopyPath -Force

Write-Host "Apply preview gotowy."
Write-Host "Mode:    $(if ($Write) { 'WRITE' } else { 'PREVIEW' })"
Write-Host "Summary: $resolvedSummaryCopyPath"
